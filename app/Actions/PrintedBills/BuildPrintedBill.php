<?php

namespace App\Actions\PrintedBills;

use App\Models\Charge;
use App\Models\Property;
use App\Support\BillingPeriod;
use App\Support\PropertyBalances;
use Illuminate\Support\Carbon;

class BuildPrintedBill
{
    public function __construct(private PropertyBalances $propertyBalances) {}

    /**
     * @return array{
     *     bill_to: string,
     *     property_label: string,
     *     address: string|null,
     *     period_label: string,
     *     lines: list<array{name: string, amount: string}>,
     *     period_total: string,
     *     balance_forward: string,
     *     opening_balance_remaining: string,
     *     older_unpaid_labels: list<string>,
     *     outstanding_balance: string
     * }
     */
    public function handle(Property $property, BillingPeriod $period): array
    {
        $balances = $this->propertyBalances->forProperty($property);

        $charges = Charge::query()
            ->where('property_id', $property->id)
            ->with('lines')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('id')
            ->get();

        $thisPeriod = $charges->first(
            fn (Charge $charge): bool => $charge->year === $period->year && $charge->month === $period->month,
        );

        /** @var list<array{name: string, amount: string}> $lines */
        $lines = [];
        $periodTotal = '0.00';

        if ($thisPeriod instanceof Charge) {
            $lines = array_values($thisPeriod->lines
                ->map(fn ($line): array => [
                    'name' => (string) $line->fee_type_name,
                    'amount' => number_format((float) $line->amount, 2, '.', ''),
                ])
                ->all());

            $periodTotal = number_format(
                (float) $thisPeriod->lines->sum(fn ($line): float => (float) $line->amount),
                2,
                '.',
                '',
            );
        }

        $openingRemaining = $balances['remaining_opening_balance'];
        $olderUnpaidLabels = [];
        $olderRemaining = '0.00';

        foreach ($charges as $charge) {
            $chargePeriod = new BillingPeriod($charge->year, $charge->month);

            if (! $chargePeriod->isBefore($period)) {
                continue;
            }

            $remaining = $this->propertyBalances->remainingChargeAmount($charge);

            if ((float) $remaining <= 0) {
                continue;
            }

            $olderUnpaidLabels[] = Carbon::create($charge->year, $charge->month, 1)->format('F Y');
            $olderRemaining = number_format((float) $olderRemaining + (float) $remaining, 2, '.', '');
        }

        $balanceForward = number_format((float) $openingRemaining + (float) $olderRemaining, 2, '.', '');
        $propertyLabel = 'Block '.$property->block.' · Lot '.$property->lot;

        return [
            'bill_to' => filled($property->recorded_owner_name)
                ? (string) $property->recorded_owner_name
                : $propertyLabel,
            'property_label' => $propertyLabel,
            'address' => $property->street_address,
            'period_label' => Carbon::create($period->year, $period->month, 1)->format('F Y'),
            'lines' => $lines,
            'period_total' => $periodTotal,
            'balance_forward' => $balanceForward,
            'opening_balance_remaining' => $openingRemaining,
            'older_unpaid_labels' => $olderUnpaidLabels,
            'outstanding_balance' => $balances['outstanding_balance'],
        ];
    }
}
