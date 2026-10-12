<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Settings\UpdateLevyDay;
use App\Enums\FinancialPermission;
use App\Http\Requests\Officer\UpdateLevySettingsRequest;
use App\Models\AssociationSetting;
use App\Support\PlatformAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LevySettingsController
{
    public function edit(Request $request): Response
    {
        $settings = AssociationSetting::current();

        return Inertia::render('officer/levy-settings/Edit', [
            'canEdit' => PlatformAccess::allowsStaff($request->user(), FinancialPermission::LevySettingsEdit),
            'levyDayOfMonth' => $settings->levy_day_of_month,
        ]);
    }

    public function update(
        UpdateLevySettingsRequest $request,
        UpdateLevyDay $updateLevyDay,
    ): RedirectResponse {
        $updateLevyDay->handle((int) $request->validated('levy_day_of_month'));

        return redirect()
            ->route('officer.levy-settings.edit')
            ->with('success', 'Levy day updated.');
    }
}
