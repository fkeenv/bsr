<?php

namespace App\Actions\Payments;

use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\PaymentAllocation;
use App\Models\Property;

class ComputePropertyBalances
{
    /**
     * @return array{outstanding_balance: string, remaining_opening_balance: string, prepaid_balance: string}
     */
    public function handle(Property $property): array
    {
        $remainingOpening = $this->remainingOpeningBalance($property);
        $remainingCharges = '0.00';

        $charges = Charge::query()
            ->where('property_id', $property->id)
            ->with('lines')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('id')
            ->get();

        foreach ($charges as $charge) {
            $remainingCharges = $this->add($remainingCharges, $this->remainingChargeAmount($charge));
        }

        return [
            'outstanding_balance' => $this->add($remainingOpening, $remainingCharges),
            'remaining_opening_balance' => $remainingOpening,
            'prepaid_balance' => number_format((float) $property->prepaid_balance, 2, '.', ''),
        ];
    }

    public function remainingOpeningBalance(Property $property): string
    {
        $allocated = PaymentAllocation::query()
            ->where('property_id', $property->id)
            ->where('target', PaymentAllocationTarget::OpeningBalance)
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Confirmed))
            ->sum('amount');

        return $this->subtract($property->opening_balance, (string) $allocated);
    }

    public function remainingChargeAmount(Charge $charge): string
    {
        $total = $charge->lines->sum(fn ($line): float => (float) $line->amount);
        $allocated = PaymentAllocation::query()
            ->where('charge_id', $charge->id)
            ->where('target', PaymentAllocationTarget::Charge)
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Confirmed))
            ->sum('amount');

        return $this->subtract(number_format((float) $total, 2, '.', ''), (string) $allocated);
    }

    private function add(string $left, string $right): string
    {
        return number_format((float) $left + (float) $right, 2, '.', '');
    }

    private function subtract(string $left, string $right): string
    {
        return number_format(max(0, (float) $left - (float) $right), 2, '.', '');
    }
}
