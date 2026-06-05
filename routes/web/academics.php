<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Livewire\AcademicYearManager;
use App\Academics\Department\Livewire\DepartmentManager;
use App\Academics\School\Livewire\SchoolEditor;

Route::prefix('admin')
    ->name('sysadmin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::get('/school', SchoolEditor::class)->name('school');
        Route::get('/departments', DepartmentManager::class)->name('departments');
        Route::get('/academic-years', AcademicYearManager::class)->name('academic-years');
    });
