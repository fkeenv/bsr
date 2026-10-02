<?php

use App\Enums\OnboardingExperience;
use App\Enums\OnboardingStep;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\OnboardingProgress;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;

/**
 * @return array{accept_terms: string, accept_privacy: string, terms_of_service_version_id: int, privacy_policy_version_id: int}
 */
function memberOnboardingRedemptionPayload(): array
{
    $terms = LegalDocumentVersion::factory()->termsOfService()->create(['published_at' => now()->subMinute()]);
    $privacy = LegalDocumentVersion::factory()->privacyPolicy()->create(['published_at' => now()->subMinute()]);

    return [
        'accept_terms' => '1',
        'accept_privacy' => '1',
        'terms_of_service_version_id' => $terms->id,
        'privacy_policy_version_id' => $privacy->id,
    ];
}

function memberOnboardingInvitationToken(Property $property, string $character): string
{
    $token = str_repeat($character, 64);
    PropertyInvitation::factory()->create([
        'property_id' => $property->id,
        'token_hash' => hash('sha256', $token),
    ]);

    return $token;
}

test('redeeming the first live Membership initializes current Member onboarding', function () {
    $user = User::factory()->create();
    $token = memberOnboardingInvitationToken(Property::factory()->create(), 'a');

    $this->actingAs($user)
        ->post(route('property-invitations.store', $token), memberOnboardingRedemptionPayload())
        ->assertRedirect(route('dashboard'));

    $progress = OnboardingProgress::query()->sole();

    expect($progress->user_id)->toBe($user->id)
        ->and($progress->experience)->toBe(OnboardingExperience::Member)
        ->and($progress->version)->toBe(OnboardingExperience::Member->currentVersion())
        ->and($progress->tour_acknowledged_at)->toBeNull()
        ->and($progress->completed_steps)->toBe([]);
});

test('joining another Property does not restart Member onboarding', function () {
    $user = User::factory()->member()->create();
    $progress = OnboardingProgress::factory()->for($user)->tourAcknowledged()->create([
        'completed_steps' => [OnboardingStep::Announcements->value],
    ]);
    $token = memberOnboardingInvitationToken(Property::factory()->create(), 'b');

    $this->actingAs($user)
        ->post(route('property-invitations.store', $token), memberOnboardingRedemptionPayload())
        ->assertRedirect(route('dashboard'));

    $progress->refresh();

    expect(OnboardingProgress::query()->count())->toBe(1)
        ->and($progress->tour_acknowledged_at)->not->toBeNull()
        ->and($progress->completed_steps)->toBe([OnboardingStep::Announcements->value]);
});

test('joining another Property does not start onboarding for an existing Member', function () {
    $user = User::factory()->member()->create();
    $token = memberOnboardingInvitationToken(Property::factory()->create(), 'c');

    $this->actingAs($user)
        ->post(route('property-invitations.store', $token), memberOnboardingRedemptionPayload())
        ->assertRedirect(route('dashboard'));

    expect(OnboardingProgress::query()->count())->toBe(0);
});

test('Member dashboard shares current onboarding progress', function () {
    $user = User::factory()->member()->create();
    OnboardingProgress::factory()->for($user)->create([
        'completed_steps' => [OnboardingStep::StatementOfAccount->value],
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('onboarding.experience', 'member')
            ->where('onboarding.version', OnboardingExperience::Member->currentVersion())
            ->where('onboarding.tour_acknowledged', false)
            ->where('onboarding.steps', ['announcements', 'statement-of-account', 'property-profile'])
            ->where('onboarding.completed_steps', ['statement-of-account']));
});

test('dashboard shares no onboarding when the User Account is not eligible or has no current progress', function (Closure $makeUser) {
    $this->actingAs($makeUser())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('onboarding', null));
})->with([
    'plain User Account' => fn () => User::factory()->create(),
    'Member without progress' => fn () => User::factory()->member()->create(),
    'progress without a live Membership' => fn () => OnboardingProgress::factory()->create()->user,
    'progress for an older version' => fn () => OnboardingProgress::factory()
        ->for(User::factory()->member())
        ->create(['version' => OnboardingExperience::Member->currentVersion() - 1])
        ->user,
]);

