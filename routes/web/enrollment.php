<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\AccountApplication\Livewire\ApplyPage;
use App\Modules\Enrollment\Domain\Placement\Livewire\DirectPlacementManager;
use App\Modules\Enrollment\Domain\Placement\Livewire\PlacementChangeManager;
use App\Modules\Enrollment\Domain\Placement\Livewire\PlacementIndex;
use App\Modules\Enrollment\Domain\Placement\Livewire\StudentPlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationCenter;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationDocumentUpload;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationVerification;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationWizard;

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
