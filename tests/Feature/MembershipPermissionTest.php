<?php

use App\Actions\Memberships\ChangeMembershipRole;
use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\MembershipChange;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

test('Administrators cannot promote or demote their own Membership through staff requests', function () {
    $actor = User::factory()->administrator()->create();
    $membership = $actor->memberships()->live()->sole();
    $this->actingAs($actor)->put(route('officer.memberships.update-role', $membership), ['role' => 'resident'])
        ->assertForbidden();
    expect($membership->fresh()->role)->toBe(MembershipRole::Owner);
    $this->assertDatabaseCount('membership_changes', 0);
});

test('ending the last Owner Membership audits the reason and removed staff roles together', function () {
    $actor = User::factory()->officer()->create();
    $target = User::factory()->administrator()->create();
    $membership = $target->memberships()->live()->sole();
    $this->actingAs($actor)->post(route('officer.memberships.end', $membership), ['end_reason' => 'Moved out'])
        ->assertRedirect();
    $membership->refresh();
    expect($membership->ended_by_user_id)->toBe($actor->id)->and($membership->end_reason)->toBe('Moved out');
    $this->assertDatabaseHas('membership_changes', ['membership_id' => $membership->id, 'actor_user_id' => $actor->id, 'action' => 'end']);
    $change = MembershipChange::query()->sole();
    expect($change->previous_state['staff_roles'])->toBe(['administrator']);
    expect($change->new_state['staff_roles'])->toBe([]);
    expect($target->fresh()->hasRole('administrator'))->toBeFalse();
});

test('Membership role mutations work without reading but require grants inside the reusable lifecycle action', function () {
    $actor = User::factory()->officer()->create();
    Role::findByName('officer', 'web')->syncPermissions(['memberships.change-role']);
    $target = User::factory()->member()->create();
    $membership = $target->memberships()->live()->sole();
    $this->actingAs($actor)->put(route('officer.memberships.update-role', $membership), ['role' => 'resident'])
        ->assertRedirect(route('dashboard'));
    expect($membership->fresh()->role)->toBe(MembershipRole::Resident);
    $this->assertDatabaseHas('membership_changes', ['actor_user_id' => $actor->id, 'action' => 'change-role']);
    $this->get(route('officer.memberships.index'))->assertForbidden();
    Role::findByName('officer', 'web')->syncPermissions([]);
    expect(fn () => app(ChangeMembershipRole::class)->handle($membership, MembershipRole::Owner, $actor))
        ->toThrow(HttpException::class);
    expect($membership->fresh()->role)->toBe(MembershipRole::Resident);
});

test('staff ending another Membership requires a reason while self service leaving stays available without grants', function () {
    $actor = User::factory()->officer()->create();
    $target = User::factory()->member()->create();
    $membership = $target->memberships()->live()->sole();
    $this->actingAs($actor)->post(route('officer.memberships.end', $membership), ['end_reason' => ' '])
        ->assertSessionHasErrors('end_reason');
    expect($membership->fresh()->ended_at)->toBeNull();
    Role::findByName('officer', 'web')->syncPermissions([]);
    $ownMembership = $actor->memberships()->live()->sole();
    $this->post(route('memberships.end', $ownMembership))->assertRedirect(route('dashboard'));
    expect($ownMembership->fresh()->ended_by_user_id)->toBe($actor->id);
    $this->get(route('officer.properties.index'))->assertForbidden();
    $this->get(route('officer.dashboard'))->assertForbidden();
});

test('losing one of several owner Memberships keeps staff roles and restoring ownership does not restore removed roles', function () {
    $actor = User::factory()->officer()->create();
    $target = User::factory()->administrator()->create();
    $first = $target->memberships()->live()->sole();
    $second = Membership::factory()->owner()->create(['user_id' => $target->id]);
    $this->actingAs($actor)->put(route('officer.memberships.update-role', $first), ['role' => 'resident'])->assertRedirect();
    expect($target->fresh()->hasRole('administrator'))->toBeTrue();
    $this->post(route('officer.memberships.end', $second), ['end_reason' => 'No longer an owner'])->assertRedirect();
    expect($target->fresh()->hasRole('administrator'))->toBeFalse();
    $this->put(route('officer.memberships.update-role', $first), ['role' => 'owner'])->assertRedirect();
    expect($target->fresh()->hasLiveOwnerMembership())->toBeTrue();
    expect($target->fresh()->hasRole('administrator'))->toBeFalse();
    $this->assertDatabaseCount('membership_changes', 3);
});

test('an audit failure rolls back the Membership change and staff assignments', function (string $operation) {
    Exceptions::fake();
    $actor = User::factory()->officer()->create();
    $target = User::factory()->administrator()->create();
    $membership = $target->memberships()->live()->sole();
    Event::listen('eloquent.creating: '.MembershipChange::class,
        fn () => throw new RuntimeException('Audit unavailable'));
    try {
        if ($operation === 'end') {
            $this->actingAs($actor)->post(route('officer.memberships.end', $membership), ['end_reason' => 'Moved out'])->assertServerError();
        } else {
            $this->actingAs($actor)->put(route('officer.memberships.update-role', $membership), ['role' => 'resident'])->assertServerError();
        }
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Audit unavailable');
    } finally {
        Event::forget('eloquent.creating: '.MembershipChange::class);
    }
    expect($membership->fresh()->role)->toBe(MembershipRole::Owner)->and($membership->fresh()->ended_at)->toBeNull();
    expect($target->fresh()->hasRole('administrator'))->toBeTrue();
    $this->assertDatabaseCount('membership_changes', 0);
})->with(['end', 'change-role']);

test('an Administrator with another live Owner Membership cannot promote their own Resident Membership', function () {
    $actor = User::factory()->administrator()->create();
    $membership = Membership::factory()->resident()->create(['user_id' => $actor->id]);
    $this->actingAs($actor)->put(route('officer.memberships.update-role', $membership), ['role' => 'owner'])->assertForbidden();
    expect($membership->fresh()->role)->toBe(MembershipRole::Resident);
    expect(fn () => app(ChangeMembershipRole::class)->handle($membership, MembershipRole::Owner, $actor))
        ->toThrow(HttpException::class);
    $this->assertDatabaseCount('membership_changes', 0);
});
