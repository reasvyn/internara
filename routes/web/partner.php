<?php

declare(strict_types=1);

use App\Modules\Partner\Domain\Company\Livewire\CompanyManager;

Route::prefix('admin')
    ->name('partner.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/companies', CompanyManager::class)->name('companies');
    });
