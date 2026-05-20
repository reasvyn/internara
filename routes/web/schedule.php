<?php

declare(strict_types=1);

use App\Domain\Schedule\Livewire\ScheduleIndex;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/schedules', ScheduleIndex::class)->name('schedules.index');
    });
