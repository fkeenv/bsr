<?php

use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('owner invitation input cannot substitute a Property or grant ownership', function (array $payload, string $field) {
    $owner = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->for($owner)->for($property)->create();
    $this->actingAs($owner)->postJson(route('owner.property-invitations.store', $property), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(PropertyInvitation::query()->count())->toBe(0);
})->with([
    'owner role' => [['role' => 'owner'], 'role'],
    'substituted property' => [['property_id' => 999], 'property_id'],
]);

test('owner invitation endpoints reject accounts without current ownership', function (string $state) {
    $account = User::factory()->create();
    $property = Property::factory()->create();
    $membership = Membership::factory()->owner()->for($account)->for($property)->create();
    $invitation = PropertyInvitation::factory()->for($property)->create(['created_by_user_id' => $account->id]);
    match ($state) {
        'resident' => $membership->update(['role' => 'resident']),
        'former owner' => $membership->update(['ended_at' => now()]),
        'unrelated account' => $account = User::factory()->create(),
    };
    $this->actingAs($account)->get(route('owner.property-invitations.index', $property))->assertNotFound();
    $this->postJson(route('owner.property-invitations.store', $property), [])->assertNotFound();
    $this->delete(route('owner.property-invitations.destroy', [$property, $invitation]))->assertNotFound();
    expect(PropertyInvitation::query()->count())->toBe(1)
        ->and($invitation->fresh()->revoked_at)->toBeNull();
})->with(['resident', 'former owner', 'unrelated account']);

test('owners only see and revoke their own invitations for the selected Property', function () {
    $owner = User::factory()->create();
    $firstProperty = Property::factory()->create();
    $secondProperty = Property::factory()->create();
    foreach ([$firstProperty, $secondProperty] as $property) {
        Membership::factory()->owner()->for($owner)->for($property)->create();
    }
    $own = PropertyInvitation::factory()->for($firstProperty)->create(['created_by_user_id' => $owner->id]);
    $otherIssuer = PropertyInvitation::factory()->for($firstProperty)->create();
    $otherProperty = PropertyInvitation::factory()->for($secondProperty)->create(['created_by_user_id' => $owner->id]);
    $this->actingAs($owner)->get(route('owner.property-invitations.index', $firstProperty))->assertInertia(fn ($page) => $page
        ->has('invitations', 1)->where('invitations.0.id', $own->id));
    $this->get(route('owner.property-invitations.index', $secondProperty))->assertInertia(fn ($page) => $page
        ->has('invitations', 1)->where('invitations.0.id', $otherProperty->id));
    $this->delete(route('owner.property-invitations.destroy', [$firstProperty, $otherIssuer]))->assertNotFound();
    $this->delete(route('owner.property-invitations.destroy', [$firstProperty, $otherProperty]))->assertNotFound();
    $this->delete(route('owner.property-invitations.destroy', [$firstProperty, $own]))->assertRedirect(route('owner.property-invitations.index', $firstProperty));
    $this->get(route('owner.property-invitations.index', $firstProperty))->assertInertia(fn ($page) => $page
        ->where('invitations.0.status', 'revoked')->where('invitations.0.can_revoke', false));
    expect($otherIssuer->fresh()->revoked_at)->toBeNull()->and($otherProperty->fresh()->revoked_at)->toBeNull();
});

test('only unused owner-issued invitations may be revoked', function (string $state) {
    $owner = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->for($owner)->for($property)->create();
    $invitation = PropertyInvitation::factory()->for($property)->create(['created_by_user_id' => $owner->id]);
    match ($state) {
        'expired' => $invitation->update(['expires_at' => now()->subDay()]),
        'consumed' => $invitation->update(['consumed_at' => now()]),
        'revoked' => $invitation->update(['revoked_at' => now()]),
    };
    $original = $invitation->fresh()->getRawOriginal();
    $this->actingAs($owner)->delete(route('owner.property-invitations.destroy', [$property, $invitation]))
        ->assertRedirect()->assertInertiaFlash('toast.message', 'Only unused invitations can be revoked.');
    expect($invitation->fresh()->getRawOriginal())->toBe($original);
})->with(['expired', 'consumed', 'revoked']);

test('guests cannot create or manage owner invitations', function () {
    $property = Property::factory()->create();
    $invitation = PropertyInvitation::factory()->for($property)->create();
    $this->getJson(route('owner.property-invitations.index', $property))->assertUnauthorized();
    $this->postJson(route('owner.property-invitations.store', $property), [])->assertUnauthorized();
    $this->deleteJson(route('owner.property-invitations.destroy', [$property, $invitation]))->assertUnauthorized();
});

test('owner invitations use shared redemption and survive later ownership changes', function (string $credentialKind, string $ownershipChange) {
    $terms = LegalDocumentVersion::factory()->termsOfService()->create();
    $privacy = LegalDocumentVersion::factory()->privacyPolicy()->create();
    $owner = User::factory()->create();
    $property = Property::factory()->create();
    $membership = Membership::factory()->owner()->for($owner)->for($property)->create();
    $issued = $this->actingAs($owner)->postJson(route('owner.property-invitations.store', $property), [])->assertCreated()->json();
    $membership->update($ownershipChange === 'ended' ? ['ended_at' => now()] : ['role' => 'resident']);
    $this->get(route('owner.property-invitations.index', $property))->assertNotFound();
    $this->postJson(route('owner.property-invitations.store', $property), [])->assertNotFound();

    $recipient = User::factory()->create();
    $credential = $credentialKind === 'code' ? $issued['code'] : basename($issued['url']);
    if ($credentialKind === 'code') {
        $this->actingAs($recipient)->post(route('property-invitation-codes.store'), ['code' => $credential])
            ->assertRedirect(route('property-invitations.show', $credential));
    }
    $this->actingAs($recipient)->get(route('property-invitations.show', $credential))->assertInertia(fn ($page) => $page
        ->where('invitation.role', 'resident')->where('invitation.issuer_name', $owner->name)
        ->where('invitation.property_label', 'Block '.$property->block.' · Lot '.$property->lot));
    $payload = ['accept_terms' => '1', 'accept_privacy' => '1', 'terms_of_service_version_id' => $terms->id, 'privacy_policy_version_id' => $privacy->id];
    $this->post(route('property-invitations.store', $credential), $payload)->assertRedirect(route('dashboard'));
    $result = Membership::query()->whereBelongsTo($recipient)->sole();
    expect($result->role->value)->toBe('resident')->and($result->property_id)->toBe($property->id);
    $otherCredential = $credentialKind === 'code' ? basename($issued['url']) : $issued['code'];
    $this->post(route('property-invitations.store', $otherCredential), $payload)->assertSessionHasErrors('invitation');
    expect(Membership::query()->whereBelongsTo($recipient)->count())->toBe(1);
})->with([
    'code after role change' => ['code', 'role'],
    'link after ownership ends' => ['link', 'ended'],
]);

test('Officers oversee and revoke owner-issued invitations', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->for($owner)->for($property)->create();
    $this->actingAs($owner)->postJson(route('owner.property-invitations.store', $property), [])->assertCreated();
    $invitation = PropertyInvitation::query()->sole();
    $this->actingAs(User::factory()->officer()->create())->get(route('officer.property-invitations.index'))->assertInertia(fn ($page) => $page
        ->where('invitations.0.creator_name', $owner->name)->where('invitations.0.status', 'unused'));
    $this->delete(route('officer.property-invitations.destroy', $invitation))->assertRedirect();
    $this->actingAs($owner)->get(route('owner.property-invitations.index', $property))->assertInertia(fn ($page) => $page
        ->where('invitations.0.status', 'revoked'));
});

