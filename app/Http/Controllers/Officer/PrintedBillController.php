<?php

namespace App\Http\Controllers\Officer;

use App\Actions\PrintedBills\RenderPrintedBillsPdf;
use App\Http\Requests\Officer\GeneratePrintedBillRequest;
use App\Models\Property;
use App\Support\BillingPeriod;
use Spatie\LaravelPdf\PdfBuilder;

class PrintedBillController
{
    public function show(
        GeneratePrintedBillRequest $request,
        Property $property,
        RenderPrintedBillsPdf $renderPrintedBillsPdf,
    ): PdfBuilder {
        $period = new BillingPeriod(
            (int) $request->validated('year'),
            (int) $request->validated('month'),
        );

        return $renderPrintedBillsPdf->forProperty($property, $period);
    }

    public function batch(
        GeneratePrintedBillRequest $request,
        RenderPrintedBillsPdf $renderPrintedBillsPdf,
    ): PdfBuilder {
        $period = new BillingPeriod(
            (int) $request->validated('year'),
            (int) $request->validated('month'),
        );

        return $renderPrintedBillsPdf->forUnpaidProperties($period);
    }
}
