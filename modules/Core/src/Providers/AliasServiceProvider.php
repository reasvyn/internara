<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class AliasServiceProvider extends ServiceProvider
{
    /**
     * The model aliases to register.
     *
     * @var array<string, string>
     */
    protected array $aliases = [
        // Assessment
        'Assessment' => \Modules\Assessment\Models\Assessment::class,
        'Competency' => \Modules\Assessment\Models\Competency::class,

        // Assignment
        'Assignment' => \Modules\Assignment\Models\Assignment::class,
        'AssignmentType' => \Modules\Assignment\Models\AssignmentType::class,
        'Submission' => \Modules\Assignment\Models\Submission::class,

        // Attendance
        'AttendanceLog' => \Modules\Attendance\Models\AttendanceLog::class,
        'AbsenceRequest' => \Modules\Attendance\Models\AbsenceRequest::class,

        // Department
        'Department' => \Modules\Department\Models\Department::class,

        // Internship
        'Internship' => \Modules\Internship\Models\Internship::class,
        'InternshipPlacement' => \Modules\Internship\Models\InternshipPlacement::class,
        'InternshipRegistration' => \Modules\Internship\Models\InternshipRegistration::class,

        // Journal
        'JournalEntry' => \Modules\Journal\Models\JournalEntry::class,

        // Log
        'Activity' => \Modules\Log\Models\Activity::class,
        'AuditLog' => \Modules\Log\Models\AuditLog::class,

        // Media
        'Media' => \Modules\Media\Models\Media::class,

        // Mentor
        'MentoringLog' => \Modules\Mentor\Models\MentoringLog::class,

        // Permission
        'Role' => \Modules\Permission\Models\Role::class,
        'Permission' => \Modules\Permission\Models\Permission::class,

        // Profile
        'Profile' => \Modules\Profile\Models\Profile::class,

        // School
        'School' => \Modules\School\Models\School::class,

        // Setting
        'Setting' => \Modules\Setting\Models\Setting::class,

        // Status
        'Status' => \Modules\Status\Models\Status::class,

        // Student
        'Student' => \Modules\Student\Models\Student::class,

        // Teacher
        'Teacher' => \Modules\Teacher\Models\Teacher::class,

        // User
        'User' => \Modules\User\Models\User::class,
    ];

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        foreach ($this->aliases as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }
}
