<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Setup\Http\Middleware\ProtectSetupRoute;
use Modules\Setup\Livewire\SetupWelcome;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Livewire\SetupComplete;

/**
 * Setup Routes
 *
 * [S1 - Secure] All routes protected by token validation middleware
 * [S3 - Scalable] Stateless tokens, UUID-compatible
 */

Route::prefix('setup')
    ->middleware([ProtectSetupRoute::class])
    ->group(function () {
        Route::get('/', fn() => redirect()->route('setup.welcome', ['token' => request('token')]))->name('setup');

        Route::get('/welcome', SetupWelcome::class)->name('setup.welcome');
        Route::get('/school', SchoolSetup::class)->name('setup.school');
        Route::get('/account', AccountSetup::class)->name('setup.account');
        Route::get('/department', DepartmentSetup::class)->name('setup.department');
        Route::get('/internship', InternshipSetup::class)->name('setup.internship');
        Route::get('/complete', SetupComplete::class)->name('setup.complete');
    });
