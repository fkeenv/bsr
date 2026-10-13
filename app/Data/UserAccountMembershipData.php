<?php

namespace App\Data;

use App\Models\Membership;
use Spatie\LaravelData\Data;

class UserAccountMembershipData extends Data
{
    public function __construct(public int $id, public string $property_label, public string $role, public string $started_at, public ?string $ended_at, public ?string $end_reason, public ?string $ended_by) {}

    public static function fromModel(Membership $membership): self
    {
        return new self($membership->id, 'Block '.$membership->property->block.' · Lot '.$membership->property->lot,
            $membership->role->value, $membership->started_at->toIso8601String(), $membership->ended_at?->toIso8601String(),
            $membership->end_reason, $membership->endedBy?->name);
    }
}
