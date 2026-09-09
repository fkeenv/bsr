<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $block
 * @property string $lot
 * @property string|null $street_address
 * @property string|null $recorded_owner_name
 * @property string $opening_balance
 * @property Carbon|null $opening_balance_frozen_at
 * @property Carbon|null $first_charged_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'block',
    'lot',
    'street_address',
    'recorded_owner_name',
    'opening_balance',
    'is_active',
])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'opening_balance' => '0.00',
        'is_active' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_balance_frozen_at' => 'datetime',
            'first_charged_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function openingBalanceIsFrozen(): bool
    {
        return $this->opening_balance_frozen_at !== null;
    }

    /** Called by Payment confirmation once a confirmed Payment exists (#payment). */
    public function freezeOpeningBalance(): void
    {
        if ($this->openingBalanceIsFrozen()) {
            return;
        }

        $this->forceFill([
            'opening_balance_frozen_at' => now(),
        ])->save();
    }

    public function assertOpeningBalanceMayBeChanged(): void
    {
        if ($this->openingBalanceIsFrozen()) {
            throw new InvalidArgumentException('Opening Balance is frozen after a confirmed Payment.');
        }
    }

    public function hasBeenCharged(): bool
    {
        return $this->first_charged_at !== null;
    }

    /** Called by Charge generation the first time a Charge is levied (#levy). */
    public function markAsCharged(): void
    {
        if ($this->hasBeenCharged()) {
            return;
        }

        $this->forceFill([
            'first_charged_at' => now(),
        ])->save();
    }

    /**
     * @param  Builder<Property>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Property>  $query
     * @param  list<string>  $columns
     */
    #[Scope]
    protected function search(Builder $query, ?string $term, array $columns = []): void
    {
        if (! filled($term) || $columns === []) {
            return;
        }

        $query->where(function (Builder $nested) use ($term, $columns): void {
            foreach ($columns as $column) {
                $nested->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }

    /**
     * @param  Builder<Property>  $query
     */
    #[Scope]
    protected function filterByStatus(Builder $query, ?string $status): void
    {
        if ($status === 'active') {
            $query->where('is_active', true);

            return;
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);
        }
    }

    /**
     * @param  Builder<Property>  $query
     */
    #[Scope]
    protected function filterByBlockLot(Builder $query, ?string $block = null, ?string $lot = null): void
    {
        if (filled($block)) {
            $query->where('block', $block);
        }

        if (filled($lot)) {
            $query->where('lot', $lot);
        }
    }
}
