<?php

declare(strict_types=1);

use App\Domain\Attendance\Livewire\AttendanceManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/attendance', AttendanceManager::class)->name('attendance');
    });
