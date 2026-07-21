<?php

declare(strict_types=1);

use App\Guidance\MonitoringVisit\Livewire\StudentVisitList;
use App\Guidance\MonitoringVisit\Livewire\VisitManager;
use App\Guidance\SupervisionLog\Livewire\StudentLogManager;
use App\Guidance\SupervisionLog\Livewire\SupervisorReviewManager;

// Teacher/admin visit routes
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|super_admin|admin'])
    ->group(function () {
        Route::get('/visits', VisitManager::class)->name('visits');
    });

// Student visit routes (read-only)
Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::get('/visits', StudentVisitList::class)->name('visits');
    });

// Student supervision log routes
Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::get('/supervision-logs', StudentLogManager::class)->name('supervision-logs');
    });

// Supervisor review routes
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::get('/logs', SupervisorReviewManager::class)->name('logs');
    });
