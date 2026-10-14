<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class EventTicketIdentity
{
    public function fromScan(string $value): string
    {
        $value = trim($value);
        if (preg_match('/\A[A-Za-z0-9]{64}\z/', $value) === 1) {
            return $value;
        }
        $parts = parse_url($value);
        if (is_array($parts) && isset($parts['path'], $parts['host'], $parts['scheme']) && in_array($parts['scheme'], ['http', 'https'], true)
            && ! isset($parts['user']) && ! isset($parts['pass']) && ! isset($parts['query']) && ! isset($parts['fragment'])) {
            $token = basename($parts['path']);
            if (preg_match('/\A[A-Za-z0-9]{64}\z/', $token) === 1) {
                $expected = parse_url(route('event-tickets.show', ['attendee' => $token]));
                if (is_array($expected) && ($parts['path'] === ($expected['path'] ?? null))
                    && strcasecmp($parts['host'], $expected['host'] ?? '') === 0 && ($parts['port'] ?? null) === ($expected['port'] ?? null)) {
                    return $token;
                }
            }
        }
        throw ValidationException::withMessages(['ticket' => 'Scan a ticket from this platform, or find the attendee by name below.']);
    }
}
