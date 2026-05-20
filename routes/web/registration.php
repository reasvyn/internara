<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\ApplicationReview;
use App\Domain\Registration\Livewire\AccountApplicationForm;
use App\Domain\Registration\Livewire\RegistrationVerification;

Route::middleware('guest')->group(function () {
    Route::livewire('/apply', AccountApplicationForm::class)->name('apply');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
        Route::livewire('/applications', ApplicationReview::class)->name('applications');
    });
