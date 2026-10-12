<?php

use App\Actions\Memberships\SyncMemberPlatformRole;
use App\Actions\PlatformRoles\AppointAdministrator;
use App\Actions\PlatformRoles\AppointOfficer;
use App\Actions\PlatformRoles\CreateStaffRole;
use App\Enums\AnnouncementVisibility;
use App\Enums\MembershipRole;
use App\Models\Announcement;
use App\Models\Membership;
use App\Models\MembershipChange;
use App\Models\RolePermissionChange;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('custom staff grants combine without inheriting unrelated or protected access', function () {
    $owner = User::factory()->member()->create();
    $read = Role::findOrCreate('records-reader', 'web');
    $read->syncPermissions(['properties.read']);
    $payments = Role::findOrCreate('payment-reviewer', 'web');
    $payments->syncPermissions(['payments.read']);
    $owner->assignRole([$read, $payments]);
    $this->actingAs($owner)->get(route('officer.properties.index'))->assertOk();
    $this->get(route('officer.payments.index'))->assertOk();
    $this->get(route('officer.charges.index'))->assertForbidden();
    $this->get(route('administrator.dashboard'))->assertForbidden();
});

test('Super Admin creates an empty custom role and records its definition atomically', function () {
    $actor = User::factory()->superAdmin()->create();
    $this->actingAs($actor)->post('/super-admin/role-permissions', ['name' => 'Community Treasurer'])
        ->assertSessionHasNoErrors()->assertRedirect(route('super-admin.role-permissions.index'));
    $role = Role::findByName('community-treasurer', 'web');
    expect($role->permissions)->toHaveCount(0);
    $this->assertDatabaseHas('role_permission_changes', ['role_id' => $role->id, 'action' => 'create', 'actor_user_id' => $actor->id]);
});

test('custom roles can be renamed and configured while retaining assignments and audit history', function () {
    $actor = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('treasurer', 'web');
    $owner->assignRole($role);
    $this->actingAs($actor)->put(route('super-admin.role-permissions.update', $role), ['grants' => ['payments.read']])->assertSessionHasNoErrors();
    $this->patch(route('super-admin.role-permissions.rename', $role), ['name' => 'Community Treasurer'])->assertSessionHasNoErrors()->assertRedirect();
    expect($role->fresh()->name)->toBe('community-treasurer');
    $this->assertDatabaseHas('model_has_roles', ['role_id' => $role->id, 'model_id' => $owner->id]);
    $this->assertDatabaseHas('role_permission_changes', ['action' => 'rename', 'previous_role_name' => 'treasurer', 'new_role_name' => 'community-treasurer']);
    $this->actingAs($owner)->get(route('officer.payments.index'))->assertOk();
    $this->actingAs($actor)->get(route('super-admin.role-permissions.index'))->assertInertia(fn ($page) => $page
        ->has('roles', 3)->where('roles.1.id', $role->id)->where('roles.1.name', 'community-treasurer')->where('roles.1.assigned_count', 1)->where('roles.1.is_builtin', false));
});

test('assigned role deletion fails without an audit and unassigned deletion preserves its final snapshot', function () {
    $actor = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('records-reader', 'web');
    $role->syncPermissions(['properties.read']);
    $owner->assignRole($role);
    $this->actingAs($actor)->delete(route('super-admin.role-permissions.destroy', $role))->assertSessionHasErrors('role');
    $this->assertDatabaseHas('roles', ['id' => $role->id]);
    $this->assertDatabaseCount('role_permission_changes', 0);
    $owner->removeRole($role);
    $this->delete(route('super-admin.role-permissions.destroy', $role))->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    $audit = RolePermissionChange::query()->sole();
    expect($audit->action)->toBe('delete')->and($audit->role_id)->toBeNull()
        ->and($audit->previous_grants)->toBe(['properties.read'])->and($audit->new_grants)->toBe([])
        ->and($audit->previous_role_name)->toBe('records-reader')->and($audit->new_role_name)->toBeNull();
});

test('all built-in role names and definitions are protected from rename and deletion', function (string $name) {
    $actor = User::factory()->superAdmin()->create();
    $role = Role::findByName($name, 'web');
    $this->actingAs($actor)->patch(route('super-admin.role-permissions.rename', $role), ['name' => 'Reserved Replacement'])->assertForbidden();
    $this->delete(route('super-admin.role-permissions.destroy', $role))->assertForbidden();
    $this->post(route('super-admin.role-permissions.store'), ['name' => strtoupper(str_replace('-', ' ', $name))])->assertSessionHasErrors('name');
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $name]);
    $this->assertDatabaseCount('role_permission_changes', 0);
})->with(['super-admin', 'administrator', 'officer', 'member', 'user']);

