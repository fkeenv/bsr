<?php

use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\User;

test('Administrator can appoint Officer for a user with a live owner Membership', function () {
    $administrator = User::factory()->administrator()->create();
    $candidate = User::factory()->member()->create();

    expect($candidate->hasRole(PlatformRole::Officer))->toBeFalse();

    $this->actingAs($administrator)
        ->post(route('administrator.officers.store'), [
            'user_id' => $candidate->id,
        ])
        ->assertRedirect(route('administrator.officers.index'));

    $candidate->refresh();

    expect($candidate->hasRole(PlatformRole::Officer))->toBeTrue()
        ->and($candidate->hasRole(PlatformRole::User))->toBeTrue()
        ->and($candidate->hasRole(PlatformRole::Member))->toBeTrue();
});

test('Super Admin can appoint Officer for a user with a live owner Membership', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $candidate = User::factory()->member()->create();

    $this->actingAs($superAdmin)
        ->post(route('administrator.officers.store'), [
            'user_id' => $candidate->id,
        ])
        ->assertRedirect(route('administrator.officers.index'));

    expect($candidate->fresh()->hasRole(PlatformRole::Officer))->toBeTrue();
});

test('resident-only Membership cannot receive the Officer role', function () {
    $administrator = User::factory()->administrator()->create();
    $resident = User::factory()->create();
    Membership::factory()->resident()->create([
        'user_id' => $resident->id,
    ]);
    $resident->assignRole(PlatformRole::Member);

    $this->actingAs($administrator)
        ->from(route('administrator.officers.index'))
        ->post(route('administrator.officers.store'), [
            'user_id' => $resident->id,
        ])
        ->assertRedirect(route('administrator.officers.index'))
        ->assertSessionHasErrors('user_id');

    expect($resident->fresh()->hasRole(PlatformRole::Officer))->toBeFalse();
});

test('Officer cannot appoint Officers', function () {
    $officer = User::factory()->officer()->create();
    $candidate = User::factory()->member()->create();

    $this->actingAs($officer)
        ->post(route('administrator.officers.store'), [
            'user_id' => $candidate->id,
        ])
        ->assertForbidden();

    expect($candidate->fresh()->hasRole(PlatformRole::Officer))->toBeFalse();
});

test('Super Admin can appoint Administrator for a user with a live owner Membership', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $candidate = User::factory()->member()->create();

    $this->actingAs($superAdmin)
        ->post(route('super-admin.administrators.store'), [
            'user_id' => $candidate->id,
        ])
        ->assertRedirect(route('super-admin.administrators.index'));

    $candidate->refresh();

    expect($candidate->hasRole(PlatformRole::Administrator))->toBeTrue()
        ->and($candidate->hasRole(PlatformRole::User))->toBeTrue()
        ->and($candidate->canAccessOfficerSurfaces())->toBeTrue()
        ->and($candidate->canAccessAdministratorSurfaces())->toBeTrue();
});

test('resident-only Membership cannot receive the Administrator role', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $resident = User::factory()->create();
    Membership::factory()->resident()->create([
        'user_id' => $resident->id,
    ]);
    $resident->assignRole(PlatformRole::Member);

    $this->actingAs($superAdmin)
        ->from(route('super-admin.administrators.index'))
        ->post(route('super-admin.administrators.store'), [
            'user_id' => $resident->id,
        ])
        ->assertRedirect(route('super-admin.administrators.index'))
        ->assertSessionHasErrors('user_id');

    expect($resident->fresh()->hasRole(PlatformRole::Administrator))->toBeFalse();
});

test('Administrator cannot appoint Administrators', function () {
    $administrator = User::factory()->administrator()->create();
    $candidate = User::factory()->member()->create();

    $this->actingAs($administrator)
        ->post(route('super-admin.administrators.store'), [
            'user_id' => $candidate->id,
        ])
        ->assertForbidden();

    expect($candidate->fresh()->hasRole(PlatformRole::Administrator))->toBeFalse();
});

test('ending the last owner Membership revokes Officer and Administrator', function () {
    $administrator = User::factory()->create();
    $administrator->assignPlatformRole(PlatformRole::Administrator);
    $administrator->assignRole(PlatformRole::Member);
    $administratorMembership = Membership::factory()->owner()->create([
        'user_id' => $administrator->id,
    ]);

    $officer = User::factory()->create();
    $officer->assignPlatformRole(PlatformRole::Officer);
    $officer->assignRole(PlatformRole::Member);
    $officerMembership = Membership::factory()->owner()->create([
        'user_id' => $officer->id,
    ]);

    $actor = User::factory()->officer()->create();

    $this->actingAs($actor)
        ->post(route('officer.memberships.end', $administratorMembership), [
            'end_reason' => 'Sold the house',
        ])
        ->assertRedirect(route('officer.memberships.index'));

    $this->actingAs($actor)
        ->post(route('officer.memberships.end', $officerMembership), [
            'end_reason' => 'Sold the house',
        ])
        ->assertRedirect(route('officer.memberships.index'));

    $administrator->refresh();
    $officer->refresh();

    expect($administrator->hasRole(PlatformRole::Administrator))->toBeFalse()
        ->and($administrator->hasRole(PlatformRole::Member))->toBeFalse()
        ->and($officer->hasRole(PlatformRole::Officer))->toBeFalse()
        ->and($officer->hasRole(PlatformRole::Member))->toBeFalse();
});
