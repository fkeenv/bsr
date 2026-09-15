<?php

namespace App\Actions\Payments;

use App\Enums\PaymentAllocationTarget;
use App\Models\Charge;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Support\PropertyBalances;

class AllocatePaymentOnProperty
{
    public function __construct(private PropertyBalances $propertyBalances) {}

    public function handle(Payment $payment, Property $property): void
    {
        $remaining = number_format((float) $payment->amount, 2, '.', '');

        $openingRemaining = $this->propertyBalances->remainingOpeningBalance($property);

        if ((float) $openingRemaining > 0 && (float) $remaining > 0) {
            $applied = $this->min($openingRemaining, $remaining);
            $this->createAllocation($payment, $property, PaymentAllocationTarget::OpeningBalance, null, $applied);
            $remaining = $this->subtract($remaining, $applied);
        }

        $property->freezeOpeningBalance();

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
            $this->createAllocation($payment, $property, PaymentAllocationTarget::Charge, $charge, $applied);
            $remaining = $this->subtract($remaining, $applied);
            $charge->freeze();
        }

        if ((float) $remaining > 0) {
            $payment->forceFill([
                'prepaid_amount' => $remaining,
            ])->save();

            $property->forceFill([
                'prepaid_balance' => number_format(
                    (float) $property->prepaid_balance + (float) $remaining,
                    2,
                    '.',
                    '',
                ),
            ])->save();
        }
    }

    private function createAllocation(
        Payment $payment,
        Property $property,
        PaymentAllocationTarget $target,
        ?Charge $charge,
        string $amount,
    ): void {
        PaymentAllocation::query()->create([
            'payment_id' => $payment->id,
            'property_id' => $property->id,
            'target' => $target,
            'charge_id' => $charge?->id,
            'amount' => $amount,
        ]);
    }

    private function min(string $left, string $right): string
    {
        return number_format(min((float) $left, (float) $right), 2, '.', '');
    }

    private function subtract(string $left, string $right): string
    {
        return number_format((float) $left - (float) $right, 2, '.', '');
    }
}
