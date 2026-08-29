<?php

declare(strict_types=1);

use App\Modules\Partners\Domain\Company\Livewire\CompanyManager;
use App\Modules\Partners\Domain\Partnership\Livewire\PartnershipManager;

Route::prefix('admin')
    ->name('partners.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/companies', CompanyManager::class)->name('companies');
        Route::livewire('/companies/partnerships', PartnershipManager::class)->name('partnerships');
    });
