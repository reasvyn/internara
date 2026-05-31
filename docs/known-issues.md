# Known Issues and Gotchas
> Last updated: 2026-06-01
> Changes: added L1 — logbook industry supervisor feedback gap

## Resolved Issues

The following issues from the original audit have been resolved:

| # | Issue | Resolution |
|---|-------|------------|
| A1 | Internship lifecycle: overview says ARCHIVED, code has CANCELLED | Doc already correct — uses CANCELLED |
| A2 | CheckCloseReadinessAction missing certificate issuance check | Added `checkCertificates()` method |
| A3 | AccountApplicationForm wrong path and extends in reference doc | Path/extends were already correct; fields updated to match 17 actual fields |
| A4 | PlacementForm fields mismatch | Doc already correct — lists company_id, internship_id, name, address, quota, description |
| A5 | DirectPlacementForm missing mentor_ids | Doc already includes mentor_ids |
| A6 | ProfileForm missing 5 staff fields | Doc already lists all 10 fields |
| A7 | MentorProfileManager and EvaluateMentor have no route | Both already routed (mentee.php, mentor.php) |
| A17 | PlacementPolicy only has viewAny — missing CRUD | CRUD methods added to PlacementPolicy |
| A18 | AccessManager (Auth) has no route | Route added |
| A19 | AuditLogManager and AccountCloneDetector (Admin) have no routes | Routes added |
| A23 | Schedule overview describes calendar UI that does not exist | Doc updated to match actual CRUD implementation |
| A33 | LangChecker extends Translator (violates "final readonly") | LangChecker fixed to extend Translator (required by Laravel internals) |
| A20 | Document template version not tracked | Added template_version column, migration, and RenderDocumentAction update |
| A22 | Shared Design Principle 2 violated by CsvHandler and LangChecker | Documented as known exceptions with rationale |
| G1 | Guidance: PDF attachment for handbooks | Handbook model implements HasMedia; HandbookManager handles upload; Create/UpdateHandbookAction manage file |
| G2 | Guidance: teacher/supervisor routes | Routes added in guidance.php for `teacher` and `supervisor` prefixes targeting HandbookIndex |
| G3 | Guidance: rename components per convention | HandbookIndex → HandbookManager (admin), StudentHandbookIndex → HandbookIndex (user-facing) |

## Critical Implementation Issues

### A8. SupervisionManager Described as "Manages" But Is Read-Only 🟠

**File:** `docs/domain/mentor-reference.md`

`SupervisionManager` description says "Manages supervision visit logs" but the component is student-facing read-only — lists logs, does not create or manage them.

### A9. RegistrationWizardForm Not Documented 🟠

**File:** `docs/domain/registration-reference.md`

`RegistrationWizardForm` exists as a Form object but is not listed in the Forms section.

### A20. Document Template Version Not Tracked 🟠

**File:** `app/Domain/Document/Actions/RenderDocumentAction.php`

Design Principle 2 says "Template version tracked at generation time" but `RenderDocumentAction` does NOT store a template version identifier — only copies template content.

---

## Doc-Implementation Discrepancies by Domain

### Admin

- **Undocumented concern:** `Admin/Livewire/Concerns/DownloadsAccountSlips.php` exists but is not listed anywhere in the reference doc. (A30)
- **Overview mentions bulk creation** but no dedicated Action exists — CSV import lives inside `UserManager` Livewire component.
- No Routes, Views, Tests, or Factories section in reference doc.

### Assessment

- **EvaluatorRole enum description incomplete:** Doc says `"Evaluator role (teacher/industry)"` but the actual enum has 4 cases: `ADMIN`, `TEACHER`, `SUPERVISOR`, `SYSTEM`. (A14)
- **Incorrect action count:** "Where to Find It" says "16 Actions" but 17 exist.
- `AssessmentView` routed in `routes/web/mentee.php`, not in `routes/web/assessment.php` — cross-domain routing not noted.
- Overview mentions "visual criteria editor with drag-and-drop" and "auto-calculated weighted totals" — no Action or Livewire description covers these UI capabilities.
- No Routes, Views, Tests, or Factories section in reference doc.

