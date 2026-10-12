<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Settings\UpdatePrintedBillSettings;
use App\Enums\FinancialPermission;
use App\Http\Requests\Officer\UpdatePrintedBillSettingsRequest;
use App\Models\AssociationSetting;
use App\Support\PlatformAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillSettingsController
{
    public function edit(Request $request): Response
    {
        $settings = AssociationSetting::current();

        return Inertia::render('officer/bill-settings/Edit', [
            'canEdit' => PlatformAccess::allowsStaff($request->user(), FinancialPermission::BillSettingsEdit),
            'letterheadName' => $settings->letterhead_name,
            'letterheadShortName' => $settings->letterhead_short_name,
            'letterheadAddressLines' => $settings->letterhead_address_lines ?? AssociationSetting::defaultLetterheadAddressLines(),
            'letterheadContact' => $settings->letterhead_contact,
            'letterheadTreasurer' => $settings->letterhead_treasurer,
            'paymentChannels' => $settings->payment_channels ?? AssociationSetting::defaultPaymentChannels(),
        ]);
    }

    public function update(
        UpdatePrintedBillSettingsRequest $request,
        UpdatePrintedBillSettings $updatePrintedBillSettings,
    ): RedirectResponse {
        $updatePrintedBillSettings->handle($request->validated());

        return redirect()
            ->route('officer.bill-settings.edit')
            ->with('success', 'Printed Bill settings updated.');
    }
}
