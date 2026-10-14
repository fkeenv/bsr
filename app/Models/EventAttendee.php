<?php

namespace App\Models;

use Database\Factories\EventAttendeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_registration_id
 * @property string $name
 * @property string $ticket_token
 * @property Carbon|null $revoked_at
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by_user_id
 */
#[Fillable(['event_registration_id', 'name', 'ticket_token', 'revoked_at', 'checked_in_at', 'checked_in_by_user_id'])]
#[Hidden(['ticket_token'])]
class EventAttendee extends Model
{
    /** @use HasFactory<EventAttendeeFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['revoked_at' => 'datetime', 'checked_in_at' => 'datetime'];
    }

    /** @return BelongsTo<EventRegistration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}
