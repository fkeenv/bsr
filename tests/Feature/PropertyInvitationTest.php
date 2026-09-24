<?php

use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

afterEach(function () {
    Str::createRandomStringsNormally();
});

test('Officer can create an invitation with a one-time link and a hashed token', function (string $role) {
    $this->travelTo('2026-09-24 10:00:00');
    Str::createRandomStringsUsing(fn (int $length): string => str_repeat('a', $length));
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create([
        'block' => '12',
        'lot' => '8',
    ]);
    $token = str_repeat('a', 64);

    $response = $this->actingAs($officer)
        ->postJson(route('officer.property-invitations.store'), [
            'property_id' => $property->id,
            'role' => $role,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('url', url('/property-invitations/'.$token));

    expect(serialize(session()->all()))->not->toContain($token);

    $invitation = PropertyInvitation::query()->sole();

    expect($invitation->property_id)->toBe($property->id)
        ->and($invitation->role->value)->toBe($role)
        ->and($invitation->created_by_user_id)->toBe($officer->id)
        ->and($invitation->token_hash)->toBe(hash('sha256', $token))
        ->not->toBe($token)
        ->and($invitation->expires_at->toIso8601String())->toBe('2026-10-24T10:00:00+00:00');

    $this->get(route('officer.property-invitations.index'))
        ->assertInertia(fn ($page) => $page
            ->missingFlash('issuedInvitation')
            ->has('invitations', 1)
            ->where('invitations.0.id', $invitation->id)
            ->where('invitations.0.status', 'unused')
            ->where('invitations.0.can_revoke', true)
            ->missing('invitations.0.token_hash'));
})->with(['owner', 'resident']);

test('Officer sees invitation audit statuses and active Property choices', function () {
    $this->travelTo('2026-09-24 10:00:00');
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create([
        'block' => '3',
        'lot' => '4',
    ]);
    $inactiveProperty = Property::factory()->inactive()->create();

    PropertyInvitation::factory()->for($property)->for($officer, 'creator')->create();
    PropertyInvitation::factory()->for($property)->for($officer, 'creator')->consumed()->create();
    PropertyInvitation::factory()->for($property)->for($officer, 'creator')->expired()->create();
    PropertyInvitation::factory()->for($property)->for($officer, 'creator')->revoked($officer)->create();

    $response = $this->actingAs($officer)
        ->get(route('officer.property-invitations.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('officer/property-invitations/Index')
        ->has('invitations', 4)
        ->where('invitations.0.status', 'revoked')
        ->where('invitations.1.status', 'expired')
        ->where('invitations.2.status', 'consumed')
        ->where('invitations.3.status', 'unused')
        ->where('invitations.0.can_revoke', false)
        ->where('invitations.1.can_revoke', false)
        ->where('invitations.2.can_revoke', false)
        ->where('invitations.3.can_revoke', true)
        ->where('invitations.3.property_label', 'Block 3 · Lot 4')
        ->where('invitations.3.role', 'owner')
        ->where('invitations.3.creator_name', $officer->name)
        ->where('invitations.3.created_at', '2026-09-24T10:00:00+00:00')
        ->where('invitations.3.expires_at', '2026-10-24T10:00:00+00:00')
        ->missing('invitations.3.token_hash')
        ->where('properties', fn ($properties) => $properties
            ->contains(fn (array $option): bool => $option['id'] === $property->id)
            && ! $properties->contains(fn (array $option): bool => $option['id'] === $inactiveProperty->id))
    );
});

test('Officer filters invitations by Manila calendar dates', function (string $range, string $column) {
    $officer = User::factory()->officer()->create();
    $inside = PropertyInvitation::factory()->create([
        $column => Carbon::parse('2026-09-23 16:00:00', 'UTC'),
    ]);
    PropertyInvitation::factory()->create([
        $column => Carbon::parse('2026-09-23 15:59:59', 'UTC'),
    ]);
    PropertyInvitation::factory()->create([
        $column => Carbon::parse('2026-09-24 16:00:00', 'UTC'),
    ]);

    $this->actingAs($officer)
        ->get(route('officer.property-invitations.index', [
            $range.'_from' => '2026-09-24',
            $range.'_to' => '2026-09-24',
        ]))
        ->assertInertia(fn ($page) => $page
            ->has('invitations', 1)
            ->where('invitations.0.id', $inside->id)
            ->where('table.dateRanges', ['created', 'expires'])
            ->where('table.values.'.$range.'_from', '2026-09-24')
            ->where('table.values.'.$range.'_to', '2026-09-24'));
})->with([
    'created' => ['created', 'created_at'],
    'expires' => ['expires', 'expires_at'],
]);

test('Officer can revoke an unused invitation', function () {
    $this->travelTo('2026-09-24 10:00:00');
    $officer = User::factory()->officer()->create();
    $invitation = PropertyInvitation::factory()->create();
    $originalTokenHash = $invitation->token_hash;

    $response = $this->actingAs($officer)
        ->delete(route('officer.property-invitations.destroy', $invitation));

    $response->assertRedirect(route('officer.property-invitations.index'));

    $invitation->refresh();

    expect($invitation->status()->value)->toBe('revoked')
        ->and($invitation->revoked_at?->toIso8601String())->toBe('2026-09-24T10:00:00+00:00')
        ->and($invitation->revoked_by_user_id)->toBe($officer->id)
        ->and($invitation->token_hash)->toBe($originalTokenHash);
});

test('Officer cannot revoke a consumed invitation', function () {
    $officer = User::factory()->officer()->create();
    $invitation = PropertyInvitation::factory()->consumed()->create();
    $consumedAt = $invitation->consumed_at;

    $response = $this->actingAs($officer)
        ->delete(route('officer.property-invitations.destroy', $invitation));

    $response
        ->assertRedirect(route('officer.property-invitations.index'))
        ->assertInertiaFlash('toast.message', 'Only unused invitations can be revoked.');

    $invitation->refresh();

    expect($invitation->status()->value)->toBe('consumed')
        ->and($invitation->consumed_at?->equalTo($consumedAt))->toBeTrue()
        ->and($invitation->revoked_at)->toBeNull()
        ->and($invitation->revoked_by_user_id)->toBeNull();
});

test('Officer cannot revoke an invitation at its expiry instant', function () {
    $this->travelTo('2026-09-24 10:00:00');
    $officer = User::factory()->officer()->create();
    $invitation = PropertyInvitation::factory()->create([
        'expires_at' => now(),
    ]);

    $response = $this->actingAs($officer)
        ->delete(route('officer.property-invitations.destroy', $invitation));

    $response
        ->assertRedirect(route('officer.property-invitations.index'))
        ->assertInertiaFlash('toast.message', 'Only unused invitations can be revoked.');

    $invitation->refresh();

    expect($invitation->status()->value)->toBe('expired')
        ->and($invitation->revoked_at)->toBeNull()
        ->and($invitation->revoked_by_user_id)->toBeNull();
});

test('Officer cannot create an invitation for an inactive Property', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->inactive()->create();

    $response = $this->actingAs($officer)
        ->postJson(route('officer.property-invitations.store'), [
            'property_id' => $property->id,
            'role' => 'owner',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_id'])
        ->assertJsonPath('errors.property_id.0', 'Select an active Property.');
    $this->assertDatabaseCount('property_invitations', 0);
});

test('Officer cannot create an invitation with an invalid Membership role', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();

    $response = $this->actingAs($officer)
        ->postJson(route('officer.property-invitations.store'), [
            'property_id' => $property->id,
            'role' => 'administrator',
        ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role'])
        ->assertJsonPath('errors.role.0', 'The selected role is invalid.');
    $this->assertDatabaseCount('property_invitations', 0);
});

test('guest cannot list Property invitations', function () {
    $this->get(route('officer.property-invitations.index'))
        ->assertRedirect(route('login'));
});

test('non-Officer cannot list Property invitations', function () {
    $member = User::factory()->member()->create();

    $this->actingAs($member)
        ->get(route('officer.property-invitations.index'))
        ->assertForbidden();
});

test('non-Officer cannot create Property invitations', function () {
    $member = User::factory()->member()->create();
    $property = Property::factory()->create();

    $this->actingAs($member)
        ->postJson(route('officer.property-invitations.store'), [
            'property_id' => $property->id,
            'role' => 'resident',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('property_invitations', 0);
});

test('non-Officer cannot revoke Property invitations', function () {
    $member = User::factory()->member()->create();
    $invitation = PropertyInvitation::factory()->create();

    $this->actingAs($member)
        ->delete(route('officer.property-invitations.destroy', $invitation))
        ->assertForbidden();

    expect($invitation->fresh()->revoked_at)->toBeNull();
});
