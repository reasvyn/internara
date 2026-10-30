<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Academic\Domain\AcademicYear\Support\AcademicYearPeriod;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Placement\Enums\PlacementChangeStatus;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Placement\Models\PlacementChangeRequest;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationDocumentStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\Journal\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journal\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\Journal\Domain\AbsenceRequest\Models\AbsenceRequest;
use App\Modules\Journal\Domain\Attendance\Enums\AttendanceStatus;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Journal\Domain\MonitoringVisit\Enums\VisitMethod;
use App\Modules\Journal\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\Journal\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journal\Domain\SupervisionLog\Enums\SupervisionType;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Report\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\Setting\Models\Setting;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Enums\EmploymentStatus;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Dataset-driven demo dataset generator (dev-only and production demo).
 *
 * Implements realistic Indonesian dataset from database/data/dummy.json.
 * Provisions 20 PKL groups (1 company, 1 teacher, 1 supervisor, 5 students each = 100 students).
 * Runs inside a single DB transaction (FR-H13) and is idempotent via firstOrCreate (FR-H5).
 */
class DummyData
{
    private string $password;

    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<string, mixed>|null */
    private ?array $dataset = null;

    private ?AcademicYear $activeYear = null;

    private ?AcademicYear $pastYear = null;

    /** @var Collection<int, Department> */
    private Collection $departments;

    /** @var Collection<int, Company> */
    private Collection $companies;

    /** @var Collection<int, User> */
    private Collection $teachers;

    /** @var Collection<int, User> */
    private Collection $supervisors;

    /** @var Collection<int, User> */
    private Collection $students;

    private ?User $admin = null;

    private ?Internship $activeInternship = null;

    private ?Internship $completedInternship = null;

    /** @var Collection<int, Placement> */
    private Collection $activePlacements;

    /** @var Collection<int, Placement> */
    private Collection $completedPlacements;

    /** @var Collection<int, Registration> */
    private Collection $activeRegistrations;

    /** @var Collection<int, Registration> */
    private Collection $pendingRegistrations;

    /** @var Collection<int, Registration> */
    private Collection $completedRegistrations;

    /** @var Collection<int, Document> */
    private Collection $documents;

    public const string ID_PREFIX = 'd0000000-';

    public static function make(): static
    {
        return new static;
    }

    /**
     * Mint a deterministic or unique dummy ID carrying the configured prefix.
     */
    public static function dummyId(): string
    {
        $prefix = (string) config('dummy.id_prefix', self::ID_PREFIX);
        $prefixLen = strlen($prefix);
        $uuid = (string) Str::uuid();

        return $prefix.substr($uuid, $prefixLen);
    }

    /**
     * Check if a given record ID belongs to the dummy dataset.
     */
    public static function isDummyId(?string $id): bool
    {
        if ($id === null) {
            return false;
        }

        $prefix = (string) config('dummy.id_prefix', self::ID_PREFIX);

        return str_starts_with($id, $prefix);
    }

    /**
     * Loads the master JSON dataset.
     *
     * @return array<string, mixed>
     */
    public static function dataset(): array
    {
        $path = database_path('data/dummy.json');
        if (! file_exists($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    /**
     * Purges all dummy records matching the dummy ID prefix and demo accounts.
     *
     * @return array<string, int> Counts of purged records per entity
     */
    public static function rollback(): array
    {
        $prefix = (string) config('dummy.id_prefix', self::ID_PREFIX);

        return DB::transaction(function () use ($prefix): array {
            // 1. Resolve base dummy entities
            $dummyUsers = User::where('id', 'like', "{$prefix}%")
                ->orWhereIn('email', self::demoAccounts())
                ->get();
            $dummyUserIds = $dummyUsers->pluck('id')->all();

            $dummyAcademicYearIds = AcademicYear::where('id', 'like', "{$prefix}%")->pluck('id')->all();
            $dummyDepartmentIds = Department::where('id', 'like', "{$prefix}%")->pluck('id')->all();
            $dummyCompanyIds = Company::where('id', 'like', "{$prefix}%")->pluck('id')->all();

            // 2. Resolve affiliated internships & placements
            $dummyInternshipIds = Internship::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyAcademicYearIds), fn ($q) => $q->orWhereIn('academic_year_id', $dummyAcademicYearIds))
                ->pluck('id')
                ->all();

            $dummyPlacementIds = Placement::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyCompanyIds), fn ($q) => $q->orWhereIn('company_id', $dummyCompanyIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('teacher_id', $dummyUserIds)->orWhereIn('company_supervisor_id', $dummyUserIds))
                ->pluck('id')
                ->all();

            // 3. Resolve affiliated registrations
            $dummyRegistrationIds = Registration::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('student_id', $dummyUserIds))
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyPlacementIds), fn ($q) => $q->orWhereIn('placement_id', $dummyPlacementIds))
                ->pluck('id')
                ->all();

