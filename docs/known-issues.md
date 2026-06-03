# Known Issues and Gotchas
> Last updated: 2026-06-03
> Changes: removed all resolved issues (A1, A10, A34, C5, C6, D1–D4, etc.); updated User section with audit findings

---

## Issues by Domain

### Admin

- **A30 — DownloadsAccountSlips Livewire concern undocumented:** `Admin/Livewire/Concerns/DownloadsAccountSlips.php` exists but is not listed anywhere in the reference doc.
- **Overview mentions bulk creation** but no dedicated Action exists — CSV import lives inside `UserManager` Livewire component.
- **C11 — AnnouncementStatus missing StatusEnum interface:** `app/Domain/Administration/Enums/AnnouncementStatus.php` defines state transitions (`DRAFT → SCHEDULED → PUBLISHED`) with `canTransitionTo()` but does not `implements StatusEnum`. Missing `isTerminal()` and `validTransitions()`.

### Assessment

- **A14 — EvaluatorRole enum description incomplete:** Doc says `"Evaluator role (teacher/industry)"` but actual enum has 4 cases: `ADMIN`, `TEACHER`, `SUPERVISOR`, `SYSTEM`.
- **Incorrect action count:** "Where to Find It" says "16 Actions" but 17 exist.
- **AssessmentView cross-domain routing:** Routed in `routes/web/mentee.php`, not in `routes/web/assessment.php`.
- **Overview UI capabilities undocumented:** Mentions "visual criteria editor with drag-and-drop" and "auto-calculated weighted totals" — no Action or Livewire description covers these.

### Assignment

- **SubmitAssignment cross-domain routing:** Routed in `routes/web/mentee.php`, not in `routes/web/assignment.php`.
- **Overview features no Action description:** Mentions "return-for-revision loop" and "grant deadline extensions" but no Action covers these.
- **C9 — View naming mismatch:** `SubmitAssignment.php` renders `view('assignment.submission')` but the file on disk is `resources/views/assignment/submit-assignment.blade.php` — either rename the view or fix the render call.

### Attendance

- **StudentClockIn / AbsenceRequestForm cross-domain routing:** Routed in `routes/web/mentee.php`, not in `routes/web/attendance.php`.
- **A24 — Multiple overview features not implemented:** geo-fencing, auto-notify mentors when attendance drops below threshold, auto-calculate total duration, digital signature, color-coded calendar, compliance progress bar.
- **Design Principle 2 — dual verification undocumented:** Says "dual verification" (school mentor + company supervisor) but only `VerifyAttendanceAction` exists — no dual workflow documented.
- **ClockInAction description incomplete:** Only mentions "schedule validation", not location/geo-fencing.

### Auth

- **A15 — Role enum description incomplete:** Doc says `"System roles (super_admin, admin, teacher, student, supervisor)"` but enum also defines `MENTOR` and `MENTEE` — 7 total cases.
- **Empty Policies directory:** `Auth/Policies/` exists on disk but is empty.
- **Overview features undocumented:** Mentions "lockout countdown timer" and "recovery codes with one-click copy/download" — not described in any Livewire component.

### Certificate

- **A25 — Overview features not in reference doc:** Mentions "preview a certificate PDF before issuing" and "batch issuance progress bar" — no Livewire component covers these.
- **A16 — EvaluatorRole description inaccurate:** Reference doc description differs from actual enum cases.
- **Serial number / batch resilience undocumented:** No Action description mentions serial number behavior or batch resilience.

### Document

- **A29 — Dependency graph claims Certificate:** No file in Document imports from Certificate domain — only an enum label reference.
- **Rendering pipeline undocumented:** Overview describes "six-step rendering pipeline" but reference doc only mentions "Blade + DomPDF" — no pipeline steps documented.
- **No Events section** despite rendering/dispatch possibilities.

### Evaluation

