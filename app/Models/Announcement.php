<?php

namespace App\Models;

use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property Carbon|null $published_at
 * @property Carbon|null $pinned_at
 * @property int $created_by_user_id
 * @property int|null $updated_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'title',
    'body',
    'published_at',
    'pinned_at',
    'created_by_user_id',
    'updated_by_user_id',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function isPinned(): bool
    {
        return $this->pinned_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * @return HasMany<AnnouncementAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->whereNull('published_at');
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function feedOrder(Builder $query): void
    {
        $query->orderByRaw('pinned_at is null')
            ->orderByDesc('pinned_at')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    #[Scope]
    protected function searchPublished(Builder $query, ?string $search): void
    {
        $term = trim((string) $search);

        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function (Builder $builder) use ($like): void {
            $builder->where('title', 'like', $like)
                ->orWhere('body', 'like', $like);
        });
    }
}
