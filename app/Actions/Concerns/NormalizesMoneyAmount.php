<?php

namespace App\Actions\Concerns;

use InvalidArgumentException;

trait NormalizesMoneyAmount
{
    private function normalizeMoneyAmount(mixed $amount, string $label = 'Amount'): string
    {
        if (! is_string($amount) && ! is_int($amount) && ! is_float($amount)) {
            throw new InvalidArgumentException("{$label} must be numeric.");
        }

        $normalized = number_format((float) $amount, 2, '.', '');

        if ((float) $normalized < 0) {
            throw new InvalidArgumentException("{$label} must be at least ₱0.");
        }

        return $normalized;
    }
}
