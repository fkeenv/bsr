<?php

namespace App\Actions\Settings;

use App\Models\AssociationSetting;
use Illuminate\Validation\ValidationException;

class UpdatePrintedBillSettings
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): AssociationSetting
    {
        $name = $data['letterhead_name'] ?? null;
        $shortName = $data['letterhead_short_name'] ?? null;
        $addressLines = $data['letterhead_address_lines'] ?? null;
        $contact = $data['letterhead_contact'] ?? null;
        $treasurer = $data['letterhead_treasurer'] ?? null;
        $channels = $data['payment_channels'] ?? null;

        if (! is_string($name) || trim($name) === '') {
            throw ValidationException::withMessages([
                'letterhead_name' => 'The association name is required.',
            ]);
        }

        if (! is_string($shortName) || trim($shortName) === '') {
            throw ValidationException::withMessages([
                'letterhead_short_name' => 'The short name is required.',
            ]);
        }

        if (! is_array($addressLines) || $addressLines === []) {
            throw ValidationException::withMessages([
                'letterhead_address_lines' => 'At least one address line is required.',
            ]);
        }

        /** @var list<string> $normalizedAddressLines */
        $normalizedAddressLines = [];

        foreach ($addressLines as $line) {
            if (! is_string($line) || trim($line) === '') {
                throw ValidationException::withMessages([
                    'letterhead_address_lines' => 'Address lines must be non-empty text.',
                ]);
            }

            $normalizedAddressLines[] = trim($line);
        }

        if (! is_string($contact) || trim($contact) === '') {
            throw ValidationException::withMessages([
                'letterhead_contact' => 'The contact line is required.',
            ]);
        }

        if (! is_string($treasurer) || trim($treasurer) === '') {
            throw ValidationException::withMessages([
                'letterhead_treasurer' => 'The treasurer line is required.',
            ]);
        }

        if (! is_array($channels) || $channels === []) {
            throw ValidationException::withMessages([
                'payment_channels' => 'At least one how-to-pay channel is required.',
            ]);
        }

        /** @var list<array{method: string, detail: string}> $normalizedChannels */
        $normalizedChannels = [];

        foreach ($channels as $channel) {
            if (! is_array($channel)) {
                throw ValidationException::withMessages([
                    'payment_channels' => 'Payment channels are invalid.',
                ]);
            }

            $method = $channel['method'] ?? null;
            $detail = $channel['detail'] ?? null;

            if (! is_string($method) || trim($method) === '' || ! is_string($detail) || trim($detail) === '') {
                throw ValidationException::withMessages([
                    'payment_channels' => 'Each payment channel needs a method and detail.',
                ]);
            }

            $normalizedChannels[] = [
                'method' => trim($method),
                'detail' => trim($detail),
            ];
        }

        $settings = AssociationSetting::current();
        $settings->update([
            'letterhead_name' => trim($name),
            'letterhead_short_name' => trim($shortName),
            'letterhead_address_lines' => $normalizedAddressLines,
            'letterhead_contact' => trim($contact),
            'letterhead_treasurer' => trim($treasurer),
            'payment_channels' => $normalizedChannels,
        ]);

        return $settings->refresh();
    }
}
