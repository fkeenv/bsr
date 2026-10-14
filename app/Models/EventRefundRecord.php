<?php

namespace App\Models;

use App\Enums\EventPaymentMethod;
use Database\Factories\EventRefundRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $event_payment_record_id
 * @property string $submission_key
 * @property int $amount_centavos
 * @property EventPaymentMethod $method
 * @property string $reference
 * @property string $reason
 * @property int $recorded_by_user_id
 * @property Carbon $refunded_at
 * @property int $id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EventPaymentRecord $payment
 */
#[Fillable(['event_payment_record_id', 'submission_key', 'amount_centavos', 'method', 'reference', 'reason', 'recorded_by_user_id', 'refunded_at'])]
class EventRefundRecord extends Model
{
    /** @use HasFactory<EventRefundRecordFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['amount_centavos' => 'integer', 'method' => EventPaymentMethod::class, 'refunded_at' => 'datetime'];
    }

    /** @return BelongsTo<EventPaymentRecord, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(EventPaymentRecord::class, 'event_payment_record_id');
    }
}
