<?php

declare(strict_types=1);

use App\Domain\Logbook\Livewire\LogbookManager;

Route::livewire('/admin/logbook', LogbookManager::class)
    ->name('admin.logbook')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);
