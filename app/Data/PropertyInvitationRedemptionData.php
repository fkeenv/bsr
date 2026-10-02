<?php

namespace App\Data;

use App\Models\LegalDocumentVersion;
use App\Models\PropertyInvitation;
use Spatie\LaravelData\Data;

class PropertyInvitationRedemptionData extends Data
{
    public function __construct(
        public string $property_label,
        public string $role,
        public string $expires_at,
        public int $terms_of_service_version_id,
        public int $privacy_policy_version_id,
    ) {}

    public static function fromModels(
        PropertyInvitation $invitation,
        LegalDocumentVersion $terms,
        LegalDocumentVersion $privacy,
    ): self {
        return new self(
            property_label: 'Block '.$invitation->property->block.' · Lot '.$invitation->property->lot,
            role: $invitation->role->value,
            expires_at: $invitation->expires_at->toIso8601String(),
            terms_of_service_version_id: $terms->id,
            privacy_policy_version_id: $privacy->id,
        );
    }
}
