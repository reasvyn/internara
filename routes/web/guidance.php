<?php

declare(strict_types=1);

use App\Guidance\Handbook\Livewire\HandbookIndex;
use App\Guidance\Handbook\Livewire\HandbookManager;
use App\Guidance\Mentor\Livewire\AssessInternship;
use App\Guidance\Mentor\Livewire\EvaluateMentor;
use App\Guidance\Mentor\Livewire\MentorProfileManager;
use App\Guidance\Mentor\Livewire\ReportNotes;
use App\Guidance\Mentor\Livewire\ReportReview;
use App\Guidance\SupervisionLog\Livewire\SupervisionManager;
use App\Guidance\SupervisionLog\Livewire\SupervisorLogManager;

// Student routes (previously in mentee.php)
Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/supervision', SupervisionManager::class)->name('supervision');
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
        Route::livewire('/mentors/evaluate', EvaluateMentor::class)->name('mentors.evaluate');
    });

// Supervision & Teacher/Supervisor routes (previously in mentor.php)
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|supervisor'])
    ->group(function () {
        Route::livewire('/logs', SupervisorLogManager::class)->name('logs');
    });

Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::livewire('/assess-internship', AssessInternship::class)->name('assess-internship');
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
    });

Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::livewire('/reports/notes', ReportNotes::class)->name('reports.notes');
        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks');
    });

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/handbooks', HandbookManager::class)->name('handbooks.index');
        Route::livewire('/internships/reports/review', ReportReview::class)->name('reports.review');
        Route::livewire('/mentors/profiles', MentorProfileManager::class)->name('mentors.profiles');
    });
