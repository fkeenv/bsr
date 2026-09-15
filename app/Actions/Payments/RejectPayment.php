<?php

namespace App\Actions\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectPayment
{
    public function handle(Payment $payment, User $officer, string $reason): Payment
    {
        if (! $payment->status->isPending()) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending Payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $officer, $reason): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (! $payment->status->isPending()) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending Payments can be rejected.',
                ]);
            }

            $payment->fill([
                'status' => PaymentStatus::Rejected,
                'rejection_reason' => $reason,
                'rejected_by_user_id' => $officer->id,
                'rejected_at' => now(),
            ])->save();

            return $payment->refresh();
        });
    }
}
