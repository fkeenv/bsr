<?php

namespace App\Actions\Payments;

use App\Actions\Concerns\NormalizesMoneyAmount;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DeclarePayment
{
    use NormalizesMoneyAmount;

    /**
     * @param  array{property_id: int, amount: string|float|int, method: string, reference?: string|null, screenshot: UploadedFile}  $data
     */
    public function handle(User $member, array $data): Payment
    {
        $propertyId = (int) $data['property_id'];

        $hasLiveMembership = $member->memberships()
            ->live()
            ->where('property_id', $propertyId)
            ->exists();

        if (! $hasLiveMembership) {
            abort(403);
        }

        $amount = $this->normalizeMoneyAmount($data['amount'], 'Payment amount');

        if ((float) $amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than ₱0.',
            ]);
        }

        $method = PaymentMethod::from($data['method']);
        $screenshot = $data['screenshot'];
        $path = $screenshot->store('payment-receipts', 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'screenshot' => 'The receipt screenshot could not be stored.',
            ]);
        }

        return Payment::query()->create([
            'property_id' => $propertyId,
            'declared_by_user_id' => $member->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $data['reference'] ?? null,
            'screenshot_path' => $path,
            'status' => PaymentStatus::Pending,
        ]);
    }
}
