<?php

namespace App\Data;

use App\Models\Membership;
use Spatie\LaravelData\Data;

class MembershipData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public int $property_id,
        public string $role,
        public string $started_at,
        public ?string $ended_at,
        public ?string $property_label = null,
        public ?string $user_name = null,
    ) {}

    public static function fromModel(Membership $membership): self
    {
        $membership->loadMissing(['property', 'user']);

        return new self(
            id: $membership->id,
            user_id: $membership->user_id,
            property_id: $membership->property_id,
            role: $membership->role->value,
            started_at: $membership->started_at->toIso8601String(),
            ended_at: $membership->ended_at?->toIso8601String(),
            property_label: $membership->property !== null
                ? 'Block '.$membership->property->block.' · Lot '.$membership->property->lot
                : null,
            user_name: $membership->user?->name,
        );
    }
}
