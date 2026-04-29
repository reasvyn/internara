<?php

declare(strict_types=1);

use App\Livewire\Admin\Department\DepartmentIndex;
use App\Livewire\Admin\School\SchoolProfile;
use App\Livewire\Setup\SetupWizard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/setup', SetupWizard::class)->name('setup');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/school', SchoolProfile::class)->name('school');
    Route::get('/departments', DepartmentIndex::class)->name('departments');
});
