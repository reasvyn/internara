<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentRenderController;
use App\Http\Controllers\ReportController;
use App\Livewire\Admin\AccountLifecycleManager;
use App\Livewire\Admin\GdprDeletionLogs;
use App\Livewire\Admin\SystemSetting;
use App\Livewire\Assessment\AssessmentGrading;
use App\Livewire\Assessment\AssessmentView;
use App\Livewire\Assessment\RubricManager;
use App\Livewire\Assignment\Admin\AssignmentManager as AdminAssignmentManager;
use App\Livewire\Assignment\Student\Submission as StudentSubmission;
use App\Livewire\Attendance\AbsenceRequestForm;
use App\Livewire\Attendance\AttendanceManager;
use App\Livewire\Attendance\StudentClockIn;
use App\Livewire\Auth\AccountRecovery;
use App\Livewire\Auth\ConfirmPassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\RecoverySlipManager;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard\AdminDashboard;
use App\Livewire\Dashboard\StudentDashboard;
use App\Livewire\Dashboard\SupervisorDashboard as MentorDashboard;
use App\Livewire\Dashboard\TeacherDashboard;
use App\Livewire\Document\Admin\ReportsManager;
use App\Livewire\Evaluation\MentorEvaluationManager;
use App\Livewire\Guidance\HandbookIndex;
use App\Livewire\Guidance\StudentHandbookIndex;
use App\Livewire\Internship\AccountApplicationForm;
use App\Livewire\Internship\ApplicationReview;
use App\Livewire\Internship\CompanyManager;
use App\Livewire\Internship\DirectPlacementManager;
use App\Livewire\Internship\InternshipManager;
use App\Livewire\Internship\PlacementIndex;
use App\Livewire\Internship\RegistrationCenter;
use App\Livewire\Internship\RegistrationDocumentUpload;
use App\Livewire\Internship\RegistrationVerification;
use App\Livewire\Internship\RegistrationWizard;
use App\Livewire\Internship\RequirementManager;
use App\Livewire\Logbook\LogbookEntry;
use App\Livewire\Logbook\LogbookManager;
use App\Livewire\Mentor\Supervision\SupervisionManager;
use App\Livewire\Mentor\Supervision\SupervisorLogManager;
use App\Livewire\Notification\Admin\AnnouncementManager;
use App\Livewire\Notification\NotificationCenter;
use App\Livewire\Schedule\ScheduleIndex;
use App\Livewire\School\AcademicYearIndex;
use App\Livewire\School\DepartmentManager;
use App\Livewire\School\SchoolEditor;
use App\Livewire\Setup\SetupWizard;
use App\Livewire\Submission\Grading\SubmissionGrading;
use App\Livewire\Teacher\AssessInternship;
use App\Livewire\User\Admin\AdminManager;
use App\Livewire\User\Admin\MenteeManager;
use App\Livewire\User\Admin\MentorManager;
use App\Livewire\User\Admin\StudentManager;
use App\Livewire\User\Admin\TeacherManager;
use App\Livewire\User\Admin\UserManager;
use App\Livewire\User\ProfileEditor;
use App\Livewire\User\RecoveryCode;

/*
|--------------------------------------------------------------------------
| Setup Routes
|--------------------------------------------------------------------------
*/
Route::middleware('setup.protected')->group(function () {
    Route::livewire('/setup', SetupWizard::class)->name('setup');
});

