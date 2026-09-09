<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    if (! app()->isProduction()) {
        Route::inertia(
            'prototype/printable-bill',
            'prototype/PrintableBill',
        )->name('prototype.printable-bill');
    }
});

require __DIR__.'/settings.php';
