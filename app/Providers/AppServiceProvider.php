<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Setup\SetupFinalized;
use App\Listeners\Setup\LogSetupFinalized;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Handbook;
use App\Models\Internship;
use App\Models\Logbook;
use App\Models\Notification;
use App\Models\Placement;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\School;
use App\Models\Submission;
use App\Models\SupervisionLog;
use App\Models\User;
use App\Policies\Assessment\AssessmentPolicy;
use App\Policies\Assignment\AssignmentPolicy;
use App\Policies\Assignment\SubmissionPolicy;
use App\Policies\Attendance\AttendancePolicy;
use App\Policies\Guidance\HandbookPolicy;
use App\Policies\Internship\CompanyPolicy;
use App\Policies\Internship\InternshipPlacementPolicy;
use App\Policies\Internship\InternshipPolicy;
use App\Policies\Internship\InternshipRegistrationPolicy;
use App\Policies\Logbook\LogbookPolicy;
use App\Policies\Mentor\SupervisionLogPolicy;
use App\Policies\Notification\NotificationPolicy;
use App\Policies\Schedule\SchedulePolicy;
use App\Policies\School\AcademicYearPolicy;
use App\Policies\School\DepartmentPolicy;
use App\Policies\School\SchoolPolicy;
use App\Policies\User\UserPolicy;
use App\Support\LangChecker;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->hasDebugModeEnabled()) {
            $this->app->extend('translator', fn ($translator) => tap(
                new LangChecker($translator->getLoader(), $translator->getLocale()),
                fn (LangChecker $checker) => $checker->setFallback($translator->getFallback()),
            ));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            SetupFinalized::class,
            [LogSetupFinalized::class, 'handle'],
        );

        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
        Blade::anonymousComponentPath(resource_path('views/components/ui'), 'ui');
        Blade::anonymousComponentPath(resource_path('views/components/widget'), 'widget');

        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Internship::class, InternshipPolicy::class);
        Gate::policy(Placement::class, InternshipPlacementPolicy::class);
        Gate::policy(Registration::class, InternshipRegistrationPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Logbook::class, LogbookPolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Assignment::class, AssignmentPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::policy(SupervisionLog::class, SupervisionLogPolicy::class);
        Gate::policy(Handbook::class, HandbookPolicy::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
    }
}