/*
|--------------------------------------------------------------------------
| Public & Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::livewire('/register', RegistrationCenter::class)->name('register');
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-password/{token}', ResetPassword::class)->name('password.reset');
    Route::livewire('/recover-account', AccountRecovery::class)->name('recover.account');
    Route::livewire('/apply', AccountApplicationForm::class)->name('apply');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/user/confirm-password', ConfirmPassword::class)->name('password.confirm');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::livewire('/profile', ProfileEditor::class)->name('profile');
    Route::livewire('/profile/recovery', RecoveryCode::class)->name('profile.recovery');
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
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {
        Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
        Route::livewire('/school', SchoolEditor::class)->name('school');
        Route::livewire('/departments', DepartmentManager::class)->name('departments');
        Route::livewire('/companies', CompanyManager::class)->name('companies');
        Route::livewire('/internships', InternshipManager::class)->name('internships');
        Route::livewire('/internships/placements', PlacementIndex::class)->name('internships.placements');
        Route::livewire('/internships/placements/direct', DirectPlacementManager::class)->name('internships.placements.direct');
        Route::livewire('/internships/{internship}/requirements', RequirementManager::class)->name('internships.requirements');
        Route::livewire('/internships/registrations/pending', RegistrationVerification::class)->name('internships.registrations.pending');
        Route::livewire('/applications', ApplicationReview::class)->name('applications');
        Route::prefix('users')->name('users.')->group(function () {
            Route::livewire('/', UserManager::class)->name('index');
            Route::livewire('/admins', AdminManager::class)->name('admins');
            Route::livewire('/students', StudentManager::class)->name('students');
            Route::livewire('/teachers', TeacherManager::class)->name('teachers');
            Route::livewire('/mentors', MentorManager::class)->name('mentors');
            Route::livewire('/mentees', MenteeManager::class)->name('mentees');
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::livewire('/', ReportsManager::class)->name('index');
            Route::get('/{report}/download', [ReportController::class, 'download'])->name('download');
        });

        Route::livewire('/assignments', AdminAssignmentManager::class)->name('assignments');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');

        Route::livewire('/assessments/rubrics', RubricManager::class)->name('assessments.rubrics');
        Route::livewire('/assessments/{registration}/grade', AssessmentGrading::class)->name('assessments.grade');

        Route::livewire('/handbooks', HandbookIndex::class)->name('handbooks.index');
        Route::get('/documents/{document}/render/{registration}', [DocumentRenderController::class, 'show'])->name('documents.render');
        Route::get('/documents/{document}/render/{registration}/save', [DocumentRenderController::class, 'store'])->name('documents.render.store');
        Route::livewire('/schedules', ScheduleIndex::class)->name('schedules.index');
        Route::livewire('/academic-years', AcademicYearIndex::class)->name('academic-years.index');
        Route::livewire('/accounts', AccountLifecycleManager::class)->name('accounts.lifecycle');
        Route::livewire('/recovery-slips', RecoverySlipManager::class)->name('recovery-slips');
        Route::livewire('/gdpr-logs', GdprDeletionLogs::class)->name('gdpr-logs');
        Route::livewire('/attendance', AttendanceManager::class)->name('attendance');
    });

/*
|--------------------------------------------------------------------------
| Super Admin Only Routes
|--------------------------------------------------------------------------
*/
Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settings')
    ->middleware(['auth', 'role:super_admin']);

Route::livewire('/admin/announcements', AnnouncementManager::class)
    ->name('admin.announcements')
    ->middleware(['auth', 'role:super_admin']);

/*
|--------------------------------------------------------------------------
| Logbook Routes (Admin & Mentor)
|--------------------------------------------------------------------------
*/
Route::livewire('/admin/logbook', LogbookManager::class)
    ->name('admin.logbook')
    ->middleware(['auth', 'role:super_admin|admin|teacher|supervisor']);

/*
|--------------------------------------------------------------------------
| Student Routes
|--------------------------------------------------------------------------
*/
Route::prefix('student')
    ->name('student.')
    ->middleware(['auth', 'role:student'])
    ->group(function () {
        Route::livewire('/dashboard', StudentDashboard::class)->name('dashboard');
        Route::livewire('/logbook', LogbookEntry::class)->name('logbook');
        Route::livewire('/assignments', StudentSubmission::class)->name('assignments');
        Route::livewire('/supervision', SupervisionManager::class)->name('supervision');
        Route::livewire('/assessments', AssessmentView::class)->name('assessments');
        Route::livewire('/internships/register', RegistrationWizard::class)->name('internships.register');
        Route::livewire('/handbooks', StudentHandbookIndex::class)->name('handbooks');
        Route::livewire('/attendance', StudentClockIn::class)->name('attendance');
        Route::livewire('/attendance/absence', AbsenceRequestForm::class)->name('attendance.absence');
        Route::livewire('/documents', RegistrationDocumentUpload::class)->name('documents');
    });

/*
|--------------------------------------------------------------------------
| Supervision Routes (Teacher & Mentor)
|--------------------------------------------------------------------------
*/
Route::prefix('supervision')
    ->name('supervision.')
    ->middleware(['auth', 'role:teacher|supervisor'])
    ->group(function () {
        Route::livewire('/logs', SupervisorLogManager::class)->name('logs');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');
    });

/*
|--------------------------------------------------------------------------
| Teacher Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:teacher'])
    ->group(function () {
        Route::livewire('/dashboard', TeacherDashboard::class)->name('dashboard');
        Route::livewire('/submissions/grading', SubmissionGrading::class)->name('submissions.grading');
        Route::livewire('/assess-internship', AssessInternship::class)->name('assess-internship');
    });

/*
|--------------------------------------------------------------------------
| Supervisor Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('supervisor')
    ->name('supervisor.')
    ->middleware(['auth', 'role:supervisor'])
    ->group(function () {
        Route::livewire('/dashboard', MentorDashboard::class)->name('dashboard');
    });

/*
|--------------------------------------------------------------------------
| Mentor Evaluation (all authenticated users)
|--------------------------------------------------------------------------
*/
Route::livewire('/evaluate', MentorEvaluationManager::class)
    ->name('mentor.evaluate')
    ->middleware('auth');
