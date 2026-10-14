<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Events\ListEventFinances;
use App\Actions\Events\RecordEventRefund;
use App\Actions\Events\ResolveEventCancellation;
use App\Actions\Events\ReviewEventPayment;
use App\Enums\EventPermission;
use App\Models\EventCancellationRequest;
use App\Models\EventPaymentRecord;
use App\Support\PlatformAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventFinancialController
{
    public function index(Request $request, ListEventFinances $finances): Response
    {
        return Inertia::render('officer/event-finances/Index', [
            'registrations' => $finances->handle($request->user()),
            'canReviewPayments' => PlatformAccess::allowsStaff($request->user(), EventPermission::PaymentsReview),
            'canReviewRefunds' => PlatformAccess::allowsStaff($request->user(), EventPermission::RefundsReview),
        ]);
    }

    public function review(Request $request, EventPaymentRecord $payment, ReviewEventPayment $review): RedirectResponse
    {
        $review->handle($request->user(), $payment, $request->all());

        return back()->with('success', 'Payment decision saved. The account owner has been notified.');
    }

    public function resolve(Request $request, EventCancellationRequest $cancellation, ResolveEventCancellation $resolve): RedirectResponse
    {
        $resolve->handle($request->user(), $cancellation, $request->all());

        return back()->with('success', 'Cancellation decision saved. Refunds must be recorded separately.');
    }

    public function refund(Request $request, EventPaymentRecord $payment, RecordEventRefund $refund): RedirectResponse
    {
        $refund->handle($request->user(), $payment, $request->all());

        return back()->with('success', 'External refund recorded. The original payment has been preserved.');
    }
}
