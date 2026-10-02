<?php

use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;

/**
 * @return array{LegalDocumentVersion, LegalDocumentVersion}
 */
function publishInvitationRedemptionLegalDocuments(): array
{
    $terms = LegalDocumentVersion::factory()->termsOfService()->create([
        'published_at' => now()->subMinute(),
    ]);
    $privacy = LegalDocumentVersion::factory()->privacyPolicy()->create([
        'published_at' => now()->subMinute(),
    ]);

    return [$terms, $privacy];
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createInvitationWithToken(string $token, array $attributes = []): PropertyInvitation
{
    return PropertyInvitation::factory()->create([
        'token_hash' => hash('sha256', $token),
        ...$attributes,
    ]);
}

test('authenticated recipient sees the invitation and current legal documents', function () {
    $this->travelTo('2026-09-24 10:00:00');
    [$terms, $privacy] = publishInvitationRedemptionLegalDocuments();
    $property = Property::factory()->create([
        'block' => '12',
        'lot' => '8',
    ]);
    $token = str_repeat('a', 64);
    createInvitationWithToken($token, [
        'property_id' => $property->id,
        'role' => MembershipRole::Resident,
        'expires_at' => now()->addDays(30),
    ]);
    $recipient = User::factory()->create();

    $this->actingAs($recipient)
        ->get('/property-invitations/'.$token)
        ->assertInertia(fn ($page) => $page
            ->component('property-invitations/Show')
            ->where('token', $token)
            ->where('invitation.property_label', 'Block 12 · Lot 8')
            ->where('invitation.role', 'resident')
            ->where('invitation.expires_at', '2026-10-24T10:00:00+00:00')
            ->where('invitation.terms_of_service_version_id', $terms->id)
            ->where('invitation.privacy_policy_version_id', $privacy->id));
});

test('guest returns to invitation confirmation after login', function () {
    publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('b', 64);
    createInvitationWithToken($token);
    $recipient = User::factory()->create();
    $invitationUrl = route('property-invitations.show', $token);

    $this->get($invitationUrl)
        ->assertRedirect(route('login'))
        ->assertSessionHas('url.intended', $invitationUrl);

    $this->post(route('login.store'), [
        'email' => $recipient->email,
        'password' => 'password',
    ])->assertRedirect($invitationUrl);

    $this->assertAuthenticatedAs($recipient);
});

test('guest returns to invitation confirmation after registration', function () {
    publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('c', 64);
    createInvitationWithToken($token);
    $invitationUrl = route('property-invitations.show', $token);

    $this->get($invitationUrl)
        ->assertRedirect(route('login'))
        ->assertSessionHas('url.intended', $invitationUrl);

    $this->post(route('register.store'), [
        'name' => 'Invitation Recipient',
        'email' => 'recipient@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect($invitationUrl);

    $this->assertAuthenticated();
});

test('recipient redeems an invitation into a live Membership', function (MembershipRole $role) {
    $this->travelTo('2026-09-24 10:00:00');
    [$terms, $privacy] = publishInvitationRedemptionLegalDocuments();
    $property = Property::factory()->create([
        'block' => '4',
        'lot' => '9',
    ]);
    $token = str_repeat('d', 64);
    $invitation = createInvitationWithToken($token, [
        'property_id' => $property->id,
        'role' => $role,
    ]);
    $recipient = User::factory()->create();

    $response = $this->actingAs($recipient)
        ->post('/property-invitations/'.$token, [
            'accept_terms' => '1',
            'accept_privacy' => '1',
            'terms_of_service_version_id' => $terms->id,
            'privacy_policy_version_id' => $privacy->id,
        ]);

    $response
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast.message', 'Your Membership for Block 4 · Lot 9 is active.');

    $membership = Membership::query()->sole();
    $invitation->refresh();
    $recipient->refresh();

    expect($membership->user_id)->toBe($recipient->id)
        ->and($membership->property_id)->toBe($property->id)
        ->and($membership->role)->toBe($role)
        ->and($membership->started_at->toIso8601String())->toBe('2026-09-24T10:00:00+00:00')
        ->and($membership->ended_at)->toBeNull()
        ->and($membership->property_invitation_id)->toBe($invitation->id)
        ->and($membership->terms_of_service_version_id)->toBe($terms->id)
        ->and($membership->privacy_policy_version_id)->toBe($privacy->id);

    expect($invitation->consumed_at?->toIso8601String())->toBe('2026-09-24T10:00:00+00:00')
        ->and($recipient->hasRole(PlatformRole::Member))->toBeTrue();
})->with([
    'owner' => MembershipRole::Owner,
    'resident' => MembershipRole::Resident,
]);

/**
 * @param  array{LegalDocumentVersion, LegalDocumentVersion}  $legalDocuments
 * @return array<string, mixed>
 */
function invitationRedemptionPayload(array $legalDocuments): array
{
    return [
        'accept_terms' => '1',
        'accept_privacy' => '1',
        'terms_of_service_version_id' => $legalDocuments[0]->id,
        'privacy_policy_version_id' => $legalDocuments[1]->id,
    ];
}

test('unavailable invitations cannot be redeemed', function (Closure $makeInvitation) {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('e', 64);
    $invitation = $makeInvitation($token);
    $consumedAtBefore = $invitation?->consumed_at?->toIso8601String();
    $recipient = User::factory()->create();

    $this->actingAs($recipient)
        ->get('/property-invitations/'.$token)
        ->assertNotFound();

    $this->actingAs($recipient)
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertSessionHasErrors('invitation');

    expect(Membership::query()->count())->toBe(0)
        ->and($recipient->refresh()->hasRole(PlatformRole::Member))->toBeFalse();

    expect($invitation?->refresh()->consumed_at?->toIso8601String())->toBe($consumedAtBefore);
})->with([
    'expired' => fn (string $token) => PropertyInvitation::factory()->expired()->create(['token_hash' => hash('sha256', $token)]),
    'revoked' => fn (string $token) => PropertyInvitation::factory()->revoked()->create(['token_hash' => hash('sha256', $token)]),
    'consumed' => fn (string $token) => PropertyInvitation::factory()->consumed()->create(['token_hash' => hash('sha256', $token)]),
    'inactive Property' => fn (string $token) => createInvitationWithToken($token, [
        'property_id' => Property::factory()->inactive()->create()->id,
    ]),
    'unknown token' => fn (string $token) => null,
]);

test('inactive Property invitations stay unconsumed after a failed redemption', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('f', 64);
    $invitation = createInvitationWithToken($token, [
        'property_id' => Property::factory()->inactive()->create()->id,
    ]);

    $this->actingAs(User::factory()->create())
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertSessionHasErrors('invitation');

    expect($invitation->refresh()->consumed_at)->toBeNull();
});

test('malformed invitation tokens are not found', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $recipient = User::factory()->create();

    $this->actingAs($recipient)
        ->get('/property-invitations/not-a-token')
        ->assertNotFound();

    $this->actingAs($recipient)
        ->post('/property-invitations/not-a-token', invitationRedemptionPayload($legalDocuments))
        ->assertNotFound();

    expect(Membership::query()->count())->toBe(0);
});

test('recipient with a live Membership on the Property cannot redeem again', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $property = Property::factory()->create();
    $recipient = User::factory()->create();
    Membership::factory()->owner()->create([
        'user_id' => $recipient->id,
        'property_id' => $property->id,
    ]);
    $token = str_repeat('g', 64);
    $invitation = createInvitationWithToken($token, [
        'property_id' => $property->id,
        'role' => MembershipRole::Resident,
    ]);

    $this->actingAs($recipient)
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertSessionHasErrors('invitation');

    expect(Membership::query()->count())->toBe(1)
        ->and($invitation->refresh()->consumed_at)->toBeNull();
});

