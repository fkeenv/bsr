<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Announcements\StoreAnnouncementAttachments;
use App\Http\Requests\Officer\StoreAnnouncementAttachmentsRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;

class AnnouncementAttachmentController
{
    public function store(
        StoreAnnouncementAttachmentsRequest $request,
        Announcement $announcement,
        StoreAnnouncementAttachments $storeAnnouncementAttachments,
    ): RedirectResponse {
        $storeAnnouncementAttachments->handle($announcement, $request->user(), $request->validated());

        return redirect()
            ->route('officer.announcements.edit', $announcement)
            ->with('success', 'Attachments uploaded.');
    }
}
