<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\PlatformRoles\AppointAdministrator;
use App\Actions\PlatformRoles\ListPlatformRoleAppointmentPage;
use App\Enums\PlatformRole;
use App\Http\Requests\SuperAdmin\AppointAdministratorRequest;
use App\Models\User;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdministratorController
{
    public function index(ListPlatformRoleAppointmentPage $listPlatformRoleAppointmentPage): Response
    {
        $page = $listPlatformRoleAppointmentPage->handle(PlatformRole::Administrator);

        return Inertia::render('super-admin/administrators/Index', [
            'candidates' => $page['candidates'],
            'administrators' => $page['holders'],
        ]);
    }

    public function store(
        AppointAdministratorRequest $request,
        AppointAdministrator $appointAdministrator,
    ): RedirectResponse {
        $user = User::query()->findOrFail((int) $request->validated('user_id'));
        $appointAdministrator->handle($user);

        FlashToast::success('Administrator appointed.');

        return redirect()->route('super-admin.administrators.index');
    }
}