test('repeat redemption requests create only one Membership', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('h', 64);
    createInvitationWithToken($token);
    $recipient = User::factory()->create();

    $this->actingAs($recipient)
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertRedirect(route('dashboard'));

    $this->actingAs($recipient)
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertSessionHasErrors('invitation');

    $this->actingAs(User::factory()->create())
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertSessionHasErrors('invitation');

    expect(Membership::query()->count())->toBe(1);
});

test('redemption requires accepting both legal documents', function (array $overrides, string $errorKey) {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('i', 64);
    $invitation = createInvitationWithToken($token);

    $this->actingAs(User::factory()->create())
        ->post('/property-invitations/'.$token, [
            ...invitationRedemptionPayload($legalDocuments),
            ...$overrides,
        ])
        ->assertSessionHasErrors($errorKey);

    expect(Membership::query()->count())->toBe(0)
        ->and($invitation->refresh()->consumed_at)->toBeNull();
})->with([
    'terms not accepted' => [['accept_terms' => '0'], 'accept_terms'],
    'privacy not accepted' => [['accept_privacy' => '0'], 'accept_privacy'],
    'missing terms version' => [['terms_of_service_version_id' => null], 'terms_of_service_version_id'],
    'missing privacy version' => [['privacy_policy_version_id' => null], 'privacy_policy_version_id'],
]);

test('redemption rejects superseded legal document versions', function () {
    $this->travelTo('2026-09-24 10:00:00');
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    LegalDocumentVersion::factory()->termsOfService()->create([
        'published_at' => now(),
    ]);
    $token = str_repeat('j', 64);
    $invitation = createInvitationWithToken($token);

    $this->actingAs(User::factory()->create())
        ->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertSessionHasErrors('legal_documents');

    expect(Membership::query()->count())->toBe(0)
        ->and($invitation->refresh()->consumed_at)->toBeNull();
});

test('guests cannot redeem an invitation', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $token = str_repeat('k', 64);
    $invitation = createInvitationWithToken($token);

    $this->post('/property-invitations/'.$token, invitationRedemptionPayload($legalDocuments))
        ->assertRedirect(route('login'));

    expect(Membership::query()->count())->toBe(0)
        ->and($invitation->refresh()->consumed_at)->toBeNull();
});

test('redemption is rate limited per account', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();
    $recipient = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($recipient)
            ->post('/property-invitations/'.str_repeat('l', 64), invitationRedemptionPayload($legalDocuments))
            ->assertSessionHasErrors('invitation');
    }

    $this->actingAs($recipient)
        ->post('/property-invitations/'.str_repeat('l', 64), invitationRedemptionPayload($legalDocuments))
        ->assertTooManyRequests();
});

test('redemption is rate limited per network address across accounts', function () {
    $legalDocuments = publishInvitationRedemptionLegalDocuments();

    foreach (range(1, 10) as $attempt) {
        $this->actingAs(User::factory()->create())
            ->post('/property-invitations/'.str_repeat('m', 64), invitationRedemptionPayload($legalDocuments))
            ->assertSessionHasErrors('invitation');
    }

    $this->actingAs(User::factory()->create())
        ->post('/property-invitations/'.str_repeat('m', 64), invitationRedemptionPayload($legalDocuments))
        ->assertTooManyRequests();
});
