<?php

namespace App\Actions\Payments;

use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Support\PropertyBalances;
use Illuminate\Support\Facades\DB;

class ApplyPrepaidToProperty
{
    public function __construct(private PropertyBalances $propertyBalances) {}

    public function handle(Property $property): void
    {
        DB::transaction(function () use ($property): void {
            $property = Property::query()->lockForUpdate()->findOrFail($property->id);

            if ((float) $property->prepaid_balance <= 0) {
                return;
            }

            $sources = Payment::query()
                ->where('property_id', $property->id)
                ->where('status', PaymentStatus::Confirmed)
                ->where('prepaid_amount', '>', 0)
                ->orderBy('confirmed_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($sources as $payment) {
                if ((float) $property->prepaid_balance <= 0) {
                    break;
                }

                $this->applyFromPayment($payment, $property);
            }
        });
    }

    private function applyFromPayment(Payment $payment, Property $property): void
    {
        $remaining = number_format((float) $payment->prepaid_amount, 2, '.', '');

        if ((float) $remaining <= 0) {
            return;
        }

        $openingRemaining = $this->propertyBalances->remainingOpeningBalance($property);

        if ((float) $openingRemaining > 0 && (float) $remaining > 0) {
            $applied = $this->min($openingRemaining, $remaining);
            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'property_id' => $property->id,
                'target' => PaymentAllocationTarget::OpeningBalance,
                'charge_id' => null,
                'amount' => $applied,
            ]);
            $remaining = $this->subtract($remaining, $applied);
            $property->freezeOpeningBalance();
            $this->reducePrepaid($property, $payment, $applied);
        }

        $charges = Charge::query()
            ->where('property_id', $property->id)
            ->with('lines')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('id')
            ->get();

        foreach ($charges as $charge) {
            if ((float) $remaining <= 0) {
                break;
            }

            $chargeRemaining = $this->propertyBalances->remainingChargeAmount($charge);

            if ((float) $chargeRemaining <= 0) {
                continue;
            }

            $applied = $this->min($chargeRemaining, $remaining);
            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'property_id' => $property->id,
                'target' => PaymentAllocationTarget::Charge,
                'charge_id' => $charge->id,
                'amount' => $applied,
            ]);
            $remaining = $this->subtract($remaining, $applied);
            $charge->freeze();
            $this->reducePrepaid($property, $payment, $applied);
        }
    }

    private function reducePrepaid(Property $property, Payment $payment, string $applied): void
    {
        $payment->forceFill([
            'prepaid_amount' => $this->subtract($payment->prepaid_amount, $applied),
        ])->save();

        $property->forceFill([
            'prepaid_balance' => $this->subtract($property->prepaid_balance, $applied),
        ])->save();
    }

    private function min(string $left, string $right): string
    {
        return number_format(min((float) $left, (float) $right), 2, '.', '');
    }

    private function subtract(string $left, string $right): string
    {
        return number_format(max(0, (float) $left - (float) $right), 2, '.', '');
    }
}