            // 4. Resolve affiliated groups, assignments, rubrics, forms, documents
            $dummyGroupIds = InternshipGroup::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyPlacementIds), fn ($q) => $q->orWhereIn('placement_id', $dummyPlacementIds))
                ->pluck('id')
                ->all();

            $dummyAssignmentIds = Assignment::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('created_by', $dummyUserIds))
                ->pluck('id')
                ->all();

            $dummyRubricIds = Rubric::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('created_by', $dummyUserIds))
                ->pluck('id')
                ->all();

            $dummyDocumentIds = Document::where('id', 'like', "{$prefix}%")->pluck('id')->all();

            $purged = [];

            // Incidents (legacy cleanup)
            if (class_exists(IncidentReport::class)) {
                $purged['incident_reports'] = IncidentReport::where('id', 'like', "{$prefix}%")
                    ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                    ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('reported_by', $dummyUserIds)->orWhereIn('resolved_by', $dummyUserIds))
                    ->delete();
            }

            // Sysadmin requests & notifications
            $purged['placement_change_requests'] = PlacementChangeRequest::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyPlacementIds), fn ($q) => $q->orWhereIn('from_placement_id', $dummyPlacementIds)->orWhereIn('to_placement_id', $dummyPlacementIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('requested_by', $dummyUserIds)->orWhereIn('processed_by', $dummyUserIds))
                ->delete();

            $purged['account_applications'] = AccountApplication::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyDepartmentIds), fn ($q) => $q->orWhereIn('department_id', $dummyDepartmentIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('processed_by', $dummyUserIds))
                ->delete();

            $purged['notifications'] = Notification::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhere(fn ($sub) => $sub->where('notifiable_type', User::class)->whereIn('notifiable_id', $dummyUserIds)))
                ->delete();

            $purged['announcements'] = Announcement::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('created_by', $dummyUserIds))
                ->delete();

            // Finalization & Assessments
            $purged['submissions'] = Submission::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyAssignmentIds), fn ($q) => $q->orWhereIn('assignment_id', $dummyAssignmentIds))
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('student_id', $dummyUserIds)->orWhereIn('graded_by', $dummyUserIds)->orWhereIn('verified_by', $dummyUserIds))
                ->delete();

            $purged['certificates'] = Certificate::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('issued_by', $dummyUserIds))
                ->delete();

            $purged['reports'] = StudentReport::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('finalized_by', $dummyUserIds))
                ->delete();

            $purged['assessments'] = Assessment::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('evaluator_id', $dummyUserIds))
                ->when(! empty($dummyRubricIds), fn ($q) => $q->orWhereIn('rubric_id', $dummyRubricIds))
                ->delete();

            // Daily operations
            $purged['monitoring_visits'] = MonitoringVisit::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('teacher_id', $dummyUserIds)->orWhereIn('verified_by', $dummyUserIds))
                ->delete();

            $purged['supervision_logs'] = SupervisionLog::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('supervisor_id', $dummyUserIds)->orWhereIn('verified_by', $dummyUserIds)->orWhereIn('reviewed_by', $dummyUserIds))
                ->delete();

            $purged['absence_requests'] = AbsenceRequest::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('user_id', $dummyUserIds)->orWhereIn('absence_processed_by', $dummyUserIds))
                ->delete();

            $purged['attendances'] = Attendance::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('user_id', $dummyUserIds)->orWhereIn('absence_processed_by', $dummyUserIds)->orWhereIn('verified_by', $dummyUserIds))
                ->delete();

            $purged['logbooks'] = Logbook::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('user_id', $dummyUserIds)->orWhereIn('verified_by', $dummyUserIds)->orWhereIn('supervisor_id', $dummyUserIds))
                ->delete();

            $purged['assignments'] = Assignment::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('created_by', $dummyUserIds))
                ->delete();

            $purged['rubrics'] = Rubric::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('created_by', $dummyUserIds))
                ->delete();

            // Groups & Documents
            InternshipGroupMember::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyGroupIds), fn ($q) => $q->orWhereIn('internship_group_id', $dummyGroupIds))
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('user_id', $dummyUserIds))
                ->delete();

            $purged['registration_documents'] = RegistrationDocument::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyRegistrationIds), fn ($q) => $q->orWhereIn('registration_id', $dummyRegistrationIds))
                ->when(! empty($dummyDocumentIds), fn ($q) => $q->orWhereIn('document_id', $dummyDocumentIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('verified_by', $dummyUserIds))
                ->delete();

            $purged['documents'] = Document::where('id', 'like', "{$prefix}%")->delete();

            $purged['groups'] = InternshipGroup::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyPlacementIds), fn ($q) => $q->orWhereIn('placement_id', $dummyPlacementIds))
                ->delete();

            // Registrations & Placements & Internships
            $purged['registrations'] = Registration::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('student_id', $dummyUserIds))
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyPlacementIds), fn ($q) => $q->orWhereIn('placement_id', $dummyPlacementIds))
                ->delete();

            $purged['placements'] = Placement::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyInternshipIds), fn ($q) => $q->orWhereIn('internship_id', $dummyInternshipIds))
                ->when(! empty($dummyCompanyIds), fn ($q) => $q->orWhereIn('company_id', $dummyCompanyIds))
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('teacher_id', $dummyUserIds)->orWhereIn('company_supervisor_id', $dummyUserIds))
                ->delete();

            $purged['internships'] = Internship::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyAcademicYearIds), fn ($q) => $q->orWhereIn('academic_year_id', $dummyAcademicYearIds))
                ->delete();

            // Partnerships (legacy cleanup)
            if (class_exists(Partnership::class)) {
                $purged['partnerships'] = Partnership::where('id', 'like', "{$prefix}%")
                    ->when(! empty($dummyCompanyIds), fn ($q) => $q->orWhereIn('company_id', $dummyCompanyIds))
                    ->delete();
            }

            $purged['companies'] = Company::where('id', 'like', "{$prefix}%")->delete();

            // Profiles & Users
            $purged['profiles'] = Profile::where('id', 'like', "{$prefix}%")
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('user_id', $dummyUserIds))
                ->delete();

            if (! empty($dummyUserIds)) {
                DB::table('model_has_roles')->whereIn('model_id', $dummyUserIds)->delete();
                DB::table('model_has_permissions')->whereIn('model_id', $dummyUserIds)->delete();
                $purged['users'] = User::whereIn('id', $dummyUserIds)->delete();
            } else {
                $purged['users'] = 0;
            }

            // Departments
            $purged['departments'] = Department::where('id', 'like', "{$prefix}%")->delete();

            // Past Academic Year
            $purged['academic_years'] = AcademicYear::where('id', 'like', "{$prefix}%")->delete();

            // Media Cleanups
            $allDeletedIds = array_filter(array_merge(
                $dummyUserIds,
                $dummyAcademicYearIds,
                $dummyDepartmentIds,
                $dummyCompanyIds,
                $dummyInternshipIds,
                $dummyPlacementIds,
                $dummyRegistrationIds,
                $dummyGroupIds,
                $dummyAssignmentIds,
                $dummyRubricIds,
                $dummyDocumentIds,
            ));

            DB::table('media')
                ->where('model_id', 'like', "{$prefix}%")
                ->when(! empty($allDeletedIds), fn ($q) => $q->orWhereIn('model_id', $allDeletedIds))
                ->delete();

            DB::table('activity_log')
                ->where('subject_id', 'like', "{$prefix}%")
                ->orWhere('causer_id', 'like', "{$prefix}%")
                ->when(! empty($dummyUserIds), fn ($q) => $q->orWhereIn('causer_id', $dummyUserIds))
                ->when(! empty($allDeletedIds), fn ($q) => $q->orWhereIn('subject_id', $allDeletedIds))
                ->delete();

            return array_filter($purged, fn (int $count): bool => $count > 0);
        });
    }

    /**
     * Deterministic demo account emails.
     *
     * @return list<string>
     */
    public static function demoAccounts(): array
    {
        $accounts = [
            'admin@example.com',
            'teacher@example.com',
            'student@example.com',
        ];

        foreach (range(1, 4) as $i) {
            $accounts[] = "admin{$i}@example.com";
        }

        foreach (range(1, 20) as $i) {
            $accounts[] = "teacher{$i}@example.com";
            $accounts[] = "supervisor{$i}@example.com";
        }

        foreach (range(1, 100) as $i) {
            $accounts[] = "student{$i}@example.com";
        }

        return array_values(array_unique($accounts));
    }

    /**
     * Generates the full demo dataset and returns per-entity creation counts.
     *
     * @return array<string, int>
     */
    public function run(): array
    {
        $this->password = (string) config('dummy.password', 'password');
        $this->dataset = self::dataset();

        return DB::transaction(function (): array {
            $this->resetState();

            $this->seedSchoolProfile();
            $this->seedAcademicYears();
            $this->seedDepartments();
            $this->seedCompanies();
            $this->seedInternships();
            $this->seedPlacements();
            $this->seedUsersAndProfiles();
            $this->seedRegistrations();
            $this->seedGroupsAndDocuments();
            $this->seedAssessmentData();
            $this->seedDailyOps();
            $this->seedFinalization();
            $this->seedSysAdminData();

            return $this->counts;
        });
    }

    private function resetState(): void
    {
        $this->counts = [];
        $this->activeYear = null;
        $this->pastYear = null;
        $this->departments = collect();
        $this->companies = collect();
        $this->teachers = collect();
        $this->supervisors = collect();
        $this->students = collect();
        $this->admin = null;
        $this->activeInternship = null;
        $this->completedInternship = null;
        $this->activePlacements = collect();
        $this->completedPlacements = collect();
        $this->activeRegistrations = collect();
        $this->pendingRegistrations = collect();
        $this->completedRegistrations = collect();
        $this->documents = collect();
    }

    private function seedSchoolProfile(): void
    {
        $school = $this->dataset['school'] ?? [];
        if (empty($school)) {
            return;
        }

        $mappings = [
            'brand_name' => $school['name'] ?? null,
            'school_name' => $school['name'] ?? null,
            'school.name' => $school['name'] ?? null,
            'school_npsn' => $school['institutional_code'] ?? null,
            'school.institutional_code' => $school['institutional_code'] ?? null,
            'school_email' => $school['email'] ?? null,
            'school.email' => $school['email'] ?? null,
            'school_phone' => $school['phone'] ?? null,
            'school.phone' => $school['phone'] ?? null,
            'school_fax' => $school['fax'] ?? null,
            'school.fax' => $school['fax'] ?? null,
            'school_address' => $school['address'] ?? null,
            'school.address' => $school['address'] ?? null,
            'school_website' => $school['website'] ?? null,
            'school.website' => $school['website'] ?? null,
            'school_principal_name' => $school['principal_name'] ?? null,
            'school.principal_name' => $school['principal_name'] ?? null,
        ];

        foreach ($mappings as $key => $value) {
            if ($value !== null) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'group' => 'general']
                );
            }
        }
    }

    private function seedAcademicYears(): void
    {
        $years = $this->dataset['academic_years'] ?? [];
        $activeYearData = collect($years)->firstWhere('is_active', true) ?? [
            'name' => AcademicYearPeriod::nameFor(now()),
            'start_date' => AcademicYearPeriod::startDateFor(now()),
            'end_date' => AcademicYearPeriod::endDateFor(now()),
            'is_active' => true,
        ];

        $this->activeYear = AcademicYear::query()->where('is_active', true)->first();

        if ($this->activeYear === null) {
            $this->activeYear = $this->firstOrCreate(
                AcademicYear::factory(),
                ['name' => $activeYearData['name']],
                [
                    'name' => $activeYearData['name'],
                    'start_date' => $activeYearData['start_date'],
                    'end_date' => $activeYearData['end_date'],
                    'is_active' => true,
                ],
                'academic_years',
            );
        }

        $pastYearData = collect($years)->firstWhere('is_active', false);
        if ($pastYearData) {
            $this->pastYear = $this->firstOrCreate(
                AcademicYear::factory(),
                ['name' => $pastYearData['name']],
                [
                    'name' => $pastYearData['name'],
                    'start_date' => $pastYearData['start_date'],
                    'end_date' => $pastYearData['end_date'],
                    'is_active' => false,
                ],
                'academic_years',
            );
        }
    }

    private function seedDepartments(): void
    {
        $depts = $this->dataset['departments'] ?? [
            ['name' => 'Sistem Informasi, Jaringan, dan Aplikasi', 'description' => 'Pengembangan aplikasi berbasis web/cloud, administrasi server jaringan enterprise, integrasi sistem cerdas, dan keamanan siber.'],
            ['name' => 'Desain Pemodelan dan Informasi Bangunan', 'description' => 'Pemodelan 3D arsitektur, penyusunan rancangan teknik gedung (BIM), estimasi anggaran biaya (RAB), dan penggambaran konstruksi digital.'],
            ['name' => 'Konstruksi Gedung, Sanitasi, dan Perawatan', 'description' => 'Pelaksanaan dan pengawasan konstruksi beton bertulang, sistem utilitas dan sanitasi gedung bertingkat, serta inspeksi perawatan fasilitas bangunan.'],
            ['name' => 'Teknik Tenaga Listrik', 'description' => 'Instalasi tenaga listrik tegangan menengah dan rendah, otomatisasi industri berbasis PLC dan SCADA, serta pemeliharaan trafo dan panel distribusi daya.'],
            ['name' => 'Teknik Elektronika Daya dan Komunikasi', 'description' => 'Perekayasaan sistem kendali elektronika industri, inverter daya, instrumentasi otomatis, serta sistem transmisi komunikasi nirkabel dan fiber optik.'],
            ['name' => 'Teknik Fabrikasi Logam dan Manufaktur', 'description' => 'Proses pemesinan konvensional dan CNC, perancangan cetakan logam (mould & die), perakitan konstruksi mekanikal presisi, dan fabrikasi lembaran logam.'],
            ['name' => 'Teknik Pengecoran Logam', 'description' => 'Teknologi metalurgi pengecoran fero dan non-fero, pembuatan pola dan cetakan pasir/resin, peleburan tanur induksi (furnace), serta uji metalografi material cetak.'],
            ['name' => 'Teknik dan Manajemen Perawatan Otomotif', 'description' => 'Diagnosis sistem manajemen mesin modern (EFI/CRDi), pemeliharaan sasis dan pemindah tenaga otomotif, manajemen bengkel resmi, dan sistem kelistrikan kendaraan listrik/hibrida.'],
        ];

        foreach ($depts as $dept) {
            $this->departments->push(
                $this->firstOrCreate(
                    Department::factory(),
                    ['name' => $dept['name']],
                    [
                        'name' => $dept['name'],
                        'description' => $dept['description'] ?? null,
                    ],
                    'departments',
                ),
            );
        }
    }

    private function seedCompanies(): void
    {
        $companies = $this->dataset['companies'] ?? [];

        foreach ($companies as $company) {
            $this->companies->push(
                $this->firstOrCreate(
                    Company::factory(),
                    ['name' => $company['name']],
                    [
                        'name' => $company['name'],
                        'industry_sector' => $company['sector'] ?? 'General',
                        'address' => $company['address'] ?? null,
                        'phone' => $company['phone'] ?? null,
                        'email' => $company['email'] ?? null,
                        'website' => $company['website'] ?? null,
                        'description' => $company['description'] ?? null,
                    ],
                    'companies',
                ),
            );
        }
    }

    private function seedInternships(): void
    {
        $this->activeInternship = $this->firstOrCreate(
            Internship::factory(),
            ['name' => 'PKL Tahun Ajaran '.$this->activeYear->name],
            [
                'name' => 'PKL Tahun Ajaran '.$this->activeYear->name,
                'academic_year_id' => $this->activeYear->id,
                'start_date' => $this->activeYear->start_date->addMonths(2)->toDateString(),
                'end_date' => $this->activeYear->end_date->subMonths(2)->toDateString(),
                'status' => InternshipStatus::ACTIVE->value,
                'phases' => $this->internshipPhases(),
                'grading_weights' => ['supervisor' => 40, 'teacher' => 20, 'assignment' => 20, 'exam' => 20],
            ],
            'internships',
        );

        if ($this->pastYear) {
            $this->completedInternship = $this->firstOrCreate(
                Internship::factory(),
                ['name' => 'PKL Tahun Ajaran '.$this->pastYear->name],
                [
                    'name' => 'PKL Tahun Ajaran '.$this->pastYear->name,
                    'academic_year_id' => $this->pastYear->id,
                    'start_date' => $this->pastYear->start_date->addMonths(2)->toDateString(),
                    'end_date' => $this->pastYear->end_date->subMonths(2)->toDateString(),
                    'status' => InternshipStatus::COMPLETED->value,
                    'phases' => $this->internshipPhases(),
                    'grading_weights' => ['supervisor' => 40, 'teacher' => 20, 'assignment' => 20, 'exam' => 20],
                ],
                'internships',
            );
        }
    }

    private function seedPlacements(): void
    {
        $placements = $this->dataset['placements'] ?? [];

        foreach ($this->companies as $index => $company) {
            $placementData = $placements[$index] ?? null;
            $name = $placementData['name'] ?? ('Program PKL '.$company->name);
            $quota = $placementData['quota'] ?? 10;
            $description = $placementData['description'] ?? null;

            $this->activePlacements->push(
                $this->firstOrCreate(
                    Placement::factory(),
                    ['company_id' => $company->id, 'internship_id' => $this->activeInternship->id],
                    [
                        'company_id' => $company->id,
                        'internship_id' => $this->activeInternship->id,
                        'name' => $name,
                        'quota' => $quota,
                        'description' => $description,
                    ],
                    'placements',
                ),
            );

            if ($this->completedInternship) {
                $this->completedPlacements->push(
                    $this->firstOrCreate(
                        Placement::factory(),
                        ['company_id' => $company->id, 'internship_id' => $this->completedInternship->id],
                        [
                            'company_id' => $company->id,
                            'internship_id' => $this->completedInternship->id,
                            'name' => $name,
                            'quota' => $quota,
                            'description' => $description,
                        ],
                        'placements',
                    ),
                );
            }
        }
    }

    private function seedUsersAndProfiles(): void
    {
        $usersData = $this->dataset['users'] ?? [];

        // 1. Standalone demo accounts
        foreach ($usersData['standalone_accounts'] ?? [] as $acc) {
            $user = $this->ensureUser($acc['email'], $acc['role'], 'users', $acc['name']);
            if ($acc['role'] === 'admin') {
                $this->admin = $user;
                $this->ensureProfile($user, Profile::factory(), [
                    'phone' => $acc['phone'] ?? null,
                    'address' => $acc['address'] ?? null,
                    'gender' => $acc['gender'] ?? 'female',
                    'pob' => $acc['pob'] ?? null,
                    'dob' => $acc['dob'] ?? null,
                    'emergency_contact' => $acc['emergency_contact'] ?? null,
                ], 'profiles');
            } elseif ($acc['role'] === 'teacher') {
                $dept = $this->departments->first();
                $this->ensureProfile($user, Profile::factory()->forTeacher(), [
                    'department_id' => $dept?->id,
                    'id_number' => $acc['id_number'] ?? null,
                    'phone' => $acc['phone'] ?? null,
                    'address' => $acc['address'] ?? null,
                    'gender' => $acc['gender'] ?? 'male',
                    'emergency_contact' => $acc['emergency_contact'] ?? null,
                ], 'profiles');
            } elseif ($acc['role'] === 'student') {
                $dept = $this->departments->first();
                $this->ensureProfile($user, Profile::factory()->forStudent($dept), [
                    'id_number' => $acc['id_number'] ?? null,
                    'national_id_number' => $acc['national_id_number'] ?? null,
                    'phone' => $acc['phone'] ?? null,
                    'address' => $acc['address'] ?? null,
                    'gender' => $acc['gender'] ?? 'male',
                    'blood_type' => strtolower((string) ($acc['blood_type'] ?? 'o')),
                    'emergency_contact' => $acc['emergency_contact'] ?? null,
                ], 'profiles');
            }
        }

        // 2. 4 Admin accounts (admin1@example.com to admin4@example.com)
        foreach ($usersData['admins'] ?? [] as $adm) {
            $user = $this->ensureUser($adm['email'], 'admin', 'users', $adm['name']);
            if ($this->admin === null) {
                $this->admin = $user;
            }
            $this->ensureProfile($user, Profile::factory(), [
                'phone' => $adm['phone'] ?? null,
                'address' => $adm['address'] ?? null,
                'gender' => $adm['gender'] ?? 'male',
                'pob' => $adm['pob'] ?? null,
                'dob' => $adm['dob'] ?? null,
            ], 'profiles');
        }

        // 3. 20 Teachers
        $this->teachers = collect();
        $teachersData = $usersData['teachers'] ?? [];
        foreach ($teachersData as $i => $teacherData) {
            $user = $this->ensureUser($teacherData['email'], 'teacher', 'users', $teacherData['name']);
            $this->teachers->push($user);
            $dept = $this->departments->firstWhere('name', $teacherData['department'] ?? '')
                ?? $this->departments->get($i % $this->departments->count());

            $this->ensureProfile($user, Profile::factory()->forTeacher(), [
                'department_id' => $dept?->id,
                'id_number' => $teacherData['id_number'] ?? null,
                'phone' => $teacherData['phone'] ?? null,
                'address' => $teacherData['address'] ?? null,
                'gender' => $teacherData['gender'] ?? 'male',
                'pob' => $teacherData['pob'] ?? null,
                'dob' => $teacherData['dob'] ?? null,
                'emergency_contact' => $teacherData['emergency_contact'] ?? null,
            ], 'profiles');
        }

        // 4. 20 Supervisors
        $this->supervisors = collect();
        $supervisorsData = $usersData['supervisors'] ?? [];
        foreach ($supervisorsData as $i => $supData) {
            $user = $this->ensureUser($supData['email'], 'supervisor', 'users', $supData['name']);
            $this->supervisors->push($user);
            $company = $this->companies->firstWhere('name', $supData['company_name'] ?? '')
                ?? $this->companies->get($i % $this->companies->count());

            $this->ensureProfile($user, Profile::factory()->forSupervisor(), [
                'company_id' => $company?->id,
                'employment_status' => EmploymentStatus::FULL_TIME->value,
                'id_number' => $supData['id_number'] ?? null,
                'phone' => $supData['phone'] ?? null,
                'address' => $supData['address'] ?? null,
                'gender' => $supData['gender'] ?? 'male',
                'emergency_contact' => $supData['emergency_contact'] ?? null,
            ], 'profiles');
        }

        // 5. 100 Students
        $this->students = collect();
        $studentsData = $usersData['students'] ?? [];
        foreach ($studentsData as $i => $stData) {
            $user = $this->ensureUser($stData['email'], 'student', 'users', $stData['name']);
            $this->students->push($user);

            $groupIndex = intdiv($i, 5);
            $groupData = ($this->dataset['groups'] ?? [])[$groupIndex] ?? null;
            $deptName = $groupData['department_name'] ?? null;
            $dept = ($deptName ? $this->departments->firstWhere('name', $deptName) : null)
                ?? $this->departments->get($groupIndex % $this->departments->count());

            $this->ensureProfile($user, Profile::factory()->forStudent($dept), [
                'id_number' => $stData['id_number'] ?? null,
                'national_id_number' => $stData['national_id_number'] ?? null,
                'phone' => $stData['phone'] ?? null,
                'address' => $stData['address'] ?? null,
                'gender' => $stData['gender'] ?? 'male',
                'blood_type' => strtolower((string) ($stData['blood_type'] ?? 'o')),
                'pob' => $stData['pob'] ?? null,
                'dob' => $stData['dob'] ?? null,
                'emergency_contact' => $stData['emergency_contact'] ?? null,
            ], 'profiles');
        }
    }

    private function seedRegistrations(): void
    {
        // 100 active students placed across 20 placements (5 students per placement)
        foreach ($this->students as $index => $student) {
            $placementIndex = intdiv($index, 5);
            $placement = $this->activePlacements->get($placementIndex % $this->activePlacements->count());

            $registration = $this->firstOrCreate(
                Registration::factory(),
                ['student_id' => $student->id, 'internship_id' => $this->activeInternship->id],
                [
                    'student_id' => $student->id,
                    'internship_id' => $this->activeInternship->id,
                    'placement_id' => $placement->id,
                    'start_date' => $this->activeInternship->start_date->toDateString(),
                    'end_date' => $this->activeInternship->end_date->toDateString(),
                    'status' => 'active',
                ],
                'registrations',
            );
            $this->activeRegistrations->push($registration);
        }

        // Completed internship: first 10 students with completed placement
        if ($this->completedInternship && $this->completedPlacements->isNotEmpty()) {
            foreach ($this->students->take(10) as $index => $student) {
                $registration = $this->firstOrCreate(
                    Registration::factory(),
                    ['student_id' => $student->id, 'internship_id' => $this->completedInternship->id],
                    [
                        'student_id' => $student->id,
                        'internship_id' => $this->completedInternship->id,
                        'placement_id' => $this->completedPlacements->get($index % $this->completedPlacements->count())->id,
                        'start_date' => $this->completedInternship->start_date->toDateString(),
                        'end_date' => $this->completedInternship->end_date->toDateString(),
                        'status' => 'active',
                    ],
                    'registrations',
                );
                $this->completedRegistrations->push($registration);
            }
        }

        $this->reconcileFilledQuotas();
    }

    private function reconcileFilledQuotas(): void
    {
        Placement::query()->each(function (Placement $placement): void {
            $active = Registration::query()
                ->where('placement_id', $placement->id)
                ->where('status', 'active')
                ->count();

            $placement->update(['filled_quota' => $active]);
        });
    }

    private function seedGroupsAndDocuments(): void
    {
        $groupCount = min(20, $this->activePlacements->count());

        for ($index = 0; $index < $groupCount; $index++) {
            $name = 'Kelompok PKL '.($index + 1);
            $placement = $this->activePlacements->get($index % $this->activePlacements->count());
            $company = $this->companies->get($index % $this->companies->count());

            $group = $this->firstOrCreate(
                InternshipGroup::factory(),
                ['name' => $name, 'internship_id' => $this->activeInternship->id],
                [
                    'name' => $name,
                    'internship_id' => $this->activeInternship->id,
                    'placement_id' => $placement->id,
                    'description' => "Kelompok PKL di {$company->name}",
                ],
                'groups',
            );

            $teacher = $this->teachers->get($index % $this->teachers->count());
            $supervisor = $this->supervisors->get($index % $this->supervisors->count());

            $this->ensureGroupMember($group, ['user_id' => $teacher->id], InternshipGroupRole::SCHOOL_TEACHER->value);
            $this->ensureGroupMember($group, ['user_id' => $supervisor->id], InternshipGroupRole::INDUSTRY_SUPERVISOR->value);

            // Exactly 5 students placed in this placement
            $placedStudents = $this->activeRegistrations
                ->where('placement_id', $placement->id)
                ->take(5);

            foreach ($placedStudents as $registration) {
                $this->ensureGroupMember($group, ['registration_id' => $registration->id], InternshipGroupRole::STUDENT->value);
            }
        }

        // Documents from dataset
        $handbooks = $this->dataset['handbooks'] ?? [];
        $officialDocs = $this->dataset['official_documents'] ?? [];
        $allDocs = array_merge($handbooks, $officialDocs);

        if (empty($allDocs)) {
            $allDocs = [
                ['slug' => 'kebijakan-pkl', 'title' => 'Kebijakan Praktik Kerja Lapangan', 'type' => 'policy'],
                ['slug' => 'panduan-pkl', 'title' => 'Panduan Pelaksanaan PKL', 'type' => 'guideline'],
            ];
        }

        foreach ($allDocs as $doc) {
            $this->documents->push(
                $this->firstOrCreate(
                    Document::factory(),
                    ['slug' => $doc['slug']],
                    [
                        'slug' => $doc['slug'],
                        'title' => $doc['title'],
                        'type' => $doc['type'] ?? $doc['category'] ?? 'guideline',
                        'content' => $doc['description'] ?? '',
                        'created_by' => $this->admin->id,
                    ],
                    'documents',
                ),
            );
        }

        $requiredIds = $this->documents->pluck('id')->all();
        $this->activeInternship->update(['required_document_ids' => $requiredIds]);
        if ($this->completedInternship) {
            $this->completedInternship->update(['required_document_ids' => $requiredIds]);
        }

        $statuses = [
            RegistrationDocumentStatus::VERIFIED->value,
            RegistrationDocumentStatus::PENDING->value,
        ];

        foreach ($this->activeRegistrations->take(20) as $index => $registration) {
            foreach ($this->documents as $docIndex => $document) {
                $this->firstOrCreate(
                    RegistrationDocument::factory(),
                    ['registration_id' => $registration->id, 'document_id' => $document->id],
                    [
                        'registration_id' => $registration->id,
                        'document_id' => $document->id,
                        'status' => $statuses[($index + $docIndex) % count($statuses)],
                    ],
                    'registration_documents',
                );
            }
        }
    }

    private function ensureGroupMember(InternshipGroup $group, array $where, string $role): void
    {
        $values = array_merge(
            ['internship_group_id' => $group->id, 'role' => $role],
            $where,
            ['registration_id' => $where['registration_id'] ?? null, 'user_id' => $where['user_id'] ?? null],
        );

        $this->firstOrCreate(
            InternshipGroupMember::factory(),
            array_merge(['internship_group_id' => $group->id], $where),
            $values,
            null,
        );
    }

    private function seedAssessmentData(): void
    {
        $rubrics = $this->dataset['rubrics'] ?? [];
        if (empty($rubrics)) {
            $rubrics = [
                ['name' => 'Rubrik Penilaian Pembimbing DUDI'],
                ['name' => 'Rubrik Penilaian Guru Pembimbing Sekolah'],
            ];
        }

        foreach ($rubrics as $rubricData) {
            $this->firstOrCreate(
                Rubric::factory(),
                ['internship_id' => $this->activeInternship->id, 'name' => $rubricData['name']],
                [
                    'internship_id' => $this->activeInternship->id,
                    'name' => $rubricData['name'],
                    'created_by' => $this->admin->id,
                ],
                'rubrics',
            );
        }

        $assignments = $this->dataset['assignments'] ?? [
            ['title' => 'Laporan Kemajuan Proyek Mingguan (Progress Report)', 'description' => 'Laporan rekapitulasi progres teknis PKL.'],
            ['title' => 'Studi Kasus Penanganan Masalah Nyata di Industri', 'description' => 'Analisis kasus teknis dan solusi di tempat kerja.'],
        ];

        foreach ($assignments as $assignmentData) {
            $this->firstOrCreate(
                Assignment::factory()->published(),
                ['internship_id' => $this->activeInternship->id, 'title' => $assignmentData['title']],
                [
                    'internship_id' => $this->activeInternship->id,
                    'title' => $assignmentData['title'],
                    'document_id' => $this->documents->first()?->id,
                    'created_by' => $this->teachers->first()->id,
                    'due_date' => $assignmentData['due_date'] ?? $this->activeInternship->end_date->toDateString(),
                ],
                'assignments',
            );
        }
    }

    private function seedDailyOps(): void
    {
        foreach ($this->activeRegistrations as $index => $registration) {
            $this->seedLogbooks($registration, $index);
            $this->seedAttendances($registration, $index);
            $this->seedSupervisionAndVisits($registration, $index);
        }

        $this->seedAbsenceRequests();
    }

    private function seedLogbooks(Registration $registration, int $regIndex): void
    {
        $statuses = [
            LogbookStatus::DRAFT->value,
            LogbookStatus::SUBMITTED->value,
            LogbookStatus::SUBMITTED->value,
            LogbookStatus::VERIFIED->value,
            LogbookStatus::VERIFIED->value,
            LogbookStatus::VERIFIED->value,
        ];

        $groupIndex = intdiv($regIndex, 5);
        $groupData = ($this->dataset['groups'] ?? [])[$groupIndex] ?? null;
        $deptName = $groupData['department_name']
            ?? $this->departments->get($groupIndex % $this->departments->count())?->name
            ?? 'Sistem Informasi, Jaringan, dan Aplikasi';
        $logbookList = $this->dataset['logbooks'][$deptName]
            ?? $this->dataset['logbooks']['Sistem Informasi, Jaringan, dan Aplikasi']
            ?? [];

        foreach ($statuses as $i => $status) {
            $date = $registration->start_date->copy()->addDays($i);
            $logItem = $logbookList[$i % max(1, count($logbookList))] ?? [
                'title' => 'Aktivitas Praktik Kerja Lapangan',
                'content' => 'Melaksanakan tugas pemagangan sesuai instruksi kerja mentor industri.',
            ];

            $values = [
                'user_id' => $registration->student_id,
                'registration_id' => $registration->id,
                'date' => $date->toDateString(),
                'content' => $logItem['content'] ?? 'Melaksanakan tugas teknis pemagangan.',
                'status' => $status,
            ];

            if ($status === LogbookStatus::VERIFIED->value) {
                $values['is_verified'] = true;
                $values['verified_by'] = $this->teachers->get($groupIndex % $this->teachers->count())->id;
                $values['verified_at'] = $date->copy()->addDay()->toDateTimeString();
            }

            $this->firstOrCreate(
                Logbook::factory(),
                ['registration_id' => $registration->id, 'date' => $date->toDateTimeString()],
                $values,
                'logbooks',
            );
        }
    }

    private function seedAttendances(Registration $registration, int $index): void
    {
        $entries = [
            AttendanceStatus::PRESENT->value,
            AttendanceStatus::PRESENT->value,
            AttendanceStatus::LATE->value,
            AttendanceStatus::PRESENT->value,
            AttendanceStatus::SICK->value,
            AttendanceStatus::PRESENT->value,
            AttendanceStatus::LATE->value,
            AttendanceStatus::PERMISSION->value,
            AttendanceStatus::PRESENT->value,
            AttendanceStatus::EARLY_OUT->value,
            AttendanceStatus::PRESENT->value,
            AttendanceStatus::PRESENT->value,
        ];

        $supervisor = $this->supervisors->get($index % $this->supervisors->count());
        $sickReasons = $this->dataset['attendance_absences']['sick_reasons'] ?? ['Sakit demam dan istirahat dokter.'];
        $permissionReasons = $this->dataset['attendance_absences']['permission_reasons'] ?? ['Izin urusan administrasi keluarga.'];

        foreach ($entries as $i => $status) {
            $date = $registration->start_date->copy()->addDays($i + 10);
            $values = [
                'user_id' => $registration->student_id,
                'registration_id' => $registration->id,
                'date' => $date->toDateString(),
                'clock_in' => $status === AttendanceStatus::LATE->value ? '08:15' : '07:45',
                'clock_out' => '16:00',
                'clock_in_latitude' => '-6.2088',
                'clock_in_longitude' => '106.8456',
                'clock_out_latitude' => '-6.2088',
                'clock_out_longitude' => '106.8456',
                'status' => $status,
                'is_verified' => $i % 3 === 0,
            ];

            if ($i % 3 === 0) {
                $values['verified_by'] = $supervisor->id;
                $values['verified_at'] = $date->copy()->addDay()->toDateTimeString();
            }

            if ($status === AttendanceStatus::SICK->value) {
                $values['absence_type'] = $status;
                $values['absence_reason'] = $sickReasons[$index % count($sickReasons)];
                $values['absence_status'] = 'approved';
            } elseif ($status === AttendanceStatus::PERMISSION->value) {
                $values['absence_type'] = $status;
                $values['absence_reason'] = $permissionReasons[$index % count($permissionReasons)];
                $values['absence_status'] = 'approved';
            }

            $this->firstOrCreate(
                Attendance::factory(),
                ['registration_id' => $registration->id, 'date' => $date->toDateTimeString()],
                $values,
                'attendances',
            );
        }
    }

    private function seedAbsenceRequests(): void
    {
        $requests = [
            [AbsenceRequestStatus::PENDING->value, AbsenceReasonType::SICK->value],
            [AbsenceRequestStatus::APPROVED->value, AbsenceReasonType::PERMISSION->value],
            [AbsenceRequestStatus::REJECTED->value, AbsenceReasonType::OTHER->value],
        ];

        $sickReasons = $this->dataset['attendance_absences']['sick_reasons'] ?? ['Sakit demam rawat jalan.'];
        $permissionReasons = $this->dataset['attendance_absences']['permission_reasons'] ?? ['Izin lomba kejuruan sekolah.'];

        foreach ($requests as $i => [$status, $reason]) {
            $registration = $this->activeRegistrations->get($i);
            if (! $registration) {
                continue;
            }
            $date = $registration->start_date->copy()->addDays($i + 5);
            $reasonText = $reason === AbsenceReasonType::SICK->value
                ? ($sickReasons[$i % count($sickReasons)])
                : ($permissionReasons[$i % count($permissionReasons)]);

            $this->firstOrCreate(
                AbsenceRequest::factory(),
                ['user_id' => $registration->student_id, 'registration_id' => $registration->id, 'date' => $date->toDateTimeString()],
                [
                    'user_id' => $registration->student_id,
                    'registration_id' => $registration->id,
                    'date' => $date->toDateString(),
                    'absence_type' => $reason,
                    'absence_reason' => $reasonText,
                    'absence_status' => $status,
                ],
                'absence_requests',
            );
        }
    }

    private function seedSupervisionAndVisits(Registration $registration, int $index): void
    {
        $groupIndex = intdiv($index, 5);
        $supervisor = $this->supervisors->get($groupIndex % $this->supervisors->count());
        $teacher = $this->teachers->get($groupIndex % $this->teachers->count());

        $statuses = [
            SupervisionLogStatus::DRAFT->value,
            SupervisionLogStatus::SUBMITTED->value,
            SupervisionLogStatus::VERIFIED->value,
        ];

        $topics = $this->dataset['supervision_topics'] ?? [
            ['topic' => 'Pemantauan kemajuan PKL', 'notes' => 'Progres kerja berjalan baik.'],
        ];

        foreach ($statuses as $i => $status) {
            $date = $registration->start_date->copy()->addDays($i + 20);
            $topicItem = $topics[$i % count($topics)];

            $this->firstOrCreate(
                SupervisionLog::factory(),
                ['registration_id' => $registration->id, 'date' => $date->toDateTimeString(), 'type' => SupervisionType::MONITORING->value],
                [
                    'registration_id' => $registration->id,
                    'supervisor_id' => $supervisor->id,
                    'type' => SupervisionType::MONITORING->value,
                    'date' => $date->toDateString(),
                    'topic' => $topicItem['topic'],
                    'notes' => $topicItem['notes'] ?? null,
                    'status' => $status,
                ],
                'supervision_logs',
            );
        }

        $visits = $this->dataset['monitoring_visits'] ?? [
            ['method' => VisitMethod::SITE_VISIT->value, 'topic' => 'Kunjungan Lapangan I', 'notes' => 'Observasi adaptasi kerja siswa.'],
            ['method' => VisitMethod::VIRTUAL_MEETING->value, 'topic' => 'Monitoring Daring II', 'notes' => 'Koordinasi laporan progres PKL.'],
        ];

        foreach ($visits as $i => $visitItem) {
            $date = $registration->start_date->copy()->addDays($i + 30);
            $method = $visitItem['method'] ?? VisitMethod::SITE_VISIT->value;

            $this->firstOrCreate(
                MonitoringVisit::factory(),
                ['registration_id' => $registration->id, 'visit_date' => $date->toDateTimeString()],
                [
                    'registration_id' => $registration->id,
                    'teacher_id' => $teacher->id,
                    'visit_date' => $date->toDateString(),
                    'method' => $method,
                    'notes' => $visitItem['notes'] ?? null,
                    'is_verified' => $i === 0,
                ],
                'monitoring_visits',
            );
        }
    }

    private function seedFinalization(): void
    {
        foreach ($this->activeRegistrations as $index => $registration) {
            $this->seedAssessments($registration, $index);
            $this->seedReport($registration, false);
        }

        foreach ($this->completedRegistrations as $index => $registration) {
            $this->seedReport($registration, true, $index);
            $this->seedCertificate($registration);
        }

        $this->seedSubmissions();
    }

    private function seedAssessments(Registration $registration, int $index): void
    {
        $rubric = Rubric::where('internship_id', $this->activeInternship->id)->first();
        $groupIndex = intdiv($index, 5);
        $teacher = $this->teachers->get($groupIndex % $this->teachers->count());
        $supervisor = $this->supervisors->get($groupIndex % $this->supervisors->count());

        $evaluations = [
            ['type' => 'midterm', 'evaluator' => $teacher, 'finalized' => false, 'score' => 88.5],
            ['type' => 'final', 'evaluator' => $supervisor, 'finalized' => true, 'score' => 92.0],
        ];

        foreach ($evaluations as $evaluation) {
            $this->firstOrCreate(
                Assessment::factory(),
                [
                    'registration_id' => $registration->id,
                    'assessment_type' => $evaluation['type'],
                    'evaluator_id' => $evaluation['evaluator']->id,
                ],
                [
                    'registration_id' => $registration->id,
                    'rubric_id' => $rubric?->id,
                    'evaluator_id' => $evaluation['evaluator']->id,
                    'assessment_type' => $evaluation['type'],
                    'score' => $evaluation['score'],
                    'finalized_at' => $evaluation['finalized'] ? now() : null,
                ],
                'assessments',
            );
        }
    }

    private function seedReport(Registration $registration, bool $finalized, int $index = 0): void
    {
        $values = [
            'registration_id' => $registration->id,
            'status' => $finalized ? StudentReportStatus::FINALIZED->value : StudentReportStatus::DRAFT->value,
        ];

        if ($finalized) {
            $values['finalized_by'] = $this->teachers->get($index % $this->teachers->count())->id;
            $values['finalized_at'] = now();
            $values['supervisor_score'] = 91.5;
            $values['teacher_score'] = 89.0;
            $values['exam_score'] = 90.0;
            $values['final_score'] = 90.5;
            $values['grade_letter'] = 'A';
            $values['industry_feedback'] = 'Siswa menunjukkan disiplin tinggi, kompetensi teknis yang kuat, dan kerjasama tim yang sangat baik.';
        }

        $this->firstOrCreate(
            StudentReport::factory(),
            ['registration_id' => $registration->id],
            $values,
            'reports',
        );
    }

    private function seedCertificate(Registration $registration): void
    {
        $template = $this->dataset['certificate_template']['content_template'] ?? '<h1>Sertifikat PKL</h1><p>Diberikan kepada {{ student_name }}</p>';

        $this->firstOrCreate(
            Certificate::factory(),
            ['registration_id' => $registration->id],
            [
                'registration_id' => $registration->id,
                'status' => 'issued',
                'template_content' => $template,
                'issued_by' => $this->admin->id,
                'issued_at' => $registration->end_date?->copy()->addDays(7)->toDateTimeString(),
            ],
            'certificates',
        );
    }

    private function seedSubmissions(): void
    {
        $assignments = Assignment::where('internship_id', $this->activeInternship->id)->get();
        $feedbacks = $this->dataset['submission_feedbacks'] ?? [
            ['score' => 90.0, 'feedback' => 'Pengerjaan tugas sistematis dan sesuai arahan.'],
        ];

        foreach ($assignments as $assignment) {
            foreach ($this->activeRegistrations->take(20) as $index => $registration) {
                $status = match ($index % 3) {
                    0 => SubmissionStatus::GRADED->value,
                    1 => SubmissionStatus::VERIFIED->value,
                    default => SubmissionStatus::DRAFT->value,
                };

                $feedbackItem = $feedbacks[$index % count($feedbacks)];

                $values = [
                    'assignment_id' => $assignment->id,
                    'registration_id' => $registration->id,
                    'student_id' => $registration->student_id,
                    'content' => 'Dokumentasi hasil pengerjaan penugasan praktik kerja lapangan terlampir lengkap.',
                    'status' => $status,
                    'submitted_at' => $status === SubmissionStatus::DRAFT->value ? null : now()->subDays(2),
                ];

                if ($status === SubmissionStatus::GRADED->value) {
                    $values['score'] = $feedbackItem['score'];
                    $values['feedback'] = $feedbackItem['feedback'];
                    $values['graded_by'] = $this->teachers->first()->id;
                    $values['graded_at'] = now()->subDay();
                }

                $this->firstOrCreate(
                    Submission::factory(),
                    ['assignment_id' => $assignment->id, 'registration_id' => $registration->id],
                    $values,
                    'submissions',
                );
            }
        }
    }

    private function seedSysAdminData(): void
    {
        $announcements = $this->dataset['announcements'] ?? [
            ['title' => 'Pembukaan Pendaftaran PKL', 'status' => AnnouncementStatus::PUBLISHED->value, 'message' => 'Pendaftaran PKL resmi dibuka.'],
        ];

        foreach ($announcements as $announcement) {
            $this->firstOrCreate(
                Announcement::factory(),
                ['title' => $announcement['title']],
                [
                    'title' => $announcement['title'],
                    'message' => $announcement['message'],
                    'status' => $announcement['status'],
                    'created_by' => $this->admin->id,
                ],
                'announcements',
            );
        }

        $notifications = $this->dataset['notifications'] ?? [
            ['title' => 'Penempatan Magang Disetujui', 'message' => 'Pendaftaran PKL telah disetujui.', 'is_read' => true],
        ];

        foreach ($notifications as $notification) {
            $this->firstOrCreate(
                Notification::factory(),
                ['user_id' => $this->admin->id, 'title' => $notification['title']],
                [
                    'user_id' => $this->admin->id,
                    'title' => $notification['title'],
                    'message' => $notification['message'],
                    'is_read' => $notification['is_read'],
                    'read_at' => $notification['is_read'] ? now() : null,
                ],
                'notifications',
            );
        }

        $applications = $this->dataset['account_applications'] ?? [
            ['email' => 'calon.siswa1@sekolah.sch.id', 'status' => 'approved', 'notes' => 'Berkas lengkap'],
        ];

        foreach ($applications as $application) {
            $this->firstOrCreate(
                AccountApplication::factory(),
                ['email' => $application['email']],
                [
                    'email' => $application['email'],
                    'department_id' => $this->departments->first()->id,
                    'status' => $application['status'],
                    'rejection_reason' => $application['rejection_reason'] ?? null,
                    'processed_by' => $this->admin->id,
                ],
                'account_applications',
            );
        }

        $changes = $this->dataset['direct_placement_changes'] ?? [];
        foreach ($changes as $i => $change) {
            $student = User::firstWhere('email', $change['student_email'] ?? '');
            $registration = $student ? Registration::firstWhere('student_id', $student->id) : null;
            if (! $registration) {
                continue;
            }

            $toPlacement = $this->activePlacements->get(($change['to_placement_index'] ?? 1) - 1)
                ?? $this->activePlacements->first();

            $this->firstOrCreate(
                PlacementChangeRequest::factory(),
                ['registration_id' => $registration->id, 'from_placement_id' => $registration->placement_id],
                [
                    'registration_id' => $registration->id,
                    'from_placement_id' => $registration->placement_id,
                    'to_placement_id' => $toPlacement->id,
                    'reason' => $change['reason'] ?? 'Relokasi langsung oleh Admin.',
                    'requested_by' => $registration->student_id,
                    'status' => $change['status'] ?? PlacementChangeStatus::APPROVED->value,
                    'processed_by' => $this->admin->id,
                    'processed_at' => now(),
                ],
                'placement_change_requests',
            );
        }
    }

    private function ensureUser(string $email, string $role, string $countKey, ?string $name = null): User
    {
        $user = User::firstWhere('email', $email);

        if ($user === null) {
            $user = User::factory()->create([
                'id' => self::dummyId(),
                'name' => $name ?? 'Pengguna Demo',
                'email' => $email,
                'username' => Str::before($email, '@'),
                'password' => Hash::make($this->password),
            ]);
            $this->bump($countKey);
        } else {
            $user->update([
                'password' => Hash::make($this->password),
                'status' => 'activated',
                'is_active' => true,
            ]);
        }

        $user->assignRole($role);

        return $user;
    }

    private function ensureProfile(User $user, Factory $factory, array $values, string $countKey): Profile
    {
        return $this->firstOrCreate(
            $factory,
            ['user_id' => $user->id],
            array_merge(['user_id' => $user->id], $values),
            $countKey,
        );
    }

    private function firstOrCreate(Factory $factory, array $where, array $values, ?string $countKey): Model
    {
        $existing = $factory->newModel()->newQuery()->where($where)->first();

        if ($existing !== null) {
            return $existing;
        }

        $record = $factory->create(array_merge(['id' => self::dummyId()], $values));

        if ($countKey !== null) {
            $this->bump($countKey);
        }

        return $record;
    }

    private function bump(string $key): void
    {
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;
    }

    /**
     * @return list<array{name: string, order: int, weight: int}>
     */
    private function internshipPhases(): array
    {
        return [
            ['name' => 'Pendaftaran & Seleksi', 'order' => 1, 'weight' => 5],
            ['name' => 'Pembekalan', 'order' => 2, 'weight' => 5],
            ['name' => 'Pelaksanaan PKL', 'order' => 3, 'weight' => 70],
            ['name' => 'Pelaporan & Penilaian', 'order' => 4, 'weight' => 12],
            ['name' => 'Evaluasi', 'order' => 5, 'weight' => 8],
        ];
    }
}
