<?php

declare(strict_types=1);

namespace App\SysAdmin\Actions;

use App\Academics\Department\Models\Department;
use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Certification\Certificate\Models\Certificate;
use App\Core\Actions\BaseAction;
use App\Enrollment\Models\Placement;
use App\Enrollment\Models\Registration;
use App\Guidance\Mentor\Models\Mentor;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\Logbook\Models\Logbook;
use App\Partners\Company\Models\Company;
use App\Partners\Partnership\Models\Partnership;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Illuminate\Support\Facades\Cache;

final class GetAdminDashboardStatsAction extends BaseAction
{
    public function execute(): array
    {
        return Cache::remember(config('cache-keys.admin_dashboard_stats'), 300, function () {
            $students = User::role(RoleEnum::STUDENT->value)->count();
            $registered = Registration::count();

            return [
                // ─── People ──────────────────────────────────────────
                'totalStudents' => $students,
                'totalTeachers' => User::role(RoleEnum::TEACHER->value)->count(),
                'totalSupervisors' => User::role(RoleEnum::SUPERVISOR->value)->count(),
                'totalMentors' => Mentor::count(),
                'totalCompanies' => Company::count(),
                'totalPartnerships' => Partnership::count(),
                'totalDepartments' => Department::count(),

                // ─── Internships ──────────────────────────────────────
                'activeInternships' => Internship::where('status', 'active')->count(),
                'allInternships' => Internship::count(),

                // ─── Registration Pipeline ───────────────────────────
                'registrationsPending' => Registration::where('status', 'pending')->count(),
                'registrationsActive' => Registration::where('status', 'active')->count(),
                'registrationsCompleted' => Registration::where('status', 'completed')->count(),
                'registrationsTotal' => $registered,

                // ─── Placements ──────────────────────────────────────
                'placementTotal' => Placement::count(),
                'placementFilled' => Placement::sum('filled_quota'),
                'placementCapacity' => Placement::sum('quota'),
                'placementsByInternship' => Internship::whereHas('placements')->count(),

                // ─── Attendance ──────────────────────────────────────
                'attendanceVerified' => Attendance::where('is_verified', true)->count(),
                'attendanceUnverified' => Attendance::where('is_verified', false)->count(),

                // ─── Logbooks ────────────────────────────────────────
                'logbookVerified' => Logbook::where('is_verified', true)->count(),
                'logbookPending' => Logbook::where('is_verified', false)->count(),

                // ─── Certificates ────────────────────────────────────
                'certificatesIssued' => Certificate::whereNotNull('issued_at')->count(),
                'certificatesRevoked' => Certificate::whereNotNull('revoked_at')->count(),
                'certificatesTotal' => Certificate::count(),

                // ─── Companies ───────────────────────────────────────
                'companiesActive' => Company::whereHas(
                    'placements',
                    fn ($q) => $q->where('filled_quota', '>', 0),
                )->count(),

                // ─── Throughput (percentage) ─────────────────────────
                'placementRate' => $registered > 0
                        ? round((Placement::sum('filled_quota') / max($registered, 1)) * 100)
                        : 0,
            ];
        });
    }
}
