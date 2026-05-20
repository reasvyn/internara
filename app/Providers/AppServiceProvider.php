<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Assessment\Models\Assessment;
use App\Domain\Assessment\Policies\AssessmentPolicy;
use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\Submission;
use App\Domain\Assignment\Policies\AssignmentPolicy;
use App\Domain\Assignment\Policies\SubmissionPolicy;
use App\Domain\Attendance\Models\Attendance;
use App\Domain\Attendance\Policies\AttendancePolicy;
use App\Domain\Auth\Policies\UserPolicy;
use App\Domain\Guidance\Models\Handbook;
use App\Domain\Guidance\Policies\HandbookPolicy;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Policies\CompanyPolicy;
use App\Domain\Internship\Policies\InternshipPolicy;
use App\Domain\Internship\Policies\InternshipRegistrationPolicy;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\Logbook\Policies\LogbookPolicy;
use App\Domain\Mentor\Models\SupervisionLog;
use App\Domain\Mentor\Policies\SupervisionLogPolicy;
use App\Domain\Partnership\Models\Company;
use App\Domain\Placement\Models\Placement;
use App\Domain\Placement\Policies\InternshipPlacementPolicy;
use App\Domain\Registration\Models\Registration;
use App\Domain\Schedule\Models\Schedule;
use App\Domain\Schedule\Policies\SchedulePolicy;
use App\Domain\School\Models\AcademicYear;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\School\Policies\AcademicYearPolicy;
use App\Domain\School\Policies\DepartmentPolicy;
use App\Domain\School\Policies\SchoolPolicy;
use App\Domain\Setup\Events\SetupFinalized;
use App\Domain\Setup\Listeners\LogSetupFinalized;
use App\Domain\Shared\Support\LangChecker;
use App\Domain\User\Models\User;
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
        Gate::policy(User::class, UserPolicy::class);
    }
}
