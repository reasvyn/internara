<?php

declare(strict_types=1);

use App\Enrollment\AccountApplication\Livewire\ApplyPage;
use App\Enrollment\Placement\Livewire\DirectPlacementManager;
use App\Enrollment\Placement\Livewire\PlacementChangeManager;
use App\Enrollment\Placement\Livewire\PlacementIndex;
use App\Enrollment\Placement\Livewire\StudentPlacementChangeRequest;
use App\Enrollment\Registration\Livewire\RegistrationCenter;
use App\Enrollment\Registration\Livewire\RegistrationDocumentUpload;
use App\Enrollment\Registration\Livewire\RegistrationVerification;
use App\Enrollment\Registration\Livewire\RegistrationWizard;

Route::middleware('guest')->group(function () {
    Route::livewire('/apply', ApplyPage::class)->name('apply');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/registration', RegistrationCenter::class)->name('registration.center');
    Route::livewire('/register', RegistrationWizard::class)->name('registration.wizard');
    Route::livewire('/registration/documents', RegistrationDocumentUpload::class)->name(
        'registration.documents',
    );
});

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire(
            '/internships/placement-change',
            StudentPlacementChangeRequest::class,
        )->name('internships.placement-change');
    });

Route::prefix('admin')
    ->name('enrollment.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire(
            '/internships/registrations/pending',
            RegistrationVerification::class,
        )->name('internships.registrations.pending');
        Route::livewire('/internships/placements', PlacementIndex::class)->name(
            'internships.placements',
        );
        Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name(
            'internships.placements.direct',
        );
        Route::livewire('/internships/placements/changes', PlacementChangeManager::class)->name(
            'internships.placements.changes',
        );
    });
