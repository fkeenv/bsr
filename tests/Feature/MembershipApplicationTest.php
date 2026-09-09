<?php

use App\Enums\LegalDocumentType;
use App\Enums\MembershipApplicationStatus;
use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Models\Property;
use App\Models\User;

function publishLegalDocuments(): array
{
    $terms = LegalDocumentVersion::factory()->termsOfService()->create([
        'body' => '<p>Current terms</p>',
    ]);
    $privacy = LegalDocumentVersion::factory()->privacyPolicy()->create([
        'body' => '<p>Current privacy</p>',
    ]);

    return [$terms, $privacy];
}

test('user can submit a Membership Application for a roster Property', function () {
    [$terms, $privacy] = publishLegalDocuments();
    $user = User::factory()->create();
    $property = Property::factory()->create(['block' => '1', 'lot' => '2']);

    $this->actingAs($user)
        ->post(route('membership-application.store'), [
            'property_id' => $property->id,
            'note' => 'We just moved in.',
            'accept_terms' => '1',
            'accept_privacy' => '1',
            'household_members' => [
                ['name' => 'Ana Reyes'],
            ],
            'emergency_contacts' => [
                [
                    'name' => 'Ben Reyes',
                    'contact_number' => '+639171234567',
                    'relationship' => 'spouse',
                ],
            ],
            'vehicles' => [
                [
                    'year' => 2019,
                    'make' => 'Toyota',
                    'model' => 'Vios',
                    'plate' => 'ABC1234',
                    'sticker_number' => 'STK-001',
                ],
            ],
        ])
        ->assertRedirect(route('membership-application.create'));

    $application = MembershipApplication::query()->where('user_id', $user->id)->first();

    expect($application)->not->toBeNull()
        ->and($application->property_id)->toBe($property->id)
        ->and($application->status)->toBe(MembershipApplicationStatus::Pending)
        ->and($application->note)->toBe('We just moved in.')
        ->and($application->terms_of_service_version_id)->toBe($terms->id)
        ->and($application->privacy_policy_version_id)->toBe($privacy->id)
        ->and($application->householdMembers)->toHaveCount(1)
        ->and($application->householdMembers->first()->name)->toBe('Ana Reyes')
        ->and($application->emergencyContacts)->toHaveCount(1)
        ->and($application->vehicles)->toHaveCount(1);
});

test('Membership Application requires acceptance of current Terms and Privacy versions', function () {
    publishLegalDocuments();
    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->from(route('membership-application.create'))
        ->post(route('membership-application.store'), [
            'property_id' => $property->id,
            'accept_terms' => '0',
            'accept_privacy' => '0',
        ])
        ->assertRedirect(route('membership-application.create'))
        ->assertSessionHasErrors(['accept_terms', 'accept_privacy']);
});

test('rejected Membership Application stays editable and resubmittable', function () {
    [$terms] = publishLegalDocuments();
    $user = User::factory()->create();
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();

    $application = MembershipApplication::factory()->rejected()->create([
        'user_id' => $user->id,
        'property_id' => $property->id,
        'note' => 'Old note',
        'terms_of_service_version_id' => $terms->id,
        'privacy_policy_version_id' => LegalDocumentVersion::current(LegalDocumentType::PrivacyPolicy)->id,
        'reviewed_by_user_id' => $officer->id,
    ]);

    $this->actingAs($user)
        ->post(route('membership-application.store'), [
            'property_id' => $property->id,
            'note' => 'Updated note after rejection',
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ])
        ->assertRedirect(route('membership-application.create'));

    $application->refresh();

    expect(MembershipApplication::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and($application->status)->toBe(MembershipApplicationStatus::Pending)
        ->and($application->note)->toBe('Updated note after rejection')
        ->and($application->reviewed_at)->toBeNull();
});

test('Officer can approve a Membership Application with owner or resident role', function () {
    publishLegalDocuments();
    $applicant = User::factory()->create();
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();

    $application = MembershipApplication::factory()->pending()->withCurrentLegalDocuments()->create([
        'user_id' => $applicant->id,
        'property_id' => $property->id,
    ]);

    $this->actingAs($officer)
        ->post(route('officer.membership-applications.approve', $application), [
            'role' => MembershipRole::Owner->value,
        ])
        ->assertRedirect(route('officer.membership-applications.index'));

    $application->refresh();
    $applicant->refresh();

    expect($application->status)->toBe(MembershipApplicationStatus::Approved)
        ->and(Membership::query()->live()->where('user_id', $applicant->id)->where('property_id', $property->id)->count())->toBe(1)
        ->and($applicant->hasRole(PlatformRole::Member))->toBeTrue()
        ->and($applicant->hasLiveMembership())->toBeTrue();

    $membership = Membership::query()->where('user_id', $applicant->id)->first();
    expect($membership->role)->toBe(MembershipRole::Owner);
});

test('Officer can reject a Membership Application without creating a Membership', function () {
    publishLegalDocuments();
    $applicant = User::factory()->create();
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();

    $application = MembershipApplication::factory()->pending()->withCurrentLegalDocuments()->create([
        'user_id' => $applicant->id,
        'property_id' => $property->id,
    ]);

    $this->actingAs($officer)
        ->post(route('officer.membership-applications.reject', $application))
        ->assertRedirect(route('officer.membership-applications.index'));

    $application->refresh();

    expect($application->status)->toBe(MembershipApplicationStatus::Rejected)
        ->and(Membership::query()->count())->toBe(1); // officer factory membership only
});

test('duplicate live Membership for the same person and Property is blocked', function () {
    publishLegalDocuments();
    $applicant = User::factory()->create();
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();

    Membership::factory()->create([
        'user_id' => $applicant->id,
        'property_id' => $property->id,
        'role' => MembershipRole::Resident,
    ]);

    $application = MembershipApplication::factory()->pending()->withCurrentLegalDocuments()->create([
        'user_id' => $applicant->id,
        'property_id' => $property->id,
    ]);

    $this->actingAs($officer)
        ->from(route('officer.membership-applications.show', $application))
        ->post(route('officer.membership-applications.approve', $application), [
            'role' => MembershipRole::Owner->value,
        ])
        ->assertRedirect(route('officer.membership-applications.show', $application))
        ->assertSessionHasErrors('application');

    expect(Membership::query()->live()->where('user_id', $applicant->id)->where('property_id', $property->id)->count())->toBe(1);
});

test('user without a live Membership is routed to Membership Application onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('membership-application.create'));
});

test('user with a live Membership can open the dashboard', function () {
    $user = User::factory()->member()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('application form stores the published Terms and Privacy version ids on submit', function () {
    [$terms, $privacy] = publishLegalDocuments();
    LegalDocumentVersion::factory()->termsOfService()->create([
        'body' => 'Older terms',
        'published_at' => now()->subDay(),
    ]);

    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->post(route('membership-application.store'), [
            'property_id' => $property->id,
            'accept_terms' => '1',
            'accept_privacy' => '1',
        ])
        ->assertRedirect();

    $application = MembershipApplication::query()->first();

    expect($application->terms_of_service_version_id)->toBe($terms->id)
        ->and($application->privacy_policy_version_id)->toBe($privacy->id);
});
