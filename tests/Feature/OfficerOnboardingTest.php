<?php

use App\Enums\OnboardingExperience;
use App\Enums\OnboardingStep;
use App\Models\Membership;
use App\Models\OnboardingProgress;
use App\Models\User;

function currentOfficerOnboarding(User $user): ?OnboardingProgress
{
    return OnboardingProgress::query()
        ->whereBelongsTo($user)
        ->where('experience', OnboardingExperience::Officer)
        ->where('version', OnboardingExperience::Officer->currentVersion())
        ->first();
}

test('appointing an Officer initializes Officer onboarding', function () {
    $candidate = User::factory()->member()->create();

    $this->actingAs(User::factory()->administrator()->create())
        ->post(route('administrator.officers.store'), ['user_id' => $candidate->id])
        ->assertRedirect(route('administrator.officers.index'));

    $progress = currentOfficerOnboarding($candidate);

    expect($progress)->not->toBeNull()
        ->and($progress->tour_acknowledged_at)->toBeNull()
        ->and($progress->completed_steps)->toBe([]);
});

test('appointing an Administrator without prior Officer access initializes Officer onboarding', function () {
    $candidate = User::factory()->member()->create();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->post(route('super-admin.administrators.store'), ['user_id' => $candidate->id])
        ->assertRedirect(route('super-admin.administrators.index'));

    expect(currentOfficerOnboarding($candidate))->not->toBeNull();
});

test('appointing an existing Officer as Administrator does not start Officer onboarding again', function () {
    $candidate = User::factory()->officer()->create();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->post(route('super-admin.administrators.store'), ['user_id' => $candidate->id])
        ->assertRedirect(route('super-admin.administrators.index'));

    expect(currentOfficerOnboarding($candidate))->toBeNull();
});

test('Officer onboarding is independent of Member onboarding', function () {
    $candidate = User::factory()->member()->create();
    $memberProgress = OnboardingProgress::factory()->for($candidate)->create([
        'completed_steps' => [OnboardingStep::Announcements->value],
    ]);

    $this->actingAs(User::factory()->administrator()->create())
        ->post(route('administrator.officers.store'), ['user_id' => $candidate->id]);

    $officerProgress = currentOfficerOnboarding($candidate);

    $this->actingAs($candidate)
        ->post(route('onboarding.tour-acknowledgement.store', 'officer'))
        ->assertNoContent();

    $this->actingAs($candidate)
        ->post(route('onboarding.steps.store', 'officer'), ['step' => 'officer-properties'])
        ->assertRedirect(route('officer.properties.index'));

    expect($memberProgress->refresh()->tour_acknowledged_at)->toBeNull()
        ->and($memberProgress->completed_steps)->toBe([OnboardingStep::Announcements->value])
        ->and($officerProgress->refresh()->tour_acknowledged_at)->not->toBeNull()
        ->and($officerProgress->completed_steps)->toBe([OnboardingStep::OfficerProperties->value]);
});

test('Officer dashboard shares current Officer onboarding progress', function () {
    $officer = User::factory()->officer()->create();
    OnboardingProgress::factory()->for($officer)->officer()->create([
        'completed_steps' => [OnboardingStep::OfficerCharges->value],
    ]);

    $this->actingAs($officer)
        ->get(route('officer.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('officer/Dashboard')
            ->where('onboarding.experience', 'officer')
            ->where('onboarding.tour_acknowledged', false)
            ->where('onboarding.steps', [
                'officer-invitations',
                'officer-properties',
                'officer-memberships',
                'officer-payments',
                'officer-charges',
                'officer-announcements',
            ])
            ->where('onboarding.completed_steps', ['officer-charges']));
});

test('Officer dashboard shares no onboarding without current Officer progress', function () {
    $officer = User::factory()->officer()->create();
    OnboardingProgress::factory()->for($officer)->create();

    $this->actingAs($officer)
        ->get(route('officer.dashboard'))
        ->assertInertia(fn ($page) => $page->where('onboarding', null));
});

test('Officer checklist links complete on first visit without changing anything else', function (string $step, string $destination) {
    $officer = User::factory()->officer()->create();
    $progress = OnboardingProgress::factory()->for($officer)->officer()->create();

    $this->actingAs($officer)
        ->post(route('onboarding.steps.store', 'officer'), ['step' => $step])
        ->assertRedirect(route($destination));

    expect($progress->refresh()->completed_steps)->toBe([$step]);
})->with([
    'Invitations' => ['officer-invitations', 'officer.property-invitations.index'],
    'Properties' => ['officer-properties', 'officer.properties.index'],
    'Memberships' => ['officer-memberships', 'officer.memberships.index'],
    'Payments and Unpaid' => ['officer-payments', 'officer.payments.index'],
    'Charges' => ['officer-charges', 'officer.charges.index'],
    'Announcements' => ['officer-announcements', 'officer.announcements.index'],
]);

test('Officer and Member steps cannot be recorded against the other experience', function (string $experience, string $step) {
    $officer = User::factory()->officer()->create();
    OnboardingProgress::factory()->for($officer)->create();
    OnboardingProgress::factory()->for($officer)->officer()->create();

    $this->actingAs($officer)
        ->post(route('onboarding.steps.store', $experience), ['step' => $step])
        ->assertSessionHasErrors('step');

    expect(OnboardingProgress::query()->get()->pluck('completed_steps')->flatten()->all())->toBe([]);
})->with([
    'Officer step on Member onboarding' => ['member', 'officer-properties'],
    'Member step on Officer onboarding' => ['officer', 'announcements'],
]);

test('Members without Officer access cannot record Officer onboarding', function () {
    $member = User::factory()->member()->create();
    $progress = OnboardingProgress::factory()->for($member)->officer()->create();

    $this->actingAs($member)
        ->post(route('onboarding.tour-acknowledgement.store', 'officer'))
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('onboarding.steps.store', 'officer'), ['step' => 'officer-properties'])
        ->assertForbidden();

    expect($progress->refresh()->tour_acknowledged_at)->toBeNull()
        ->and($progress->completed_steps)->toBe([]);
});

test('losing and regaining Officer access keeps current Officer progress', function () {
    $officer = User::factory()->member()->create();
    $administrator = User::factory()->administrator()->create();

    $this->actingAs($administrator)
        ->post(route('administrator.officers.store'), ['user_id' => $officer->id]);

    $progress = currentOfficerOnboarding($officer);
    $progress->forceFill([
        'tour_acknowledged_at' => now(),
        'completed_steps' => [OnboardingStep::OfficerInvitations->value],
    ])->save();

    $membership = $officer->memberships()->live()->sole();
    $this->actingAs($officer)->post(route('memberships.end', $membership));

    expect($officer->refresh()->canAccessOfficerSurfaces())->toBeFalse();

    Membership::factory()->owner()->create(['user_id' => $officer->id]);
    $this->actingAs($administrator)
        ->post(route('administrator.officers.store'), ['user_id' => $officer->id])
        ->assertRedirect(route('administrator.officers.index'));

    $progress->refresh();

    expect(OnboardingProgress::query()->where('experience', OnboardingExperience::Officer)->count())->toBe(1)
        ->and($progress->tour_acknowledged_at)->not->toBeNull()
        ->and($progress->completed_steps)->toBe([OnboardingStep::OfficerInvitations->value]);
});
