<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Properties\ImportPropertiesFromCsv;
use App\Http\Requests\Officer\ImportPropertiesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PropertyImportController
{
    public function create(Request $request): Response
    {
        return Inertia::render('officer/properties/Import', [
            'canImport' => $request->user()?->isSuperAdmin() ?? false,
        ]);
    }

    public function sample(): StreamedResponse
    {
        $csv = implode("\n", [
            'block,lot,street_address,recorded_owner_name,opening_balance',
            '1,1,12 Rose St,Juan Dela Cruz,100.00',
            '1,2,,Maria Santos,0',
            '',
        ]);

        return response()->streamDownload(
            function () use ($csv): void {
                echo $csv;
            },
            'property-roster-sample.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ],
        );
    }

    public function store(
        ImportPropertiesRequest $request,
        ImportPropertiesFromCsv $importPropertiesFromCsv,
    ): RedirectResponse {
        try {
            $result = $importPropertiesFromCsv->handle($request->file('csv'));
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['csv' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['csv' => 'The CSV could not be imported.']);
        }

        return redirect()
            ->route('officer.properties.index')
            ->with('success', "Imported {$result['created']} created, {$result['updated']} updated.");
    }
}
