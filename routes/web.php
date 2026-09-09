<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    if (! app()->isProduction()) {
        Route::inertia(
            'prototype/officer-unpaid-roster',
            'prototype/OfficerUnpaidRoster',
        )->name('prototype.officer-unpaid-roster');
    }
});

require __DIR__.'/settings.php';
