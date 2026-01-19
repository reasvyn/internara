<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Admin\Livewire\Dashboard;

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin', Dashboard::class)->name('admin.dashboard');
});
