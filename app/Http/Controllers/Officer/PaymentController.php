<?php

namespace App\Http\Controllers\Officer;

use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\CreateAndConfirmPayment;
use App\Actions\Payments\ListOfficerPaymentsPage;
use App\Actions\Payments\RejectPayment;
use App\Actions\Payments\VoidPayment;
use App\Http\Requests\Officer\ConfirmPaymentRequest;
use App\Http\Requests\Officer\RejectPaymentRequest;
use App\Http\Requests\Officer\StoreConfirmedPaymentRequest;
use App\Http\Requests\Officer\VoidPaymentRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController
{
    public function index(Request $request, ListOfficerPaymentsPage $listOfficerPaymentsPage): Response
    {
        $payload = $listOfficerPaymentsPage->handle(
            search: $request->string('search')->toString() ?: null,
            status: $request->string('status')->toString() ?: null,
        );

        return Inertia::render('officer/payments/Index', $payload);
    }

    public function create(): Response
    {
        $properties = Property::query()
            ->orderBy('block')
            ->orderBy('lot')
            ->get()
            ->map(fn (Property $property): array => [
                'id' => $property->id,
                'label' => 'Block '.$property->block.' · Lot '.$property->lot,
            ])
            ->values()
            ->all();

        return Inertia::render('officer/payments/Create', [
            'properties' => $properties,
        ]);
    }

    public function store(
        StoreConfirmedPaymentRequest $request,
        CreateAndConfirmPayment $createAndConfirmPayment,
    ): RedirectResponse {
        $officer = $request->user();
        assert($officer !== null);

        /** @var array{property_id: int, amount: string|float|int, method: string, reference?: string|null, screenshot?: UploadedFile|null} $validated */
        $validated = $request->validated();

        $createAndConfirmPayment->handle($officer, $validated);

        FlashToast::success('Payment recorded and confirmed.');

        return redirect()->route('officer.payments.index');
    }

    public function confirm(
        ConfirmPaymentRequest $request,
        Payment $payment,
        ConfirmPayment $confirmPayment,
    ): RedirectResponse {
        $officer = $request->user();
        assert($officer !== null);

        $confirmPayment->handle($payment, $officer);

        FlashToast::success('Payment confirmed and allocated.');

        return redirect()->route('officer.payments.index');
    }

    public function reject(
        RejectPaymentRequest $request,
        Payment $payment,
        RejectPayment $rejectPayment,
    ): RedirectResponse {
        $officer = $request->user();
        assert($officer !== null);

        $rejectPayment->handle($payment, $officer, $request->validated('rejection_reason'));

        FlashToast::success('Payment rejected.');

        return redirect()->route('officer.payments.index');
    }

    public function void(
        VoidPaymentRequest $request,
        Payment $payment,
        VoidPayment $voidPayment,
    ): RedirectResponse {
        $officer = $request->user();
        assert($officer !== null);

        $voidPayment->handle($payment, $officer, $request->validated('void_reason'));

        FlashToast::success('Payment voided. Allocation reversed.');

        return redirect()->route('officer.payments.index');
    }
}
