<?php

namespace App\Actions\Charges;

use App\Models\Charge;
use App\Models\FeeType;
use App\Models\Property;
use App\Models\Suspend;
use App\Support\BillingPeriod;
use Illuminate\Support\Facades\DB;

class GenerateChargesForPeriod
{
    public function handle(BillingPeriod $period): int
    {
        $feeTypes = FeeType::query()
            ->active()
            ->orderBy('name')
            ->get();

        $properties = Property::query()
            ->active()
            ->orderBy('id')
            ->get();

        $existingPropertyIds = Charge::query()
            ->where('year', $period->year)
            ->where('month', $period->month)
            ->pluck('property_id')
            ->all();

        $created = 0;

        foreach ($properties as $property) {
            if (in_array($property->id, $existingPropertyIds, true)) {
                continue;
            }

            DB::transaction(function () use ($property, $period, $feeTypes, &$created): void {
                $charge = Charge::query()->create([
                    'property_id' => $property->id,
                    'year' => $period->year,
                    'month' => $period->month,
                ]);

                foreach ($feeTypes as $feeType) {
                    if ($this->isSuspended($property, $feeType, $period)) {
                        continue;
                    }

                    $charge->lines()->create([
                        'fee_type_id' => $feeType->id,
                        'fee_type_name' => $feeType->name,
                        'amount' => $feeType->amount,
                    ]);
                }

                $property->markAsCharged();
                $created++;
            });
        }

        return $created;
    }

    private function isSuspended(Property $property, FeeType $feeType, BillingPeriod $period): bool
    {
        return Suspend::query()
            ->where('property_id', $property->id)
            ->where('fee_type_id', $feeType->id)
            ->get()
            ->contains(fn (Suspend $suspend): bool => $suspend->covers($period));
    }
}
