<?php

namespace App\Http\Controllers;

use App\Actions\Payments\DeclarePayment;
use App\Http\Requests\StorePaymentDeclarationRequest;
use App\Support\FlashToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class PaymentController
{
    public function store(
        StorePaymentDeclarationRequest $request,
        DeclarePayment $declarePayment,
    ): RedirectResponse {
        $user = $request->user();
        assert($user !== null);

        /** @var array{property_id: int, amount: string|float|int, method: string, reference?: string|null, screenshot: UploadedFile} $validated */
        $validated = $request->validated();

        $declarePayment->handle($user, $validated);

        FlashToast::success('Payment declared. An Officer will confirm it.');

        return back();
    }
}
