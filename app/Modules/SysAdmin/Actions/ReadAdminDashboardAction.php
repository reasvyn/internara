<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Actions;

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Auth\Domain\Permission\Enums\Role as RoleEnum;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Models\ActivityLog;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * @suppress:parameterized constant aggregate counts
 */
final class ReadAdminDashboardAction extends BaseReadAction
{
    public function execute(): array
    {
        return Cache::remember(config('cache-keys.admin_dashboard_stats'), 300, function () {
            $students = User::role(RoleEnum::STUDENT->value)->count();

            $regStats = Registration::query()->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) as completed
            ")->first();
            $registered = (int) ($regStats->total ?? 0);

            $placementStats = Placement::query()->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(filled_quota), 0) as filled,
                COALESCE(SUM(quota), 0) as capacity
            ')->first();
            $placementFilled = (int) ($placementStats->filled ?? 0);
            $placementCapacity = (int) ($placementStats->capacity ?? 0);

            $attStats = Attendance::query()->selectRaw('
                COALESCE(SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END), 0) as verified,
                COALESCE(SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END), 0) as unverified
            ')->first();

            $logStats = Logbook::query()->selectRaw('
                COALESCE(SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END), 0) as verified,
                COALESCE(SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END), 0) as pending
            ')->first();

            $certStats = Certificate::query()->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN issued_at IS NOT NULL THEN 1 ELSE 0 END), 0) as issued,
                COALESCE(SUM(CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END), 0) as revoked
            ')->first();

            return [
                // ─── People ──────────────────────────────────────────
                'totalStudents' => $students,
                'totalTeachers' => User::role(RoleEnum::TEACHER->value)->count(),
                'totalSupervisors' => User::role(RoleEnum::SUPERVISOR->value)->count(),
                'totalMentors' => User::role([RoleEnum::TEACHER->value, RoleEnum::SUPERVISOR->value])->count(),
                'totalCompanies' => Company::count(),
                'totalDepartments' => Department::count(),

                // ─── Internships ──────────────────────────────────────
                'activeInternships' => Internship::where('status', 'active')->count(),
                'allInternships' => Internship::count(),

                // ─── Registration Pipeline ───────────────────────────
                'registrationsPending' => (int) ($regStats->pending ?? 0),
                'registrationsActive' => (int) ($regStats->active ?? 0),
                'registrationsCompleted' => (int) ($regStats->completed ?? 0),
                'registrationsTotal' => $registered,

                // ─── Placements ──────────────────────────────────────
                'placementTotal' => (int) ($placementStats->total ?? 0),
                'placementFilled' => $placementFilled,
                'placementCapacity' => $placementCapacity,
                'placementsByInternship' => Internship::whereHas('placements')->count(),

                // ─── Attendance ──────────────────────────────────────
                'attendanceVerified' => (int) ($attStats->verified ?? 0),
                'attendanceUnverified' => (int) ($attStats->unverified ?? 0),

                // ─── Logbooks ────────────────────────────────────────
                'logbookVerified' => (int) ($logStats->verified ?? 0),
                'logbookPending' => (int) ($logStats->pending ?? 0),

                // ─── Certificates ────────────────────────────────────
                'certificatesIssued' => (int) ($certStats->issued ?? 0),
                'certificatesRevoked' => (int) ($certStats->revoked ?? 0),
                'certificatesTotal' => (int) ($certStats->total ?? 0),

                // ─── Companies ───────────────────────────────────────
                'companiesActive' => Company::whereHas(
                    'placements',
                    fn ($q) => $q->where('filled_quota', '>', 0),
                )->count(),

                // ─── Throughput (percentage) ─────────────────────────
                'placementRate' => $registered > 0
                        ? (int) round(($placementFilled / max($registered, 1)) * 100)
                        : 0,

                // ─── Super Admin ──────────────────────────────────────
                'totalAuditEntries' => ActivityLog::count(),
                'failedLogins7d' => ActivityLog::where('description', 'login_failed')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'activeUsersToday' => ActivityLog::where('created_at', '>=', now()->startOfDay())
                    ->distinct('causer_id')
                    ->count('causer_id'),
            ];
        });
    }
}
