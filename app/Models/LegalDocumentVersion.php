<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Database\Factories\LegalDocumentVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property LegalDocumentType $type
 * @property string $body
 * @property Carbon $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type',
    'body',
    'published_at',
])]
class LegalDocumentVersion extends Model
{
    /** @use HasFactory<LegalDocumentVersionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
            'published_at' => 'datetime',
        ];
    }

    public static function current(LegalDocumentType $type): ?self
    {
        return static::query()
            ->where('type', $type)
            ->latest('published_at')
            ->latest('id')
            ->first();
    }
}
