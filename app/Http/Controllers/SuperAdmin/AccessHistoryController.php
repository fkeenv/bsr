<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\PlatformRoles\ListAccessHistory;
use App\Http\Requests\SuperAdmin\AccessHistoryRequest;
use Inertia\Inertia;
use Inertia\Response;

class AccessHistoryController
{
    public function index(AccessHistoryRequest $request, ListAccessHistory $listHistory): Response
    {
        return Inertia::render('super-admin/access-history/Index', [...$listHistory->handle($request->user(), $request->validated()),
            'values' => $request->only(['actor', 'account', 'role', 'action', 'occurred_from', 'occurred_to'])]);
    }
}
