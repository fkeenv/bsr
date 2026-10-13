<?php

namespace App\Actions\Announcements;

use App\Data\AnnouncementPreviewData;
use App\Enums\AnnouncementVisibility;
use App\Models\Announcement;

class ListPublicAnnouncementPreviews
{
    /** @return list<AnnouncementPreviewData> */
    public function handle(): array
    {
        return array_values(Announcement::query()
            ->published()
            ->where('visibility', AnnouncementVisibility::Public)
            ->feedOrder()
            ->limit(6)
            ->with(['attachments' => fn ($query) => $query->where('mime_type', 'like', 'image/%')->orderBy('id')->limit(1)->select(['id', 'announcement_id'])])
            ->get(['id', 'title', 'body', 'published_at'])
            ->map(fn (Announcement $announcement): AnnouncementPreviewData => AnnouncementPreviewData::fromModel($announcement))
            ->all());
    }
}
