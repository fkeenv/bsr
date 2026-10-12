<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\PlatformRoles\ListRolePermissionsPage;
use App\Actions\PlatformRoles\UpdateRolePermissions;
use App\Http\Requests\SuperAdmin\UpdateRolePermissionsRequest;
use App\Models\User;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RolePermissionController
{
    public function index(ListRolePermissionsPage $listRolePermissionsPage): Response
    {
        return Inertia::render('super-admin/role-permissions/Index', $listRolePermissionsPage->handle());
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role, UpdateRolePermissions $updateRolePermissions): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $updateRolePermissions->handle($actor, $role, $request->validated('grants'));
        FlashToast::success('Role permissions saved. Changes apply on the next request.');

        return redirect()->route('super-admin.role-permissions.index');
    }
}
