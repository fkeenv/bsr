<?php

use App\Models\MembershipChange;
use App\Models\RolePermissionChange;
use App\Models\StaffRoleAssignmentChange;
use App\Models\User;
use App\Support\StaffPermissionCatalog;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->withoutVite());

test('Super Admin can review recorded permissions and assignments in one read only history', function () {
    $root = User::factory()->superAdmin()->create();
    $subject = User::factory()->member()->create();
    $permissions = RolePermissionChange::factory()->create(['actor_user_id' => $root->id, 'role_name' => 'officer', 'created_at' => '2026-10-13 12:00:00']);
    $assignment = StaffRoleAssignmentChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $subject->id, 'created_at' => '2026-10-13 13:00:00']);
    $this->actingAs($root)->get('/super-admin/access-history')->assertOk()->assertInertia(fn ($page) => $page
        ->component('super-admin/access-history/Index')->has('entries', 2)->where('entries.0.id', 'assignment-'.$assignment->id)
        ->where('entries.0.actor_name', $root->name)->where('entries.0.account_name', $subject->name)->where('entries.0.new_values', ['officer'])
        ->where('entries.1.id', 'role-'.$permissions->id)->where('entries.1.role_name', 'officer')->where('entries.1.previous_values', ['announcements.read', 'announcements.create']));
});

test('successful role lifecycle and assignments retain snapshots after rename and deletion while failed changes stay out', function () {
    $root = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $this->actingAs($root)->post(route('super-admin.role-permissions.store'), ['name' => 'Ledger'])->assertSessionHasNoErrors();
    $role = Role::findByName('ledger', 'web');
    $this->put(route('super-admin.role-permissions.update', $role), ['grants' => ['payments.read']])->assertSessionHasNoErrors();
    $this->post(route('administrator.user-accounts.staff-roles.store', [$owner, $role]))->assertSessionHasNoErrors();
    $this->delete(route('super-admin.role-permissions.destroy', $role))->assertSessionHasErrors('role');
    $this->put(route('super-admin.role-permissions.update', $role), ['grants' => ['unknown.permission']])->assertSessionHasErrors('grants.0');
    $this->post(route('super-admin.role-permissions.store'), ['name' => 'Ledger'])->assertSessionHasErrors('name');
    $this->patch(route('super-admin.role-permissions.rename', $role), ['name' => 'Finance team'])->assertSessionHasNoErrors();
    $role->refresh();
    $this->delete(route('administrator.user-accounts.staff-roles.destroy', [$owner, $role]))->assertSessionHasNoErrors();
    $this->delete(route('super-admin.role-permissions.destroy', $role))->assertSessionHasNoErrors();
    $this->get(route('super-admin.access-history.index'))->assertInertia(fn ($page) => $page->has('entries', 6)->where('entries', fn ($entries) => collect($entries)->pluck('action')->sort()->values()->all() === ['assign', 'create', 'delete', 'permissions', 'rename', 'revoke']));
    $this->get(route('super-admin.access-history.index', ['action' => 'rename']))->assertInertia(fn ($page) => $page->has('entries', 1)->where('entries.0.previous_role_name', 'ledger')->where('entries.0.new_role_name', 'finance-team'));
    $this->get(route('super-admin.access-history.index', ['action' => 'delete']))->assertInertia(fn ($page) => $page->where('entries.0.role_name', 'finance-team')->where('entries.0.previous_values', ['payments.read'])->where('entries.0.new_values', []));
    $this->get(route('super-admin.access-history.index', ['role' => 'ledger']))->assertInertia(fn ($page) => $page->has('entries', 4)->where('filter_options.role', fn ($options) => collect($options)->contains('value', 'ledger')));
});

test('automatic removals show actor origin affected account and reason after ownership loss', function () {
    $root = User::factory()->superAdmin()->create();
    $owner = User::factory()->officer()->create();
    $this->actingAs($root)->post(route('administrator.user-accounts.memberships.end', [$owner, $owner->memberships()->sole()]), ['end_reason' => 'Sold the Property', 'confirm_staff_access_loss' => true])->assertSessionHasNoErrors();
    $this->get(route('super-admin.access-history.index'))->assertInertia(fn ($page) => $page->has('entries', 1)->where('entries.0.origin', 'automatic')->where('entries.0.action', 'automatic-removal')->where('entries.0.actor_name', $root->name)->where('entries.0.account_name', $owner->name)->where('entries.0.reason', 'last-owner-membership-ended')->where('entries.0.previous_values', ['officer'])->where('entries.0.new_values', []));
});

