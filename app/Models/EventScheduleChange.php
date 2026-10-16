<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int $changed_by_user_id
 * @property Carbon $old_starts_at
 * @property Carbon $old_ends_at
 * @property Carbon $old_registration_deadline
 * @property Carbon $new_starts_at
 * @property Carbon $new_ends_at
 * @property Carbon $new_registration_deadline
 * @property Carbon $created_at
 */
#[Fillable(['event_id', 'changed_by_user_id', 'old_starts_at', 'old_ends_at', 'old_registration_deadline', 'new_starts_at', 'new_ends_at', 'new_registration_deadline'])]
class EventScheduleChange extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return array_fill_keys(['old_starts_at', 'old_ends_at', 'old_registration_deadline', 'new_starts_at', 'new_ends_at', 'new_registration_deadline'], 'datetime');
    }
}
