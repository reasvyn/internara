<?php

declare(strict_types=1);

use App\Domain\Certification\Aggregates\Certificate\Http\Controllers\CertificateDownloadController;
use App\Domain\Certification\Aggregates\Document\Http\Controllers\DocumentRenderController;
use App\Domain\Certification\Aggregates\Certificate\Livewire\CertificateList;
use App\Domain\Certification\Aggregates\Certificate\Livewire\CertificateTemplateManager;
use App\Domain\Certification\Aggregates\Document\Livewire\ReportsManager;
use App\Domain\Reports\Aggregates\Report\Http\Controllers\ReportController;

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

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::livewire('/', ReportsManager::class)->name('index');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
        });

        Route::get('/documents/{document}/render/{registration}', [DocumentRenderController::class, 'show'])->name('documents.render');
        Route::get('/documents/{document}/render/{registration}/save', [DocumentRenderController::class, 'store'])->name('documents.render.store');
    });
