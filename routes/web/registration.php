<?php

declare(strict_types=1);

use App\Domain\Admin\Livewire\ApplicationReview;
use App\Domain\Registration\Livewire\ApplyPage;
use App\Domain\Registration\Livewire\RegistrationCenter;
use App\Domain\Registration\Livewire\RegistrationDocumentUpload;
use App\Domain\Registration\Livewire\RegistrationVerification;
use App\Domain\Registration\Livewire\RegistrationWizard;

Route::middleware('guest')->group(function () {
    Route::livewire('/apply', ApplyPage::class)->name('apply');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
    Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
    Route::livewire('/registration/documents', RegistrationDocumentUpload::class)->name('registration.documents');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
        Route::livewire('/applications', ApplicationReview::class)->name('applications');
    });
