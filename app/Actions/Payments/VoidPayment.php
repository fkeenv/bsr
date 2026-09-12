<?php

namespace App\Actions\Payments;

use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidPayment
{
    public function handle(Payment $payment, User $officer, string $reason): Payment
    {
        if (! $payment->status->isConfirmed()) {
            throw ValidationException::withMessages([
                'payment' => 'Only confirmed Payments can be voided.',
            ]);
        }

        return DB::transaction(function () use ($payment, $officer, $reason): Payment {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $property = Property::query()->lockForUpdate()->findOrFail($payment->property_id);

            if (! $payment->status->isConfirmed()) {
                throw ValidationException::withMessages([
                    'payment' => 'Only confirmed Payments can be voided.',
                ]);
            }

            $chargeIds = $payment->allocations()
                ->where('target', PaymentAllocationTarget::Charge)
                ->whereNotNull('charge_id')
                ->pluck('charge_id')
                ->unique()
                ->all();

            if ((float) $payment->prepaid_amount > 0) {
                $property->forceFill([
                    'prepaid_balance' => number_format(
                        max(0, (float) $property->prepaid_balance - (float) $payment->prepaid_amount),
                        2,
                        '.',
                        '',
                    ),
                ])->save();
            }

            $payment->allocations()->delete();

            $payment->fill([
                'status' => PaymentStatus::Voided,
                'void_reason' => $reason,
                'voided_by_user_id' => $officer->id,
                'voided_at' => now(),
                'prepaid_amount' => '0.00',
            ])->save();

            foreach ($chargeIds as $chargeId) {
                $stillAllocated = PaymentAllocation::query()
                    ->where('charge_id', $chargeId)
                    ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Confirmed))
                    ->exists();

                if (! $stillAllocated) {
                    $charge = Charge::query()->whereKey($chargeId)->first();

                    if ($charge instanceof Charge) {
                        $charge->unfreeze();
                    }
                }
            }

            $stillHasConfirmedPayment = Payment::query()
                ->where('property_id', $property->id)
                ->where('status', PaymentStatus::Confirmed)
                ->exists();

            if (! $stillHasConfirmedPayment) {
                $property->unfreezeOpeningBalance();
            }

            return $payment->refresh();
        });
    }
}
