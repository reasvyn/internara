<?php

declare(strict_types=1);

use App\Domain\Setup\Http\Controllers\HomeController;
use App\Domain\User\Http\Controllers\DashboardController;

Route::get('/', HomeController::class)->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
