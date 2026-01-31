<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Assignment\Livewire\AssignmentSubmission;

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

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {
    Route::get('/assignments', AssignmentSubmission::class)->name('assignments.index');
});

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin/assignments', \Modules\Assignment\Livewire\AssignmentManager::class)->name(
        'admin.assignments.index',
    );
    Route::get('/admin/assignments/types', \Modules\Assignment\Livewire\AssignmentTypeManager::class)->name(
        'admin.assignments.types',
    );
});
