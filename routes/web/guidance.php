<?php

declare(strict_types=1);

use App\Guidance\SupervisionLog\Livewire\SupervisionManager;
use App\Guidance\SupervisionLog\Livewire\SupervisorLogManager;

// Student routes (previously in mentee.php)
Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/supervision', SupervisionManager::class)->name('supervision');
    });

// Supervision & Teacher/Supervisor routes
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|supervisor'])
    ->group(function () {
        Route::livewire('/logs', SupervisorLogManager::class)->name('logs');
    });
