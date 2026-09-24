<?php

namespace App\Data;

use App\Models\PropertyInvitation;
use Spatie\LaravelData\Data;

class PropertyInvitationData extends Data
{
    public function __construct(
        public int $id,
        public int $property_id,
        public string $property_label,
        public string $role,
        public string $creator_name,
        public string $created_at,
        public string $expires_at,
        public string $status,
        public bool $can_revoke,
    ) {}

    public static function fromModel(PropertyInvitation $invitation): self
    {
        $invitation->loadMissing(['property', 'creator']);

        return new self(
            id: $invitation->id,
            property_id: $invitation->property_id,
            property_label: 'Block '.$invitation->property->block.' · Lot '.$invitation->property->lot,
            role: $invitation->role->value,
            creator_name: $invitation->creator->name,
            created_at: $invitation->created_at?->toIso8601String() ?? '',
            expires_at: $invitation->expires_at->toIso8601String(),
            status: $invitation->status()->value,
            can_revoke: $invitation->canBeRevoked(),
        );
    }
}
