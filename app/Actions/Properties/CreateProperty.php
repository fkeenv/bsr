<?php

namespace App\Actions\Properties;

use App\Models\Property;
use InvalidArgumentException;

class CreateProperty
{
    use NormalizesOpeningBalance;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Property
    {
        $block = $data['block'] ?? null;
        $lot = $data['lot'] ?? null;

        if (! is_string($block) || $block === '' || ! is_string($lot) || $lot === '') {
            throw new InvalidArgumentException('Block and Lot are required.');
        }

        $openingBalance = $this->normalizeOpeningBalance(
            isset($data['opening_balance']) && (is_string($data['opening_balance']) || is_int($data['opening_balance']) || is_float($data['opening_balance']))
                ? $data['opening_balance']
                : '0',
        );

        return Property::query()->create([
            'block' => $block,
            'lot' => $lot,
            'street_address' => $this->nullableString($data['street_address'] ?? null),
            'recorded_owner_name' => $this->nullableString($data['recorded_owner_name'] ?? null),
            'opening_balance' => $openingBalance,
            'is_active' => true,
        ]);
    }
}