test('custom role names reject blank invalid overlong and duplicate values without auditing', function (mixed $name) {
    $actor = User::factory()->superAdmin()->create();
    Role::findOrCreate('existing-role', 'web');
    $this->actingAs($actor)->post(route('super-admin.role-permissions.store'), ['name' => $name])->assertSessionHasErrors('name');
    $this->assertDatabaseCount('role_permission_changes', 0);
})->with(['blank' => '', 'punctuation' => '!!!', 'leading number' => '12-treasurer', 'long' => str_repeat('x', 65), 'duplicate' => 'Existing Role', 'not a string' => [[1, 2]]]);

test('role definition and permission changes remain Super Admin only despite custom or direct grants', function () {
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('role-manager', 'web');
    $role->syncPermissions(['properties.read', 'memberships.change-role']);
    $owner->assignRole($role);
    $owner->givePermissionTo('payments.read');
    $this->actingAs($owner)->get(route('super-admin.role-permissions.index'))->assertForbidden();
    $this->post(route('super-admin.role-permissions.store'), ['name' => 'New Role'])->assertForbidden();
    $this->patch(route('super-admin.role-permissions.rename', $role), ['name' => 'New Role'])->assertForbidden();
    $this->delete(route('super-admin.role-permissions.destroy', $role))->assertForbidden();
    $this->put(route('super-admin.role-permissions.update', $role), ['grants' => ['payments.read']])->assertForbidden();
    $this->get(route('officer.payments.index'))->assertForbidden();
    $this->assertDatabaseCount('role_permission_changes', 0);
});

test('every staff role loses access without live ownership while a zero-permission Owner may open only the workspace', function () {
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('observer', 'web');
    $owner->assignRole($role);
    $this->actingAs($owner)->get(route('officer.dashboard'))->assertOk()->assertInertia(fn ($page) => $page
        ->where('auth.capabilities.canAccessOfficer', true)->where('auth.capabilities.canAccessAdministrator', false)->where('auth.staffPermissions', []));
    $this->get(route('officer.properties.index'))->assertForbidden();
    $role->syncPermissions(['properties.read']);
    $this->get(route('officer.properties.index'))->assertOk();
    $owner->memberships()->sole()->update(['role' => MembershipRole::Resident]);
    $this->get(route('officer.properties.index'))->assertForbidden();
    $this->get(route('officer.dashboard'))->assertForbidden();
    $this->get(route('dashboard'))->assertInertia(fn ($page) => $page->where('auth.capabilities.canAccessOfficer', false));
});

test('last ownership loss removes all staff assignments and records custom role removal without restoring roles later', function () {
    $owner = User::factory()->officer()->create();
    $role = Role::findOrCreate('treasurer', 'web');
    $owner->assignRole($role);
    $membership = $owner->memberships()->live()->sole();
    $this->actingAs($owner)->post(route('memberships.end', $membership))->assertSessionHasNoErrors();
    expect($owner->fresh()->roles->pluck('name')->all())->toBe(['user']);
    $audit = MembershipChange::query()->sole();
    expect($audit->previous_state['staff_roles'])->toBe(['officer', 'treasurer'])->and($audit->new_state['staff_roles'])->toBe([]);
    Membership::factory()->owner()->create(['user_id' => $owner->id]);
    app(SyncMemberPlatformRole::class)->handle($owner);
    expect($owner->fresh()->hasRole('treasurer'))->toBeFalse()->and($owner->fresh()->hasRole('officer'))->toBeFalse();
});

test('custom grant and assignment revocations take effect with a loaded role relation in the same session', function () {
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('records-reader', 'web');
    $role->syncPermissions(['properties.read']);
    $owner->assignRole($role);
    $owner->load('roles.permissions');
    $this->actingAs($owner)->get(route('officer.properties.index'))->assertOk();
    $role->syncPermissions([]);
    $this->get(route('officer.properties.index'))->assertForbidden();
    $owner->removeRole($role);
    $this->get(route('officer.dashboard'))->assertForbidden();
});

test('failed role definition audits roll back create rename and delete operations', function (string $operation) {
    Exceptions::fake();
    $actor = User::factory()->superAdmin()->create();
    $role = Role::findOrCreate('treasurer', 'web');
    Event::listen('eloquent.creating: '.RolePermissionChange::class,
        fn () => throw new RuntimeException('Audit unavailable'));
    try {
        $response = match ($operation) {
            'create' => $this->actingAs($actor)->post(route('super-admin.role-permissions.store'), ['name' => 'New Role']),
            'rename' => $this->actingAs($actor)->patch(route('super-admin.role-permissions.rename', $role), ['name' => 'New Role']),
            'delete' => $this->actingAs($actor)->delete(route('super-admin.role-permissions.destroy', $role)),
        };
        $response->assertServerError();
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Audit unavailable');
    } finally {
        Event::forget('eloquent.creating: '.RolePermissionChange::class);
    }
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'treasurer']);
    $this->assertDatabaseMissing('roles', ['name' => 'new-role']);
    $this->assertDatabaseCount('role_permission_changes', 0);
})->with(['create', 'rename', 'delete']);

