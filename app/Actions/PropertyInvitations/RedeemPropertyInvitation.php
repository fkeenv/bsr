<?php

namespace App\Actions\PropertyInvitations;

use App\Actions\Memberships\SyncMemberPlatformRole;
use App\Actions\Onboarding\InitializeOnboarding;
use App\Enums\LegalDocumentType;
use App\Enums\OnboardingExperience;
use App\Enums\PropertyInvitationStatus;
use App\Models\LegalDocumentVersion;
use App\Models\Membership;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedeemPropertyInvitation
{
    public function __construct(
        private SyncMemberPlatformRole $syncMemberPlatformRole,
        private InitializeOnboarding $initializeOnboarding,
    ) {}

    public function handle(
        string $token,
        User $recipient,
        int $termsOfServiceVersionId,
        int $privacyPolicyVersionId,
    ): Membership {
        return DB::transaction(function () use (
            $token,
            $recipient,
            $termsOfServiceVersionId,
            $privacyPolicyVersionId,
        ): Membership {
            $lockedRecipient = User::query()->lockForUpdate()->findOrFail($recipient->id);
            $invitation = PropertyInvitation::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if ($invitation === null || $invitation->status() !== PropertyInvitationStatus::Unused) {
                throw ValidationException::withMessages([
                    'invitation' => 'This Property invitation is no longer available.',
                ]);
            }

            $property = Property::query()->lockForUpdate()->findOrFail($invitation->property_id);

            if (! $property->is_active) {
                throw ValidationException::withMessages([
                    'invitation' => 'This Property invitation is no longer available.',
                ]);
            }

            $terms = $this->currentLegalDocumentForUpdate(LegalDocumentType::TermsOfService);
            $privacy = $this->currentLegalDocumentForUpdate(LegalDocumentType::PrivacyPolicy);

            if (
                $terms === null
                || $privacy === null
                || $terms->id !== $termsOfServiceVersionId
                || $privacy->id !== $privacyPolicyVersionId
            ) {
                throw ValidationException::withMessages([
                    'legal_documents' => 'The legal documents changed. Review and accept the current versions.',
                ]);
            }

            $duplicateMembershipExists = Membership::query()
                ->live()
                ->whereBelongsTo($lockedRecipient)
                ->whereBelongsTo($property)
                ->exists();

            if ($duplicateMembershipExists) {
                throw ValidationException::withMessages([
                    'invitation' => 'You already hold a live Membership on this Property.',
                ]);
            }

            $isFirstLiveMembership = ! $lockedRecipient->hasLiveMembership();

            $membership = Membership::query()->create([
                'user_id' => $lockedRecipient->id,
                'property_id' => $property->id,
                'property_invitation_id' => $invitation->id,
                'terms_of_service_version_id' => $terms->id,
                'privacy_policy_version_id' => $privacy->id,
                'role' => $invitation->role,
                'started_at' => now(),
            ]);

            $invitation->forceFill([
                'consumed_at' => now(),
            ])->save();

            $this->syncMemberPlatformRole->handle($lockedRecipient);

            if ($isFirstLiveMembership) {
                $this->initializeOnboarding->handle($lockedRecipient, OnboardingExperience::Member);
            }

            return $membership;
        }, attempts: 3);
    }

    private function currentLegalDocumentForUpdate(LegalDocumentType $type): ?LegalDocumentVersion
    {
        return LegalDocumentVersion::query()
            ->where('type', $type)
            ->latest('published_at')
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }
}
