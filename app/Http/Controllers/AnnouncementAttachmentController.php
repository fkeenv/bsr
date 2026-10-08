<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementAttachment;
use App\Support\AnnouncementAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementAttachmentController
{
    public function show(
        Request $request,
        AnnouncementAttachment $attachment,
        AnnouncementAccess $announcementAccess,
    ): StreamedResponse {
        $attachment->loadMissing('announcement');
        $announcementAccess->ensureCanView($attachment->announcement, $request->user());

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_filename,
        );
    }
}