test('dashboard invitation entries identify only the accounts active owned Properties', function () {
    $account = User::factory()->create();
    $first = Property::factory()->create();
    $second = Property::factory()->create();
    $inactive = Property::factory()->create(['is_active' => false]);
    foreach ([$first, $second, $inactive] as $property) {
        Membership::factory()->owner()->for($account)->for($property)->create();
    }
    Membership::factory()->resident()->for($account)->create();
    Membership::factory()->owner()->ended()->for($account)->create();
    $this->actingAs($account)->get(route('dashboard'))->assertInertia(fn ($page) => $page
        ->where('ownerInvitationPropertyIds', [$inactive->id, $second->id, $first->id])
        ->where('activeOwnerInvitationPropertyIds', [$second->id, $first->id]));
    $this->postJson(route('owner.property-invitations.store', $first), [])->assertCreated();
    $this->postJson(route('owner.property-invitations.store', $second), [])->assertCreated();
    expect(PropertyInvitation::query()->pluck('property_id')->all())->toBe([$first->id, $second->id]);
});

test('owners can review and revoke existing invitations for an inactive Property but cannot issue more', function () {
    $owner = User::factory()->create();
    $property = Property::factory()->create(['is_active' => false]);
    Membership::factory()->owner()->for($owner)->for($property)->create();
    $invitation = PropertyInvitation::factory()->for($property)->create(['created_by_user_id' => $owner->id]);
    $this->actingAs($owner)->get(route('owner.property-invitations.index', $property))->assertOk()->assertInertia(fn ($page) => $page
        ->where('property.is_active', false)->where('invitations.0.id', $invitation->id));
    $this->postJson(route('owner.property-invitations.store', $property), [])->assertNotFound();
    $this->delete(route('owner.property-invitations.destroy', [$property, $invitation]))->assertRedirect();
    expect($invitation->fresh()->revoked_at)->not->toBeNull();
});

test('a live owner issues a resident invitation for their active Property', function () {
    $this->travelTo(now()->startOfSecond());
    $owner = User::factory()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->for($owner)->for($property)->create();
    $response = $this->actingAs($owner)->postJson(route('owner.property-invitations.store', $property), [])
        ->assertCreated()->assertHeader('Cache-Control', 'no-store, private');
    expect($response->json('code'))->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    $invitation = PropertyInvitation::query()->sole();
    expect($invitation->property_id)->toBe($property->id)
        ->and($invitation->created_by_user_id)->toBe($owner->id)
        ->and($invitation->role->value)->toBe('resident')
        ->and($invitation->expires_at->equalTo(now()->addDays(30)))->toBeTrue();
    expect(basename($response->json('url')))->toBe($invitation->share_token);
    $this->get(route('owner.property-invitations.index', $property))->assertOk()->assertInertia(fn ($page) => $page
        ->component('owner/property-invitations/Index')
        ->where('property.id', $property->id)
        ->where('invitations.0.creator_name', $owner->name)
        ->where('invitations.0.status', 'unused')
        ->missing('invitations.0.code')->missing('invitations.0.share_token'));
});