test('custom grants cannot confer protected appointment authority through requests or direct operations', function () {
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('coordinator', 'web');
    $role->syncPermissions(['memberships.change-role', 'memberships.end']);
    $owner->assignRole($role);
    $candidate = User::factory()->member()->create();
    $this->actingAs($owner)->post(route('super-admin.administrators.store'), ['user_id' => $candidate->id])->assertForbidden();
    $this->post(route('administrator.officers.store'), ['user_id' => $candidate->id])->assertForbidden();
    expect(fn () => app(AppointAdministrator::class)->handle($candidate, $owner))->toThrow(HttpException::class);
    expect(fn () => app(AppointOfficer::class)->handle($candidate, $owner))->toThrow(HttpException::class);
    expect(fn () => app(CreateStaffRole::class)->handle($owner, 'Forbidden'))->toThrow(HttpException::class);
    expect($candidate->fresh()->hasRole('administrator'))->toBeFalse()->and($candidate->fresh()->hasRole('officer'))->toBeFalse();
});

test('an Administrator cannot appoint themselves as Officer to gain a different role grants', function () {
    $actor = User::factory()->administrator()->create();
    Role::findByName('administrator', 'web')->syncPermissions([]);
    $this->actingAs($actor)->post(route('administrator.officers.store'), ['user_id' => $actor->id])->assertForbidden();
    expect($actor->fresh()->hasRole('officer'))->toBeFalse();
    $this->get(route('officer.payments.index'))->assertForbidden();
});

test('future and protected permission names are ungranted while Super Admin always retains catalog access', function () {
    $actor = User::factory()->superAdmin()->create();
    $role = Role::findOrCreate('observer', 'web');
    $owner = User::factory()->member()->create();
    $owner->assignRole($role);
    Permission::findOrCreate('future.action', 'web');
    $this->seed(RoleSeeder::class);
    expect($role->fresh()->permissions)->toHaveCount(0);
    $this->actingAs($actor)->put(route('super-admin.role-permissions.update', $role), ['grants' => ['administrators.appoint']])->assertSessionHasErrors('grants.0');
    Role::findByName('super-admin', 'web')->syncPermissions([]);
    $this->get(route('officer.payments.index'))->assertOk();
    $this->get(route('super-admin.role-permissions.index'))->assertInertia(fn ($page) => $page->has('auth.staffPermissions', 44));
});

test('privileged Announcement reading follows custom grants and immediately falls back to Membership scope on revocation', function () {
    $owner = User::factory()->member()->create();
    $role = Role::findOrCreate('notice-reader', 'web');
    $role->syncPermissions(['announcements.read']);
    $owner->assignRole($role);
    $hidden = Announcement::factory()->published()->create(['visibility' => AnnouncementVisibility::Hidden]);
    $private = Announcement::factory()->published()->create(['visibility' => AnnouncementVisibility::Private]);
    $this->actingAs($owner)->get(route('announcements.show', $hidden))->assertOk();
    $this->get(route('officer.announcements.index'))->assertOk();
    $role->syncPermissions([]);
    $this->get(route('announcements.show', $hidden))->assertNotFound();
    $this->get(route('officer.announcements.index'))->assertForbidden();
    $this->get(route('announcements.show', $private))->assertOk();
});

test('role definitions resolve web roles even when another guard has the same role name', function () {
    $actor = User::factory()->superAdmin()->create();
    $apiRole = Role::findOrCreate('treasurer', 'api');
    $this->actingAs($actor)->post(route('super-admin.role-permissions.store'), ['name' => 'Treasurer'])->assertSessionHasNoErrors();
    $webRole = Role::findByName('treasurer', 'web');
    $this->put(route('super-admin.role-permissions.update', $webRole), ['grants' => ['payments.read']])->assertSessionHasNoErrors();
    expect($webRole->fresh()->permissions->pluck('name')->all())->toBe(['payments.read']);
    expect($apiRole->fresh()->permissions)->toHaveCount(0);
    $this->patch(route('super-admin.role-permissions.rename', $webRole), ['name' => 'Community Treasurer'])->assertSessionHasNoErrors();
    expect($webRole->fresh()->name)->toBe('community-treasurer')->and($apiRole->fresh()->name)->toBe('treasurer');
});

test('Super Admin Member state stays derived while protected staff access remains exempt', function () {
    $root = User::factory()->superAdmin()->create();
    $membership = Membership::factory()->owner()->create(['user_id' => $root->id]);
    app(SyncMemberPlatformRole::class)->handle($root);
    expect($root->fresh()->hasRole('member'))->toBeTrue();
    $this->actingAs($root)->post(route('memberships.end', $membership))->assertSessionHasNoErrors();
    expect($root->fresh()->hasRole('member'))->toBeFalse()->and($root->fresh()->isSuperAdmin())->toBeTrue();
    $this->get(route('officer.payments.index'))->assertOk();
});
