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
    Route::get('/internships', InternshipManager::class)
        ->middleware('can:internship.view')
        ->name('internship.index');

    Route::get('/internships/placements', PlacementManager::class)
        ->middleware('can:internship.update')
        ->name('internship.placement.index');

    Route::get(
        '/internships/registrations',
        \Modules\Internship\Livewire\RegistrationManager::class,
    )
        ->middleware('can:internship.update')
        ->name('internship.registration.index');

    Route::get('/internships/requirements', \Modules\Internship\Livewire\RequirementManager::class)
        ->middleware('can:internship.update')
        ->name('internship.requirement.index');

    Route::get('/internships/deliverables', \Modules\Internship\Livewire\DeliverableSubmission::class)
        ->name('internship.deliverables');
});
