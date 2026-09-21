<?php

namespace App\Support;

class LaravelPdfDriver
{
    public static function default(?string $environment = null): string
    {
        $environment ??= (string) (getenv('APP_ENV') ?: 'production');

        return $environment === 'production' ? 'cloudflare' : 'dompdf';
    }
}
