<?php

use App\Models\RolePermissionChange;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

test('Super Admin can replace a staff roles Announcement grants and audit the change', function () {
    $actor = User::factory()->superAdmin()->create();
    $role = Role::findByName('officer', 'web');
    $role->syncPermissions([
        'announcements.read', 'announcements.create', 'announcements.edit',
        'announcements.publish', 'announcements.pin', 'announcements.attachments',
    ]);

    $this->actingAs($actor)
        ->put(route('super-admin.role-permissions.update', $role), [
            'grants' => ['announcements.read'],
        ])
        ->assertRedirect(route('super-admin.role-permissions.index'));

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['announcements.read']);
    $this->assertDatabaseHas('role_permission_changes', [
        'actor_user_id' => $actor->id,
        'role_id' => $role->id,
        'role_name' => 'officer',
    ]);
    $audit = RolePermissionChange::query()->sole();
    expect($audit->previous_grants)->toBe([
        'announcements.attachments', 'announcements.create', 'announcements.edit',
        'announcements.pin', 'announcements.publish', 'announcements.read',
    ])->and($audit->new_grants)->toBe(['announcements.read'])
        ->and($audit->created_at)->not->toBeNull();
});

test('the editor lists only editable roles and enforced staff permissions', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('super-admin.role-permissions.index'))
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('super-admin/role-permissions/Index')
        ->has('roles', 2)
        ->where('roles.0.name', 'administrator')
        ->where('roles.1.name', 'officer')
        ->has('permissions', 49)
        ->where('permissions.0.value', 'announcements.read'));
});

test('a failed audit write rolls back the permission change', function () {
    $role = Role::findByName('officer', 'web');
    $before = $role->permissions->pluck('name')->sort()->values()->all();
    Event::listen('eloquent.creating: '.RolePermissionChange::class,
        fn () => throw new RuntimeException('Audit storage unavailable'));
    Exceptions::fake();

    try {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('super-admin.role-permissions.update', $role), ['grants' => []])->assertServerError();
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Audit storage unavailable');
    } finally {
        Event::forget('eloquent.creating: '.RolePermissionChange::class);
    }

    expect($role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe($before);
    $this->assertDatabaseCount('role_permission_changes', 0);
});

test('non Super Admin accounts cannot open or update the permission editor', function (string $state) {
    $user = User::factory()->{$state}()->create();
    $role = Role::findByName('officer', 'web');
    $this->actingAs($user)->get(route('super-admin.role-permissions.index'))->assertForbidden();
    $this->put(route('super-admin.role-permissions.update', $role), ['grants' => []])->assertForbidden();
    $this->assertDatabaseCount('role_permission_changes', 0);
    expect($role->fresh()->permissions)->toHaveCount(43);
})->with(['officer', 'administrator', 'member']);

test('protected roles and unknown or duplicate permissions cannot be changed', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    foreach (['super-admin', 'member', 'user'] as $name) {
        $this->put(route('super-admin.role-permissions.update', Role::findByName($name, 'web')),
            ['grants' => ['announcements.read']])->assertForbidden();
    }
    $role = Role::findByName('officer', 'web');
    $this->put(route('super-admin.role-permissions.update', $role), ['grants' => ['announcements.delete']])
        ->assertSessionHasErrors('grants.0');
    $this->put(route('super-admin.role-permissions.update', $role), ['grants' => ['announcements.read', 'announcements.read']])
        ->assertSessionHasErrors('grants.0');
    $this->assertDatabaseCount('role_permission_changes', 0);
    expect($role->fresh()->permissions)->toHaveCount(43);
});

test('saving unchanged permissions does not add an audit change', function () {
    $role = Role::findByName('officer', 'web');
    $this->actingAs(User::factory()->superAdmin()->create())
        ->put(route('super-admin.role-permissions.update', $role), ['grants' => $role->permissions->pluck('name')->all()])
        ->assertRedirect();
    $this->assertDatabaseCount('role_permission_changes', 0);
});
