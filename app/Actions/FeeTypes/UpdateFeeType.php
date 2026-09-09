<?php

namespace App\Actions\FeeTypes;

use App\Actions\Concerns\NormalizesMoneyAmount;
use App\Models\FeeType;
use InvalidArgumentException;

class UpdateFeeType
{
    use NormalizesMoneyAmount;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(FeeType $feeType, array $data): FeeType
    {
        $payload = [];

        if (array_key_exists('name', $data)) {
            $name = $data['name'];

            if (! is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException('Fee Type name is required.');
            }

            $payload['name'] = trim($name);
        }

        if (array_key_exists('amount', $data)) {
            $payload['amount'] = $this->normalizeMoneyAmount($data['amount'], 'Fee Type amount');
        }

        $feeType->update($payload);

        return $feeType->refresh();
    }
}
