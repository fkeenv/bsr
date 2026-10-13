<?php

namespace App\Data;

use App\Models\Announcement;
use App\Support\SanitizeHtml;
use Illuminate\Support\Str;
use LogicException;
use Spatie\LaravelData\Data;

class AnnouncementPreviewData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $published_at,
        public string $excerpt,
        public ?string $image_url,
    ) {}

    public static function fromModel(Announcement $announcement): self
    {
        $publishedAt = $announcement->published_at;
        if ($publishedAt === null) {
            throw new LogicException('Announcement previews require a publication date.');
        }
        $image = $announcement->attachments->first();

        return new self(
            $announcement->id,
            $announcement->title,
            $publishedAt->toIso8601String(),
            Str::limit(SanitizeHtml::plainText($announcement->body), 180),
            $image !== null ? route('announcements.attachments.show', $image->id) : null,
        );
    }
}
