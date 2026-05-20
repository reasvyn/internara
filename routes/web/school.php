<?php

declare(strict_types=1);

use App\Domain\School\Livewire\AcademicYearIndex;
use App\Domain\School\Livewire\DepartmentManager;
use App\Domain\School\Livewire\SchoolEditor;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/school', SchoolEditor::class)->name('school');
        Route::livewire('/departments', DepartmentManager::class)->name('departments');
        Route::livewire('/academic-years', AcademicYearIndex::class)->name('academic-years.index');
    });
