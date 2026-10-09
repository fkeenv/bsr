<?php

use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Support\Str;

/** @return array{string, string, Property, User} */
function issueCodeInvitation(): array
{
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();
    $response = test()->actingAs($officer)->postJson(route('officer.property-invitations.store'), ['property_id' => $property->id, 'role' => 'resident'])->assertCreated();

    return [$response->json('code'), $response->json('url'), $property, $officer];
}

test('new invitations return a UUIDv4 code and protect recoverable credentials', function () {
    [$code, $url] = issueCodeInvitation();
    expect($code)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    $invitation = PropertyInvitation::query()->sole();
    expect($invitation->share_code)->toBe($code)
        ->and($invitation->share_token)->toBe(basename($url))
        ->and($invitation->getRawOriginal('share_code'))->not->toContain($code)
        ->and($invitation->getRawOriginal('share_token'))->not->toContain(basename($url));
    foreach (['share_code', 'share_token', 'code_hash', 'token_hash'] as $attribute) {
        expect($invitation->toArray())->not->toHaveKey($attribute);
    }
    $this->get(route('officer.property-invitations.index'))->assertInertia(fn ($page) => $page->missing('invitations.0.code')->missing('invitations.0.share_code')->missing('invitations.0.share_token'));
});

test('a recipient confirms and redeems a code without consuming it on lookup', function () {
    $legalDocuments = publishCodeInvitationLegalDocuments();
    [$code, $url, $property, $officer] = issueCodeInvitation();
    $recipient = User::factory()->create();
    $this->actingAs($recipient)->post(route('property-invitation-codes.store'), ['code' => '  '.Str::upper($code).'  '])->assertRedirect(route('property-invitations.show', $code));
    $this->get(route('property-invitations.show', $code))->assertInertia(fn ($page) => $page
        ->component('property-invitations/Show')
        ->where('token', $code)
        ->where('invitation.property_label', 'Block '.$property->block.' · Lot '.$property->lot)
        ->where('invitation.role', 'resident')
        ->where('invitation.issuer_name', $officer->name));
    expect(PropertyInvitation::query()->sole()->consumed_at)->toBeNull();
    $this->post(route('property-invitations.store', $code), codeInvitationPayload($legalDocuments))->assertRedirect(route('dashboard'));
    expect(Membership::query()->whereBelongsTo($recipient)->sole()->role->value)->toBe('resident');
    $this->get($url)->assertNotFound();
    $this->post(route('property-invitations.store', basename($url)), codeInvitationPayload($legalDocuments))->assertSessionHasErrors('invitation');
    expect(Membership::query()->whereBelongsTo($recipient)->count())->toBe(1);
});

/** @return array{LegalDocumentVersion, LegalDocumentVersion} */
function publishCodeInvitationLegalDocuments(): array
{
    return [LegalDocumentVersion::factory()->termsOfService()->create(), LegalDocumentVersion::factory()->privacyPolicy()->create()];
}

/**
 * @param  array{LegalDocumentVersion, LegalDocumentVersion}  $documents
 * @return array{accept_terms: string, accept_privacy: string, terms_of_service_version_id: int, privacy_policy_version_id: int}
 */
function codeInvitationPayload(array $documents): array
{
    return ['accept_terms' => '1', 'accept_privacy' => '1', 'terms_of_service_version_id' => $documents[0]->id, 'privacy_policy_version_id' => $documents[1]->id];
}

test('unavailable codes cannot reveal a Property or be redeemed', function (string $state) {
    $documents = publishCodeInvitationLegalDocuments();
    [$code] = issueCodeInvitation();
    $invitation = PropertyInvitation::query()->sole();
    match ($state) {
        'expired' => $invitation->update(['expires_at' => now()->subSecond()]),
        'revoked' => $invitation->update(['revoked_at' => now()]),
        'consumed' => $invitation->update(['consumed_at' => now()]),
        'inactive' => $invitation->property->update(['is_active' => false]),
        'unknown' => $code = '121bd641-2514-46ce-a6dd-b7b8a246a1ff',
    };
    $recipient = User::factory()->create();
    $this->actingAs($recipient)->post(route('property-invitation-codes.store'), ['code' => $code])->assertSessionHasErrors('code')->assertSessionMissing('_old_input.code');
    $this->get(route('property-invitations.show', $code))->assertNotFound()->assertInertia(fn ($page) => $page->component('property-invitations/Unavailable')->missing('invitation'));
    $this->post(route('property-invitations.store', $code), codeInvitationPayload($documents))->assertSessionHasErrors('invitation');
    expect(Membership::query()->whereBelongsTo($recipient)->count())->toBe(0);
})->with(['expired', 'revoked', 'consumed', 'inactive', 'unknown']);

