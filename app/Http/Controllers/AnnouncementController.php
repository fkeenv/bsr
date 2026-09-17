<?php

namespace App\Http\Controllers;

use App\Data\AnnouncementData;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController
{
    public function index(Request $request): Response
    {
        $this->ensureCanReadFeed($request->user());

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
        ]);
    }

    public function show(Request $request, Announcement $announcement): Response
    {
        $this->ensureCanReadFeed($request->user());
        abort_unless($announcement->isPublished(), 404);

        $announcement->load('attachments');

        return Inertia::render('announcements/Show', [
            'announcement' => AnnouncementData::fromModel($announcement),
        ]);
    }

    private function ensureCanReadFeed(?User $user): void
    {
        abort_unless(
            $user !== null && ($user->hasLiveMembership() || $user->canAccessOfficerSurfaces()),
            403,
        );
    }
}
