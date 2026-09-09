<?php

use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;

test('Member can end their own Membership without a reason', function () {
    $user = User::factory()->member()->create();
    $membership = $user->memberships()->live()->first();

    $this->actingAs($user)
        ->post(route('memberships.end', $membership))
        ->assertRedirect();

    $membership->refresh();
    $user->refresh();

    expect($membership->ended_at)->not->toBeNull()
        ->and($membership->end_reason)->toBeNull()
        ->and($user->hasLiveMembership())->toBeFalse()
        ->and($user->hasRole(PlatformRole::Member))->toBeFalse();
});

test('Officer can end a Membership with a reason', function () {
    $member = User::factory()->member()->create();
    $officer = User::factory()->officer()->create();
    $membership = $member->memberships()->live()->first();

    $this->actingAs($officer)
        ->post(route('officer.memberships.end', $membership), [
            'end_reason' => 'Sold the house',
        ])
        ->assertRedirect(route('officer.memberships.index'));

    $membership->refresh();
    $member->refresh();

    expect($membership->ended_at)->not->toBeNull()
        ->and($membership->end_reason)->toBe('Sold the house')
        ->and($membership->ended_by_user_id)->toBe($officer->id)
        ->and($member->hasRole(PlatformRole::Member))->toBeFalse();
});

test('Officer ending a Membership without a reason is rejected', function () {
    $member = User::factory()->member()->create();
    $officer = User::factory()->officer()->create();
    $membership = $member->memberships()->live()->first();

    $this->actingAs($officer)
        ->from(route('officer.memberships.index'))
        ->post(route('officer.memberships.end', $membership), [
            'end_reason' => '',
        ])
        ->assertRedirect(route('officer.memberships.index'))
        ->assertSessionHasErrors('end_reason');
});

test('Officer can change a live Membership role between owner and resident', function () {
    $member = User::factory()->member()->create();
    $officer = User::factory()->officer()->create();
    $membership = $member->memberships()->live()->first();

    expect($membership->role)->toBe(MembershipRole::Owner);

    $this->actingAs($officer)
        ->put(route('officer.memberships.update-role', $membership), [
            'role' => MembershipRole::Resident->value,
        ])
        ->assertRedirect(route('officer.memberships.index'));

    $membership->refresh();

    expect($membership->role)->toBe(MembershipRole::Resident);
});

test('one User Account may hold live Memberships on several Properties', function () {
    $user = User::factory()->create();
    $first = Property::factory()->create(['block' => '1', 'lot' => '1']);
    $second = Property::factory()->create(['block' => '2', 'lot' => '2']);

    Membership::factory()->owner()->create([
        'user_id' => $user->id,
        'property_id' => $first->id,
    ]);
    Membership::factory()->resident()->create([
        'user_id' => $user->id,
        'property_id' => $second->id,
    ]);

    expect(Membership::query()->live()->where('user_id', $user->id)->count())->toBe(2)
        ->and($user->fresh()->hasLiveMembership())->toBeTrue();
});

test('ending the last owner Membership revokes Officer even if a resident Membership remains', function () {
    $user = User::factory()->create();
    $user->assignPlatformRole(PlatformRole::Officer);
    $user->assignRole(PlatformRole::Member);

    $ownerMembership = Membership::factory()->owner()->create([
        'user_id' => $user->id,
    ]);
    Membership::factory()->resident()->create([
        'user_id' => $user->id,
        'property_id' => Property::factory(),
    ]);

    $officer = User::factory()->officer()->create();

    $this->actingAs($officer)
        ->post(route('officer.memberships.end', $ownerMembership), [
            'end_reason' => 'Sold one house',
        ])
        ->assertRedirect(route('officer.memberships.index'));

    $user->refresh();

    expect($user->hasRole(PlatformRole::Officer))->toBeFalse()
        ->and($user->hasRole(PlatformRole::Member))->toBeTrue()
        ->and($user->hasLiveMembership())->toBeTrue();
});

test('changing the last owner Membership to resident revokes Officer', function () {
    $user = User::factory()->create();
    $user->assignPlatformRole(PlatformRole::Officer);
    $user->assignRole(PlatformRole::Member);

    $membership = Membership::factory()->owner()->create([
        'user_id' => $user->id,
    ]);

    $officer = User::factory()->officer()->create();

    $this->actingAs($officer)
        ->put(route('officer.memberships.update-role', $membership), [
            'role' => MembershipRole::Resident->value,
        ])
        ->assertRedirect(route('officer.memberships.index'));

    $user->refresh();

    expect($membership->fresh()->role)->toBe(MembershipRole::Resident)
        ->and($user->hasRole(PlatformRole::Officer))->toBeFalse()
        ->and($user->hasRole(PlatformRole::Member))->toBeTrue();
});
