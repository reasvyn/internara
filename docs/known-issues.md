# Known Issues & Limitations

> **Last updated:** 2026-06-08

This document catalogs known issues, caveats, and workarounds for the Internara system. Items are
organized by module and cross-cutting concern.

---

## Issues by Module

### Auth

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A15 | Role enum description in reference doc missing MENTOR/MENTEE cases (enum defines 7, doc says 5) | Medium | Open |
| — | Policies directory `Auth/Policies/` exists but is empty | Medium | Open |
| — | Lockout countdown timer and recovery codes copy/download not documented in any Livewire component | Low | Open |

### SysAdmin

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| C11 | `AnnouncementStatus` missing `implements StatusEnum` — has `canTransitionTo()` but not `isTerminal()` or `validTransitions()` | Low | Open |
| A46 | `MentorManager` Livewire component not implemented | High | Open |
| A47 | `MenteeManager` Livewire component not implemented | High | Open |

### Assessment

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A14 | `EvaluatorRole` enum has 4 cases (ADMIN, TEACHER, SUPERVISOR, SYSTEM) but doc says "teacher/industry" | Medium | Open |
| — | Action count mismatch: doc says 16, code has 17 | Low | Open |
| — | Visual criteria editor and drag-and-drop UI not implemented | Low | Open |

### Assignment

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| C9 | View naming mismatch: `SubmitAssignment.php` renders `view('assignment.submission')` but file is `submit-assignment.blade.php` | Low | Open |
| — | Return-for-revision loop not described in any Action | Low | Open |

### Attendance / Journals

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A24 | Geo-fencing, compliance progress bar, auto-notify not implemented | Medium | Open |
| C9 | View naming mismatch across attendance components | Low | Open |
| — | Dual verification workflow (school + company) not documented | Low | Open |
| L1 | **No industry supervisor feedback container:** Logbook only supports school teacher verification. Supervisors cannot add per-entry notes or scores. | **Critical** | Proposal |

### Certificate

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A25 | Batch issuance progress bar and preview before issuing not implemented | Medium | Open |

### Evaluation

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A48 | Company evaluation, overall satisfaction, program quality evaluations not implemented | High | Open |
| A49 | Evaluation aggregation/analytics not implemented | Medium | Open |
| A39 | `FACILITY` enum case not mentioned in overview | Low | Open |

### Program (Internship)

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A43 | Closure pipeline beyond readiness check not implemented (7 steps: only CheckCloseReadiness + batch COMPLETED exist) | High | Open |
| A44 | Archived program view not implemented | Medium | Open |
| A45 | Archive restoration not implemented | Low | Open |

### User

| ID | Issue | Severity | Status |
|----|-------|----------|--------|
| A40 | Student dashboard progress tracking limited — only journal progress displayed | Medium | Open |
| A41 | Dashboard quick actions incomplete — only 4 of 7 planned buttons exist | Medium | Open |
| A42 | No dedicated ProgressTracker or QuickActions Livewire components | Low | Open |

### Cross-Cutting Documentation

| Issue | Detail |
|-------|--------|
| Routes | Every module reference doc missing Routes section (23 route files exist) |
| Views | Every module reference doc missing Views section |
| Tests | Every module reference doc missing Tests section |
| Factories | Every module reference doc missing Factories section |
| Migrations | Every module reference doc missing Migrations section |

---

## Infrastructure & Code Pattern Issues

### C1 — Read Actions Extending BaseAction (9 files) — 🔴 Critical

Read Actions should be plain invocable classes, not extend BaseAction:

| File | Location |
|------|----------|
| `GetAdminDashboardStatsAction` | SysAdmin |
| `GetUserManagerStatsAction` | SysAdmin/Account |
| `ReadRecoveryKeyAction` | SysAdmin/Account |
| `DetectUserAccountCloneAction` | User/AccountStatus |
| `GetTeacherDashboardStatsAction` | User/Dashboard |
| `GetSupervisorDashboardStatsAction` | User/Dashboard |
| `GetStudentDashboardDataAction` | User/Dashboard |
| `GetProfileFormDataAction` | User/Profile |
| `GetActivityLogsAction` | User |

### C2 — Actions Missing execute() Method (2 files) — 🔴 Critical

| File | Location | Issue |
|------|----------|-------|
| `GenerateAccountSlipAction` | SysAdmin/Account | Has `download()`/`downloadBatch()` but no `execute()` |
| `CompileLogbookReportAction` | Logbook | Has `download()` but no `execute()` |

### C4 — Livewire CRUD Not Extending BaseRecordManager (5 files) — 🟠 High

| File | Location | Current Base |
|------|----------|-------------|
| `AnnouncementManager` | SysAdmin/Announcement | `extends Component` |
| `RubricManager` | Assessment | `extends Component` |
| `AttendanceManager` | Attendance | `extends Component` (WithPagination) |
| `RequirementManager` | Program | `extends Component` |
| `TemplateManager` | Certification | `extends Component` (WithPagination) |

### C8 — config/mary.php References Non-Existent Class — 🔴 Critical

`config/mary.php:43`: `'class' => 'App\Support\Spotlight'` — class does not exist.

### C10 — Missing Entity Accessor Methods (2 files) — 🟡 Low

| Model | Missing Accessor |
|-------|-----------------|
| User (Auth) | `asSuperAdminIntegrityRules()` |
| Evaluation | `asEvaluationResult()` |

---

## Backlog

### B1 — Feature Test Coverage — 🔴 Critical

| Module | Actions | E2E Tests | Gap |
|--------|---------|-----------|-----|
| Assessment | 17 | 0 | 🔴 |
| Internship | 21 | 7 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Attendance | 8 | 0 | 🔴 |
| Guidance | 8 | 0 | 🔴 |
| Assignment | 7 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Evaluation | 3 | 1 | 🟡 |

### B2–B6 — Other Backlog Items

| ID | Issue | Severity |
|----|-------|----------|
| B2 | Acknowledgement not used as gate (informational only) | Medium |
| B3 | Livewire Form Object migration needed (~45 components) | Low |
| B4 | Cross-module event flow undocumented | Low |
| B5 | Real-time features (Echo + Reverb) installed but no active channels | Low |
| B6 | BaseAction cannot enforce execute() signature (no abstract method) | Low |

---

## Cross-Module Routing (Undocumented)

These Livewire components are routed in a different module's route file:

| Component | Module | Routed In |
|-----------|--------|-----------|
| `AssessmentView` | Assessment | `routes/web/mentee.php` |
| `SubmitAssignment` | Assignment | `routes/web/mentee.php` |
| `StudentClockIn`, `AbsenceRequestForm` | Attendance | `routes/web/mentee.php` |
| `LogbookEntry` | Logbook | `routes/web/mentee.php` |
| `SupervisionManager` | Guidance | `routes/web/mentee.php` |
| `StudentPlacementChangeRequest` | Placement | `routes/web/mentee.php` |

---

## Severity Legend

| Level | Label | Description |
|-------|-------|-------------|
| 🔴 Critical | Must fix | Blocks functionality, security, or core architecture |
| 🟠 High | Should fix | Significant feature gap or maintenance burden |
| 🟡 Medium/Low | Nice to have | Documentation gap, naming inconsistency, minor optimization |
