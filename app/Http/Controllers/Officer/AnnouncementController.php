<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Announcements\CreateAnnouncementDraft;
use App\Actions\Announcements\PinAnnouncement;
use App\Actions\Announcements\PublishAnnouncement;
use App\Actions\Announcements\UnpinAnnouncement;
use App\Actions\Announcements\UnpublishAnnouncement;
use App\Actions\Announcements\UpdateAnnouncementDraft;
use App\Actions\Settings\UpdateAnnouncementsPageVisibility;
use App\Data\AnnouncementData;
use App\Enums\AnnouncementsPageVisibility;
use App\Http\Requests\Officer\PinAnnouncementRequest;
use App\Http\Requests\Officer\PublishAnnouncementRequest;
use App\Http\Requests\Officer\StoreAnnouncementRequest;
use App\Http\Requests\Officer\UnpinAnnouncementRequest;
use App\Http\Requests\Officer\UnpublishAnnouncementRequest;
use App\Http\Requests\Officer\UpdateAnnouncementRequest;
use App\Http\Requests\Officer\UpdateAnnouncementsPageVisibilityRequest;
use App\Models\Announcement;
use App\Models\AssociationSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController
{
    public function index(): Response
    {
        $announcements = Announcement::query()
            ->with('attachments')
            ->orderByRaw('published_at is null desc')
            ->orderByDesc('pinned_at')
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Announcement $announcement): AnnouncementData => AnnouncementData::fromModel($announcement))
            ->values()
            ->all();

        $visibility = AssociationSetting::current()->announcements_page_visibility;

        return Inertia::render('officer/announcements/Index', [
            'announcements' => $announcements,
            'pageVisibility' => $visibility->value,
            'pageVisibilityOptions' => collect(AnnouncementsPageVisibility::ordered())
                ->map(fn (AnnouncementsPageVisibility $option): array => [
                    'value' => $option->value,
                    'label' => $option->label(),
                    'description' => $option->description(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('officer/announcements/Create');
    }

    public function store(
        StoreAnnouncementRequest $request,
        CreateAnnouncementDraft $createAnnouncementDraft,
    ): RedirectResponse {
        $createAnnouncementDraft->handle($request->user(), $request->validated());

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcement draft saved.');
    }

    public function edit(Announcement $announcement): Response
    {
        $announcement->load('attachments');

        return Inertia::render('officer/announcements/Edit', [
            'announcement' => AnnouncementData::fromModel($announcement),
        ]);
    }

    public function update(
        UpdateAnnouncementRequest $request,
        Announcement $announcement,
        UpdateAnnouncementDraft $updateAnnouncementDraft,
    ): RedirectResponse {
        $updateAnnouncementDraft->handle($announcement, $request->user(), $request->validated());

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcement updated.');
    }

    public function publish(
        PublishAnnouncementRequest $request,
        Announcement $announcement,
        PublishAnnouncement $publishAnnouncement,
    ): RedirectResponse {
        $publishAnnouncement->handle($announcement, $request->user());

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcement published.');
    }

    public function unpublish(
        UnpublishAnnouncementRequest $request,
        Announcement $announcement,
        UnpublishAnnouncement $unpublishAnnouncement,
    ): RedirectResponse {
        $unpublishAnnouncement->handle($announcement, $request->user());

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcement returned to drafts.');
    }

    public function pin(
        PinAnnouncementRequest $request,
        Announcement $announcement,
        PinAnnouncement $pinAnnouncement,
    ): RedirectResponse {
        $pinAnnouncement->handle($announcement, $request->user());

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcement pinned.');
    }

    public function unpin(
        UnpinAnnouncementRequest $request,
        Announcement $announcement,
        UnpinAnnouncement $unpinAnnouncement,
    ): RedirectResponse {
        $unpinAnnouncement->handle($announcement, $request->user());

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcement unpinned.');
    }

    public function updatePageVisibility(
        UpdateAnnouncementsPageVisibilityRequest $request,
        UpdateAnnouncementsPageVisibility $updateAnnouncementsPageVisibility,
    ): RedirectResponse {
        $visibility = AnnouncementsPageVisibility::from(
            $request->validated('announcements_page_visibility'),
        );

        $updateAnnouncementsPageVisibility->handle($visibility);

        return redirect()
            ->route('officer.announcements.index')
            ->with('success', 'Announcements page visibility updated.');
    }
}
