<?php

namespace App\Support;

use InvalidArgumentException;

readonly class BillingPeriod
{
    public function __construct(
        public int $year,
        public int $month,
    ) {
        if ($this->month < 1 || $this->month > 12) {
            throw new InvalidArgumentException('Billing Period month must be between 1 and 12.');
        }
    }

    public function label(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function compare(self $other): int
    {
        return ($this->year * 12 + $this->month) <=> ($other->year * 12 + $other->month);
    }

    public function isBefore(self $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function isAfter(self $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function equals(self $other): bool
    {
        return $this->compare($other) === 0;
    }
}
