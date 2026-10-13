<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\PlatformRoles\CreateStaffRole;
use App\Actions\PlatformRoles\DeleteStaffRole;
use App\Actions\PlatformRoles\ListRolePermissionsPage;
use App\Actions\PlatformRoles\RenameStaffRole;
use App\Actions\PlatformRoles\UpdateRolePermissions;
use App\Http\Requests\SuperAdmin\DeleteStaffRoleRequest;
use App\Http\Requests\SuperAdmin\StaffRoleNameRequest;
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

    public function store(StaffRoleNameRequest $request, CreateStaffRole $createStaffRole): RedirectResponse
    {
        $actor = $request->user();
        assert($actor !== null);
        $createStaffRole->handle($actor, $request->validated('name'));
        FlashToast::success('Staff role created. Choose its permissions before assigning it.');

        return redirect()->route('super-admin.role-permissions.index');
    }

    public function rename(StaffRoleNameRequest $request, Role $role, RenameStaffRole $renameStaffRole): RedirectResponse
    {
        $actor = $request->user();
        assert($actor !== null);
        $renameStaffRole->handle($actor, $role, $request->validated('name'));
        FlashToast::success('Staff role renamed.');

        return redirect()->route('super-admin.role-permissions.index');
    }

    public function destroy(DeleteStaffRoleRequest $request, Role $role, DeleteStaffRole $deleteStaffRole): RedirectResponse
    {
        $actor = $request->user();
        assert($actor !== null);
        $deleteStaffRole->handle($actor, $role);
        FlashToast::success('Staff role deleted.');

        return redirect()->route('super-admin.role-permissions.index');
    }
}