- **A28 — Dependency graph claims Mentor and Internship:** Only Registration is actually imported.
- **Star-rating widget not implemented:** Overview mentions company star-rating widget but no company evaluation component exists — only `MentorEvaluationManager`.
- **A39 — FACILITY enum case not mentioned in overview:** `EvaluationCategory` has FACILITY case but overview only mentions mentor, company, and overall evaluations.
- **Trend analysis / bar charts not implemented:** Overview mentions these but no analytics component exists.
- **SubmitEvaluationAction description incomplete:** Omits conditional `mentor_id`, `target_type`/`target_id` assignment logic.

### Guidance

- **Full-screen reader (⏳ planned):** Overview mentions "full-screen reading view with ToC sidebar" — not implemented.
- **Acknowledgement history (⏳ planned):** Overview mentions "personal acknowledgment history" — no dedicated component exists.

### Incident

- **Action descriptions omit significant validation/detail** for `ResolveIncidentAction` and `ReportIncidentAction`.

### Internship

- **CheckCloseReadinessAction / overview mismatch:** Description checks `is_verified`, not signature, but overview says "signed".
- **RequirementType description wrong:** Says "Document requirement types" but enum has DOCUMENT, SKILL, TEXT.

### Logbook

- **A13 — Dependency graph claims Mentor:** Zero Mentor domain files imported directly.
- **A26 — Overview features not implemented:** digital signature, auto-save, compliance monitoring/auto-notify, photo captions/timestamps.
- **LogbookEntry cross-domain routing:** Routed in `routes/web/mentee.php`, not in `routes/web/logbook.php`.
- **No Routes, Tests, or Events/Notifications sections** in reference doc.
- **L1 — No feedback container for industry supervisors (DUDI) (🔴):** Logbook currently only supports verification by `school_teacher`. Industry supervisors (`industry_supervisor`) cannot add per-entry notes/feedback, provide optional acknowledgment, or submit a final rubric-based score. Impact: domain goals unmet — students receive no DUDI input, no evidence of industry involvement in mentoring, and no logbook compilation for PKL report materials. Design proposal in `docs/domain/logbook.md` (Planned Enhancements).

### Mentee

- **A27 — Dependency graph claims Internship:** No direct import exists.

### Mentor

- **A8 — SupervisionManager described as "manages" but is read-only (🟠):** `SupervisionManager` description says "Manages supervision visit logs" but component is student-facing read-only — lists logs, does not create or manage them.
- **A11 — Dependency graph missing Internship and Evaluation:** `ReportNotes` and `ReportReview` import `Internship\Models\Report` and related Actions; `EvaluateMentor` imports `Evaluation\Actions\EvaluateMentorAction`.
- **CreateSupervisionLogAction description misleading:** Says "Creates a supervision visit log" — the word "visit" is misleading; creates general supervision logs.
- **Cross-domain routing:** `SupervisionManager` routed in `routes/web/mentee.php`.

### Partnership

- **A12 — Dependency graph missing Placement and Core/CsvHandler:** `PartnershipManager` imports `Placement\Models\Placement`; both `CompanyManager` and `PartnershipManager` import `Core\Support\CsvHandler`.
- **BatchDelete return docs missing:** `BatchDeleteCompanyAction` and `BatchDeletePartnershipAction` omit `{deleted, blocked}` return type docs.
- **CSV import/export/template download features not documented** in reference doc.

### Placement

- **StudentPlacementChangeRequest cross-domain routing:** Routed in `routes/web/mentee.php`.
- **CreatePlacementAction sets `filled_quota = 0`** — not mentioned in reference doc.

### Registration

- **A9 — RegistrationWizardForm not documented at all (🟠):** Exists as a Form object but is not listed in the Forms section.
- **Livewire component count off by 1:** Counts Form object as Component.
- **Cross-domain routing:** `ApplicationReview` (Admin) routed in `routes/web/registration.php`.

### Schedule

- **ScheduleStatus description imprecise:** Entity tracks time-based state (`isOngoing`, `isUpcoming`), not explicit status.
- **"Where to Find It" incomplete:** Missing Entities, Policies, Livewire, routes, views, config.

