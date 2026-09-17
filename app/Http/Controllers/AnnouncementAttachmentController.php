<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementAttachment;
use App\Support\AnnouncementsPageAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementAttachmentController
{
    public function show(
        Request $request,
        AnnouncementAttachment $attachment,
        AnnouncementsPageAccess $announcementsPageAccess,
    ): StreamedResponse {
        $attachment->loadMissing('announcement');
        $user = $request->user();

        if ($attachment->announcement->isPublished()) {
            $announcementsPageAccess->ensureCanViewFeed($user);
        } else {
            abort_unless($user?->canAccessOfficerSurfaces() ?? false, 404);
        }

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_filename,
        );
    }
}
