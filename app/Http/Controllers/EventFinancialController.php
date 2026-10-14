<?php

namespace App\Http\Controllers;

use App\Actions\Events\RequestEventCancellation;
use App\Actions\Events\SubmitEventPayment;
use App\Models\EventPaymentRecord;
use App\Models\EventRegistration;
use App\Support\EventFinancialAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventFinancialController
{
    public function submit(Request $request, EventRegistration $registration, SubmitEventPayment $submit): RedirectResponse
    {
        $submit->handle($request->user(), $registration, $request->all());

        return back()->with('success', 'Payment submitted. Your places remain reserved while staff review it.');
    }

    public function requestCancellation(Request $request, EventRegistration $registration, RequestEventCancellation $cancel): RedirectResponse
    {
        $cancel->handle($request->user(), $registration, $request->all());

        return back()->with('success', 'Cancellation requested. Staff will review the whole registration.');
    }

    public function receipt(Request $request, EventPaymentRecord $payment, EventFinancialAccess $access): BinaryFileResponse
    {
        $access->ensureCanRead($payment->registration, $request->user());
        abort_unless($payment->receipt_path !== null && Storage::disk('local')->exists($payment->receipt_path), 404);

        return response()->file(Storage::disk('local')->path($payment->receipt_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])->setPrivate();
    }
}
