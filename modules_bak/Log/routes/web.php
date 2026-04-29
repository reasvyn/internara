<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Log\Livewire\ActivityFeed;

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

Route::middleware(['auth', 'verified', 'role:admin|super-admin'])->group(function () {
    Route::get('/admin/activities', ActivityFeed::class)->name('admin.activities');
});
