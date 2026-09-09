<?php

namespace Database\Seeders;

use App\Models\FeeType;
use Illuminate\Database\Seeder;

class FeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Guard', 'amount' => '200.00'],
            ['name' => 'Garbage collection', 'amount' => '200.00'],
        ] as $feeType) {
            FeeType::query()->firstOrCreate(
                ['name' => $feeType['name']],
                ['amount' => $feeType['amount']],
            );
        }
    }
}
