<?php

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\Property;
use App\Models\StaffRoleAssignmentChange;
use App\Models\User;
use App\Support\StaffPermissionCatalog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->withoutVite());

test('administrators can browse accounts without Memberships and only safe personal details are serialized', function () {
    $actor = User::factory()->administrator()->create();
    $subject = User::factory()->create(['name' => 'Directory Target']);
    $this->actingAs($actor)->get('/administrator/user-accounts?search=Directory%20Target')->assertOk()
        ->assertInertia(fn ($page) => $page->component('administrator/user-accounts/Index')
            ->has('accounts', 1)->where('accounts.0.id', $subject->id)
            ->where('accounts.0.live_memberships', [])->missing('accounts.0.password')->missing('accounts.0.two_factor_secret'));
});

test('an Administrator can assign multiple custom roles and revoke one with atomic history and next request access', function () {
    $actor = User::factory()->administrator()->create();
    $owner = User::factory()->member()->create();
    $read = Role::findOrCreate('records-reader', 'web');
    $read->syncPermissions(['properties.read']);
    $money = Role::findOrCreate('treasurer', 'web');
    $money->syncPermissions(['payments.read']);
    foreach ([$read, $money] as $role) {
        $this->actingAs($actor)->post('/administrator/user-accounts/'.$owner->id.'/staff-roles/'.$role->name)->assertRedirect()->assertSessionHasNoErrors();
    }
    $this->assertDatabaseCount('staff_role_assignment_changes', 2);
    $this->actingAs($owner)->get(route('officer.payments.index'))->assertOk();
    $owner->load('roles');
    $this->actingAs($actor)->delete('/administrator/user-accounts/'.$owner->id.'/staff-roles/'.$money->name)->assertRedirect();
    $this->actingAs($owner)->get(route('officer.payments.index'))->assertForbidden();
    $this->get(route('officer.properties.index'))->assertOk();
    $audit = StaffRoleAssignmentChange::query()->latest('id')->first();
    expect($audit->previous_assignments)->toBe(['records-reader', 'treasurer'])->and($audit->new_assignments)->toBe(['records-reader'])
        ->and($audit->actor_user_id)->toBe($actor->id)->and($audit->user_id)->toBe($owner->id)->and($audit->created_at)->not->toBeNull();
});

test('ending the last Owner Membership requires confirmation and records automatic staff removal', function () {
    $actor = User::factory()->superAdmin()->create();
    $owner = User::factory()->officer()->create();
    $owner->assignRole(Role::findOrCreate('treasurer', 'web'));
    $membership = $owner->memberships()->sole();
    $url = '/administrator/user-accounts/'.$owner->id.'/memberships/'.$membership->id.'/end';
    $this->actingAs($actor)->post($url, ['end_reason' => 'Sold property'])->assertSessionHasErrors('confirm_staff_access_loss');
    expect($membership->fresh()->isLive())->toBeTrue();
    $this->post($url, ['end_reason' => 'Sold property', 'confirm_staff_access_loss' => true])->assertRedirect();
    expect($membership->fresh()->end_reason)->toBe('Sold property')->and($owner->fresh()->canAccessOfficerSurfaces())->toBeFalse();
    $audit = StaffRoleAssignmentChange::query()->sole();
    expect($audit->reason)->toBe('last-owner-membership-ended')->and($audit->previous_assignments)->toBe(['officer', 'treasurer'])
        ->and($audit->new_assignments)->toBe([])->and($audit->actor_user_id)->toBe($actor->id);
});

