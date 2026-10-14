<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property EventRegistrationStatus $status
 * @property int $price_centavos
 * @property int $total_centavos
 * @property string|null $payment_instructions
 * @property Carbon|null $expires_at
 * @property Carbon|null $expired_at
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property int $attendee_count
 * @property string|null $submission_key
 * @property string|null $submission_hash
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Event $event
 * @property-read User $user
 * @property-read EventPaymentRecord|null $payment
 * @property-read Collection<int, EventCancellationRequest> $cancellationRequests
 * @property-read Collection<int, EventAttendee> $attendees
 */
#[Fillable(['event_id', 'user_id', 'attendee_count', 'submission_key', 'submission_hash', 'confirmed_at', 'cancelled_at', 'status', 'price_centavos', 'total_centavos', 'payment_instructions', 'expires_at', 'expired_at'])]
#[Hidden(['submission_key', 'submission_hash'])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'confirmed'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => EventRegistrationStatus::class, 'price_centavos' => 'integer', 'total_centavos' => 'integer', 'expires_at' => 'datetime', 'expired_at' => 'datetime', 'attendee_count' => 'integer', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<EventPaymentRecord, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(EventPaymentRecord::class);
    }

    /** @return HasMany<EventCancellationRequest, $this> */
    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(EventCancellationRequest::class);
    }

    /** @return HasMany<EventAttendee, $this> */
    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }
}
