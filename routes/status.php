<?php

use App\Http\Controllers\StatusPage\HistoryController;
use Cachet\Cachet;
use Illuminate\Support\Facades\Route;

Route::middleware(config('cachet.middleware', []))
    ->domain(config('cachet.domain'))
    ->prefix(Cachet::path())
    ->as('cachet.')
    ->group(function (): void {
        Route::get('/history', [HistoryController::class, 'index'])->name('status-page.history');
    });
