<?php

namespace App\Support;

use Inertia\Inertia;

class FlashToast
{
    public static function success(string $message): void
    {
        self::flash('success', $message);
    }

    public static function info(string $message): void
    {
        self::flash('info', $message);
    }

    public static function warning(string $message): void
    {
        self::flash('warning', $message);
    }

    public static function error(string $message): void
    {
        self::flash('error', $message);
    }

    /**
     * @param  'success'|'info'|'warning'|'error'  $type
     */
    private static function flash(string $type, string $message): void
    {
        Inertia::flash('toast', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}
