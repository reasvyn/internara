<?php

declare(strict_types=1);

use App\Domain\Partners\Aggregates\Company\Livewire\CompanyManager;
use App\Domain\Partners\Aggregates\Partnership\Livewire\PartnershipManager;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/companies', CompanyManager::class)->name('companies');
        Route::livewire('/companies/partnerships', PartnershipManager::class)->name('partnerships');
    });