test('history filters actor account role and action across both audit sources', function (string $filter) {
    $root = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    RolePermissionChange::factory()->create(['actor_user_id' => $root->id, 'role_name' => 'old-ledger', 'previous_role_name' => 'old-ledger', 'new_role_name' => 'ledger', 'action' => 'rename']);
    RolePermissionChange::factory()->create(['actor_user_id' => $other->id, 'role_name' => 'officer']);
    StaffRoleAssignmentChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'new_assignments' => ['old-ledger'], 'previous_assignments' => []]);
    $query = match ($filter) {
        'actor' => ['actor' => $root->id], 'account' => ['account' => $owner->id], 'role' => ['role' => 'old-ledger'], 'action' => ['action' => 'rename']
    };
    $count = in_array($filter, ['actor', 'role'], true) ? 2 : 1;
    $this->actingAs($root)->get(route('super-admin.access-history.index', $query))->assertInertia(fn ($page) => $page->has('entries', $count)->where('pagination.total', $count)->where('values.'.$filter, (string) array_values($query)[0]));
})->with(['actor', 'account', 'role', 'action']);

test('role filtering matches removed assignments and exact names rather than partial matches', function () {
    $root = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    StaffRoleAssignmentChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'action' => 'revoke', 'previous_assignments' => ['ledger'], 'new_assignments' => []]);
    StaffRoleAssignmentChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'new_assignments' => ['ledger-extra']]);
    $this->actingAs($root)->get(route('super-admin.access-history.index', ['role' => 'ledger']))->assertInertia(fn ($page) => $page->has('entries', 1)->where('entries.0.action', 'revoke'));
    $this->get(route('super-admin.access-history.index', ['role' => "ledger' OR 1=1 --"]))->assertInertia(fn ($page) => $page->has('entries', 0));
});

test('date filters use inclusive Manila calendar days and can use just one bound', function () {
    $root = User::factory()->superAdmin()->create();
    foreach (['2026-10-12 15:59:59', '2026-10-12 16:00:00', '2026-10-13 15:59:59', '2026-10-13 16:00:00'] as $timestamp) {
        RolePermissionChange::factory()->create(['actor_user_id' => $root->id, 'created_at' => $timestamp]);
    }
    $this->actingAs($root)->get(route('super-admin.access-history.index', ['occurred_from' => '2026-10-13', 'occurred_to' => '2026-10-13']))->assertInertia(fn ($page) => $page->has('entries', 2)->where('entries.0.occurred_at', '2026-10-13T15:59:59+00:00')->where('entries.1.occurred_at', '2026-10-12T16:00:00+00:00'));
    $this->get(route('super-admin.access-history.index', ['occurred_to' => '2026-10-13']))->assertInertia(fn ($page) => $page->has('entries', 3));
    $this->get(route('super-admin.access-history.index', ['actor' => 0, 'account' => 'bad', 'action' => 'bad', 'occurred_from' => 'bad', 'page' => 0]))->assertSessionHasErrors(['actor', 'account', 'action', 'occurred_from', 'page']);
    $this->get(route('super-admin.access-history.index', ['occurred_from' => '2026-10-13', 'occurred_to' => '2026-10-12']))->assertSessionHasErrors('occurred_to');
});

test('mixed audit pagination is stable when timestamps and numeric IDs overlap', function () {
    $root = User::factory()->superAdmin()->create();
    $subject = User::factory()->create();
    RolePermissionChange::factory()->count(13)->create(['actor_user_id' => $root->id, 'created_at' => '2026-10-13 12:00:00']);
    StaffRoleAssignmentChange::factory()->count(13)->create(['actor_user_id' => $root->id, 'user_id' => $subject->id, 'created_at' => '2026-10-13 12:00:00']);
    $this->actingAs($root)->get(route('super-admin.access-history.index', ['actor' => $root->id]))->assertInertia(fn ($page) => $page->has('entries', 25)->where('pagination.total', 26)->where('entries.0.id', 'assignment-13')->where('entries.24.id', 'role-2'));
    $this->get(route('super-admin.access-history.index', ['actor' => $root->id, 'page' => 2]))->assertInertia(fn ($page) => $page->has('entries', 1)->where('entries.0.id', 'role-1')->where('pagination.current_page', 2));
});

