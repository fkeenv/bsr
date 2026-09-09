<?php

namespace App\Actions\Suspends;

use App\Support\BillingPeriod;
use InvalidArgumentException;

trait ResolvesSuspendPeriods
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function periodFrom(array $data, string $prefix): BillingPeriod
    {
        $year = $data["{$prefix}_year"] ?? null;
        $month = $data["{$prefix}_month"] ?? null;

        if (! is_int($year) && ! is_numeric($year)) {
            throw new InvalidArgumentException('Billing Period year is required.');
        }

        if (! is_int($month) && ! is_numeric($month)) {
            throw new InvalidArgumentException('Billing Period month is required.');
        }

        return new BillingPeriod((int) $year, (int) $month);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function optionalPeriodFrom(array $data, string $prefix): ?BillingPeriod
    {
        $year = $data["{$prefix}_year"] ?? null;
        $month = $data["{$prefix}_month"] ?? null;

        if ($year === null && $month === null) {
            return null;
        }

        if ($year === null || $month === null) {
            throw new InvalidArgumentException('Suspend end Billing Period needs both year and month.');
        }

        return $this->periodFrom($data, $prefix);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{property_id: mixed, fee_type_id: mixed, starts_year: int, starts_month: int, ends_year: int|null, ends_month: int|null}
     */
    private function suspendPayload(array $data): array
    {
        $starts = $this->periodFrom($data, 'starts');
        $ends = $this->optionalPeriodFrom($data, 'ends');

        if ($ends !== null && $ends->isBefore($starts)) {
            throw new InvalidArgumentException('Suspend end Billing Period cannot be before the start.');
        }

        return [
            'property_id' => $data['property_id'],
            'fee_type_id' => $data['fee_type_id'],
            'starts_year' => $starts->year,
            'starts_month' => $starts->month,
            'ends_year' => $ends?->year,
            'ends_month' => $ends?->month,
        ];
    }
}
