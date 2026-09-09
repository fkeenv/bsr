<?php

namespace App\Actions\Charges;

use App\Actions\Concerns\NormalizesMoneyAmount;
use App\Models\Charge;
use App\Models\ChargeLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateChargeLines
{
    use NormalizesMoneyAmount;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Charge $charge, array $data): Charge
    {
        $charge->assertMayBeEdited();

        $lines = $data['lines'] ?? null;

        if (! is_array($lines)) {
            throw new InvalidArgumentException('Charge lines are required.');
        }

        DB::transaction(function () use ($charge, $lines): void {
            $keptIds = [];

            foreach ($lines as $lineData) {
                if (! is_array($lineData)) {
                    throw new InvalidArgumentException('Each Charge line must be an object.');
                }

                $amount = $this->normalizeMoneyAmount($lineData['amount'] ?? null, 'Charge line amount');
                $name = $lineData['fee_type_name'] ?? null;

                if (! is_string($name) || trim($name) === '') {
                    throw new InvalidArgumentException('Fee Type name is required on each Charge line.');
                }

                $feeTypeId = $lineData['fee_type_id'] ?? null;
                $lineId = $lineData['id'] ?? null;

                if ($lineId !== null) {
                    /** @var ChargeLine|null $existing */
                    $existing = $charge->lines()->whereKey($lineId)->first();

                    if ($existing === null) {
                        throw new InvalidArgumentException('Charge line does not belong to this Charge.');
                    }

                    $existing->update([
                        'fee_type_id' => is_numeric($feeTypeId) ? (int) $feeTypeId : null,
                        'fee_type_name' => trim($name),
                        'amount' => $amount,
                    ]);

                    $keptIds[] = $existing->id;

                    continue;
                }

                $created = $charge->lines()->create([
                    'fee_type_id' => is_numeric($feeTypeId) ? (int) $feeTypeId : null,
                    'fee_type_name' => trim($name),
                    'amount' => $amount,
                ]);

                $keptIds[] = $created->id;
            }

            $charge->lines()
                ->when(
                    $keptIds !== [],
                    fn ($query) => $query->whereKeyNot($keptIds),
                    fn ($query) => $query,
                )
                ->delete();
        });

        return $charge->refresh()->load('lines');
    }
}
