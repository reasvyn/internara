<?php

declare(strict_types=1);

use App\Http\Controllers\AccountLifecycleController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeacherController;
use App\Livewire\Admin\Company\CompanyIndex;
use App\Livewire\Admin\Department\DepartmentIndex;
use App\Livewire\Admin\Internship\DirectPlacementManager;
use App\Livewire\Admin\Internship\InternshipIndex;
use App\Livewire\Admin\Internship\PlacementIndex;
use App\Livewire\Admin\School\SchoolProfile;
use App\Livewire\Admin\SystemSetting;
use App\Livewire\Admin\Report\ReportsManager;
use App\Livewire\Admin\User\AdminManager;
use App\Livewire\Admin\User\StudentManager;
use App\Livewire\Admin\User\TeacherManager;
use App\Livewire\Admin\User\MentorManager;
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
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::get('/school', SchoolProfile::class)->name('school');
    Route::get('/departments', DepartmentIndex::class)->name('departments');
    Route::get('/companies', CompanyIndex::class)->name('companies');
    Route::get('/internships', InternshipIndex::class)->name('internships');
    Route::get('/internships/placements', PlacementIndex::class)->name('internships.placements');
    Route::get('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
    Route::get('/settings', SystemSetting::class)->name('settings');

    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/admins', AdminManager::class)->name('admins');
        Route::get('/students', StudentManager::class)->name('students');
        Route::get('/teachers', TeacherManager::class)->name('teachers');
        Route::get('/mentors', MentorManager::class)->name('mentors');
    });

    // Report Management Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', ReportsManager::class)->name('index');
        Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
    });

    // Handbook Management Routes
    Route::prefix('handbooks')->name('handbooks.')->group(function () {
        Route::get('/', [HandbookController::class, 'index'])->name('index');
        Route::post('/', [HandbookController::class, 'store'])->name('store');
        Route::post('/{handbook}/acknowledge', [HandbookController::class, 'acknowledge'])->name('acknowledge');
    });

    // Schedule Management Routes
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');
        Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
    });

    // Academic Year Management Routes
    Route::prefix('academic-years')->name('academic-years.')->group(function () {
        Route::get('/', [AcademicYearController::class, 'index'])->name('index');
        Route::post('/', [AcademicYearController::class, 'store'])->name('store');
        Route::post('/{year}/activate', [AcademicYearController::class, 'activate'])->name('activate');
    });

    // Account Lifecycle Routes
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/lifecycle', [AccountLifecycleController::class, 'index'])->name('lifecycle');
        Route::post('/{user}/lock', [AccountLifecycleController::class, 'lock'])->name('lock');
        Route::post('/{user}/unlock', [AccountLifecycleController::class, 'unlock'])->name('unlock');
        Route::get('/detect-clones', [AccountLifecycleController::class, 'detectClones'])->name('detect-clones');
    });
});

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return redirect()->route('student.dashboard');
})->middleware('auth')->name('dashboard');

Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard', StudentDashboard::class)->name('dashboard');
    Route::get('/journals', JournalManager::class)->name('journals');
    Route::get('/supervision', SupervisionManager::class)->name('supervision');
    Route::get('/internships/register', \App\Livewire\Internship\RegistrationWizard::class)->name('internships.register');
});

/*
|--------------------------------------------------------------------------
| Supervision Routes (Teacher & Mentor)
|--------------------------------------------------------------------------
*/
Route::prefix('supervision')->name('supervision.')->middleware(['auth', 'role:teacher|mentor'])->group(function () {
    Route::get('/logs', SupervisorLogManager::class)->name('logs');
    Route::get('/monitoring', MonitoringVisitIndex::class)->name('monitoring');
});

/*
|--------------------------------------------------------------------------
| Teacher Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/assess-internship', [TeacherController::class, 'assessInternship'])->name('assess-internship');
});

/*
|--------------------------------------------------------------------------
| Mentor Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('mentor')->name('mentor.')->middleware(['auth', 'role:mentor'])->group(function () {
    Route::get('/dashboard', [MentorController::class, 'dashboard'])->name('dashboard');
    Route::post('/{mentor}/evaluate', [MentorController::class, 'evaluate'])->name('evaluate');
});
