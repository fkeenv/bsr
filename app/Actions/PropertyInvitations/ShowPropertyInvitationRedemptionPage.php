<?php

namespace App\Actions\PropertyInvitations;

use App\Data\PropertyInvitationRedemptionData;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Models\Property;
use App\Models\PropertyInvitation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ShowPropertyInvitationRedemptionPage
{
    public function handle(string $token): PropertyInvitationRedemptionData
    {
        $invitation = PropertyInvitation::query()
            ->with(['property', 'creator'])
            ->forCredential($token)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->whereIn('property_id', Property::query()->active()->select('id'))
            ->first();

        if ($invitation === null) {
            throw new NotFoundHttpException('This Property invitation is invalid or no longer available. Ask the person who invited you for a new invitation.');
        }

        $terms = LegalDocumentVersion::current(LegalDocumentType::TermsOfService);
        $privacy = LegalDocumentVersion::current(LegalDocumentType::PrivacyPolicy);

        if ($terms === null || $privacy === null) {
            throw new ServiceUnavailableHttpException(null, 'The association must publish its Terms of Service and Privacy Policy before you can accept this invitation. Please contact an Officer.');
        }

        return PropertyInvitationRedemptionData::fromModels($invitation, $terms, $privacy);
    }
}
