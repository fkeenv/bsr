<?php

namespace App\Actions\Statements;

use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Enums\PeriodStatus;
use App\Models\Charge;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Support\ChargeFinancialView;
use App\Support\PropertyFinancialView;
use Illuminate\Support\Collection;

class LoadPropertyFinancials
{
    /**
     * @param  Collection<int, Property>  $properties
     * @return Collection<int, PropertyFinancialView> Views keyed by Property id, owned by the current read only.
     */
    public function handle(Collection $properties): Collection
    {
        if ($properties->isEmpty()) {
            return collect();
        }

        $charges = Charge::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->with('lines')
            ->orderBy('year')->orderBy('month')->orderBy('id')
            ->get()->groupBy('property_id');

        $allocations = PaymentAllocation::query()
            ->whereIn('property_id', $properties->pluck('id'))
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Confirmed))
            ->select(['property_id', 'target', 'charge_id'])
            ->selectRaw('SUM(amount) as allocated_amount')
            ->groupBy('property_id', 'target', 'charge_id')
            ->toBase()->get()->groupBy('property_id');

        return $properties->mapWithKeys(function (Property $property) use ($charges, $allocations): array {
            $openingAllocated = '0.00';
            $chargeAllocated = [];
            foreach ($allocations->get($property->id, collect()) as $allocation) {
                if ($allocation->target === PaymentAllocationTarget::OpeningBalance->value) {
                    $openingAllocated = (string) $allocation->allocated_amount;
                } elseif ($allocation->target === PaymentAllocationTarget::Charge->value) {
                    $chargeAllocated[$allocation->charge_id] = (string) $allocation->allocated_amount;
                }
            }

            $opening = $this->remaining($property->opening_balance, $openingAllocated);
            $outstanding = $opening;
            $periods = [];
            foreach ($charges->get($property->id, collect()) as $charge) {
                $total = number_format((float) $charge->lines->sum(fn ($line): float => (float) $line->amount), 2, '.', '');
                $remaining = $this->remaining($total, $chargeAllocated[$charge->id] ?? '0.00');
                $status = (float) $remaining <= 0 ? PeriodStatus::Paid
                    : ($remaining === $total ? PeriodStatus::Unpaid : PeriodStatus::Partial);
                $periods[] = new ChargeFinancialView($charge, $total, $remaining, $status);
                $outstanding = number_format((float) $outstanding + (float) $remaining, 2, '.', '');
            }

            return [$property->id => new PropertyFinancialView($periods, [
                'outstanding_balance' => $outstanding,
                'remaining_opening_balance' => $opening,
                'prepaid_balance' => number_format((float) $property->prepaid_balance, 2, '.', ''),
            ])];
        });
    }

    private function remaining(string $total, string $allocated): string
    {
        return number_format(max(0, (float) $total - (float) $allocated), 2, '.', '');
    }
}
