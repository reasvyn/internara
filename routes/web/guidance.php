<?php

declare(strict_types=1);

use App\Domain\Guidance\Livewire\HandbookIndex;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks.index');
    });