### School

- **Logo upload not in form:** Overview mentions school logo upload with preview — correctly implemented but `SchoolForm` has no logo field (handled in component).
- **"Where to Find It" incomplete:** Missing Livewire components and Entities.

### Settings

- **C3 — GetAcademicYearsAction missing BaseAction altogether:** `GetAcademicYearsAction` extends nothing (class GetAcademicYearsAction with no extends). Fits the Read Action pattern correctly (no BaseAction needed) but the bare class structure deviates from project conventions.
- **Cross-domain violation:** `SystemSetting` imports `School\Models\AcademicYear` and `School\Actions\ActivateAcademicYearAction` — correctly documented as violation in reference doc.

### Setup

- **Overview / reference doc mismatch:** Overview implies 7-step wizard — reference doc describes 4 main form steps.
- **Recovery key length (64 chars) not documented** in reference doc.
- **C14 — RecoverSuperAdminAction uses raw cache key (🔴):** Uses `'recover_admin_attempts_' . md5($email)` raw string literal (lines 25, 71) instead of a `CacheKeys` constant. Violates the mandatory convention: *"Every cache key used across the codebase MUST be defined here as a constant."* No matching constant exists in `CacheKeys`.

### Core

- *(Shared domain was merged into Core. All Shared issues below are carried over.)*
- **A21 — Core overview previously said "no Views" but views exist (carried over from Shared):** 12 Blade UI components, 5 widgets, 7 layout files exist.
- **A31 — Core Blade UI components missing avatar and credit** from reference doc (carried over from Shared).
- **Layout files (7) not documented at all** in Core reference doc.
- **No Tests section** in Core reference doc.

### User

_No open issues._

---

## Cross-Cutting Issues

### Documentation Standards

#### Missing Standard Sections Across All Reference Docs

