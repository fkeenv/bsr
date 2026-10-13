<?php

namespace App\Http\Controllers;

use App\Actions\Announcements\ListPublicAnnouncementPreviews;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class WelcomeController extends Controller
{
    public function __invoke(ListPublicAnnouncementPreviews $listPreviews): Response
    {
        return Inertia::render('Welcome', [
            'announcementPreviews' => $listPreviews->handle(),
            'canRegister' => Features::enabled(Features::registration()),
        ]);
    }
}
