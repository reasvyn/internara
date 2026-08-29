<?php

declare(strict_types=1);

use App\Modules\Settings\Livewire\SystemSetting;

Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settings')
    ->middleware(['auth', 'role:super_admin|admin']);
