# Reports — Final Grade Card Compilation, Weighted Aggregation & Archival Snapshot

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering grade card creation,
> cross-module score aggregation, configurable grading weights, grade letter classification,
> finalization with snapshot, and PDF download

## Description

Complete specification of the Internara Reports module: grade card creation linked 1:1 to student
registrations, weighted score aggregation from Assessment and Submission models via configurable
program-level weights, grade letter classification, immutable finalization with identity/metadata
archival snapshot, and PDF download through cross-module Document reference.

---

## 1. Problem Statements

### PS-1 — Centralized Grade Compilation Across Disparate Scoring Sources

Student performance data is scattered across multiple modules: industry supervisors record
attendance and competency evaluations in Assessment, school teachers provide pedagogical grades,
exam scores come from formal assessments, and assignment submissions carry their own grades. There
is no single authoritative record that compiles these into a final grade card. Without
centralization, administrators must manually aggregate scores from different screens, introducing
calculation errors and inconsistencies.

### PS-2 — Configurable Grading Weight Distribution Per Program

Different internship programs assign different importance to each scoring source. A vocational
program (SMK) may weight industry supervisor feedback at 40% while an academic program (SMA) may
weight exam results higher. Hardcoding weight percentages into the aggregation logic makes the
system rigid. The weights must be configurable per internship program and read dynamically at
calculation time.

### PS-3 — Cross-Module Score Aggregation With Verified Completeness

