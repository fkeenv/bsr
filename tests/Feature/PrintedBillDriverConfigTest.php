<?php

use App\Support\LaravelPdfDriver;

test('non-production environments default to the local-safe dompdf driver', function () {
    expect(LaravelPdfDriver::default('local'))->toBe('dompdf')
        ->and(LaravelPdfDriver::default('testing'))->toBe('dompdf');
});

test('production defaults to the Cloudflare PDF driver', function () {
    expect(LaravelPdfDriver::default('production'))->toBe('cloudflare');
});

test('application config uses the local-safe driver outside production', function () {
    expect(config('laravel-pdf.driver'))->toBe('dompdf');
});
