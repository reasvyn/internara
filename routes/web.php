<?php

declare(strict_types=1);

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AccountLifecycleController;
use App\Http\Controllers\HandbookController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TeacherController;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\Company\CompanyIndex;
use App\Livewire\Admin\Department\DepartmentIndex;
use App\Livewire\Admin\Internship\DirectPlacementManager;
use App\Livewire\Admin\Internship\InternshipIndex;
use App\Livewire\Admin\Internship\PlacementIndex;
use App\Livewire\Admin\Report\ReportsManager;
use App\Livewire\Admin\School\SchoolProfile;
use App\Livewire\Admin\SystemSetting;
use App\Livewire\Admin\User\AdminManager;
use App\Livewire\Admin\User\MentorManager;
use App\Livewire\Admin\User\StudentManager;
use App\Livewire\Admin\User\TeacherManager;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Common\NotificationCenter;
use App\Livewire\Dashboard\StudentDashboard;
use App\Livewire\Internship\RegistrationWizard;
use App\Livewire\Profile\ProfileEditor;
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
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/profile', ProfileEditor::class)->name('profile');
    Route::livewire('/notifications', NotificationCenter::class)->name('notifications');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::redirect('/', '/login');

Route::middleware('setup.protected')->group(function () {
    Route::livewire('/setup', SetupWizard::class)->name('setup');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
    Route::livewire('/school', SchoolProfile::class)->name('school');
    Route::livewire('/departments', DepartmentIndex::class)->name('departments');
    Route::livewire('/companies', CompanyIndex::class)->name('companies');
    Route::livewire('/internships', InternshipIndex::class)->name('internships');
    Route::livewire('/internships/placements', PlacementIndex::class)->name('internships.placements');
    Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
    Route::livewire('/settings', SystemSetting::class)->name('settings');

    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::livewire('/admins', AdminManager::class)->name('admins');
        Route::livewire('/students', StudentManager::class)->name('students');
        Route::livewire('/teachers', TeacherManager::class)->name('teachers');
        Route::livewire('/mentors', MentorManager::class)->name('mentors');
    });

    // Report Management Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::livewire('/', ReportsManager::class)->name('index');
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
    $user = auth()->user();

    if ($user->hasAnyRole(['super_admin', 'admin'])) {
        return redirect()->route('admin.dashboard');
    }

    if ($user->hasRole('student')) {
        return redirect()->route('student.dashboard');
    }

    if ($user->hasRole('teacher')) {
        return redirect()->route('teacher.dashboard');
    }

    if ($user->hasRole('mentor')) {
        return redirect()->route('mentor.dashboard');
    }

    return redirect()->route('home');
})->middleware('auth')->name('dashboard');

Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::livewire('/dashboard', StudentDashboard::class)->name('dashboard');
    Route::livewire('/journals', JournalManager::class)->name('journals');
    Route::livewire('/supervision', SupervisionManager::class)->name('supervision');
    Route::livewire('/internships/register', RegistrationWizard::class)->name('internships.register');
});

/*
|--------------------------------------------------------------------------
| Supervision Routes (Teacher & Mentor)
|--------------------------------------------------------------------------
*/
Route::prefix('supervision')->name('supervision.')->middleware(['auth', 'role:teacher|mentor'])->group(function () {
    Route::livewire('/logs', SupervisorLogManager::class)->name('logs');
    Route::livewire('/monitoring', MonitoringVisitIndex::class)->name('monitoring');
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
