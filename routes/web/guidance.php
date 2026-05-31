<?php

declare(strict_types=1);

use App\Domain\Guidance\Livewire\HandbookIndex;
use App\Domain\Guidance\Livewire\HandbookManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/handbooks', HandbookManager::class)->name('handbooks.index');
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
    });

Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
    });