### Assignment

- `SubmitAssignment` routed in `routes/web/mentee.php`, not in `routes/web/assignment.php`.
- Overview mentions "return-for-revision loop" and "grant deadline extensions" but no Action description covers these.
- No Routes, Views, Tests, or Factories section.

### Attendance

- `StudentClockIn` and `AbsenceRequestForm` routed in `routes/web/mentee.php`, not in `routes/web/attendance.php`.
- **Multiple overview features not implemented:** geo-fencing, auto-notify mentors when attendance drops below threshold, auto-calculate total duration, digital signature, color-coded calendar, compliance progress bar. (A24)
- Design Principle 2 says "dual verification" (school mentor + company supervisor) but only `VerifyAttendanceAction` exists — no dual workflow documented.
- `ClockInAction` description only mentions "schedule validation", not location/geo-fencing.
- No Routes, Views, Tests, or Factories section.

### Auth

- **Role enum description incomplete:** Doc says `"System roles (super_admin, admin, teacher, student, supervisor)"` but the enum also defines `MENTOR` and `MENTEE` — 7 total cases. (A15)
- `Auth/Policies/` directory exists on disk but is empty.
- Overview mentions "lockout countdown timer" and "recovery codes with one-click copy/download" — not described in any Livewire component description.
- No Routes, Views, Tests, or Factories section.

### Certificate

- Overview mentions "preview a certificate PDF before issuing" and "batch issuance progress bar" — no Livewire component description covers these. (A25)
- No Action description mentions serial number behavior or batch resilience.
- No Routes, Views, Tests, or Factories section.

### Core

- **Dependency graph claims BaseState as consumed** by business domains but no file outside Core imports or uses `BaseState` — only defined in Core, never referenced externally. (A10)
- `HandlesActionErrors` description oversimplifies compared to actual PHPDoc.
- `AuditCategory` description glosses over TERMINAL and RECOMMENDATIONS cases. (A34)
- Overview mentions toast notifications and error pages but reference doc has no section covering these.

### Document

- **Dependency graph claims Certificate** but no file in Document imports from Certificate domain — only an enum label reference. (A29)
- Overview describes "six-step rendering pipeline" but reference doc only mentions "Blade + DomPDF" — no pipeline steps documented.
- ~~**Template version not tracked** despite Principle 2 claiming it — `RenderDocumentAction` now stores `template_version` and `template_id`. (A20 — Resolved)~~
- No Events section despite rendering/dispatch possibilities.
- Overview doc missing "Last updated" header.

### Evaluation

- **Dependency graph claims Mentor and Internship** but only Registration is actually imported. (A28)
- Overview mentions company star-rating widget but no company evaluation component exists — only `MentorEvaluationManager`.
- `EvaluationCategory` has FACILITY case but overview only mentions mentor, company, and overall evaluations. (A39)
- Overview mentions "trend analysis" and "bar charts" — no analytics component exists.
- `SubmitEvaluationAction` description omits conditional `mentor_id`, `target_type`/`target_id` assignment logic.
- Overview doc missing "Last updated" header.

### Guidance — Remaining Planned

- **Full-screen reader (⏳ planned):** Overview mentions "full-screen reading view with ToC sidebar" — not implemented.
- **Acknowledgement history (⏳ planned):** Overview mentions "personal acknowledgment history" — no dedicated component exists.

### Incident

- `IncidentReportedNotification` implements `ShouldQueue` with `Queueable` but reference doc only lists `Notification`. (A36)
- `ResolveIncidentAction` and `ReportIncidentAction` descriptions are accurate but omit significant validation/detail.
- Overview doc missing "Last updated" header.

### Internship

- Dependency graph claims `BaseState` — no Internship file imports it. (A10)
- `CheckCloseReadinessAction` description matches code (checks `is_verified`, not signature) but overview says "signed" — mismatch.
- `RequirementType` description says "Document requirement types" but enum has DOCUMENT, SKILL, TEXT.
- Overview doc missing "Last updated" header.

