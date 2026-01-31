<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Guidance\Http\Controllers\HandbookDownloadController;
use Modules\Guidance\Livewire\ManageHandbook;

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
    Route::get('/guidance/manage', ManageHandbook::class)
        ->middleware('can:guidance.view')
        ->name('guidance.index');

    Route::get('/guidance/download/{handbook}', HandbookDownloadController::class)->name(
        'guidance.download',
    );
});
