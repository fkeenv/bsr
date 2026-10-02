<?php

namespace App\Actions\PropertyInvitations;

use App\Data\PropertyInvitationRedemptionData;
use App\Enums\LegalDocumentType;
use App\Models\LegalDocumentVersion;
use App\Models\Property;
use App\Models\PropertyInvitation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowPropertyInvitationRedemptionPage
{
    public function handle(string $token): PropertyInvitationRedemptionData
    {
        $invitation = PropertyInvitation::query()
            ->with('property')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->whereIn('property_id', Property::query()->active()->select('id'))
            ->first();

        $terms = LegalDocumentVersion::current(LegalDocumentType::TermsOfService);
        $privacy = LegalDocumentVersion::current(LegalDocumentType::PrivacyPolicy);

        if ($invitation === null || $terms === null || $privacy === null) {
            throw new NotFoundHttpException;
        }

        return PropertyInvitationRedemptionData::fromModels($invitation, $terms, $privacy);
    }
}
