<?php

namespace App\Actions\Statements;

use App\Data\ChargeLineData;
use App\Data\PaymentData;
use App\Data\StatementOfAccountPageData;
use App\Data\StatementPeriodData;
use App\Data\StatementPeriodPaymentData;
use App\Data\StatementPropertyOptionData;
use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Enums\PeriodStatus;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Support\ChargeFinancialView;
use App\Support\PropertyFinancialView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildStatementOfAccountPage
{
    public function __construct(private LoadPropertyFinancials $loadPropertyFinancials) {}

    /**
     * @param  list<StatementPropertyOptionData>  $switcher
     */
    public function handle(
        Property $property,
        ?int $selectedChargeId = null,
        array $switcher = [],
        ?PropertyFinancialView $financials = null,
    ): StatementOfAccountPageData {
        $financials ??= $this->loadPropertyFinancials->handle(collect([$property]))->get($property->id);
        $balances = $financials->balances;

        $pendingDeclarations = array_values(Payment::query()
            ->pending()
            ->where('property_id', $property->id)
            ->with(['property', 'declaredBy'])
            ->latest('id')
            ->get()
            ->map(fn (Payment $payment): PaymentData => PaymentData::fromModel($payment))
            ->all());

        $allocations = PaymentAllocation::query()
            ->whereIn('charge_id', array_map(fn (ChargeFinancialView $period): int => $period->charge->id, $financials->charges))
            ->where('target', PaymentAllocationTarget::Charge)
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Confirmed))
            ->with('payment')->orderBy('id')->get()->groupBy('charge_id');

        $periods = array_map(
            fn (ChargeFinancialView $period): StatementPeriodData => $this->periodFromCharge(
                $period, $allocations->get($period->charge->id, collect()),
            ),
            array_reverse($financials->charges),
        );

        $selectedChargeId = $this->resolveSelectedChargeId($periods, $selectedChargeId);
        $selectedPeriod = collect($periods)->firstWhere('charge_id', $selectedChargeId);

        return new StatementOfAccountPageData(
            property: [
                'id' => $property->id,
                'label' => 'Block '.$property->block.' · Lot '.$property->lot,
            ],
            outstanding_balance: $balances['outstanding_balance'],
            remaining_opening_balance: $balances['remaining_opening_balance'],
            prepaid_balance: $balances['prepaid_balance'],
            pending_declarations: $pendingDeclarations,
            periods: $periods,
            selected_charge_id: $selectedChargeId,
            selected_period: $selectedPeriod,
            switcher: $switcher,
        );
    }

    /**
     * @param  list<StatementPeriodData>  $periods
     */
    private function resolveSelectedChargeId(array $periods, ?int $requestedChargeId): ?int
    {
        if ($periods === []) {
            return null;
        }

        $periodIds = collect($periods)->pluck('charge_id');

        if ($requestedChargeId !== null && $periodIds->contains($requestedChargeId)) {
            return $requestedChargeId;
        }

        $firstOpen = collect($periods)->first(
            fn (StatementPeriodData $period): bool => $period->status !== PeriodStatus::Paid->value,
        );

        if ($firstOpen instanceof StatementPeriodData) {
            return $firstOpen->charge_id;
        }

        return $periods[0]->charge_id;
    }

    /**
     * @param  Collection<int, PaymentAllocation>  $allocations
     */
    private function periodFromCharge(ChargeFinancialView $period, Collection $allocations): StatementPeriodData
    {
        $charge = $period->charge;

        $lines = array_values(
            $charge->lines
                ->map(fn ($line): ChargeLineData => ChargeLineData::fromModel($line))
                ->all(),
        );

        $payments = array_values($allocations
            ->map(function (PaymentAllocation $allocation): StatementPeriodPaymentData {
                $payment = $allocation->payment;

                return new StatementPeriodPaymentData(
                    id: $payment->id,
                    amount: $allocation->amount,
                    method: $payment->method->value,
                    reference: $payment->reference,
                    status: $payment->status->value,
                    recorded_on: $payment->created_at?->timezone('Asia/Manila')->format('Y-m-d'),
                );
            })
            ->all());

        return new StatementPeriodData(
            charge_id: $charge->id,
            year: $charge->year,
            month: $charge->month,
            label: Carbon::create($charge->year, $charge->month, 1)->format('F Y'),
            status: $period->status->value,
            remaining: $period->remaining,
            charge_total: $period->total,
            lines: $lines,
            payments: $payments,
        );
    }
}
