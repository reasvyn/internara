<?php

declare(strict_types=1);

use App\Settings\Livewire\SystemSetting;

Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settings')
    ->middleware(['auth', 'role:superadmin']);
