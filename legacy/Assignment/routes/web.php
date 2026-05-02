<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Assignment\Livewire\AssignmentIndex;
use Modules\Assignment\Livewire\AssignmentSubmission;
use Modules\Assignment\Livewire\AssignmentTypeIndex;

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
    Route::livewire('/assignments', AssignmentSubmission::class)->name('assignments.index');
});

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::livewire('/admin/assignments', AssignmentIndex::class)->name('admin.assignments.index');
    Route::livewire('/admin/assignments/types', AssignmentTypeIndex::class)->name(
        'admin.assignments.types',
    );
});