Every domain reference doc is missing these sections:
- **Routes** — route files exist for 23 domains (Core has no routes; `routes/web/core.php` was deleted)
- **Views** — Blade view files exist in `resources/views/{domain}/` (note: `resources/views/core/` contains Core's cross-domain Blade views — layouts, UI components, widgets)
- **Tests** — test files exist in `tests/{Feature,Unit}/{Domain}/{Aggregate}/`
- **Factories** — model factories exist for most models
- **Migrations** — migration files exist for all domains

#### "Last Updated" Header Missing

6 overview docs lack `> Last updated:` header: Document, Evaluation, Guidance, Incident, Internship, Schedule.

#### Stale Last-Updated Dates

Reference docs for Document, Evaluation, Guidance, Incident, Logbook, Certificate, Assignment, Assessment all say "Last updated: 2026-05-23" — 8+ days older than more recently updated docs (core-reference: 2026-05-27).

#### Cross-Domain Routing Not Documented

Multiple Livewire components are routed in a different domain's route file. None of these cross-domain routings are noted in the domain reference docs:

| Component | Domain | Routed In |
|-----------|--------|-----------|
| `AssessmentView` | Assessment | `routes/web/mentee.php` |
| `SubmitAssignment` | Assignment | `routes/web/mentee.php` |
| `StudentClockIn`, `AbsenceRequestForm` | Attendance | `routes/web/mentee.php` |
| `LogbookEntry` | Logbook | `routes/web/mentee.php` |
| `SupervisionManager` | Mentor | `routes/web/mentee.php` |
| `StudentPlacementChangeRequest` | Placement | `routes/web/mentee.php` |
| `ApplicationReview` | Admin | `routes/web/registration.php` |

### Infrastructure / Code Patterns

#### C1. Read Actions Extending BaseAction (9 files) 🔴

The Action Triad (docs/architecture.md) mandates that Read Actions should NOT extend BaseAction — they should be plain invocable classes.

| File | Location | Issue |
|------|----------|-------|
| `GetAdminDashboardStatsAction.php` | `app/Domain/Administration/Actions/` | Read-only query, unnecessarily extends BaseAction |
| `GetUserManagerStatsAction.php` | `app/Domain/Administration/Actions/` | Same — returns cached counts |
| `ReadRecoveryKeyAction.php` | `app/Domain/Administration/Actions/` | Pure file read operation |
| `DetectUserAccountCloneAction.php` | `app/Domain/Auth/Actions/` | Read-only duplicate email detection |
| `GetTeacherDashboardStatsAction.php` | `app/Domain/User/Actions/` | Pure READ query |
| `GetSupervisorDashboardStatsAction.php` | `app/Domain/User/Actions/` | Same |
| `GetStudentDashboardDataAction.php` | `app/Domain/User/Actions/` | Same |
| `GetProfileFormDataAction.php` | `app/Domain/User/Actions/` | Returns static field config |
| `GetActivityLogsAction.php` | `app/Domain/User/Actions/` | Read-only paginated query |

#### C2. Actions Missing execute() Method (2 files) 🔴

All actions must expose a single `execute()` method per the documented pattern.

| File | Location | Issue |
|------|----------|-------|
| `GenerateAccountSlipAction.php` | `app/Domain/Administration/Actions/` | Has `download()`/`downloadBatch()` but no `execute()` |
| `CompileLogbookReportAction.php` | `app/Domain/Logbook/Actions/` | Has `download()` but no `execute()` |

#### C4. Livewire CRUD Components Not Extending BaseRecordManager (5 files) 🟠

These CRUD management components should extend `BaseRecordManager` to inherit search, sort, filter, pagination, and bulk-action functionality.

| File | Location | Current Base |
|------|----------|-------------|
| `AnnouncementManager.php` | `app/Domain/Administration/Livewire/` | `extends Component` |
| `RubricManager.php` | `app/Domain/Assessment/Livewire/` | `extends Component` |
| `AttendanceManager.php` | `app/Domain/Attendance/Livewire/` | `extends Component` (with `WithPagination`) |
| `RequirementManager.php` | `app/Domain/Program/Livewire/` | `extends Component` |
| `TemplateManager.php` | `app/Domain/Certification/Livewire/` | `extends Component` (with `WithPagination`) |

#### C7. Dead Config in config/domain.php (factories Section) 🟠

**File:** `config/domain.php:97-103`

The `factories` block defines `enabled`, `path`, `namespace`, and `faker` settings but `config('domain.factories')` is referenced zero times across the entire codebase. Entirely unused.

#### C8. config/mary.php References Non-Existent Class 🔴

**File:** `config/mary.php:43`

```php
'class' => 'App\Support\Spotlight',
```

`App\Support\Spotlight` does not exist. Will cause a runtime error if the maryUI spotlight component is used.

#### C10. Missing Entity Accessor Methods (2 files) 🟡

Models should expose entities via `as{EntityName}()` accessors per documented pattern.

| Model | Entity | Missing Accessor |
|-------|--------|-----------------|
| `User` (in Auth) | `Auth\SuperAdminIntegrityRules` | `asSuperAdminIntegrityRules()` |
| `Evaluation` | `Evaluation\EvaluationResult` | `asEvaluationResult()` |

### Pre-Existing Backlog

#### B1. Feature Test Coverage 🔴

| Domain | Actions | Feature Tests | Gap |
|--------|---------|---------------|-----|
| Assessment | 17 | 0 | 🔴 |
| Internship | 21 | 7 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Attendance | 8 | 0 | 🔴 |
| Mentor | 8 | 0 | 🔴 |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Registration | 6 | 2 | 🟡 |
| Evaluation | 3 | 1 | 🟡 |
| Admin | 14 | 9 | 🟢 |
| Guidance | 2 | 2 | 🟢 |
| Partnership | 8 | 8 | 🟢 |
| Placement | 7 | 7 | 🟢 |
| Setup | 9 | 9 | 🟢 |
| Settings | 6 | 6 | 🟢 |
| User | 8 | 5 | 🟢 |

#### B2. GD8 — Acknowledgement Not Used as Gate 🟠

Handbook acknowledgement is purely informational — no action is blocked.

#### B3. Livewire Form Object Migration 🟡

~45 components still manage form state via flat public properties. Completed: Setup, Auth, Profile, Settings, Internship, Guidance, Registration, Placement.

#### B4. Cross-Domain Event Flow Undocumented 🟡

Which events fire and which listeners react is not documented.

#### B5. Real-Time Features (Future) 🟡

Laravel Echo and Reverb installed but no real-time channels active.

#### B6. BaseAction Cannot Enforce execute() Signature 🟡

No abstract `execute()` method on `BaseAction`. Each Action defines its own signature.

---

## Summary

| # | Issue | Domain / Layer | Severity | Status |
|---|-------|----------------|----------|--------|
| B1 | Feature test coverage — 68 Actions uncovered | Cross-Cutting (Backlog) | 🔴 Critical | Open |
| C1 | Read Actions extending BaseAction (9 files) | Cross-Cutting (Infrastructure) | 🔴 Critical | Open |
| C2 | Actions missing execute() method (2 files) | Cross-Cutting (Infrastructure) | 🔴 Critical | Open |
| C3 | GetAcademicYearsAction missing BaseAction | Settings | 🔴 Critical | Open |
| C8 | config/mary.php references non-existent Spotlight class | Cross-Cutting (Infrastructure) | 🔴 Critical | Open |
| C14 | RecoverSuperAdminAction uses raw cache key | Setup | 🔴 Critical | Open |
| L1 | Logbook: no industry supervisor feedback container | Logbook | 🔴 Critical | Proposal |
| A8 | SupervisionManager described as "manages" but is read-only | Mentor | 🟠 High | Open |
| A9 | RegistrationWizardForm not documented at all | Registration | 🟠 High | Open |
| A11 | Mentor dependency graph missing Internship and Evaluation | Mentor | 🟠 High | Open |
| C4 | Livewire CRUD not extending BaseRecordManager (5 files) | Cross-Cutting (Infrastructure) | 🟠 High | Open |
| A12 | Partnership dependency graph missing Placement and Core/CsvHandler | Partnership | 🟠 Medium | Open |
| A13 | Logbook dependency graph claims Mentor | Logbook | 🟠 Medium | Open |
| A14 | Assessment EvaluatorRole enum description incomplete | Assessment | 🟠 Medium | Open |
| A15 | Auth Role enum description missing MENTOR and MENTEE | Auth | 🟠 Medium | Open |
| A16 | Certificate EvaluatorRole description inaccurate | Certificate | 🟠 Medium | Open |
| A21 | Core overview says "no Views" but views exist (carried over from Shared) | Core | 🟠 Medium | Open |

| A31 | Core Blade UI missing avatar and credit (carried over from Shared) | Core | 🟡 Low | Open |
| B3 | Livewire Form Object migration (~45 components) | Cross-Cutting (Backlog) | 🟡 Low | Open |
| B4 | Cross-domain event flow undocumented | Cross-Cutting (Backlog) | 🟡 Low | Open |
| B5 | Real-time features (Echo + Reverb) | Cross-Cutting (Backlog) | 🟡 Low | Open |
| B6 | BaseAction cannot enforce execute() signature | Cross-Cutting (Backlog) | 🟡 Low | Open |
| C9 | View naming mismatch: SubmitAssignment | Assignment | 🟡 Low | Open |
| C10 | Missing entity accessor methods (2 files) | Cross-Cutting (Infrastructure) | 🟡 Low | Open |
| C11 | AnnouncementStatus missing StatusEnum | Admin | 🟡 Low | Open |

**Categories:** A = Audit (doc-implementation), B = Backlog, C = Infrastructure/code audit, D = Documentation inaccuracy
**Severity:** 🔴 Critical = must fix, 🟠 High/Medium = should fix, 🟡 Low = nice to have
