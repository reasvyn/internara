<?php

declare(strict_types=1);

use App\Domain\Academics\Aggregates\AcademicYear\Livewire\AcademicYearManager;
use App\Domain\Academics\Aggregates\Department\Livewire\DepartmentManager;
use App\Domain\Academics\Aggregates\School\Livewire\SchoolEditor;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/school', SchoolEditor::class)->name('school');
        Route::get('/departments', DepartmentManager::class)->name('departments');
        Route::get('/academic-years', AcademicYearManager::class)->name('academic-years');
    });
