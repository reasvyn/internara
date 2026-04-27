<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Internship\Livewire\InternshipManager;
use Modules\Internship\Livewire\PlacementManager;

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
    Route::get('/internships', \Modules\Internship\Livewire\InternshipIndex::class)
        ->middleware('can:internship.view')
        ->name('internship.index');

    Route::get('/internships/placements', \Modules\Internship\Livewire\InternshipPlacementIndex::class)
        ->middleware('can:internship.update')
        ->name('internship.placement.index');

    Route::get(
        '/internships/student-placement',
        \Modules\Internship\Livewire\StudentPlacementIndex::class,
    )
        ->middleware('can:internship.manage')
        ->name('internship.student-placement.index');

    Route::get(
        '/internships/register',
        \Modules\Internship\Livewire\InternshipRegistrationManager::class,
    )
        ->middleware('role:student')
        ->name('internship.registration.student');

    // Legacy routes for backward compatibility
    Route::get(
        '/internships/registrations',
        \Modules\Internship\Livewire\RegistrationIndex::class,
    )
        ->middleware('can:internship.manage')
        ->name('internship.registration.index');

    Route::get('/internships/bulk-placement', \Modules\Internship\Livewire\InternshipRegistrationManager::class)
        ->middleware('can:internship.manage')
        ->name('internship.bulk-placement.index');

    Route::get('/internships/requirements', \Modules\Internship\Livewire\RequirementIndex::class)
        ->middleware('can:internship.update')
        ->name('internship.requirement.index');

    Route::get('/internships/companies', \Modules\Internship\Livewire\CompanyIndex::class)
        ->middleware('can:internship.manage')
        ->name('internship.company.index');
});
