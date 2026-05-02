<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Access management routes with permission-based access control.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\Permission\Livewire\AccessManager;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::livewire('/access', AccessManager::class)
        ->name('access.manager')
        ->middleware('can:user.manage');
});
