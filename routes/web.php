<?php

declare(strict_types=1);

use App\Livewire\Admin\Company\CompanyIndex;
use App\Livewire\Admin\Department\DepartmentIndex;
use App\Livewire\Admin\Internship\DirectPlacementManager;
use App\Livewire\Admin\Internship\InternshipIndex;
use App\Livewire\Admin\Internship\PlacementIndex;
use App\Livewire\Admin\School\SchoolProfile;
use App\Livewire\Admin\SystemSetting;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard\StudentDashboard;
use App\Livewire\Setup\SetupWizard;
use App\Livewire\Student\JournalManager;
use App\Livewire\Supervision\MonitoringVisitIndex;
use App\Livewire\Supervision\SupervisionManager;
use App\Livewire\Supervision\SupervisorLogManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/login');

Route::middleware('setup.protected')->group(function () {
    Route::get('/setup', SetupWizard::class)->name('setup');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/school', SchoolProfile::class)->name('school');
    Route::get('/departments', DepartmentIndex::class)->name('departments');
    Route::get('/companies', CompanyIndex::class)->name('companies');
    Route::get('/internships', InternshipIndex::class)->name('internships');
    Route::get('/internships/placements', PlacementIndex::class)->name('internships.placements');
    Route::get('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
    Route::get('/settings', SystemSetting::class)->name('settings');
});

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/
Route::prefix('student')->name('student.')->middleware(['auth'])->group(function () {
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    Route::get('/journals', JournalManager::class)->name('journals');
    Route::get('/supervision', SupervisionManager::class)->name('supervision');
});

/*
|--------------------------------------------------------------------------
| Supervision Routes
|--------------------------------------------------------------------------
*/
Route::prefix('supervision')->name('supervision.')->middleware(['auth'])->group(function () {
    Route::get('/logs', SupervisorLogManager::class)->name('logs');
    Route::get('/monitoring', MonitoringVisitIndex::class)->name('monitoring');
});