Grade calculation requires querying Assessment and Submission models across module boundaries.
If scores are missing (e.g., a supervisor hasn't submitted an evaluation), the system must handle
the gap gracefully — either defaulting to zero or refusing calculation — rather than producing a
silently incorrect final score. The aggregation logic must be explicit about which sources
contribute and how missing data is handled.

### PS-4 — Archival Snapshot for Historical Grade Accuracy

Once a grade card is finalized and used for certification, the underlying source records (student
name, supervisor assignment, company details) may change — a student transfers, a supervisor
leaves, or a company rebrands. The grade card must capture a frozen snapshot of all relevant
identity and metadata at finalization time so that historical grade cards remain accurate and
auditable regardless of future data mutations.

### PS-5 — Finalization Immutability and Coordinator Sign-Off

A draft grade card can be edited freely, but once the coordinator signs off (finalizes), the
grade must be locked permanently. Without a clear DRAFT→FINALIZED state transition with
immutability enforcement, a late edit could silently alter a student's grade after it has been
communicated to the student or used for certificate generation.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a DRAFT-to-FINALIZED lifecycle for grade cards with coordinator sign-off |
| G2  | Aggregate scores from Assessment (supervisor, teacher, exam) and Submission (assignment) using configurable per-program weights |
| G3  | Compute composite final scores on a 0–100 scale and assign grade letters via fixed thresholds |
| G4  | Capture an immutable identity/metadata snapshot at finalization for standalone archival |
| G5  | Allow administrators to download finalized grade cards as PDF through cross-module Document reference |
| G6  | Dispatch domain events (`GradeCalculated`, `ReportFinalized`) for downstream consumers |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Grade appeal or dispute workflow |
| NG2  | Automated finalization when internship period ends (manual coordinator action only) |
| NG3  | Student-facing grade view (students see grades via Certification module, not Reports) |
| NG4  | Historical grade recalculation or retroactive weight changes |
| NG5  | Multi-tenancy or cross-tenant grade comparisons |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates a Report (Grade Card)

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` role; a Registration exists for a student
**Flow:**
1. Admin navigates to the reports management area
2. System calls `CreateReportAction::execute(CreateReportData $data)` with the target `registrationId`
3. Action validates that no Report already exists for this registration (unique constraint)
4. Action creates Report with `status = DRAFT` and all score fields `null`
5. Report persisted with `registration_id` linked to the Registration
**Postconditions:** DRAFT Report exists, ready for grade calculation

### UC-2 — Admin Calculates Final Grades

**Actor:** Admin
**Preconditions:** Report exists in DRAFT status; linked Registration has an Internship with `grading_weights` configured; student has Assessment and/or Submission records
**Flow:**
1. Admin triggers grade calculation on the Report
2. System calls `CalculateFinalGradeAction::execute(Report $report)`
3. Action reads `grading_weights` from `$report->registration->internship->grading_weights`
4. Action queries Assessment model for supervisor_score, teacher_score, exam_score
5. Action queries Submission model for assignment average
6. Action computes weighted composite: `(supervisor × w1) + (teacher × w2) + (assignment × w3) + (exam × w4)`
7. Action assigns grade letter: A ≥ 90, B ≥ 80, C ≥ 70, D ≥ 60, E < 60
8. Action dispatches `GradeCalculated` event
9. Action returns updated Report with populated score fields
**Postconditions:** Report has `final_score` and `grade_letter` populated; `GradeCalculated` event dispatched

### UC-3 — Admin Finalizes Report

**Actor:** Admin
**Preconditions:** Report exists in DRAFT status with `final_score` and `grade_letter` populated
**Flow:**
1. Admin reviews the Report and confirms accuracy
2. System calls `FinalizeReportAction::execute(Report $report, string $finalizedBy)`
3. Action transitions `status` from DRAFT to FINALIZED
4. Action sets `finalized_by` and `finalized_at`
5. Action dispatches `ReportFinalized` event
6. `ReportObserver::saved()` detects FINALIZED status, calls `captureSnapshot()`
7. Snapshot saves student/internship/company/supervisor/teacher names into `archived_data`
8. Report saved quietly (without triggering observer again)
**Postconditions:** Report is FINALIZED, immutable; `archived_data` populated with identity snapshot; `ReportFinalized` event dispatched

### UC-4 — Admin Downloads Report

**Actor:** Admin
**Preconditions:** Admin is authenticated with `admin` role; finalized Report exists with generated PDF
**Flow:**
1. Admin requests download for the Report
2. Route `GET /admin/reports/{report}/download` resolved
3. `ReportController@download` finds Document by the report's document ID (cross-module reference)
4. Controller authorizes the user
5. Controller tries media URL first (Spatie MediaLibrary), falls back to local file path
6. File streamed to browser with appropriate headers
**Postconditions:** PDF downloaded by admin

---

## 4. Functional Requirements

### Report Creation

| ID     | Requirement |
| ------ | ----------- |
| FR-CR1 | System must allow admins to create a Report linked to a Registration via `CreateReportAction` |
| FR-CR2 | System must enforce 1:1 uniqueness between Report and Registration (unique constraint on `registration_id`) |
| FR-CR3 | Newly created Reports must default to `status = DRAFT` with all score fields `null` |
| FR-CR4 | `CreateReportData` DTO must accept exactly one field: `registrationId` (string) |

### Grade Calculation

| ID     | Requirement |
| ------ | ----------- |
| FR-GC1 | System must read `grading_weights` from `$registration->internship->grading_weights` with defaults: supervisor=40, teacher=20, assignment=20, exam=20 |
| FR-GC2 | System must query Assessment model for `supervisor_score`, `teacher_score`, and `exam_score` |
| FR-GC3 | System must query Submission model for assignment average score |
| FR-GC4 | System must compute weighted composite: `(supervisor_score × supervisor_weight) + (teacher_score × teacher_weight) + (assignment_avg × assignment_weight) + (exam_score × exam_weight)` normalized to 0–100 |
| FR-GC5 | System must assign grade letter: A ≥ 90, B ≥ 80, C ≥ 70, D ≥ 60, E < 60 |
| FR-GC6 | System must dispatch `GradeCalculated` event carrying the updated Report |

### Finalization

| ID     | Requirement |
| ------ | ----------- |
| FR-FI1 | System must transition Report `status` from DRAFT to FINALIZED via `FinalizeReportAction` |
| FR-FI2 | System must record `finalized_by` (User ID) and `finalized_at` (timestamp) on finalization |
| FR-FI3 | System must dispatch `ReportFinalized` event carrying the finalized Report |
| FR-FI4 | System must enforce immutability: no score or status modifications allowed after FINALIZED |
| FR-FI5 | System must require `final_score` and `grade_letter` to be non-null before allowing finalization |

### Archival

| ID     | Requirement |
| ------ | ----------- |
| FR-AR1 | System must capture `archived_data` snapshot on finalization containing: student name/NISN, internship dates, company name/address, department name, supervisor name, teacher name |
| FR-AR2 | Snapshot must be captured via `ReportObserver::saved()` when status transitions to FINALIZED |
| FR-AR3 | Snapshot save must use quiet update to avoid recursive observer invocation |

### Download

| ID     | Requirement |
| ------ | ----------- |
| FR-DL1 | System must serve finalized Report PDFs via `ReportController@download` at `GET /admin/reports/{report}/download` |
| FR-DL2 | Controller must resolve Document by cross-module reference, authorize user, try media URL then local file fallback |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | Report creation and finalization endpoints must require authenticated admin role via middleware |
| NFR-S2 | `archived_data` must be stored as encrypted JSON to protect PII (student NISN, names) |
| NFR-P1 | Grade calculation (`CalculateFinalGradeAction`) must complete within 2 seconds for a single Report |
| NFR-P2 | `reports` table must use UUID primary keys to prevent enumeration |
| NFR-R1 | Grade calculation must be idempotent — recalculating the same Report produces identical results given identical source data |
| NFR-R2 | Report finalization must be atomic — status transition, snapshot capture, and event dispatch must succeed or fail as a unit |
| NFR-U1 | Admin must see clear DRAFT/FINALIZED status indicators on all Report views |
| NFR-U2 | Download endpoint must return appropriate `Content-Type` and `Content-Disposition` headers for PDF files |
| NFR-M1 | Report model must use `#[Fillable]` attribute for mass-assignment protection |
| NFR-M2 | All Actions must extend BaseCommandAction and follow Action Triad conventions |

---

## 6. API / Data Contracts

### Report Model

```php
// app/Models/Report.php
final class Report extends Model
{
    use HasUuids, HasFactory;

    #[Fillable]
    protected $fillable = [
        'registration_id',
        'supervisor_score',
        'teacher_score',
        'exam_score',
        'final_score',
        'grade_letter',
        'industry_feedback',
        'status',
        'finalized_by',
        'finalized_at',
        'archived_data',
    ];

    protected $casts = [
        'status'         => ReportStatus::class,
        'supervisor_score' => 'float',
        'teacher_score'    => 'float',
        'exam_score'       => 'float',
        'final_score'      => 'float',
        'finalized_at'     => 'datetime',
        'archived_data'    => 'json',
    ];

    // Relations
    public function registration(): BelongsTo { /* → Registration */ }
    public function finalizedBy(): BelongsTo { /* → User */ }

    // Methods
    public function captureSnapshot(): void { /* snapshots identity into archived_data */ }
}
```

### ReportStatus Enum

```php
// app/Enums/ReportStatus.php
enum ReportStatus: string
{
    case DRAFT      = 'draft';
    case FINALIZED  = 'finalized';

    public function isTerminal(): bool
    {
        return $this === self::FINALIZED;
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT     => [self::FINALIZED],
            self::FINALIZED => [],
        };
    }
}
```

### CreateReportData DTO

```php
// app/DTOs/CreateReportData.php
final readonly class CreateReportData extends BaseData
{
    public function __construct(
        public string $registrationId,
    ) {}
}
```

### Action Signatures

```php
// app/Actions/Reports/CreateReportAction.php
class CreateReportAction extends BaseCommandAction
{
    public function execute(CreateReportData $data): Report { /* ... */ }
}

// app/Actions/Reports/CalculateFinalGradeAction.php
class CalculateFinalGradeAction extends BaseCommandAction
{
    public function execute(Report $report): Report { /* ... */ }
}

// app/Actions/Reports/FinalizeReportAction.php
class FinalizeReportAction extends BaseCommandAction
{
    public function execute(Report $report, string $finalizedBy): Report { /* ... */ }
}
```

### Events

```php
// app/Events/Reports/GradeCalculated.php
class GradeCalculated extends BaseEvent
{
    public string $eventName = 'report.grade_calculated';
    public function __construct(public Report $report) {}
}

// app/Events/Reports/ReportFinalized.php
class ReportFinalized extends BaseEvent
{
    public string $eventName = 'report.finalized';
    public function __construct(public Report $report) {}
}
```

### Observer

```php
// app/Observers/ReportObserver.php
class ReportObserver
{
    public function saved(Report $report): void
    {
        if ($report->status === ReportStatus::FINALIZED && empty($report->archived_data)) {
            $report->captureSnapshot();
            $report->saveQuietly();
        }
    }
}
```

### Route

```php
Route::get('/admin/reports/{report}/download', [ReportController::class, 'download'])
    ->middleware(['auth', 'admin'])
    ->name('admin.reports.download');
```

### Database Schema — `reports`

| Column | Type | Nullable | Default | Index | FK | Notes |
| ------ | ---- | -------- | ------- | ----- | -- | ----- |
| id | uuid | no | — | PK | — | UUID primary key |
| registration_id | string | yes | — | unique | → registrations | nullOnDelete |
| supervisor_score | float | yes | — | — | — | Industry supervisor score |
| teacher_score | float | yes | — | — | — | School teacher score |
| exam_score | float | yes | — | — | — | Exam assessment score |
| final_score | float | yes | — | — | — | Weighted composite (0–100) |
| grade_letter | string | yes | — | — | — | A/B/C/D/E |
| industry_feedback | text | yes | — | — | — | Qualitative feedback |
| status | string | no | 'draft' | indexed | — | DRAFT or FINALIZED |
| finalized_by | string | yes | — | — | → users | Coordinator user ID |
| finalized_at | timestamp | yes | — | — | — | Finalization timestamp |
| archived_data | json | yes | — | — | — | Identity/metadata snapshot |
| created_at | timestamp | no | — | — | — | — |
| updated_at | timestamp | no | — | — | — | — |

### Grading Weights (read from Program module)

```php
// Default weights (used when program has no custom weights)
$defaults = [
    'supervisor' => 40,
    'teacher'    => 20,
    'assignment' => 20,
    'exam'       => 20,
];

// Read from: $report->registration->internship->grading_weights
```

---

## 7. Design Decisions

### DD-1 — Observer-Based Snapshot Capture (Not Inline in Finalization)

**Decision:** Snapshot capture is implemented in `ReportObserver::saved()` rather than inside
`FinalizeReportAction::execute()`.

**Rationale:** Separating snapshot capture into the observer keeps the Action focused on state
transition and event dispatch. The observer pattern ensures snapshot capture is triggered
regardless of how finalization occurs (direct Action call, queue job, Artisan command). It also
avoids coupling the Action to the specific fields that need snapshotting — the observer owns that
responsibility.

**Trade-off:** Observer logic is implicit — a developer reading `FinalizeReportAction` won't
immediately see that snapshot capture occurs. This is mitigated by the observer being colocated
in the same module directory and the `captureSnapshot()` method being clearly named on the
Report model.

**Rejected alternative:** Inline snapshot in `FinalizeReportAction` — simpler to trace but
creates a fat Action and duplicates logic if finalization is triggered from multiple paths.

### DD-2 — Grade Letter Thresholds as Simple Comparison (Not Configurable)

**Decision:** Grade letter thresholds are hardcoded: A ≥ 90, B ≥ 80, C ≥ 70, D ≥ 60, E < 60.

**Rationale:** Indonesian PKL grading follows a standardized national scale. Making thresholds
configurable adds complexity (config storage, UI, validation) for no practical benefit — schools
do not customize these boundaries. Simple comparison is transparent, auditable, and matches the
regulated grading policy.

**Trade-off:** If a school requests non-standard thresholds, a code change is required. This is
considered acceptable given the regulatory context.

**Rejected alternative:** Configurable thresholds stored in `grading_weights` — adds UI burden
and config validation with no current demand.

### DD-3 — ReportController References Document Model (Cross-Module Download)

**Decision:** `ReportController@download` resolves a Document by ID to serve the PDF, rather than
generating the PDF on-the-fly or storing the PDF directly on the Report model.

**Rationale:** PDF generation and storage are responsibilities of the Document module. Reports
module owns the grade data; Document module owns rendering and file management. This separation
follows module boundaries and allows the Document module to evolve its rendering pipeline
independently. The ReportController acts as a thin adapter that authorizes access and delegates
to the Document module's file resolution.

**Trade-off:** Cross-module dependency means Reports depends on Document module being installed
and functional. This is acceptable because Document is a core module in the module dependency
graph.

**Rejected alternative:** Store PDF bytes directly on Report model — violates single
responsibility and duplicates file storage logic already handled by Spatie MediaLibrary in the
Document module.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Grade calculation accuracy | 100% match between manual spreadsheet calculation and `CalculateFinalGradeAction` output for test data |
| Finalization time | < 1 second from trigger to snapshot persisted and event dispatched |
| Snapshot completeness | `archived_data` contains all 7 fields (student name, NISN, internship dates, company, department, supervisor, teacher) for every finalized Report |
| PDF download success rate | 100% of finalized Reports with generated documents serve a valid PDF |
| Zero data loss | No score modifications possible on a FINALIZED Report (verified by integration tests) |
| Observer reliability | Snapshot captured exactly once per finalization (no duplicates, no misses) regardless of finalization path |
| Weight calculation correctness | Recalculation with identical source data produces identical `final_score` (idempotency) |

---

## Quick References

- `app/Actions/Reports/CreateReportAction.php` — Report creation Action
- `app/Actions/Reports/CalculateFinalGradeAction.php` — Weighted score aggregation Action
- `app/Actions/Reports/FinalizeReportAction.php` — Finalization with state transition
- `app/Models/Report.php` — Report model with `captureSnapshot()`
- `app/Enums/ReportStatus.php` — DRAFT/FINALIZED enum with transitions
- `app/DTOs/CreateReportData.php` — Creation DTO
- `app/Events/Reports/GradeCalculated.php` — Grade calculation event
- `app/Events/Reports/ReportFinalized.php` — Finalization event
- `app/Observers/ReportObserver.php` — Snapshot observer
- `app/Http/Controllers/ReportController.php` — Download controller
- `database/migrations/*_create_reports_table.php` — Reports schema
- `docs/modules/reports.md` — Reports module conceptual documentation
- `docs/modules/reports-reference.md` — Reports module reference documentation
- `docs/architecture/action-pattern.md` — Action Triad pattern
- `docs/architecture/entity-pattern.md` — Entity contracts
- `docs/architecture/data-pattern.md` — DTO/Data contracts
- `docs/conventions.md` — Coding conventions and invariants
