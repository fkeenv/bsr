<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $levy_day_of_month
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'levy_day_of_month',
])]
class AssociationSetting extends Model
{
    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'levy_day_of_month' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'levy_day_of_month' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'levy_day_of_month' => 1,
        ]);
    }
}