test('valid invitations explain when required legal documents are unpublished', function () {
    [$code, $url] = issueCodeInvitation();
    $this->actingAs(User::factory()->create())->post(route('property-invitation-codes.store'), ['code' => $code])->assertSessionHasErrors(['code' => 'The association must publish its Terms of Service and Privacy Policy before you can accept this invitation. Please contact an Officer.']);
    $this->get($url)->assertStatus(503)->assertInertia(fn ($page) => $page->component('property-invitations/Unavailable')->where('message', 'The association must publish its Terms of Service and Privacy Policy before you can accept this invitation. Please contact an Officer.')->missing('invitation'));
    expect(PropertyInvitation::query()->sole()->consumed_at)->toBeNull();
});

test('using the link first also consumes its code for another recipient', function () {
    $documents = publishCodeInvitationLegalDocuments();
    [$code, $url] = issueCodeInvitation();
    $first = User::factory()->create();
    $second = User::factory()->create();
    $this->actingAs($first)->post(route('property-invitations.store', basename($url)), codeInvitationPayload($documents))->assertRedirect(route('dashboard'));
    $this->actingAs($second)->post(route('property-invitation-codes.store'), ['code' => $code])->assertSessionHasErrors('code');
    $this->post(route('property-invitations.store', $code), codeInvitationPayload($documents))->assertSessionHasErrors('invitation');
    expect(Membership::query()->where('property_invitation_id', PropertyInvitation::query()->sole()->id)->count())->toBe(1);
});

test('code redemption retains duplicate Membership and legal acceptance checks', function () {
    $documents = publishCodeInvitationLegalDocuments();
    [$code, , $property] = issueCodeInvitation();
    $recipient = User::factory()->create();
    $this->actingAs($recipient)->post(route('property-invitations.store', $code), [...codeInvitationPayload($documents), 'accept_privacy' => '0'])->assertSessionHasErrors('accept_privacy');
    Membership::factory()->for($recipient)->for($property)->create();
    $this->post(route('property-invitations.store', $code), codeInvitationPayload($documents))->assertSessionHasErrors(['invitation' => 'You already hold a live Membership on this Property.']);
    expect(PropertyInvitation::query()->sole()->consumed_at)->toBeNull();
});

test('code lookup validates malformed inputs on the server', function (mixed $code) {
    $this->actingAs(User::factory()->create())->postJson(route('property-invitation-codes.store'), ['code' => $code])->assertUnprocessable()->assertJsonValidationErrors('code');
})->with(['empty' => '', 'not UUID' => '1234', 'UUIDv7' => '121bd641-2514-76ce-a6dd-b7b8a246a1ff', 'array' => [['wrong']]]);

test('code lookup requires an authenticated account', function () {
    $code = '121bd641-2514-46ce-a6dd-b7b8a246a1ff';
    $this->post(route('property-invitation-codes.store'), ['code' => $code])->assertRedirect(route('login'));
});

test('code lookup is limited per account including direct confirmation requests', function () {
    $code = '121bd641-2514-46ce-a6dd-b7b8a246a1ff';
    $this->actingAs(User::factory()->create());
    foreach (range(1, 15) as $attempt) {
        $this->get(route('property-invitations.show', $code))->assertNotFound();
    }
    $this->post(route('property-invitation-codes.store'), ['code' => $code])->assertTooManyRequests();
});

test('code lookup is limited across accounts on the same network', function () {
    $code = '121bd641-2514-46ce-a6dd-b7b8a246a1ff';
    foreach (range(1, 30) as $attempt) {
        $this->actingAs(User::factory()->create())->post(route('property-invitation-codes.store'), ['code' => $code])->assertSessionHasErrors('code');
    }
    $this->actingAs(User::factory()->create())->post(route('property-invitation-codes.store'), ['code' => $code])->assertTooManyRequests();
});

test('code redemption shares the existing account limiter with link redemption', function () {
    $documents = publishCodeInvitationLegalDocuments();
    $this->actingAs(User::factory()->create());
    foreach (range(1, 5) as $attempt) {
        $this->post(route('property-invitations.store', '121bd641-2514-46ce-a6dd-b7b8a246a1ff'), codeInvitationPayload($documents))->assertSessionHasErrors('invitation');
    }
    $this->post(route('property-invitations.store', str_repeat('z', 64)), codeInvitationPayload($documents))->assertTooManyRequests();
});
