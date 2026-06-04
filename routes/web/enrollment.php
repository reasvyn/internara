<?php

declare(strict_types=1);

use App\Domain\Enrollment\Livewire\ApplyPage;
use App\Domain\Enrollment\Livewire\DirectPlacementManager;
use App\Domain\Enrollment\Livewire\PlacementChangeManager;
use App\Domain\Enrollment\Livewire\PlacementIndex;
use App\Domain\Enrollment\Livewire\RegistrationCenter;
use App\Domain\Enrollment\Livewire\RegistrationDocumentUpload;
use App\Domain\Enrollment\Livewire\RegistrationVerification;
use App\Domain\Enrollment\Livewire\RegistrationWizard;
use App\Domain\Enrollment\Livewire\StudentPlacementChangeRequest;

Route::middleware('guest')->group(function () {
    Route::livewire('/apply', ApplyPage::class)->name('apply');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
    Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
    Route::livewire('/registration/documents', RegistrationDocumentUpload::class)->name('registration.documents');
});

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/internships/placement-change', StudentPlacementChangeRequest::class)->name('internships.placement-change');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/internships/placements', PlacementIndex::class)->name('internships.placements');
        Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
        Route::livewire('/internships/placements/changes', PlacementChangeManager::class)->name('internships.placements.changes');
    });
