<?php

namespace App\Http\Controllers\Officer;

use App\Actions\FeeTypes\CreateFeeType;
use App\Actions\FeeTypes\UpdateFeeType;
use App\Data\FeeTypeData;
use App\Enums\FinancialPermission;
use App\Http\Requests\Officer\StoreFeeTypeRequest;
use App\Http\Requests\Officer\UpdateFeeTypeRequest;
use App\Models\FeeType;
use App\Support\StaffWorkspace;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FeeTypeController
{
    public function index(): Response
    {
        $feeTypes = FeeType::query()
            ->orderBy('name')
            ->get()
            ->map(fn (FeeType $feeType): FeeTypeData => FeeTypeData::fromModel($feeType))
            ->values()
            ->all();

        return Inertia::render('officer/fee-types/Index', [
            'feeTypes' => $feeTypes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('officer/fee-types/Create');
    }

    public function store(StoreFeeTypeRequest $request, CreateFeeType $createFeeType): RedirectResponse
    {
        $createFeeType->handle($request->validated());

        return StaffWorkspace::redirect($request->user(), FinancialPermission::FeeTypesRead, 'officer.fee-types.index')
            ->with('success', 'Fee Type created.');
    }

    public function edit(FeeType $feeType): Response
    {
        return Inertia::render('officer/fee-types/Edit', [
            'feeType' => FeeTypeData::fromModel($feeType),
        ]);
    }

    public function update(
        UpdateFeeTypeRequest $request,
        FeeType $feeType,
        UpdateFeeType $updateFeeType,
    ): RedirectResponse {
        $updateFeeType->handle($feeType, $request->validated());

        return StaffWorkspace::redirect($request->user(), FinancialPermission::FeeTypesRead, 'officer.fee-types.index')
            ->with('success', 'Fee Type updated.');
    }
}
