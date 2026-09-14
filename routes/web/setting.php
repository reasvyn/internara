<?php

declare(strict_types=1);

use App\Modules\Setting\Livewire\SystemSetting;

Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settingss')
    ->middleware(['auth', 'role:super_admin|admin']);
