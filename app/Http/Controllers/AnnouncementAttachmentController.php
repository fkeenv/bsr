<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementAttachmentController
{
    public function show(Request $request, AnnouncementAttachment $attachment): StreamedResponse
    {
        $user = $request->user();
        $this->ensureCanReadAttachment($user, $attachment);

        return Storage::disk($attachment->disk)->response(
            $attachment->path,
            $attachment->original_filename,
        );
    }

    private function ensureCanReadAttachment(?User $user, AnnouncementAttachment $attachment): void
    {
        abort_unless($user !== null, 403);

        $attachment->loadMissing('announcement');
        $announcement = $attachment->announcement;

        if ($user->canAccessOfficerSurfaces()) {
            return;
        }

        abort_unless(
            $user->hasLiveMembership() && $announcement->isPublished(),
            403,
        );
    }
}
