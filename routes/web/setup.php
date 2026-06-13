<?php

declare(strict_types=1);

use App\Setup\Http\Controllers\SetupController;
use App\Setup\SetupWizard\Livewire\SetupWizard;
use Illuminate\Support\Facades\Route;

Route::middleware('setup.protected')->group(function () {
    Route::get('/setup', SetupWizard::class)->name('setup');
    Route::post('/setup', [SetupController::class, 'redirect']);
});

Route::post('/setup/cleanup', [SetupController::class, 'cleanup'])->name('setup.cleanup');
