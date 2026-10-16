<?php

namespace App\Data;

use App\Models\EventScheduleChange;
use Spatie\LaravelData\Data;

class EventScheduleChangeData extends Data
{
    public function __construct(
        public int $id,
        public int $changed_by_user_id,
        public string $changed_at,
        public string $old_starts_at,
        public string $old_ends_at,
        public string $old_registration_deadline,
        public string $new_starts_at,
        public string $new_ends_at,
        public string $new_registration_deadline,
    ) {}

    public static function fromModel(EventScheduleChange $change): self
    {
        return new self($change->id, $change->changed_by_user_id, $change->created_at->toIso8601String(),
            $change->old_starts_at->toIso8601String(), $change->old_ends_at->toIso8601String(), $change->old_registration_deadline->toIso8601String(),
            $change->new_starts_at->toIso8601String(), $change->new_ends_at->toIso8601String(), $change->new_registration_deadline->toIso8601String());
    }
}