### Logbook

- **Dependency graph claims Mentor** — zero Mentor domain files imported directly. (A13)
- **Overview describes features not implemented:** digital signature, auto-save, compliance monitoring/auto-notify, photo captions/timestamps. (A26)
- `LogbookEntry` missing `WithPagination` and `WithFileUploads` traits from reference doc. (A37)
- `LogbookManager` routed in `routes/web/logbook.php`, `LogbookEntry` routed in `routes/web/mentee.php` — not noted.
- No Routes, Tests, or Events/Notifications sections.
- **L1 — No feedback container for industry supervisors (DUDI):** Logbook currently only supports verification by `school_teacher`. Industry supervisors (`industry_supervisor`) cannot add per-entry notes/feedback, provide optional acknowledgment, or submit a final rubric-based score. Impact: domain goals unmet — students receive no DUDI input, no evidence of industry involvement in mentoring, and no logbook compilation for PKL report materials. Design proposal in `docs/domain/logbook.md` (Planned Enhancements). (🔴 High)

### Mentee

- **Dependency graph claims Internship** — no direct import. (A27)
- `MenteeState` entity methods `canClockIn`, `canSubmitLogbook`, `canSubmitAssignment`, `hasEnded`, `daysRemaining` not documented. (A38)
- No Routes or Tests sections.

### Mentor

- **SupervisionManager described as "manages"** but is student-facing read-only — lists logs, does not create/manage. (A8)
- **Dependency graph missing Internship** — `ReportNotes` and `ReportReview` import `Internship\Models\Report` and related Actions. (A11)
- **Dependency graph missing Evaluation** — `EvaluateMentor` imports `Evaluation\Actions\EvaluateMentorAction`. (A11)
- `CreateSupervisionLogAction` described as "Creates a supervision visit log" — the word "visit" is misleading; creates general supervision logs.
- Cross-domain routing (`SupervisionManager` in `routes/web/mentee.php`) not noted.
- No Routes, Tests, or Factories sections.

### Partnership

- **Dependency graph missing Placement** — `PartnershipManager` imports `Placement\Models\Placement`. (A12)
- **Dependency graph missing Shared/CsvHandler** — both `CompanyManager` and `PartnershipManager` import it. (A12)
- `BatchDeleteCompanyAction` and `BatchDeletePartnershipAction` omit `{deleted, blocked}` return type docs.
- No Routes, Tests, or Factories sections.
- CSV import/export/template download features not documented.

### Placement

- `StudentPlacementChangeRequest` routed in `routes/web/mentee.php` — not noted.
- `CreatePlacementAction` sets `filled_quota = 0` — not mentioned.
- No Routes, Tests, or Factories sections.

### Registration

- **RegistrationWizardForm not documented at all.** (A9)
- Livewire component count off by 1 (counts Form object as Component).
- No Routes, Tests, or Factories sections.
- Cross-domain routing (`ApplicationReview` from Admin) not documented.

### Schedule

- `ScheduleStatus` entity tracks time-based state (`isOngoing`, `isUpcoming`), not explicit status — description imprecise.
- No Routes, Tests, Views, or Migrations sections.
- "Where to Find It" missing Entities, Policies, Livewire, routes, views, config.

### School

- Overview mentions school logo upload with preview — correctly implemented but `SchoolForm` has no logo field (handled in component).
- No Routes, Tests, Views, or Migrations sections.
- "Where to Find It" missing Livewire components and Entities.

### Settings

- Cross-domain violation: `SystemSetting` imports `School\Models\AcademicYear` and `School\Actions\ActivateAcademicYearAction` — correctly documented as violation.
- No Routes, Tests, Views, or Migrations sections.

### Setup

- Overview implies 7-step wizard — reference doc describes 4 main form steps.
- Recovery key length (64 chars) not in reference doc.
- No Routes (only "Where to Find It" mention), Tests, Views, or Migrations sections.

### Shared

