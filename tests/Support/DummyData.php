<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Academics\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Academics\Domain\AcademicYear\Support\AcademicYearPeriod;
use App\Modules\Academics\Domain\Department\Models\Department;
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
use App\Modules\Evaluation\Models\EvaluationAnswer;
use App\Modules\Evaluation\Models\EvaluationForm;
use App\Modules\Evaluation\Models\EvaluationQuestion;
use App\Modules\Evaluation\Models\EvaluationResponse;
use App\Modules\Evaluation\Models\EvaluationSection;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentSeverity;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentStatus;
use App\Modules\Incident\Domain\IncidentReport\Enums\IncidentType;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\Journals\Domain\AbsenceRequest\Models\AbsenceRequest;
use App\Modules\Journals\Domain\Attendance\Enums\AttendanceStatus;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use App\Modules\Journals\Domain\MonitoringVisit\Enums\VisitMethod;
use App\Modules\Journals\Domain\MonitoringVisit\Models\MonitoringVisit;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionType;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\Partners\Domain\Partnership\Models\Partnership;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Enums\InternshipGroupRole;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroup;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Reports\Domain\StudentReport\Enums\StudentReportStatus;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use App\Modules\SysAdmin\Domain\Announcement\Enums\AnnouncementStatus;
use App\Modules\SysAdmin\Domain\Announcement\Models\Announcement;
use App\Modules\User\Domain\Notifications\Models\Notification;
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
 * Factory-driven demo dataset generator (dev-only, autoloaded via `autoload-dev`).
 *
 * Implements docs/specs/3UOZP-dummy-data.md: generates a coherent, interconnected demo dataset using
 * the existing model factories. Runs inside a single DB transaction (FR-H13), reuses base-seeded
 * data (roles, settings, active academic year — FR-H14), and is idempotent via firstOrCreate on
 * natural keys (FR-H5). Indonesian-locale demo content via app.faker_locale = id_ID (DD-7).
 */
final class DummyData
{
    private string $password;

    /** @var array<string, int> */
    private array $counts = [];

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

    public static function make(): self
    {
        return new self;
    }

    /**
     * Deterministic demo account emails (UC-2, §6.3) — single source of truth
     * for the summary printed by DummySeeder. Derived from config/dummy.php.
     *
     * @return list<string>
     */
    public static function demoAccounts(): array
    {
        $accounts = [config('dummy.accounts.admin_email')];

        foreach (range(1, config('dummy.accounts.teacher_count')) as $i) {
            $accounts[] = "teacher{$i}@example.com";
        }

        foreach (range(1, config('dummy.accounts.supervisor_count')) as $i) {
            $accounts[] = "supervisor{$i}@example.com";
        }

        foreach (range(1, config('dummy.accounts.student_count')) as $i) {
            $accounts[] = "student{$i}@example.com";
        }

        return $accounts;
    }

