<?php

declare(strict_types=1);

use App\Domain\Certification\Aggregates\Certificate\Http\Controllers\CertificateDownloadController;
use App\Domain\Certification\Aggregates\Certificate\Livewire\CertificateList;
use App\Domain\Certification\Aggregates\Certificate\Livewire\CertificateTemplateManager;
use App\Domain\Certification\Aggregates\Certificate\Livewire\StudentCertificates;

Route::middleware('auth')->group(function () {
    Route::get('/certificates/{certificate}/download', CertificateDownloadController::class)
        ->name('certificates.download');
});

Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/certificates', StudentCertificates::class)->name('certificates');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/certificates/templates', CertificateTemplateManager::class)->name('certificates.templates');
        Route::livewire('/certificates', CertificateList::class)->name('certificates');
    });
