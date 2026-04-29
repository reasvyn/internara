<?php

declare(strict_types=1);

use App\Livewire\Admin\Company\CompanyIndex;
use App\Livewire\Admin\Department\DepartmentIndex;
use App\Livewire\Admin\Internship\DirectPlacementManager;
use App\Livewire\Admin\Internship\InternshipIndex;
use App\Livewire\Admin\Internship\PlacementIndex;
use App\Livewire\Admin\School\SchoolProfile;
use App\Livewire\Dashboard\StudentDashboard;
use App\Livewire\Setup\SetupWizard;
use App\Livewire\Student\JournalManager;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/setup', SetupWizard::class)->name('setup');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/school', SchoolProfile::class)->name('school');
    Route::get('/departments', DepartmentIndex::class)->name('departments');
    Route::get('/companies', CompanyIndex::class)->name('companies');
    Route::get('/internships', InternshipIndex::class)->name('internships');
    Route::get('/internships/placements', PlacementIndex::class)->name('internships.placements');
    Route::get('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
});

Route::prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    Route::get('/journals', JournalManager::class)->name('journals');
});
