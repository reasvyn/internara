<?php

declare(strict_types=1);

use App\Domain\Setup\Livewire\SetupWizard;
use Illuminate\Support\Facades\Session;

Route::middleware('setup.protected')->group(function () {
    Route::livewire('/setup', SetupWizard::class)->name('setup');
    Route::post('/setup', fn () => redirect()->route('setup'));
});

Route::post('/setup/cleanup', function () {
    Session::forget(['setup.form_data', 'setup.authorized']);

    return response()->noContent();
})->name('setup.cleanup');
