<?php

namespace App\Data;

use App\Models\Charge;
use Spatie\LaravelData\Data;

class ChargeData extends Data
{
    /**
     * @param  list<ChargeLineData>  $lines
     */
    public function __construct(
        public int $id,
        public int $property_id,
        public string $property_label,
        public int $year,
        public int $month,
        public string $period_label,
        public bool $is_frozen,
        public array $lines,
    ) {}

    public static function fromModel(Charge $charge): self
    {
        $charge->loadMissing(['property', 'lines']);

        return new self(
            id: $charge->id,
            property_id: $charge->property_id,
            property_label: "Block {$charge->property->block} · Lot {$charge->property->lot}",
            year: $charge->year,
            month: $charge->month,
            period_label: sprintf('%04d-%02d', $charge->year, $charge->month),
            is_frozen: $charge->isFrozen(),
            lines: array_values(
                $charge->lines
                    ->map(fn ($line): ChargeLineData => ChargeLineData::fromModel($line))
                    ->all(),
            ),
        );
    }
}
