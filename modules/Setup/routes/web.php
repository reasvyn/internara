<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Setup\Http\Middleware\ProtectSetupRoute;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Livewire\SetupComplete;
use Modules\Setup\Livewire\SystemSetup;

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

Route::prefix('setup')
    ->middleware(ProtectSetupRoute::class)
    ->group(function () {
        Route::get('/', fn() => redirect()->route('setup.school'))->name('setup');
        Route::get('/school', SchoolSetup::class)->name('setup.school');
        Route::get('/account', AccountSetup::class)->name('setup.account');
        Route::get('/system', SystemSetup::class)->name('setup.system');
        Route::get('/department', DepartmentSetup::class)->name('setup.department');
        Route::get('/internship', InternshipSetup::class)->name('setup.internship');
        Route::get('/complete', SetupComplete::class)->name('setup.complete');
    });
