<?php

namespace App\Http\Middleware;

use App\Support\AnnouncementsPageAccess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $announcementsPageAccess = app(AnnouncementsPageAccess::class);
        $profile = $user?->profile;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    ...collect($user->toArray())->except(['profile'])->all(),
                    'is_super_admin' => $user->isSuperAdmin(),
                    'title' => $profile?->title,
                    'avatar' => filled($profile?->avatar_path)
                        ? route('profile.avatar.show')
                        : null,
                ],
                'capabilities' => $user === null ? null : [
                    'isSuperAdmin' => $user->isSuperAdmin(),
                    'canAccessOfficer' => $user->canAccessOfficerSurfaces(),
                    'canAccessAdministrator' => $user->canAccessAdministratorSurfaces(),
                    'isMembershipHolder' => $user->isMembershipHolder(),
                ],
            ],
            'announcementsPageVisibility' => $announcementsPageAccess->visibility()->value,
            'announcementsPageListed' => $announcementsPageAccess->isListedInMembershipNav(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
