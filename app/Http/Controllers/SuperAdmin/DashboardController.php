<?php

namespace App\Http\Controllers\SuperAdmin;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('super-admin/Dashboard');
    }
}
