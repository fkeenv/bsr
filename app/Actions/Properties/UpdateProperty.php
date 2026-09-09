<?php

namespace App\Actions\Properties;

use App\Models\Property;
use InvalidArgumentException;

class UpdateProperty
{
    use NormalizesOpeningBalance;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Property $property, array $data): Property
    {
        $payload = [
            'street_address' => array_key_exists('street_address', $data)
                ? $this->nullableString($data['street_address'])
                : $property->street_address,
            'recorded_owner_name' => array_key_exists('recorded_owner_name', $data)
                ? $this->nullableString($data['recorded_owner_name'])
                : $property->recorded_owner_name,
        ];

        if (array_key_exists('opening_balance', $data)) {
            $property->assertOpeningBalanceMayBeChanged();
            $amount = $data['opening_balance'];

            if (! is_string($amount) && ! is_int($amount) && ! is_float($amount) && $amount !== null) {
                throw new InvalidArgumentException('Opening Balance must be numeric.');
            }

            $payload['opening_balance'] = $this->normalizeOpeningBalance($amount ?? 0);
        }

        $property->update($payload);

        return $property->refresh();
    }
}
