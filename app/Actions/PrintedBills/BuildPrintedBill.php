<?php

namespace App\Actions\PrintedBills;

use App\Actions\Statements\LoadPropertyFinancials;
use App\Models\Property;
use App\Support\BillingPeriod;
use App\Support\ChargeFinancialView;
use App\Support\PropertyFinancialView;
use Illuminate\Support\Carbon;

class BuildPrintedBill
{
    public function __construct(private LoadPropertyFinancials $loadPropertyFinancials) {}

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
    public function handle(Property $property, BillingPeriod $period, ?PropertyFinancialView $financials = null): array
    {
        $financials ??= $this->loadPropertyFinancials->handle(collect([$property]))->get($property->id);
        $balances = $financials->balances;

        $thisPeriod = collect($financials->charges)->first(
            fn (ChargeFinancialView $view): bool => $view->charge->year === $period->year && $view->charge->month === $period->month,
        );

        /** @var list<array{name: string, amount: string}> $lines */
        $lines = [];
        $periodTotal = '0.00';

        if ($thisPeriod instanceof ChargeFinancialView) {
            $lines = array_values($thisPeriod->charge->lines
                ->map(fn ($line): array => [
                    'name' => (string) $line->fee_type_name,
                    'amount' => number_format((float) $line->amount, 2, '.', ''),
                ])
                ->all());

            $periodTotal = $thisPeriod->total;
        }

        $openingRemaining = $balances['remaining_opening_balance'];
        $olderUnpaidLabels = [];
        $olderRemaining = '0.00';

        foreach ($financials->charges as $view) {
            $charge = $view->charge;
            $chargePeriod = new BillingPeriod($charge->year, $charge->month);

            if (! $chargePeriod->isBefore($period)) {
                continue;
            }

            $remaining = $view->remaining;

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
