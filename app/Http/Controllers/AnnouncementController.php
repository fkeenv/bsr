<?php

namespace App\Http\Controllers;

use App\Data\AnnouncementData;
use App\Models\Announcement;
use App\Support\AnnouncementsPageAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController
{
    public function index(Request $request, AnnouncementsPageAccess $announcementsPageAccess): Response
    {
        $announcementsPageAccess->ensureCanViewFeed($request->user());

        $search = $request->string('search')->toString();

        $announcements = Announcement::query()
            ->published()
            ->with('attachments')
            ->searchPublished($search)
            ->feedOrder()
            ->get()
            ->map(fn (Announcement $announcement): AnnouncementData => AnnouncementData::fromModel($announcement))
            ->values()
            ->all();

        return Inertia::render('announcements/Index', [
            'announcements' => $announcements,
            'filters' => [
                'search' => $search,
            ],
            'isPublicVisitor' => $request->user() === null,
        ]);
    }

    public function show(
        Request $request,
        Announcement $announcement,
        AnnouncementsPageAccess $announcementsPageAccess,
    ): Response {
        $announcementsPageAccess->ensureCanViewFeed($request->user());
        abort_unless($announcement->isPublished(), 404);

        $announcement->load('attachments');

        return Inertia::render('announcements/Show', [
            'announcement' => AnnouncementData::fromModel($announcement),
            'isPublicVisitor' => $request->user() === null,
        ]);
    }
}
