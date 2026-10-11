<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Announcements\StoreAnnouncementAttachments;
use App\Enums\AnnouncementPermission;
use App\Http\Requests\Officer\StoreAnnouncementAttachmentsRequest;
use App\Models\Announcement;
use App\Support\PlatformAccess;
use Illuminate\Http\RedirectResponse;

class AnnouncementAttachmentController
{
    public function store(
        StoreAnnouncementAttachmentsRequest $request,
        Announcement $announcement,
        StoreAnnouncementAttachments $storeAnnouncementAttachments,
    ): RedirectResponse {
        $storeAnnouncementAttachments->handle($announcement, $request->user(), $request->validated());

        return (PlatformAccess::allowsAnnouncement($request->user(), AnnouncementPermission::Read)
            || PlatformAccess::allowsAnnouncement($request->user(), AnnouncementPermission::Edit)
                ? redirect()->route('officer.announcements.edit', $announcement)
                : redirect()->route('dashboard'))
            ->with('success', 'Attachments uploaded.');
    }
}