test('non Super Admin cannot read history even when every staff grant is assigned', function (string $role) {
    $actor = match ($role) {
        'administrator' => User::factory()->administrator()->create(), 'officer' => User::factory()->officer()->create(), 'custom' => User::factory()->member()->create(), default => User::factory()->create()
    };
    if ($role === 'custom') {
        $custom = Role::findOrCreate('all-grants', 'web');
        $custom->syncPermissions(StaffPermissionCatalog::values());
        $actor->assignRole($custom);
    }
    $this->actingAs($actor)->get(route('super-admin.access-history.index'))->assertForbidden();
})->with(['administrator', 'officer', 'custom', 'user']);

test('history requires sign in and exposes no write endpoints', function () {
    $this->get(route('super-admin.access-history.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->superAdmin()->create());
    foreach (['post', 'put', 'patch', 'delete'] as $method) {
        $this->{$method}(route('super-admin.access-history.index'))->assertStatus(405);
    }
});

test('earlier Membership snapshots remain visible without duplicating newer automatic removal audits', function () {
    $root = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $membership = $owner->memberships()->sole();
    $before = ['staff_roles' => ['officer', 'historical-team']];
    $after = ['staff_roles' => []];
    $older = MembershipChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'membership_id' => $membership->id, 'action' => 'change-role', 'previous_state' => $before, 'new_state' => $after, 'created_at' => '2026-10-11 12:00:00']);
    $modern = StaffRoleAssignmentChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'membership_id' => $membership->id, 'action' => 'automatic-removal', 'previous_assignments' => ['officer'], 'new_assignments' => [], 'reason' => 'last-owner-membership-ended', 'created_at' => '2026-10-13 12:00:00']);
    MembershipChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'membership_id' => $membership->id, 'action' => 'end', 'previous_state' => ['staff_roles' => ['officer']], 'new_state' => $after, 'created_at' => '2026-10-13 12:00:01']);
    $this->actingAs($root)->get(route('super-admin.access-history.index', ['account' => $owner->id, 'action' => 'automatic-removal']))->assertInertia(fn ($page) => $page->has('entries', 2)->where('entries.0.id', 'assignment-'.$modern->id)->where('entries.1.id', 'membership-'.$older->id)->where('entries.1.previous_values', ['officer', 'historical-team'])->where('entries.1.new_values', [])->where('entries.1.reason', 'last-owner-membership-changed-to-resident'));
    $this->get(route('super-admin.access-history.index', ['role' => 'historical-team', 'occurred_from' => '2026-10-11', 'occurred_to' => '2026-10-11', 'actor' => $root->id]))->assertInertia(fn ($page) => $page->has('entries', 1)->where('pagination.total', 1)->where('filter_options.role', fn ($options) => collect($options)->contains('value', 'historical-team')));
});

test('legacy automatic removals participate in pagination while ordinary Membership changes are excluded', function () {
    $root = User::factory()->superAdmin()->create();
    $owner = User::factory()->member()->create();
    $membership = $owner->memberships()->sole();
    RolePermissionChange::factory()->count(25)->create(['actor_user_id' => $root->id, 'created_at' => '2026-10-13 12:00:00']);
    $legacy = MembershipChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'membership_id' => $membership->id, 'action' => 'end', 'previous_state' => ['staff_roles' => ['officer']], 'new_state' => ['staff_roles' => []], 'created_at' => '2026-10-11 12:00:00']);
    MembershipChange::factory()->create(['actor_user_id' => $root->id, 'user_id' => $owner->id, 'action' => 'end', 'previous_state' => ['staff_roles' => []], 'new_state' => ['staff_roles' => []]]);
    $this->actingAs($root)->get(route('super-admin.access-history.index', ['page' => 2]))->assertInertia(fn ($page) => $page->has('entries', 1)->where('pagination.total', 26)->where('entries.0.id', 'membership-'.$legacy->id)->where('entries.0.reason', 'last-owner-membership-ended'));
});
