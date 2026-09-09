<?php

namespace App\Actions\FeeTypes;

use App\Actions\Concerns\NormalizesMoneyAmount;
use App\Models\FeeType;
use InvalidArgumentException;

class CreateFeeType
{
    use NormalizesMoneyAmount;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): FeeType
    {
        $name = $data['name'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw new InvalidArgumentException('Fee Type name is required.');
        }

        return FeeType::query()->create([
            'name' => trim($name),
            'amount' => $this->normalizeMoneyAmount($data['amount'] ?? null, 'Fee Type amount'),
        ]);
    }
}
