<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Internship\Livewire\CompanyIndex;
use Modules\Internship\Livewire\InternshipIndex;
use Modules\Internship\Livewire\InternshipPlacementIndex;
use Modules\Internship\Livewire\InternshipRegistrationManager;
use Modules\Internship\Livewire\RegistrationIndex;
use Modules\Internship\Livewire\RequirementIndex;
use Modules\Internship\Livewire\StudentPlacementIndex;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/internships', InternshipIndex::class)
        ->middleware('can:internship.view')
        ->name('internship.index');

    Route::get('/internships/placements', InternshipPlacementIndex::class)
        ->middleware('can:internship.update')
        ->name('internship.placement.index');

    Route::get('/internships/student-placement', StudentPlacementIndex::class)
        ->middleware('can:internship.manage')
        ->name('internship.student-placement.index');

    Route::get('/internships/register', InternshipRegistrationManager::class)
        ->middleware('role:student')
        ->name('internship.registration.student');

    // Legacy routes for backward compatibility
    Route::get('/internships/registrations', RegistrationIndex::class)
        ->middleware('can:internship.manage')
        ->name('internship.registration.index');

    Route::get('/internships/bulk-placement', InternshipRegistrationManager::class)
        ->middleware('can:internship.manage')
        ->name('internship.bulk-placement.index');

    Route::get('/internships/requirements', RequirementIndex::class)
        ->middleware('can:internship.update')
        ->name('internship.requirement.index');

    Route::get('/internships/companies', CompanyIndex::class)
        ->middleware('can:internship.manage')
        ->name('internship.company.index');
});
