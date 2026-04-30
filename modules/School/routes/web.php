<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\School\Livewire\SchoolManager;

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

Route::middleware(['auth', 'verified', 'can:school.manage'])->group(function () {
    Route::get('/school/settings', SchoolManager::class)->name('school.settings');
});
