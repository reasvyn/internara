<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Certificate\Models\Certificate;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Support\CacheKeys;
use App\Domain\Internship\Models\Internship;
use App\Domain\Logbook\Models\Logbook;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\Partnership\Models\Company;
use App\Domain\Partnership\Models\Partnership;
use App\Domain\Placement\Models\Placement;
use App\Domain\Registration\Models\Registration;
use App\Domain\School\Models\Department;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Cache;

final class GetAdminDashboardStatsAction extends BaseAction
{
    public function execute(): array
    {
        return Cache::remember(CacheKeys::ADMIN_DASHBOARD_STATS, 300, function () {
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
                'companiesActive' => Company::whereHas('placements', fn ($q) => $q->where('filled_quota', '>', 0))->count(),

                // ─── Throughput (percentage) ─────────────────────────
                'placementRate' => $registered > 0
                    ? round((Placement::sum('filled_quota') / max($registered, 1)) * 100)
                    : 0,
            ];
        });
    }
}