test('each directory search matches accounts once across multiple live Properties', function (string $search) {
    $actor = User::factory()->administrator()->create();
    $subject = User::factory()->create(['name' => 'Unique Zinnia', 'email' => 'zinnia@example.test', 'mobile_number' => '+639112233445']);
    foreach ([['block' => 'ZinniaBlock', 'lot' => 'ZinniaLot'], ['block' => 'ZinniaBlock', 'lot' => 'AnotherLot']] as $property) {
        Membership::factory()->owner()->create(['user_id' => $subject->id, 'property_id' => Property::factory()->create($property)->id]);
    }
    $this->actingAs($actor)->get(route('administrator.user-accounts.index', ['search' => $search]))->assertInertia(fn ($page) => $page
        ->has('accounts', 1)->where('accounts.0.id', $subject->id)->where('pagination.total', 1)->has('accounts.0.live_memberships', 2));
})->with(['Unique Zinnia', 'zinnia@example.test', '+639112233445', 'ZinniaBlock', 'ZinniaLot']);

test('staff role Property role and live Membership filters exclude ended Memberships', function (array $filter) {
    $actor = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $owner->assignRole(Role::findOrCreate('filter-role', 'web'));
    $resident = User::factory()->create();
    Membership::factory()->resident()->create(['user_id' => $resident->id]);
    $ended = User::factory()->create();
    Membership::factory()->owner()->create(['user_id' => $ended->id, 'ended_at' => now()]);
    $ids = match ($filter) {
        ['staff_role' => 'filter-role'] => [$owner->id],
        ['membership_role' => 'owner'] => [$owner->id],
        ['membership_role' => 'resident'] => [$resident->id],
        ['live_membership' => 'yes'] => [$owner->id, $resident->id],
        ['live_membership' => 'no'] => [$actor->id, $ended->id],
        default => [],
    };
    $this->actingAs($actor)->get(route('administrator.user-accounts.index', $filter))->assertInertia(fn ($page) => $page
        ->where('accounts', fn ($accounts) => collect($accounts)->pluck('id')->sort()->values()->all() === collect($ids)->sort()->values()->all()));
})->with([[['staff_role' => 'filter-role']], [['membership_role' => 'owner']], [['membership_role' => 'resident']], [['live_membership' => 'yes']], [['live_membership' => 'no']]]);

test('search and filters remain server paginated with a stable order', function () {
    $actor = User::factory()->superAdmin()->create();
    User::factory()->count(27)->create(['name' => 'Pagination Match']);
    $this->actingAs($actor)->get(route('administrator.user-accounts.index', ['search' => 'Pagination Match', 'live_membership' => 'no']))->assertInertia(fn ($page) => $page->has('accounts', 25)->where('pagination.total', 27)->where('pagination.last_page', 2));
    $this->get(route('administrator.user-accounts.index', ['search' => 'Pagination Match', 'live_membership' => 'no', 'page' => 2]))->assertInertia(fn ($page) => $page->has('accounts', 2)->where('pagination.current_page', 2));
});

test('directory dates use Manila calendar boundaries and reject invalid filters', function () {
    $actor = User::factory()->superAdmin()->create();
    $subject = User::factory()->create(['created_at' => '2026-10-12 16:00:00']);
    User::factory()->create(['created_at' => '2026-10-12 15:59:59']);
    $this->actingAs($actor)->get(route('administrator.user-accounts.index', ['registered_from' => '2026-10-13', 'registered_to' => '2026-10-13']))->assertInertia(fn ($page) => $page->where('accounts', fn ($accounts) => collect($accounts)->contains('id', $subject->id) && ! collect($accounts)->contains('registered_at', '2026-10-12T15:59:59+00:00')));
    $this->get(route('administrator.user-accounts.index', ['registered_to' => '2026-10-13']))->assertSessionHasNoErrors()->assertOk();
    $this->get(route('administrator.user-accounts.index', ['membership_role' => 'bogus', 'staff_role' => 'super-adminx', 'live_membership' => 'bogus', 'registered_from' => 'wrong', 'page' => 0]))->assertSessionHasErrors(['membership_role', 'staff_role', 'live_membership', 'registered_from', 'page']);
});

