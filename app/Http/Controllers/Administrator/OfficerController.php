<?php

namespace App\Http\Controllers\Administrator;

use App\Actions\PlatformRoles\AppointOfficer;
use App\Actions\PlatformRoles\ListPlatformRoleAppointmentPage;
use App\Enums\PlatformRole;
use App\Http\Requests\Administrator\AppointOfficerRequest;
use App\Models\User;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OfficerController
{
    public function index(ListPlatformRoleAppointmentPage $listPlatformRoleAppointmentPage): Response
    {
        $page = $listPlatformRoleAppointmentPage->handle(PlatformRole::Officer);

        return Inertia::render('administrator/officers/Index', [
            'candidates' => $page['candidates'],
            'officers' => $page['holders'],
        ]);
    }

    public function store(
        AppointOfficerRequest $request,
        AppointOfficer $appointOfficer,
    ): RedirectResponse {
        $user = User::query()->findOrFail((int) $request->validated('user_id'));
        $appointOfficer->handle($user);

        FlashToast::success('Officer appointed.');

        return redirect()->route('administrator.officers.index');
    }
}
