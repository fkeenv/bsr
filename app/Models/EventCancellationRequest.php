<?php

namespace App\Models;

use Database\Factories\EventCancellationRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $event_registration_id
 * @property string $submission_key
 * @property string $reason
 * @property string $status
 * @property int|null $resolved_by_user_id
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_reason
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventRegistration $registration
 */
#[Fillable(['event_registration_id', 'submission_key', 'reason', 'status', 'resolved_by_user_id', 'resolved_at', 'resolution_reason'])]
class EventCancellationRequest extends Model
{
    /** @use HasFactory<EventCancellationRequestFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'pending'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /** @return BelongsTo<EventRegistration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}
