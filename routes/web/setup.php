<?php

declare(strict_types=1);

use App\Domain\Setup\Livewire\SetupWizard;

Route::middleware('setup.protected')->group(function () {
    Route::livewire('/setup', SetupWizard::class)->name('setup');
});
