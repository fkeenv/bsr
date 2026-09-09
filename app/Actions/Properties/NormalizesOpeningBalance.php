<?php

namespace App\Actions\Properties;

use InvalidArgumentException;

trait NormalizesOpeningBalance
{
    private function normalizeOpeningBalance(string|float|int $amount): string
    {
        $normalized = number_format((float) $amount, 2, '.', '');

        if ((float) $normalized < 0) {
            throw new InvalidArgumentException('Opening Balance must be at least ₱0.');
        }

        return $normalized;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new InvalidArgumentException('Expected a string value.');
        }

        return $value;
    }
}