test('acknowledging the Member tour records it once', function () {
    $this->travelTo('2026-10-02 09:00:00');
    $user = User::factory()->member()->create();
    $progress = OnboardingProgress::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('onboarding.tour-acknowledgement.store', 'member'))
        ->assertNoContent();

    $this->travelTo('2026-10-03 09:00:00');

    $this->actingAs($user)
        ->post(route('onboarding.tour-acknowledgement.store', 'member'))
        ->assertNoContent();

    expect($progress->refresh()->tour_acknowledged_at?->toIso8601String())->toBe('2026-10-02T09:00:00+00:00');
});

test('tour acknowledgement rejects ineligible or unknown onboarding experiences', function (Closure $makeUser, string $experience, int $status) {
    $this->actingAs($makeUser())
        ->post('/onboarding/'.$experience.'/tour-acknowledgement')
        ->assertStatus($status);

    expect(OnboardingProgress::query()->whereNotNull('tour_acknowledged_at')->count())->toBe(0);
})->with([
    'progress without a live Membership' => [fn () => OnboardingProgress::factory()->create()->user, 'member', 403],
    'Member without progress' => [fn () => User::factory()->member()->create(), 'member', 404],
    'unknown experience' => [fn () => OnboardingProgress::factory()->for(User::factory()->member())->create()->user, 'officer', 404],
]);

test('guests cannot record onboarding progress', function () {
    $this->post(route('onboarding.tour-acknowledgement.store', 'member'))
        ->assertRedirect(route('login'));

    $this->post(route('onboarding.steps.store', 'member'), ['step' => 'announcements'])
        ->assertRedirect(route('login'));
});

test('following a checklist link completes the step and opens its page', function (string $step, string $destination) {
    $user = User::factory()->member()->create();
    $progress = OnboardingProgress::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('onboarding.steps.store', 'member'), ['step' => $step])
        ->assertRedirect(route($destination));

    $this->actingAs($user)
        ->post(route('onboarding.steps.store', 'member'), ['step' => $step])
        ->assertRedirect(route($destination));

    expect($progress->refresh()->completed_steps)->toBe([$step]);
})->with([
    'Announcements' => ['announcements', 'announcements.index'],
    'Statement of Account' => ['statement-of-account', 'statement-of-account.index'],
]);

test('checklist links reject unknown and save-only steps', function (mixed $step) {
    $user = User::factory()->member()->create();
    $progress = OnboardingProgress::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('onboarding.steps.store', 'member'), ['step' => $step])
        ->assertSessionHasErrors('step');

    expect($progress->refresh()->completed_steps)->toBe([]);
})->with([
    'unknown step' => ['payments'],
    'missing step' => [null],
    'Property profile' => ['property-profile'],
]);

test('checklist links reject ineligible User Accounts', function () {
    $progress = OnboardingProgress::factory()->create();

    $this->actingAs($progress->user)
        ->post(route('onboarding.steps.store', 'member'), ['step' => 'announcements'])
        ->assertForbidden();

    expect($progress->refresh()->completed_steps)->toBe([]);
});

test('saving a Property profile completes the Property profile step', function () {
    $membership = Membership::factory()->create();
    $progress = OnboardingProgress::factory()->for($membership->user)->create();

    $this->actingAs($membership->user)
        ->put(route('property-profile.update', $membership->property), [
            'household_members' => [['name' => 'Ana Cruz']],
            'emergency_contacts' => [],
            'vehicles' => [],
        ])
        ->assertRedirect();

    expect($progress->refresh()->completed_steps)->toBe([OnboardingStep::PropertyProfile->value]);
});

test('a failed Property profile save leaves the Property profile step open', function () {
    $membership = Membership::factory()->create();
    $progress = OnboardingProgress::factory()->for($membership->user)->create();

    $this->actingAs($membership->user)
        ->put(route('property-profile.update', $membership->property), [
            'household_members' => [['name' => '']],
            'emergency_contacts' => [],
            'vehicles' => [],
        ])
        ->assertSessionHasErrors();

    expect($progress->refresh()->completed_steps)->toBe([]);
});