    /**
     * Generates the full demo dataset and returns per-entity creation counts.
     *
     * @return array<string, int>
     */
    public function run(): array
    {
        $this->password = config('dummy.password');
        $originalLocale = config('app.faker_locale');
        config(['app.faker_locale' => 'id_ID']);

        try {
            return DB::transaction(function (): array {
                $this->resetState();

                $this->seedAcademicYears();
                $this->seedDepartments();
                $this->seedCompanies();
                $this->seedPartnerships();
                $this->seedInternships();
                $this->seedPlacements();
                $this->seedUsersAndProfiles();
                $this->seedRegistrations();
                $this->seedGroupsAndDocuments();
                $this->seedAssessmentData();
                $this->seedDailyOps();
                $this->seedFinalization();
                $this->seedSysAdminData();
                $this->seedIncidents();

                return $this->counts;
            });
        } finally {
            config(['app.faker_locale' => $originalLocale]);
        }
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

    /**
     * FR-C1, DD-9 — reuse the active year seeded by AcademicYearSeeder; add exactly one past year.
     */
    private function seedAcademicYears(): void
    {
        $this->activeYear = AcademicYear::query()->where('is_active', true)->first();

        if ($this->activeYear === null) {
            $this->activeYear = $this->firstOrCreate(
                AcademicYear::factory(),
                ['name' => AcademicYearPeriod::nameFor(now())],
                [
                    'name' => AcademicYearPeriod::nameFor(now()),
                    'start_date' => AcademicYearPeriod::startDateFor(now()),
                    'end_date' => AcademicYearPeriod::endDateFor(now()),
                    'is_active' => true,
                ],
                'academic_years',
            );
        }

        [$startYear] = AcademicYearPeriod::yearsFor(now());
        $pastName = ($startYear - 1).'/'.$startYear;

        $this->pastYear = $this->firstOrCreate(
            AcademicYear::factory(),
            ['name' => $pastName],
            [
                'name' => $pastName,
                'start_date' => ($startYear - 1).'-07-01',
                'end_date' => $startYear.'-06-30',
                'is_active' => false,
            ],
            'academic_years',
        );
    }

    /**
     * FR-C2 — at least three vocational-major departments (deterministic Indonesian names).
     */
    private function seedDepartments(): void
    {
        $names = [
            'Rekayasa Perangkat Lunak',
            'Teknik Komputer dan Jaringan',
            'Akuntansi dan Keuangan',
            'Tata Boga',
        ];

        foreach ($names as $name) {
            $this->departments->push(
                $this->firstOrCreate(Department::factory(), ['name' => $name], ['name' => $name], 'departments'),
            );
        }
    }

    /**
     * FR-C3 — 6-8 companies across diverse industry sectors (deterministic Indonesian names).
     */
    private function seedCompanies(): void
    {
        $companies = [
            ['name' => 'PT Karya Digital Nusantara', 'sector' => 'Technology'],
            ['name' => 'PT Mitra Manufaktur Indonesia', 'sector' => 'Manufacturing'],
            ['name' => 'PT Bank Nusantara Sejahtera', 'sector' => 'Finance'],
            ['name' => 'RS Harapan Sehat', 'sector' => 'Healthcare'],
            ['name' => 'PT Ritel Maju Jaya', 'sector' => 'Retail'],
            ['name' => 'CV Edukasi Cerdas', 'sector' => 'Education'],
            ['name' => 'PT Perkasa Logistik', 'sector' => 'General'],
        ];

        foreach ($companies as $company) {
            $this->companies->push(
                $this->firstOrCreate(
                    Company::factory(),
                    ['name' => $company['name']],
                    ['name' => $company['name'], 'industry_sector' => $company['sector']],
                    'companies',
                ),
            );
        }
    }

    /**
     * FR-C4 — one partnership per company, at least one active and one expired.
     */
    private function seedPartnerships(): void
    {
        [$startYear] = AcademicYearPeriod::yearsFor(now());

        foreach ($this->companies as $index => $company) {
            $isExpired = $index === $this->companies->count() - 1;

            $factory = $isExpired ? Partnership::factory()->expired() : Partnership::factory()->active();

            $this->firstOrCreate(
                $factory,
                ['company_id' => $company->id],
                [
                    'company_id' => $company->id,
                    'agreement_number' => sprintf('MOU-%d-%02d', $startYear, $index + 1),
                    'title' => 'Perjanjian Kerja Sama Praktik Kerja Lapangan',
                ],
                'partnerships',
            );
        }
    }

    /**
     * FR-C5 — one active internship (current year) and one completed (past year).
     */
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

    /**
     * FR-C6, DD-10 — one placement per company per internship.
     */
    private function seedPlacements(): void
    {
        foreach ($this->companies as $company) {
            $this->activePlacements->push(
                $this->firstOrCreate(
                    Placement::factory(),
                    ['company_id' => $company->id, 'internship_id' => $this->activeInternship->id],
                    [
                        'company_id' => $company->id,
                        'internship_id' => $this->activeInternship->id,
                        'name' => 'Program PKL '.$company->name,
                        'quota' => 15,
                    ],
                    'placements',
                ),
            );

            $this->completedPlacements->push(
                $this->firstOrCreate(
                    Placement::factory(),
                    ['company_id' => $company->id, 'internship_id' => $this->completedInternship->id],
                    [
                        'company_id' => $company->id,
                        'internship_id' => $this->completedInternship->id,
                        'name' => 'Program PKL '.$company->name,
                        'quota' => 15,
                    ],
                    'placements',
                ),
            );
        }
    }

    /**
     * FR-C7/FR-C8 — deterministic demo accounts (no superadmin, DD-8) with profiles.
     */
    private function seedUsersAndProfiles(): void
    {
        $this->admin = $this->ensureUser(config('dummy.accounts.admin_email'), 'admin', 'users');

        $this->ensureProfile($this->admin, Profile::factory(), [
            'department_id' => null,
            'company_id' => null,
        ], 'profiles');

        $this->teachers = collect();
        foreach (range(1, config('dummy.accounts.teacher_count')) as $i) {
            $user = $this->ensureUser("teacher{$i}@example.com", 'teacher', 'users');
            $this->teachers->push($user);
            $this->ensureProfile($user, Profile::factory()->forTeacher(), [
                'department_id' => $this->departments->get(($i - 1) % $this->departments->count())->id,
            ], 'profiles');
        }

        $this->supervisors = collect();
        foreach (range(1, config('dummy.accounts.supervisor_count')) as $i) {
            $user = $this->ensureUser("supervisor{$i}@example.com", 'supervisor', 'users');
            $this->supervisors->push($user);
            $company = $this->companies->get(($i - 1) % $this->companies->count());
            $this->ensureProfile($user, Profile::factory()->forSupervisor(), [
                'company_id' => $company->id,
                'employment_status' => EmploymentStatus::FULL_TIME->value,
            ], 'profiles');
        }

        $this->students = collect();
        foreach (range(1, config('dummy.accounts.student_count')) as $i) {
            $user = $this->ensureUser("student{$i}@example.com", 'student', 'users', $this->studentName());
            $this->students->push($user);
            $department = $this->departments->get(($i - 1) % $this->departments->count());
            $this->ensureProfile($user, Profile::factory()->forStudent($department), [
                'national_id_number' => (string) (1000000000 + $i),
            ], 'profiles');
        }
    }

    /**
     * FR-C9 — >= 80% of students registered; majority active with placement, remainder pending.
     */
    private function seedRegistrations(): void
    {
        // Current internship: students 1-16 active with placement, 17-20 pending.
        foreach ($this->students->take(16) as $index => $student) {
            $registration = $this->firstOrCreate(
                Registration::factory(),
                ['student_id' => $student->id, 'internship_id' => $this->activeInternship->id],
                [
                    'student_id' => $student->id,
                    'internship_id' => $this->activeInternship->id,
                    'placement_id' => $this->activePlacements->get($index % $this->activePlacements->count())->id,
                    'start_date' => $this->activeInternship->start_date->toDateString(),
                    'end_date' => $this->activeInternship->end_date->toDateString(),
                    'status' => 'active',
                ],
                'registrations',
            );
            $this->activeRegistrations->push($registration);
        }

        foreach ($this->students->slice(16, 4) as $index => $student) {
            $company = $this->companies->get($index % $this->companies->count());
            $registration = $this->firstOrCreate(
                Registration::factory(),
                ['student_id' => $student->id, 'internship_id' => $this->activeInternship->id],
                [
                    'student_id' => $student->id,
                    'internship_id' => $this->activeInternship->id,
                    'status' => 'pending',
                    'proposed_company_details' => [
                        'company_name' => $company->name,
                        'address' => $company->address,
                    ],
                ],
                'registrations',
            );
            $this->pendingRegistrations->push($registration);
        }

        // Completed internship: students 1-10 with placement (they completed the previous PKL).
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

        $this->reconcileFilledQuotas();
    }

    /**
     * FR-H12 — every placement's filled_quota equals the number of active registrations assigned.
     */
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

    /**
     * FR-C10 (groups covering placed students) + FR-C11 (documents with mixed verification states).
     */
    private function seedGroupsAndDocuments(): void
    {
        $groupNames = ['Kelompok 1', 'Kelompok 2', 'Kelompok 3', 'Kelompok 4'];

        $groups = collect();
        foreach ($groupNames as $index => $name) {
            $group = $this->firstOrCreate(
                InternshipGroup::factory(),
                ['name' => $name, 'internship_id' => $this->activeInternship->id],
                ['name' => $name, 'internship_id' => $this->activeInternship->id],
                'groups',
            );
            $groups->push($group);

            $teacher = $this->teachers->get($index % $this->teachers->count());
            $supervisor = $this->supervisors->get($index % $this->supervisors->count());

            $this->ensureGroupMember($group, ['user_id' => $teacher->id], InternshipGroupRole::SCHOOL_TEACHER->value);
            $this->ensureGroupMember($group, ['user_id' => $supervisor->id], InternshipGroupRole::INDUSTRY_SUPERVISOR->value);
        }

        foreach ($this->activeRegistrations as $index => $registration) {
            $group = $groups->get($index % $groups->count());
            $this->ensureGroupMember($group, ['registration_id' => $registration->id], InternshipGroupRole::STUDENT->value);
        }

        $documents = [
            ['slug' => 'kebijakan-pkl', 'title' => 'Kebijakan Praktik Kerja Lapangan', 'type' => 'policy'],
            ['slug' => 'panduan-pkl', 'title' => 'Panduan Pelaksanaan PKL', 'type' => 'guideline'],
            ['slug' => 'etika-pkl', 'title' => 'Kode Etik Peserta PKL', 'type' => 'policy'],
        ];

        foreach ($documents as $document) {
            $this->documents->push(
                $this->firstOrCreate(
                    Document::factory(),
                    ['slug' => $document['slug']],
                    [
                        'slug' => $document['slug'],
                        'title' => $document['title'],
                        'type' => $document['type'],
                        'created_by' => $this->admin->id,
                    ],
                    'documents',
                ),
            );
        }

        $requiredIds = $this->documents->pluck('id')->all();
        $this->activeInternship->update(['required_document_ids' => $requiredIds]);
        $this->completedInternship->update(['required_document_ids' => $requiredIds]);

        $statuses = [
            RegistrationDocumentStatus::VERIFIED->value,
            RegistrationDocumentStatus::PENDING->value,
            RegistrationDocumentStatus::REJECTED->value,
        ];

        foreach ($this->activeRegistrations as $index => $registration) {
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

    /**
     * FR-C12 (rubrics + assignments) and FR-H10 (evaluation forms with structure + responses).
     */
    private function seedAssessmentData(): void
    {
        foreach (['Rubrik Penilaian Supervisor', 'Rubrik Penilaian Guru'] as $name) {
            $this->firstOrCreate(
                Rubric::factory(),
                ['internship_id' => $this->activeInternship->id, 'name' => $name],
                ['internship_id' => $this->activeInternship->id, 'name' => $name, 'created_by' => $this->admin->id],
                'rubrics',
            );
        }

        $this->firstOrCreate(
            Rubric::factory(),
            ['internship_id' => $this->completedInternship->id, 'name' => 'Rubrik Penilaian PKL'],
            [
                'internship_id' => $this->completedInternship->id,
                'name' => 'Rubrik Penilaian PKL',
                'created_by' => $this->admin->id,
            ],
            'rubrics',
        );

        $activeTitles = ['Laporan Harian PKL', 'Proyek Akhir Unit', 'Esai Refleksi', 'Praktik Lapangan'];
        $completedTitles = ['Laporan Magang', 'Presentasi Produk', 'Jurnal Kegiatan'];

        foreach ($activeTitles as $title) {
            $this->firstOrCreate(
                Assignment::factory()->published(),
                ['internship_id' => $this->activeInternship->id, 'title' => $title],
                [
                    'internship_id' => $this->activeInternship->id,
                    'title' => $title,
                    'document_id' => $this->documents->first()->id,
                    'created_by' => $this->teachers->first()->id,
                    'due_date' => $this->activeInternship->end_date->toDateString(),
                ],
                'assignments',
            );
        }

        foreach ($completedTitles as $title) {
            $this->firstOrCreate(
                Assignment::factory()->published(),
                ['internship_id' => $this->completedInternship->id, 'title' => $title],
                [
                    'internship_id' => $this->completedInternship->id,
                    'title' => $title,
                    'document_id' => $this->documents->first()->id,
                    'created_by' => $this->teachers->first()->id,
                    'due_date' => $this->completedInternship->end_date->toDateString(),
                ],
                'assignments',
            );
        }

        $this->seedEvaluationForms();
    }

    /**
     * FR-H10 — evaluation forms with sections/questions and responses + answers.
     */
    private function seedEvaluationForms(): void
    {
        $forms = [
            ['name' => 'Evaluasi Mentor Industri', 'target_type' => 'mentor'],
            ['name' => 'Evaluasi Program PKL', 'target_type' => 'program'],
            ['name' => 'Evaluasi Perusahaan', 'target_type' => 'company'],
        ];

        foreach ($forms as $formData) {
            $form = $this->firstOrCreate(
                EvaluationForm::factory(),
                ['name' => $formData['name']],
                [
                    'name' => $formData['name'],
                    'target_type' => $formData['target_type'],
                    'created_by' => $this->admin->id,
                ],
                'evaluation_forms',
            );

            $sections = [
                ['title' => 'Kualitas Bimbingan', 'questions' => [
                    'Kejelasan arahan dan materi bimbingan',
                    'Ketersediaan mentor saat dibutuhkan',
                    'Kualitas umpan balik yang diberikan',
                ]],
                ['title' => 'Lingkungan Kerja', 'questions' => [
                    'Kenyamanan dan keamanan lingkungan kerja',
                    'Kelengkapan sarana dan fasilitas',
                ]],
            ];

            foreach ($sections as $sectionIndex => $sectionData) {
                $section = $this->firstOrCreate(
                    EvaluationSection::factory(),
                    ['form_id' => $form->id, 'title' => $sectionData['title']],
                    ['form_id' => $form->id, 'title' => $sectionData['title'], 'order' => $sectionIndex],
                    null,
                );

                foreach ($sectionData['questions'] as $questionIndex => $questionText) {
                    $this->firstOrCreate(
                        EvaluationQuestion::factory(),
                        ['form_id' => $form->id, 'question_text' => $questionText],
                        [
                            'form_id' => $form->id,
                            'question_text' => $questionText,
                            'section_id' => $section->id,
                            'order' => $questionIndex,
                        ],
                        null,
                    );
                }
            }

            $this->seedEvaluationResponses($form, $formData['target_type']);
        }
    }

    private function seedEvaluationResponses(EvaluationForm $form, string $targetType): void
    {
        $targetIds = match ($targetType) {
            'mentor' => $this->supervisors->take(2)->pluck('id')->all(),
            'program' => [$this->activeInternship->id],
            default => [$this->companies->first()->id],
        };

        foreach ($targetIds as $targetIndex => $targetId) {
            $evaluator = $this->students->get($targetIndex % $this->students->count());

            $response = $this->firstOrCreate(
                EvaluationResponse::factory(),
                ['form_id' => $form->id, 'evaluator_id' => $evaluator->id, 'target_type' => $targetType, 'target_id' => $targetId],
                [
                    'form_id' => $form->id,
                    'evaluator_id' => $evaluator->id,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'submitted_at' => now(),
                ],
                null,
            );

            foreach (EvaluationQuestion::where('form_id', $form->id)->get() as $question) {
                $this->firstOrCreate(
                    EvaluationAnswer::factory(),
                    ['response_id' => $response->id, 'question_id' => $question->id],
                    ['response_id' => $response->id, 'question_id' => $question->id, 'value' => (string) random_int(1, 5), 'score' => (float) random_int(1, 5)],
                    null,
                );
            }
        }
    }

    /**
     * FR-H8 — daily ops only for current-internship active registrations.
     */
    private function seedDailyOps(): void
    {
        foreach ($this->activeRegistrations as $index => $registration) {
            $this->seedLogbooks($registration);
            $this->seedAttendances($registration, $index);
            $this->seedSupervisionAndVisits($registration, $index);
        }

        $this->seedAbsenceRequests();
    }

    /**
     * FR-C13 — 5-10 logbooks per active registration with mixed statuses.
     */
    private function seedLogbooks(Registration $registration): void
    {
        $statuses = [
            LogbookStatus::DRAFT->value,
            LogbookStatus::DRAFT->value,
            LogbookStatus::SUBMITTED->value,
            LogbookStatus::SUBMITTED->value,
            LogbookStatus::VERIFIED->value,
            LogbookStatus::VERIFIED->value,
        ];

        foreach ($statuses as $i => $status) {
            $date = $registration->start_date->copy()->addDays($i);
            $values = [
                'user_id' => $registration->student_id,
                'registration_id' => $registration->id,
                'date' => $date->toDateString(),
                'content' => fake()->paragraph(),
                'status' => $status,
            ];

            if ($status === LogbookStatus::VERIFIED->value) {
                $values['is_verified'] = true;
                $values['verified_by'] = $this->teachers->first()->id;
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

    /**
     * FR-C14 — 10-20 attendances per active registration with mixed statuses.
     */
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
            AttendanceStatus::LATE->value,
        ];

        $supervisor = $this->supervisors->get($index % $this->supervisors->count());

        foreach ($entries as $i => $status) {
            $date = $registration->start_date->copy()->addDays($i + 10);
            $values = [
                'user_id' => $registration->student_id,
                'registration_id' => $registration->id,
                'date' => $date->toDateString(),
                'clock_in' => $status === AttendanceStatus::LATE->value ? '08:15' : '07:45',
                'clock_out' => '16:00',
                'status' => $status,
                'is_verified' => $i % 3 === 0,
            ];

            if ($i % 3 === 0) {
                $values['verified_by'] = $supervisor->id;
                $values['verified_at'] = $date->copy()->addDay()->toDateTimeString();
            }

            if (in_array($status, [AttendanceStatus::SICK->value, AttendanceStatus::PERMISSION->value], true)) {
                $values['absence_type'] = $status;
                $values['absence_reason'] = fake()->sentence();
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

    /**
     * FR-C14 — a few absence requests with mixed statuses.
     */
    private function seedAbsenceRequests(): void
    {
        $requests = [
            [AbsenceRequestStatus::PENDING->value, AbsenceReasonType::SICK->value],
            [AbsenceRequestStatus::APPROVED->value, AbsenceReasonType::PERMISSION->value],
            [AbsenceRequestStatus::REJECTED->value, AbsenceReasonType::OTHER->value],
        ];

        foreach ($requests as $i => [$status, $reason]) {
            $registration = $this->activeRegistrations->get($i);
            $date = $registration->start_date->copy()->addDays($i + 5);

            $this->firstOrCreate(
                AbsenceRequest::factory(),
                ['user_id' => $registration->student_id, 'registration_id' => $registration->id, 'date' => $date->toDateTimeString()],
                [
                    'user_id' => $registration->student_id,
                    'registration_id' => $registration->id,
                    'date' => $date->toDateString(),
                    'absence_type' => $reason,
                    'absence_reason' => fake()->sentence(),
                    'absence_status' => $status,
                ],
                'absence_requests',
            );
        }
    }

    /**
     * FR-C15 — several supervision logs and monitoring visits per active registration.
     */
    private function seedSupervisionAndVisits(Registration $registration, int $index): void
    {
        $supervisor = $this->supervisors->get($index % $this->supervisors->count());
        $teacher = $this->teachers->get($index % $this->teachers->count());

        $statuses = [
            SupervisionLogStatus::DRAFT->value,
            SupervisionLogStatus::SUBMITTED->value,
            SupervisionLogStatus::VERIFIED->value,
        ];

        foreach ($statuses as $i => $status) {
            $date = $registration->start_date->copy()->addDays($i + 20);

            $this->firstOrCreate(
                SupervisionLog::factory(),
                ['registration_id' => $registration->id, 'date' => $date->toDateTimeString(), 'type' => SupervisionType::MONITORING->value],
                [
                    'registration_id' => $registration->id,
                    'supervisor_id' => $supervisor->id,
                    'type' => SupervisionType::MONITORING->value,
                    'date' => $date->toDateString(),
                    'topic' => 'Pemantauan kemajuan PKL',
                    'status' => $status,
                ],
                'supervision_logs',
            );
        }

        $methods = [VisitMethod::SITE_VISIT->value, VisitMethod::VIRTUAL_MEETING->value];

        foreach ($methods as $i => $method) {
            $date = $registration->start_date->copy()->addDays($i + 30);

            $this->firstOrCreate(
                MonitoringVisit::factory(),
                ['registration_id' => $registration->id, 'visit_date' => $date->toDateTimeString()],
                [
                    'registration_id' => $registration->id,
                    'teacher_id' => $teacher->id,
                    'visit_date' => $date->toDateString(),
                    'method' => $method,
                    'is_verified' => $i === 0,
                ],
                'monitoring_visits',
            );
        }
    }

    /**
     * FR-C16 (assessments + reports), FR-C17 (certificates), FR-C12 (submissions).
     */
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

    /**
     * FR-C16 — midterm (teacher) + final (supervisor) assessments for active registrations.
     */
    private function seedAssessments(Registration $registration, int $index): void
    {
        $rubric = Rubric::where('internship_id', $this->activeInternship->id)->first();
        $teacher = $this->teachers->get($index % $this->teachers->count());
        $supervisor = $this->supervisors->get($index % $this->supervisors->count());

        $evaluations = [
            ['type' => 'midterm', 'evaluator' => $teacher, 'finalized' => false],
            ['type' => 'final', 'evaluator' => $supervisor, 'finalized' => true],
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
                    'rubric_id' => $rubric->id,
                    'evaluator_id' => $evaluation['evaluator']->id,
                    'assessment_type' => $evaluation['type'],
                    'score' => fake()->randomFloat(2, 70, 100),
                    'finalized_at' => $evaluation['finalized'] ? now() : null,
                ],
                'assessments',
            );
        }
    }

    /**
     * FR-C16 — draft reports for the current internship, finalized for the completed one.
     */
    private function seedReport(Registration $registration, bool $finalized, int $index = 0): void
    {
        $values = [
            'registration_id' => $registration->id,
            'status' => $finalized ? StudentReportStatus::FINALIZED->value : StudentReportStatus::DRAFT->value,
        ];

        if ($finalized) {
            $values['finalized_by'] = $this->teachers->get($index % $this->teachers->count())->id;
            $values['finalized_at'] = now();
        }

        $this->firstOrCreate(
            StudentReport::factory(),
            ['registration_id' => $registration->id],
            $values,
            'reports',
        );
    }

    /**
     * FR-C17 — issued certificates for completed-internship registrations.
     */
    private function seedCertificate(Registration $registration): void
    {
        $this->firstOrCreate(
            Certificate::factory(),
            ['registration_id' => $registration->id],
            [
                'registration_id' => $registration->id,
                'status' => 'issued',
                'template_content' => '<h1>Sertifikat PKL</h1><p>Diberikan kepada {{ student_name }}</p>',
                'issued_by' => $this->admin->id,
                'issued_at' => $registration->end_date?->copy()->addDays(7)->toDateTimeString(),
            ],
            'certificates',
        );
    }

    /**
     * FR-C12 — submissions with mixed statuses for the published assignments.
     */
    private function seedSubmissions(): void
    {
        $assignments = Assignment::where('internship_id', $this->activeInternship->id)->get();

        foreach ($assignments as $assignment) {
            foreach ($this->activeRegistrations->take(8) as $index => $registration) {
                $status = match ($index % 3) {
                    0 => SubmissionStatus::GRADED->value,
                    1 => SubmissionStatus::VERIFIED->value,
                    default => SubmissionStatus::DRAFT->value,
                };

                $values = [
                    'assignment_id' => $assignment->id,
                    'registration_id' => $registration->id,
                    'student_id' => $registration->student_id,
                    'content' => fake()->paragraph(),
                    'status' => $status,
                    'submitted_at' => $status === SubmissionStatus::DRAFT->value ? null : now()->subDays(2),
                ];

                if ($status === SubmissionStatus::GRADED->value) {
                    $values['score'] = fake()->randomFloat(1, 70, 100);
                    $values['feedback'] = fake()->sentence();
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

    /**
     * FR-H11 — announcements, notifications, account applications, placement changes (each status).
     */
    private function seedSysAdminData(): void
    {
        $announcements = [
            ['title' => 'Pembukaan Pendaftaran PKL', 'status' => AnnouncementStatus::PUBLISHED->value],
            ['title' => 'Jadwal Pembekalan PKL', 'status' => AnnouncementStatus::SCHEDULED->value],
            ['title' => 'Rancangan Penilaian Akhir', 'status' => AnnouncementStatus::DRAFT->value],
        ];

        foreach ($announcements as $announcement) {
            $this->firstOrCreate(
                Announcement::factory(),
                ['title' => $announcement['title']],
                [
                    'title' => $announcement['title'],
                    'message' => fake()->paragraph(),
                    'status' => $announcement['status'],
                    'created_by' => $this->admin->id,
                ],
                'announcements',
            );
        }

        $notifications = [
            ['title' => 'Pembaruan Sistem', 'is_read' => false],
            ['title' => 'Sertifikat Tersedia', 'is_read' => true],
        ];

        foreach ($notifications as $notification) {
            $this->firstOrCreate(
                Notification::factory(),
                ['user_id' => $this->admin->id, 'title' => $notification['title']],
                [
                    'user_id' => $this->admin->id,
                    'title' => $notification['title'],
                    'message' => fake()->paragraph(),
                    'is_read' => $notification['is_read'],
                    'read_at' => $notification['is_read'] ? now() : null,
                ],
                'notifications',
            );
        }

        $applications = [
            ['email' => 'applicant1@example.com', 'status' => 'pending'],
            ['email' => 'applicant2@example.com', 'status' => 'approved'],
            ['email' => 'applicant3@example.com', 'status' => 'rejected', 'rejection_reason' => 'Dokumen tidak lengkap'],
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

        $statuses = [
            PlacementChangeStatus::PENDING->value,
            PlacementChangeStatus::APPROVED->value,
            PlacementChangeStatus::REJECTED->value,
        ];

        foreach ($statuses as $i => $status) {
            $registration = $this->activeRegistrations->get($i);
            $toPlacement = $this->activePlacements->get(($i + 1) % $this->activePlacements->count());

            $this->firstOrCreate(
                PlacementChangeRequest::factory(),
                ['registration_id' => $registration->id, 'from_placement_id' => $registration->placement_id],
                [
                    'registration_id' => $registration->id,
                    'from_placement_id' => $registration->placement_id,
                    'to_placement_id' => $toPlacement->id,
                    'reason' => 'Perubahan penempatan karena penyesuaian jadwal',
                    'requested_by' => $registration->student_id,
                    'status' => $status,
                ],
                'placement_change_requests',
            );
        }
    }

    /**
     * FR-C18 — incident reports with mixed severities and statuses.
     */
    private function seedIncidents(): void
    {
        $incidents = [
            ['type' => IncidentType::DISCIPLINARY->value, 'severity' => IncidentSeverity::MEDIUM->value, 'status' => IncidentStatus::INVESTIGATING->value, 'offset' => 3],
            ['type' => IncidentType::OTHER->value, 'severity' => IncidentSeverity::LOW->value, 'status' => IncidentStatus::RESOLVED->value, 'offset' => 8],
        ];

        foreach ($incidents as $i => $incident) {
            $registration = $this->activeRegistrations->get($i);
            $date = $registration->start_date->copy()->addDays($incident['offset']);

            $this->firstOrCreate(
                IncidentReport::factory(),
                ['registration_id' => $registration->id, 'incident_date' => $date->toDateTimeString(), 'type' => $incident['type']],
                [
                    'registration_id' => $registration->id,
                    'reported_by' => $this->supervisors->first()->id,
                    'incident_date' => $date->toDateTimeString(),
                    'type' => $incident['type'],
                    'severity' => $incident['severity'],
                    'status' => $incident['status'],
                    'description' => fake()->paragraph(),
                    'location' => 'Lokasi PKL',
                ],
                'incident_reports',
            );
        }
    }

    private function ensureUser(string $email, string $role, string $countKey, ?string $name = null): User
    {
        $user = User::firstWhere('email', $email);

        if ($user === null) {
            $user = User::factory()->create([
                'name' => $name ?? fake()->name(),
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

    /**
     * FR-C19 — a student name carries no academic title: `firstName` + `lastName` only,
     * skipping the faker `id_ID` `suffix` provider (S.Pd, S.Kom, M.TI., ...). See DD-11.
     */
    private function studentName(): string
    {
        return fake()->firstName().' '.fake()->lastName();
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

        $record = $factory->create($values);

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
