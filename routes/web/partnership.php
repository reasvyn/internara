<?php

declare(strict_types=1);

use App\Domain\Partnership\Livewire\CompanyManager;
use App\Domain\Partnership\Livewire\PartnershipManager;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/companies', CompanyManager::class)->name('companies');
        Route::livewire('/companies/partnerships', PartnershipManager::class)->name('partnerships');
    });
