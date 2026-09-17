<?php

namespace App\Actions\Statements;

use App\Data\UnpaidRosterPageData;
use App\Data\UnpaidRosterRowData;
use App\Enums\PeriodStatus;
use App\Models\Charge;
use App\Models\Property;
use App\Support\PropertyBalances;
use Illuminate\Support\Carbon;

class BuildOfficerUnpaidRosterPage
{
    public function __construct(
        private PropertyBalances $propertyBalances,
        private BuildStatementOfAccountPage $buildStatementOfAccountPage,
    ) {}

    public function handle(
        ?string $block = null,
        ?string $lot = null,
        ?string $status = null,
        ?string $owesFor = null,
        ?int $selectedPropertyId = null,
        ?int $selectedChargeId = null,
    ): UnpaidRosterPageData {
        $now = Carbon::now('Asia/Manila');
        $periodYear = $now->year;
        $periodMonth = $now->month;
        $periodKey = sprintf('%04d-%02d', $periodYear, $periodMonth);
        $periodLabel = $now->copy()->startOfMonth()->format('F Y');

        $properties = Property::query()
            ->orderBy('block')
            ->orderBy('lot')
            ->get();

        $rosterCount = $properties->count();

        /** @var list<array{property: Property, balances: array{outstanding_balance: string, remaining_opening_balance: string, prepaid_balance: string}, this_period_status: string, oldest_open_label: string|null, open_period_keys: list<string>, open_period_labels: array<string, string>}> $unpaid */
        $unpaid = [];

        foreach ($properties as $property) {
            $balances = $this->propertyBalances->forProperty($property);

            if ((float) $balances['outstanding_balance'] <= 0) {
                continue;
            }

            $charges = Charge::query()
                ->where('property_id', $property->id)
                ->with('lines')
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('id')
                ->get();

            $thisPeriodStatus = PeriodStatus::Paid->value;
            $openPeriodKeys = [];
            $openPeriodLabels = [];
            $oldestOpenLabel = null;

            if ((float) $balances['remaining_opening_balance'] > 0) {
                $oldestOpenLabel = 'Opening Balance';
            }

            foreach ($charges as $charge) {
                $chargeTotal = number_format(
                    (float) $charge->lines->sum(fn ($line): float => (float) $line->amount),
                    2,
                    '.',
                    '',
                );
                $remaining = $this->propertyBalances->remainingChargeAmount($charge);
                $periodStatus = $this->statusFor($chargeTotal, $remaining);
                $key = sprintf('%04d-%02d', $charge->year, $charge->month);

                if ($charge->year === $periodYear && $charge->month === $periodMonth) {
                    $thisPeriodStatus = $periodStatus->value;
                }

                if ((float) $remaining > 0) {
                    $openPeriodKeys[] = $key;
                    $openPeriodLabels[$key] = Carbon::create($charge->year, $charge->month, 1)->format('F Y');

                    if ($oldestOpenLabel === null) {
                        $oldestOpenLabel = $openPeriodLabels[$key];
                    }
                }
            }

            $unpaid[] = [
                'property' => $property,
                'balances' => $balances,
                'this_period_status' => $thisPeriodStatus,
                'oldest_open_label' => $oldestOpenLabel,
                'open_period_keys' => $openPeriodKeys,
                'open_period_labels' => $openPeriodLabels,
            ];
        }

        $unpaidCount = count($unpaid);
        $filterOptions = $this->filterOptions($unpaid);
        $filtersActive = filled($block) || filled($lot) || filled($status) || filled($owesFor);

        $filtered = array_values(array_filter(
            $unpaid,
            function (array $row) use ($block, $lot, $status, $owesFor): bool {
                if (filled($block) && (string) $row['property']->block !== (string) $block) {
                    return false;
                }

                if (filled($lot) && (string) $row['property']->lot !== (string) $lot) {
                    return false;
                }

                if (filled($status) && $row['this_period_status'] !== $status) {
                    return false;
                }

                if ($owesFor === 'opening') {
                    return (float) $row['balances']['remaining_opening_balance'] > 0;
                }

                if (filled($owesFor)) {
                    return in_array($owesFor, $row['open_period_keys'], true);
                }

                return true;
            },
        ));

        usort($filtered, function (array $left, array $right): int {
            $balanceCmp = (float) $right['balances']['outstanding_balance']
                <=> (float) $left['balances']['outstanding_balance'];

            if ($balanceCmp !== 0) {
                return $balanceCmp;
            }

            $blockCmp = strnatcmp((string) $left['property']->block, (string) $right['property']->block);

            if ($blockCmp !== 0) {
                return $blockCmp;
            }

            return strnatcmp((string) $left['property']->lot, (string) $right['property']->lot);
        });

        $rows = [];

        foreach ($filtered as $row) {
            $property = $row['property'];

            $rows[] = new UnpaidRosterRowData(
                property_id: $property->id,
                block: (string) $property->block,
                lot: (string) $property->lot,
                label: 'Block '.$property->block.' · Lot '.$property->lot,
                recorded_owner_name: $property->recorded_owner_name,
                outstanding_balance: $row['balances']['outstanding_balance'],
                remaining_opening_balance: $row['balances']['remaining_opening_balance'],
                this_period_status: $row['this_period_status'],
                oldest_open_label: $row['oldest_open_label'],
            );
        }

        $emptyState = null;

        if ($unpaidCount === 0) {
            $emptyState = 'clear';
        } elseif ($rows === []) {
            $emptyState = 'no_matches';
        }

        $selected = null;

        if ($selectedPropertyId !== null) {
            foreach ($filtered as $row) {
                if ($row['property']->id !== $selectedPropertyId) {
                    continue;
                }

                $selected = $this->buildStatementOfAccountPage->handle(
                    $row['property'],
                    $selectedChargeId,
                    [],
                );
                break;
            }
        }

        return new UnpaidRosterPageData(
            this_billing_period: [
                'year' => $periodYear,
                'month' => $periodMonth,
                'label' => $periodLabel,
                'key' => $periodKey,
            ],
            roster_count: $rosterCount,
            unpaid_count: $unpaidCount,
            filters_active: $filtersActive,
            empty_state: $emptyState,
            rows: $rows,
            selected: $selected,
            filter_options: $filterOptions,
            values: [
                'block' => filled($block) ? (string) $block : null,
                'lot' => filled($lot) ? (string) $lot : null,
                'status' => filled($status) ? (string) $status : null,
                'owes_for' => filled($owesFor) ? (string) $owesFor : null,
                'property' => $selectedPropertyId,
                'charge' => $selectedChargeId,
            ],
        );
    }

