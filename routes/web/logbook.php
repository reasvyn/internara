<?php

declare(strict_types=1);

use App\Domain\Logbook\Actions\CompileLogbookReportAction;
use App\Domain\Logbook\Livewire\IndustryAssessmentForm;
use App\Domain\Logbook\Livewire\LogbookManager;
use App\Domain\Registration\Models\Registration;

Route::livewire('/admin/logbook', LogbookManager::class)
    ->name('admin.logbook')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);

Route::livewire('/supervisor/logbook/assessment', IndustryAssessmentForm::class)
    ->name('supervisor.logbook.assessment')
    ->middleware(['auth', 'role:supervisor']);

Route::get('/admin/logbook/report/{registration}', function (Registration $registration) {
    return app(CompileLogbookReportAction::class)->download($registration);
})
    ->name('admin.logbook.report')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);