- **Overview says "no Views" but views exist:** 12 Blade UI components, 5 widgets, 7 layout files. (A21)
- ~~**Design Principle 2 violated:** Classes not all final/static/readonly — `CsvHandler`, `LangChecker` noted as exceptions. (A22 — Resolved)~~
- **Blade UI components missing avatar and credit** from reference doc. (A31)
- **HasModelStatuses deprecated** but not noted in reference doc. (A32)
- Layout files (7) not documented at all.
- No Tests section.

### User

- Avatar handling (`WithFileUploads`, `$avatar` property) not documented in reference doc.
- `EmploymentStatus` enum documented but not mentioned as used by `ProfileForm`.
- No Routes, Tests, Views, or Migrations sections.
- Dashboard views not referenced.

---

## Cross-Cutting Issues

### Missing Standard Sections Across All Reference Docs

Every domain reference doc is missing these sections:
- **Routes** — route files exist for all 24 domains
- **Views** — Blade view files exist in `resources/views/{domain}/`
- **Tests** — test files exist in `tests/{Feature,Unit}/{Domain}/`
- **Factories** — model factories exist for most models
- **Migrations** — migration files exist for all domains

### Overview Docs Missing "Last Updated" Header

5 overview docs lack `> Last updated:` header: Document, Evaluation, Guidance, Incident, Internship, Schedule. (A40)

### Stale Last-Updated Dates

Reference docs for Document, Evaluation, Guidance, Incident, Logbook, Certificate, Assignment, Assessment all say "Last updated: 2026-05-23" — 8+ days older than more recently updated docs (core-reference: 2026-05-27).

### Cross-Domain Routing Not Documented

Multiple Livewire components are routed in a different domain's route file:
- `AssessmentView` → `routes/web/mentee.php`
- `SubmitAssignment` → `routes/web/mentee.php`
- `StudentClockIn`, `AbsenceRequestForm` → `routes/web/mentee.php`
- `LogbookEntry` → `routes/web/mentee.php`
- `SupervisionManager` → `routes/web/mentee.php`
- `StudentPlacementChangeRequest` → `routes/web/mentee.php`
- `ApplicationReview` (Admin) → `routes/web/registration.php`

None of these cross-domain routings are documented in the domain reference docs.

---

## Pre-Existing Backlog

### B1. Feature Test Coverage 🔴

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

### B2. GD8 — Acknowledgement Not Used as Gate 🟠

Handbook acknowledgement is purely informational — no action is blocked.

### B3. Livewire Form Object Migration 🟡

~45 components still manage form state via flat public properties. Completed: Setup, Auth, Profile, Settings, Internship, Guidance, Registration, Placement.

### B4. Cross-Domain Event Flow Undocumented 🟡

Which events fire and which listeners react is not documented.

### B5. Real-Time Features (Future) 🟡

Laravel Echo and Reverb installed but no real-time channels active.

### B6. BaseAction Cannot Enforce execute() Signature 🟡

No abstract execute() method on BaseAction. Each Action defines its own signature.

---

## Summary