test('Officers custom staff and ordinary accounts cannot access directory or details even with every grant', function (string $kind) {
    $subject = User::factory()->create();
    $actor = match ($kind) {
        'officer' => User::factory()->officer()->create(), 'custom' => User::factory()->member()->create(), default => User::factory()->create()
    };
    if ($kind === 'custom') {
        $role = Role::findOrCreate('all-grants', 'web');
        $role->syncPermissions(StaffPermissionCatalog::values());
        $actor->assignRole($role);
    }
    $this->actingAs($actor)->get(route('administrator.user-accounts.index'))->assertForbidden();
    $this->get(route('administrator.user-accounts.show', $subject))->assertForbidden();
})->with(['officer', 'custom', 'user']);

test('details include ended history without credentials or mutation grants implied by directory access', function () {
    $actor = User::factory()->administrator()->create();
    Role::findByName('administrator', 'web')->syncPermissions([]);
    $subject = User::factory()->member()->create();
    Membership::factory()->resident()->create(['user_id' => $subject->id, 'ended_at' => now(), 'ended_by_user_id' => $actor->id, 'end_reason' => 'Moved out']);
    $this->actingAs($actor)->get(route('administrator.user-accounts.show', $subject))->assertOk()->assertInertia(fn ($page) => $page
        ->has('account.live_memberships', 1)->has('ended_memberships', 1)->where('ended_memberships.0.end_reason', 'Moved out')->where('ended_memberships.0.ended_by', $actor->name)
        ->where('can_end_memberships', false)->where('can_change_membership_roles', false)
        ->where('account', fn ($account) => array_diff(array_keys($account->toArray()), ['id', 'name', 'email', 'mobile_number', 'registered_at', 'staff_roles', 'live_memberships', 'is_super_admin', 'has_owner_membership']) === []));
    $membership = $subject->memberships()->live()->sole();
    $this->put(route('administrator.user-accounts.memberships.update-role', [$subject, $membership]), ['role' => 'resident'])->assertForbidden();
    $this->post(route('administrator.user-accounts.memberships.end', [$subject, $membership]), ['end_reason' => 'Moved'])->assertForbidden();
});

test('Administrators cannot change their own staff roles or Administrator and protected assignments', function (string $roleName, bool $self) {
    $actor = User::factory()->administrator()->create();
    $subject = $self ? $actor : User::factory()->member()->create();
    $role = Role::findOrCreate($roleName, 'web');
    $subject->assignRole($role);
    $url = route('administrator.user-accounts.staff-roles.store', [$subject, $role]);
    $this->actingAs($actor)->post($url)->assertForbidden();
    $this->delete(route('administrator.user-accounts.staff-roles.destroy', [$subject, $role]))->assertForbidden();
    $this->assertDatabaseCount('staff_role_assignment_changes', 0);
    expect($subject->fresh()->hasRole($role))->toBeTrue();
})->with([['officer', true], ['custom-role', true], ['administrator', false], ['super-admin', false], ['member', false], ['user', false]]);

test('Super Admin appointments and revocations use existing appointment rules and take effect next request', function () {
    $root = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $this->actingAs($root)->post(route('super-admin.administrators.store'), ['user_id' => $owner->id])->assertRedirect();
    $this->assertDatabaseHas('staff_role_assignment_changes', ['user_id' => $owner->id, 'actor_user_id' => $root->id, 'action' => 'assign']);
    $this->actingAs($owner)->get(route('administrator.user-accounts.index'))->assertOk();
    $owner->load('roles');
    $this->actingAs($root)->delete(route('administrator.user-accounts.staff-roles.destroy', [$owner, 'administrator']))->assertRedirect();
    $this->actingAs($owner)->get(route('administrator.user-accounts.index'))->assertForbidden();
    $this->get(route('administrator.user-accounts.show', $root))->assertForbidden();
});

test('legacy Officer appointments are audited and repeated assignments do not duplicate history', function () {
    $actor = User::factory()->administrator()->create();
    $owner = User::factory()->member()->create();
    $this->actingAs($actor)->post(route('administrator.officers.store'), ['user_id' => $owner->id])->assertRedirect();
    $this->post(route('administrator.user-accounts.staff-roles.store', [$owner, 'officer']))->assertRedirect();
    $this->assertDatabaseCount('staff_role_assignment_changes', 1);
    $this->delete(route('administrator.user-accounts.staff-roles.destroy', [$owner, 'officer']))->assertRedirect();
    $this->delete(route('administrator.user-accounts.staff-roles.destroy', [$owner, 'officer']))->assertRedirect();
    $this->assertDatabaseCount('staff_role_assignment_changes', 2);
});

