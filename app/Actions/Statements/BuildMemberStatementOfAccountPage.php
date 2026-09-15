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
use App\Models\Charge;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\User;
use App\Support\PropertyBalances;
use Illuminate\Support\Carbon;

class BuildMemberStatementOfAccountPage
{
    public function __construct(private PropertyBalances $propertyBalances) {}

    public function handle(User $member, Property $property, ?int $selectedChargeId = null): StatementOfAccountPageData
    {
        $hasLiveMembership = $member->memberships()
            ->live()
            ->where('property_id', $property->id)
            ->exists();

        if (! $hasLiveMembership) {
            abort(403);
        }

        $balances = $this->propertyBalances->forProperty($property);

        $pendingDeclarations = array_values(Payment::query()
            ->pending()
            ->where('property_id', $property->id)
            ->with(['property', 'declaredBy'])
            ->latest('id')
            ->get()
            ->map(fn (Payment $payment): PaymentData => PaymentData::fromModel($payment))
            ->all());

        $charges = Charge::query()
            ->where('property_id', $property->id)
            ->with(['lines', 'property'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->get();

        $periods = array_values($charges
            ->map(fn (Charge $charge): StatementPeriodData => $this->periodFromCharge($charge))
            ->all());

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
            switcher: $this->switcherFor($member),
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

    private function periodFromCharge(Charge $charge): StatementPeriodData
    {
        $charge->loadMissing('lines');

        $chargeTotal = number_format(
            (float) $charge->lines->sum(fn ($line): float => (float) $line->amount),
            2,
            '.',
            '',
        );
        $remaining = $this->propertyBalances->remainingChargeAmount($charge);
        $status = $this->statusFor($chargeTotal, $remaining);

        $lines = array_values(
            $charge->lines
                ->map(fn ($line): ChargeLineData => ChargeLineData::fromModel($line))
                ->all(),
        );

        $payments = array_values(PaymentAllocation::query()
            ->where('charge_id', $charge->id)
            ->where('target', PaymentAllocationTarget::Charge)
            ->whereHas('payment', fn ($query) => $query->where('status', PaymentStatus::Confirmed))
            ->with('payment')
            ->orderBy('id')
            ->get()
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
            status: $status->value,
            remaining: $remaining,
            charge_total: $chargeTotal,
            lines: $lines,
            payments: $payments,
        );
    }

    private function statusFor(string $chargeTotal, string $remaining): PeriodStatus
    {
        if ((float) $remaining <= 0) {
            return PeriodStatus::Paid;
        }

        if ((float) $remaining === (float) $chargeTotal) {
            return PeriodStatus::Unpaid;
        }

        return PeriodStatus::Partial;
    }

    /**
     * @return list<StatementPropertyOptionData>
     */
    private function switcherFor(User $member): array
    {
        $memberships = Membership::query()
            ->live()
            ->where('user_id', $member->id)
            ->with('property')
            ->orderBy('property_id')
            ->get();

        return array_values($memberships
            ->map(function (Membership $membership): StatementPropertyOptionData {
                $property = $membership->property;
                $balances = $this->propertyBalances->forProperty($property);

                return new StatementPropertyOptionData(
                    property_id: $property->id,
                    label: 'Block '.$property->block.' · Lot '.$property->lot,
                    outstanding_balance: $balances['outstanding_balance'],
                );
            })
            ->all());
    }
}
