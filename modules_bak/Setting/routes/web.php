<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Setting\Livewire\SystemSetting;

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin/settings', SystemSetting::class)->name('admin.settings');
});
