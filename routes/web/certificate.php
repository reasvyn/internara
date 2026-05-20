<?php

declare(strict_types=1);

use App\Domain\Certificate\Http\Controllers\CertificateDownloadController;
use App\Domain\Certificate\Livewire\CertificateList;
use App\Domain\Certificate\Livewire\CertificateTemplateManager;

Route::middleware('auth')->group(function () {
    Route::get('/certificates/{certificate}/download', CertificateDownloadController::class)
        ->name('certificates.download');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/certificates/templates', CertificateTemplateManager::class)->name('certificates.templates');
        Route::livewire('/certificates', CertificateList::class)->name('certificates');
    });
