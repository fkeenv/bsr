<?php

namespace App\Models;

use App\Enums\EventPaymentMethod;
use Database\Factories\EventPaymentRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $event_registration_id
 * @property int $amount_centavos
 * @property EventPaymentMethod $method
 * @property string $reference
 * @property string|null $receipt_path
 * @property string $status
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $review_reason
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventRegistration $registration
 * @property-read Collection<int, EventRefundRecord> $refunds
 */
#[Fillable(['event_registration_id', 'amount_centavos', 'method', 'reference', 'receipt_path', 'status', 'reviewed_by_user_id', 'reviewed_at', 'review_reason'])]
class EventPaymentRecord extends Model
{
    /** @use HasFactory<EventPaymentRecordFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'submitted'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount_centavos' => 'integer', 'method' => EventPaymentMethod::class, 'reviewed_at' => 'datetime'];
    }

    /** @return BelongsTo<EventRegistration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    /** @return HasMany<EventRefundRecord, $this> */
    public function refunds(): HasMany
    {
        return $this->hasMany(EventRefundRecord::class, 'event_payment_record_id');
    }
}
