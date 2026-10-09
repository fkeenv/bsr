<?php

use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;

test('Officers retrieve the original credentials without changing the invitation', function () {
    $this->actingAs(User::factory()->officer()->create());
    $issued = $this->postJson(route('officer.property-invitations.store'), [
        'property_id' => Property::factory()->create()->id, 'role' => 'resident',
    ])->assertCreated()->json();
    $invitation = PropertyInvitation::query()->sole();
    $original = $invitation->getRawOriginal();
    $this->getJson(route('officer.property-invitations.share', $invitation))
        ->assertOk()->assertExactJson($issued)->assertHeader('Cache-Control', 'no-store, private');
    expect($invitation->fresh()->getRawOriginal())->toBe($original);
    $this->get(route('officer.property-invitations.index'))->assertInertia(fn ($page) => $page
        ->where('invitations.0.can_share', true)
        ->missing('invitations.0.url')->missing('invitations.0.code')
        ->missing('invitations.0.share_token')->missing('invitations.0.share_code'));
});

test('unavailable invitations never return credentials', function (string $state) {
    $this->actingAs(User::factory()->officer()->create());
    $this->postJson(route('officer.property-invitations.store'), [
        'property_id' => Property::factory()->create()->id, 'role' => 'owner',
    ])->assertCreated();
    $invitation = PropertyInvitation::query()->sole();
    match ($state) {
        'expired' => $invitation->update(['expires_at' => now()->subSecond()]),
        'consumed' => $invitation->update(['consumed_at' => now()]),
        'revoked' => $this->delete(route('officer.property-invitations.destroy', $invitation))->assertRedirect(),
        'inactive' => $invitation->property->update(['is_active' => false]),
    };
    $this->getJson(route('officer.property-invitations.share', $invitation))
        ->assertUnprocessable()->assertJsonMissingPath('url')->assertJsonMissingPath('code');
    $this->get(route('officer.property-invitations.index'))->assertInertia(fn ($page) => $page
        ->where('invitations.0.can_share', false));
})->with(['expired', 'consumed', 'revoked', 'inactive']);

test('legacy invitations explain replacement without regenerating credentials', function () {
    $this->actingAs(User::factory()->officer()->create());
    $invitation = PropertyInvitation::factory()->create()->fresh();
    $original = $invitation->getRawOriginal();
    $this->getJson(route('officer.property-invitations.share', $invitation))
        ->assertUnprocessable()->assertJsonPath('message', 'The original link cannot be recovered for this older invitation. Revoke it and create a replacement to share a link or code.');
    expect($invitation->fresh()->getRawOriginal())->toBe($original);
});

test('guests and ordinary accounts cannot retrieve invitation credentials', function () {
    $invitation = PropertyInvitation::factory()->create();
    $this->getJson(route('officer.property-invitations.share', $invitation))->assertUnauthorized();
    $this->actingAs(User::factory()->create())->getJson(route('officer.property-invitations.share', $invitation))->assertForbidden();
});

test('inherited Officer roles can retrieve credentials', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create());
    $issued = $this->postJson(route('officer.property-invitations.store'), [
        'property_id' => Property::factory()->create()->id, 'role' => 'owner',
    ])->assertCreated()->json();
    $this->getJson(route('officer.property-invitations.share', PropertyInvitation::query()->sole()))
        ->assertOk()->assertExactJson($issued);
})->with(['superAdmin', 'administrator']);
