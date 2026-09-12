<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmPayment
{
    public function __construct(private AllocatePaymentOnProperty $allocatePaymentOnProperty) {}

    public function handle(Payment $payment, User $officer): Payment
    {
        if (! $payment->status->isPending()) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending Payments can be confirmed.',
            ]);
        }

        return DB::transaction(function () use ($payment, $officer): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $property = $payment->property()->lockForUpdate()->firstOrFail();

            if (! $payment->status->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending Payments can be confirmed.',
                ]);
            }

            $payment->fill([
                'status' => PaymentStatus::Confirmed,
                'confirmed_by_user_id' => $officer->id,
                'confirmed_at' => now(),
            ])->save();

            $this->allocatePaymentOnProperty->handle($payment, $property);

            return $payment->refresh();
        });
    }
}
