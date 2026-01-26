<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

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
    ->middleware(Modules\Setup\Http\Middleware\ProtectSetupRoute::class)
    ->group(function () {
        Route::get('/', fn () => redirect()->route('setup.welcome'))->name('setup');
        Route::get('/welcome', Modules\Setup\Livewire\SetupWelcome::class)->name('setup.welcome');
        Route::get('/account', Modules\Setup\Livewire\AccountSetup::class)->name('setup.account');
        Route::get('/school', Modules\Setup\Livewire\SchoolSetup::class)->name('setup.school');
        Route::get('/system', Modules\Setup\Livewire\SystemSetup::class)->name('setup.system');
        Route::get('/department', Modules\Setup\Livewire\DepartmentSetup::class)->name(
            'setup.department',
        );
        Route::get('/internship', Modules\Setup\Livewire\InternshipSetup::class)->name(
            'setup.internship',
        );
        Route::get('/complete', Modules\Setup\Livewire\SetupComplete::class)->name(
            'setup.complete',
        );
    });