| # | Issue | Category | Severity | Status |
|---|-------|----------|----------|--------|
| A8 | SupervisionManager described as "manages" but is read-only | Doc-Implementation | 🟠 High | Open |
| A9 | RegistrationWizardForm not documented at all | Documentation | 🟠 High | Open |
| A10 | Internship dependency graph claims BaseState — no file imports it | Documentation | 🟠 High | Open |
| A11 | Mentor dependency graph missing Internship and Evaluation | Documentation | 🟠 High | Open |
| A12 | Partnership dependency graph missing Placement and Shared/CsvHandler | Documentation | 🟠 Medium | Open |
| A13 | Logbook dependency graph claims Mentor — no direct import exists | Documentation | 🟠 Medium | Open |
| A14 | Assessment EvaluatorRole enum description missing ADMIN and SYSTEM cases | Documentation | 🟠 Medium | Open |
| A15 | Auth Role enum description missing MENTOR and MENTEE cases | Documentation | 🟠 Medium | Open |
| A16 | Certificate EvaluatorRole description inaccurate | Documentation | 🟠 Medium | Open |
| A20 | Document template version not tracked despite Principle 2 claiming it | Doc-Implementation | 🟠 Medium | Resolved |
| A21 | Shared overview says "no Views" but views exist | Documentation | 🟠 Medium | Open |
| A22 | Shared Design Principle 2: classes not all final/static/readonly | Doc-Implementation | 🟠 Medium | Resolved |
| A24 | Attendance overview describes features not implemented | Doc-Implementation | 🟠 Medium | Resolved |
| A25 | Certificate overview mentions features not in reference doc | Doc-Implementation | 🟡 Low | Resolved |
| A26 | Logbook overview mentions features not implemented | Doc-Implementation | 🟠 Medium | Resolved |
| A27 | Mentee dependency graph claims Internship — no direct import exists | Documentation | 🟡 Low | Resolved |
| A28 | Evaluation dependency graph claims Mentor and Internship | Documentation | 🟡 Low | Resolved |
| A29 | Document dependency graph claims Certificate | Documentation | 🟡 Low | Resolved |
| A30 | Admin has undocumented DownloadsAccountSlips Livewire concern | Documentation | 🟡 Low | Resolved |
| A31 | Shared Blade UI components missing avatar and credit | Documentation | 🟡 Low | Resolved |
| A32 | HasModelStatuses trait deprecated but not noted in reference doc | Documentation | 🟡 Low | Resolved |
| A34 | AuditCategory enum description glosses over TERMINAL and RECOMMENDATIONS | Documentation | 🟡 Low | Resolved |
| A25 | Certificate overview mentions features not in reference doc | Doc-Implementation | 🟡 Low | Open |
| A26 | Logbook overview mentions features not implemented | Doc-Implementation | 🟠 Medium | Open |
| A27 | Mentee dependency graph claims Internship — no direct import exists | Documentation | 🟡 Low | Open |
| A28 | Evaluation dependency graph claims Mentor and Internship | Documentation | 🟡 Low | Open |
| A29 | Document dependency graph claims Certificate | Documentation | 🟡 Low | Open |
| A30 | Admin has undocumented DownloadsAccountSlips Livewire concern | Documentation | 🟡 Low | Open |
| A31 | Shared Blade UI components missing avatar and credit | Documentation | 🟡 Low | Open |
| A32 | HasModelStatuses trait deprecated but not noted in reference doc | Documentation | 🟡 Low | Open |
| A34 | AuditCategory enum description glosses over TERMINAL and RECOMMENDATIONS | Documentation | 🟡 Low | Open |
| A35 | HandbookForm missing $id field from reference doc | Documentation | 🟡 Low | Resolved |
| A36 | Incident notification ShouldQueue interface missing from ref doc | Documentation | 🟡 Low | Resolved |
| A37 | LogbookEntry missing WithPagination and WithFileUploads from ref doc | Documentation | 🟡 Low | Resolved |
| A38 | MenteeState entity methods undocumented | Documentation | 🟡 Low | Resolved |
| A39 | Evaluation FACILITY enum case not mentioned in overview | Documentation | 🟡 Low | Resolved |
| A40 | 5 overview docs missing "Last updated" header | Documentation | 🟡 Low | Resolved |
| B1 | Feature test coverage — 68 Actions uncovered | Backlog | 🔴 High | Open |
| B2 | GD8 — Acknowledgement not used as gate | Backlog | 🟠 Medium | Open |
| B3 | Livewire Form Object migration (~45 components) | Backlog | 🟡 Low | Open |
| B4 | Cross-domain event flow undocumented | Backlog | 🟡 Low | Open |
| B5 | Real-time features (Echo + Reverb) | Backlog | 🟡 Low | Open |
 | B6 | BaseAction cannot enforce execute() signature | Backlog | 🟡 Low | Open |
| L1 | Logbook: no industry supervisor feedback container | Design Gap | 🔴 High | Proposal |

**Categories:** A = Audit (new findings), B = Backlog
**Severity:** 🔴 Critical = must fix, 🟠 High/Medium = should fix, 🟡 Low = nice to have
