<?php

namespace App\Data;

use App\Models\Payment;
use Spatie\LaravelData\Data;

class PaymentData extends Data
{
    public function __construct(
        public int $id,
        public int $property_id,
        public string $amount,
        public string $method,
        public ?string $reference,
        public string $status,
        public string $property_label,
        public ?string $declared_by_name,
        public ?string $rejection_reason,
        public ?string $void_reason,
        public ?string $confirmed_at,
        public ?string $created_at,
        public ?string $recorded_on,
        public bool $has_screenshot,
    ) {}

    public static function fromModel(Payment $payment): self
    {
        $payment->loadMissing(['property', 'declaredBy']);

        $createdAt = $payment->created_at;

        return new self(
            id: $payment->id,
            property_id: $payment->property_id,
            amount: $payment->amount,
            method: $payment->method->value,
            reference: $payment->reference,
            status: $payment->status->value,
            property_label: 'Block '.$payment->property->block.' · Lot '.$payment->property->lot,
            declared_by_name: $payment->declaredBy?->name,
            rejection_reason: $payment->rejection_reason,
            void_reason: $payment->void_reason,
            confirmed_at: $payment->confirmed_at?->toIso8601String(),
            created_at: $createdAt?->toIso8601String(),
            recorded_on: $createdAt?->timezone('Asia/Manila')->format('Y-m-d'),
            has_screenshot: filled($payment->screenshot_path),
        );
    }
}
