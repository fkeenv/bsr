<?php

namespace App\Data;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Models\EventRegistration;
use Spatie\LaravelData\Data;

class EventFinancialData extends Data
{
    /** @param array<string, mixed>|null $payment
     * @param  list<array<string, mixed>>  $cancellations
     */
    public function __construct(public ?array $payment, public array $cancellations, public bool $full_refund_eligible = false) {}

    public static function fromModel(EventRegistration $registration): self
    {
        $registration->loadMissing(['payment.refunds', 'cancellationRequests', 'event']);
        $payment = $registration->payment;

        return new self($payment === null ? null : [
            'id' => $payment->id, 'amount_centavos' => $payment->amount_centavos, 'method' => $payment->method->value,
            'reference' => $payment->reference, 'status' => $payment->status, 'submitted_at' => $payment->created_at?->toIso8601String(),
            'receipt_url' => $payment->receipt_path !== null ? route('event-payments.receipt', $payment) : null,
            'reviewed_by_user_id' => $payment->reviewed_by_user_id, 'reviewed_at' => $payment->reviewed_at?->toIso8601String(),
            'review_reason' => $payment->review_reason,
            'refund_due_centavos' => $payment->status === 'confirmed' && $registration->cancelled_at !== null ? max(0, $payment->amount_centavos - (int) $payment->refunds->sum('amount_centavos')) : 0,
            'refunded_centavos' => (int) $payment->refunds->sum('amount_centavos'),
            'refunds' => $payment->refunds->map(fn ($refund): array => [
                'id' => $refund->id, 'amount_centavos' => $refund->amount_centavos, 'method' => $refund->method->value,
                'reference' => $refund->reference, 'reason' => $refund->reason, 'recorded_by_user_id' => $refund->recorded_by_user_id,
                'refunded_at' => $refund->refunded_at->toIso8601String(), 'recorded_at' => $refund->created_at?->toIso8601String(),
            ])->all(),
        ], array_values($registration->cancellationRequests->sortByDesc('id')->map(fn ($request): array => [
            'id' => $request->id, 'reason' => $request->reason, 'status' => $request->status,
            'requested_at' => $request->created_at?->toIso8601String(), 'resolved_at' => $request->resolved_at?->toIso8601String(),
            'resolved_by_user_id' => $request->resolved_by_user_id, 'resolution_reason' => $request->resolution_reason,
        ])->values()->all()), $registration->event->status === EventStatus::Rescheduled && $registration->status === EventRegistrationStatus::Confirmed && $payment?->status === 'confirmed');
    }
}