test('staff assignment requires ownership while Super Admin remains exempt for custom roles', function () {
    $root = User::factory()->superAdmin()->create();
    $resident = User::factory()->create();
    Membership::factory()->resident()->create(['user_id' => $resident->id]);
    $role = Role::findOrCreate('volunteer', 'web');
    $this->actingAs($root)->post(route('administrator.user-accounts.staff-roles.store', [$resident, $role]))->assertSessionHasErrors('user_id');
    $this->post(route('administrator.user-accounts.staff-roles.store', [$root, $role]))->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('administrator.user-accounts.staff-roles.store', [$root, 'officer']))->assertSessionHasErrors('user_id');
    $this->post(route('administrator.user-accounts.staff-roles.store', [$resident, 'super-admin']))->assertForbidden();
    $this->assertDatabaseCount('staff_role_assignment_changes', 1);
});

test('staff mutation is denied to custom roles even with every permission', function () {
    $actor = User::factory()->member()->create();
    $target = User::factory()->member()->create();
    $role = Role::findOrCreate('all-grants', 'web');
    $role->syncPermissions(StaffPermissionCatalog::values());
    $actor->assignRole($role);
    $this->actingAs($actor)->post(route('administrator.user-accounts.staff-roles.store', [$target, 'officer']))->assertForbidden();
    $this->delete(route('administrator.user-accounts.staff-roles.destroy', [$target, 'officer']))->assertForbidden();
    $this->assertDatabaseCount('staff_role_assignment_changes', 0);
});

test('membership actions require their independent grants and a reason for ending another account Membership', function () {
    $actor = User::factory()->administrator()->create();
    $subject = User::factory()->member()->create();
    $membership = $subject->memberships()->sole();
    $role = Role::findByName('administrator', 'web');
    $role->syncPermissions(['memberships.change-role']);
    $this->actingAs($actor)->post(route('administrator.user-accounts.memberships.end', [$subject, $membership]), ['end_reason' => 'Moved'])->assertForbidden();
    $this->put(route('administrator.user-accounts.memberships.update-role', [$subject, $membership]), ['role' => 'resident'])->assertRedirect()->assertSessionHasNoErrors();
    expect($membership->fresh()->role)->toBe(MembershipRole::Resident);
    $role->syncPermissions(['memberships.end']);
    $this->put(route('administrator.user-accounts.memberships.update-role', [$subject, $membership]), ['role' => 'owner'])->assertForbidden();
    $this->post(route('administrator.user-accounts.memberships.end', [$subject, $membership]), ['end_reason' => '   '])->assertSessionHasErrors('end_reason');
    $this->post(route('administrator.user-accounts.memberships.end', [$subject, $membership]), ['end_reason' => 'Moved'])->assertRedirect()->assertSessionHasNoErrors();
    expect($membership->fresh()->ended_by_user_id)->toBe($actor->id);
    $this->post(route('administrator.user-accounts.memberships.end', [$subject, $membership]), ['end_reason' => 'Again'])->assertSessionHasErrors('membership');
});