    /**
     * @param  list<array{property: Property, balances: array{outstanding_balance: string, remaining_opening_balance: string, prepaid_balance: string}, this_period_status: string, oldest_open_label: string|null, open_period_keys: list<string>, open_period_labels: array<string, string>}>  $unpaid
     * @return array{blocks: list<string>, owes_for: list<array{value: string, label: string}>}
     */
    private function filterOptions(array $unpaid): array
    {
        $blocks = [];

        foreach ($unpaid as $row) {
            $blocks[] = (string) $row['property']->block;
        }

        $blocks = array_values(array_unique($blocks));
        natcasesort($blocks);
        $blocks = array_values($blocks);

        $owesFor = [];
        $hasOpening = false;
        $periodLabels = [];

        foreach ($unpaid as $row) {
            if ((float) $row['balances']['remaining_opening_balance'] > 0) {
                $hasOpening = true;
            }

            foreach ($row['open_period_labels'] as $key => $label) {
                $periodLabels[$key] = $label;
            }
        }

        if ($hasOpening) {
            $owesFor[] = [
                'value' => 'opening',
                'label' => 'Opening Balance',
            ];
        }

        ksort($periodLabels);

        foreach ($periodLabels as $value => $label) {
            $owesFor[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return [
            'blocks' => $blocks,
            'owes_for' => $owesFor,
        ];
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
}