test('directory membership actions cannot target another account or manage own status and self service leaving still works', function () {
    $actor = User::factory()->administrator()->create();
    $subject = User::factory()->member()->create();
    $membership = $actor->memberships()->sole();
    $this->actingAs($actor)->put(route('administrator.user-accounts.memberships.update-role', [$subject, $membership]), ['role' => 'resident'])->assertNotFound();
    $this->put(route('administrator.user-accounts.memberships.update-role', [$actor, $membership]), ['role' => 'resident'])->assertForbidden();
    $this->post(route('administrator.user-accounts.memberships.end', [$actor, $membership]), ['end_reason' => 'Moved'])->assertForbidden();
    $this->get(route('administrator.user-accounts.show', $actor))->assertInertia(fn ($page) => $page->where('can_change_membership_roles', false)->where('can_end_memberships', false));
    $this->post(route('memberships.end', $membership))->assertRedirect(route('dashboard'));
    expect($membership->fresh()->isLive())->toBeFalse()->and($actor->fresh()->canAccessAdministratorSurfaces())->toBeFalse();
    $this->assertDatabaseHas('staff_role_assignment_changes', ['action' => 'automatic-removal', 'reason' => 'last-owner-membership-ended', 'actor_user_id' => $actor->id]);
});

test('ownership across Properties preserves staff until the last Owner is removed and restoration needs reappointment', function () {
    $root = User::factory()->superAdmin()->create();
    $subject = User::factory()->officer()->create();
    $subject->assignRole(Role::findOrCreate('community-staff', 'web'));
    $first = $subject->memberships()->sole();
    $last = Membership::factory()->owner()->create(['user_id' => $subject->id]);
    $this->actingAs($root)->post(route('administrator.user-accounts.memberships.end', [$subject, $first]), ['end_reason' => 'Sold first Property'])->assertSessionHasNoErrors();
    expect($subject->fresh()->hasRole('community-staff'))->toBeTrue();
    $this->assertDatabaseCount('staff_role_assignment_changes', 0);
    $url = route('administrator.user-accounts.memberships.update-role', [$subject, $last]);
    $this->put($url, ['role' => 'resident'])->assertSessionHasErrors('confirm_staff_access_loss');
    $this->assertDatabaseCount('staff_role_assignment_changes', 0);
    $this->put($url, ['role' => 'resident', 'confirm_staff_access_loss' => true])->assertSessionHasNoErrors();
    expect($subject->fresh()->hasRole('community-staff'))->toBeFalse()->and($subject->fresh()->hasRole('officer'))->toBeFalse();
    $this->assertDatabaseHas('staff_role_assignment_changes', ['reason' => 'last-owner-membership-changed-to-resident']);
    $this->put($url, ['role' => 'owner'])->assertSessionHasNoErrors();
    expect($subject->fresh()->canAccessOfficerSurfaces())->toBeFalse();
    $this->post(route('administrator.user-accounts.staff-roles.store', [$subject, 'community-staff']))->assertSessionHasNoErrors();
    expect($subject->fresh()->canAccessOfficerSurfaces())->toBeTrue();
});

test('failed assignment audit rolls back the role change', function (string $operation) {
    $root = User::factory()->superAdmin()->create();
    $subject = User::factory()->member()->create();
    if ($operation === 'revoke') {
        $subject->assignRole('officer');
    }
    if ($operation === 'automatic-removal') {
        $subject->assignRole('officer');
    }
    Exceptions::fake();
    $event = 'eloquent.creating: '.StaffRoleAssignmentChange::class;
    Event::listen($event, fn () => throw new RuntimeException('Audit storage unavailable'));
    try {
        $this->actingAs($root);
        if ($operation === 'assign') {
            $this->post(route('administrator.user-accounts.staff-roles.store', [$subject, 'officer']))->assertStatus(500);
        } elseif ($operation === 'revoke') {
            $this->delete(route('administrator.user-accounts.staff-roles.destroy', [$subject, 'officer']))->assertStatus(500);
        } else {
            $this->post(route('administrator.user-accounts.memberships.end', [$subject, $subject->memberships()->sole()]), ['end_reason' => 'Moved', 'confirm_staff_access_loss' => true])->assertStatus(500);
        }
    } finally {
        Event::forget($event);
    }
    expect($subject->fresh()->hasRole('officer'))->toBe($operation !== 'assign');
    expect($subject->memberships()->live()->count())->toBe(1);
    $this->assertDatabaseCount('staff_role_assignment_changes', 0);
})->with(['assign', 'revoke', 'automatic-removal']);
