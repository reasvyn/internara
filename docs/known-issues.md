# Known Issues & Limitations

> **Last updated:** 2026-06-13
> **Changes:** add — full source code audit findings C-1 through C-15 (Action migration, security, code smells, views, routes, tests, config); sync-docs — fix broken links, route count (18→17), wizard steps (7→6), shared paths, PiiMasker path

This document catalogs known gaps between documented requirements and actual implementation, as well as code quality issues found during systematic audits.

---

## HIGH

### B-1 — Phase Manager Does Not Exist

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/key-features.md` — "Phase Manager: Program phases/timeline stages" |
| **Expected** | Dedicated CRUD for program phases/timeline stages |
| **Actual** | `Internship` model has a `phases` JSON field but no model, entity, Livewire component, or dedicated Actions for managing phases |

---

### B-2 — Compliance Monitoring Does Not Exist

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/key-features.md` — "Auto-notify mentor if N days without entry" |
| **Expected** | Scheduled job that checks for missing journal entries and notifies mentors/coordinators |
| **Actual** | No compliance/notification logic exists in `app/Journals/`. No command, job, or listener for detecting missed entries |

---

### B-3 — Score Bands Not Implemented

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/key-features.md` — "EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39)" |
| **Expected** | ScoreBand enum or logic in Evaluation module |
| **Actual** | No ScoreBand enum, no score band logic anywhere in `app/Evaluation/` or elsewhere |

---

### B-4 — Grade Card Management Does Not Exist

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/key-features.md` — "Grade Card Management: review, override, finalize student grade card" |
| **Expected** | Dedicated Grade Card feature with review/override/finalize workflow |
| **Actual** | The Reports module has a supervision `Report` (student-written, not admin grade card). No dedicated Grade Card model, Actions, or Livewire component exists |

---

### B-5 — Acknowledgement System Does Not Exist

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/key-features.md` — "Immutable acknowledgement log (user, timestamp, IP, browser)" |
| **Expected** | Dedicated acknowledgement model with immutable log entries |
| **Actual** | No acknowledgement model, Livewire component, or immutable log exists. Only a reference to `forEvent('acknowledged')` in a dashboard query |

---

### B-6 — Rendering Pipeline Not Fully Implemented

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/key-features.md` — "6-step: resolve template → discover renderer → gather data → inject → invoke driver → store" |
| **Expected** | Explicit 6-step rendering pipeline |
| **Actual** | `DocumentRenderer` and `RenderDocumentAction` exist but implement a simpler render→store flow. No explicit step-by-step pipeline matching the documented architecture |

---

### B-7 — Dual Mentor Fallback Protocol Not Implemented

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/foundation/project-requirements.md` — "48h teacher bypass window" for inactive supervisors |
| **Expected** | Proxy scoring or weight redistribution when no supervisor scores exist; configurable bypass window |
| **Actual** | No fallback logic exists. No `proxy_score`, weight redistribution, or bypass window implementation |

---

### B-8 — Composite Score Formula Not Implemented

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/foundation/project-requirements.md` — "Final Score = (Supervisor × 40%) + (Teacher × 20%) + (Exam × 40%)" |
| **Expected** | Configurable composite score calculation with documented weights |
| **Actual** | `Internship` model has a `grading_weights` JSON field; `Report` has individual score fields. No explicit formula implementation matching the documented 40/20/40 breakdown |

---

### B-9 — QR Hash Not Populated on Certificate Issuance

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/foundation/project-requirements.md` — "SHA-256(student_id + institutional_code + final_score + issuer_key)" |
| **Expected** | `IssueCertificateAction` generates and stores SHA-256 QR hash |
| **Actual** | `Certificate` model has a `qr_hash` column but it is never populated by `IssueCertificateAction`. No hash generation logic exists |

---

### B-10 — [RESOLVED] Reference Docs: Enum Values Don't Match Implementation

All four module reference docs have been updated to match the actual enum values (`2026-06-13`).

---

## MEDIUM

### B-11 — UserManagement Submodule Undocumented

| Attribute | Detail |
|-----------|--------|
| **File** | `docs/modules/user-reference.md` |
| **Scope** | `app/User/UserManagement/` — 14 Actions, 7 Livewire components, 5 Forms |

The entire `UserManagement` submodule is absent from `user-reference.md`.

---

### B-12 — [RESOLVED] `routes/web/core.php` Documented but Does Not Exist

The core-reference.md no longer references `routes/web/core.php`. Health check `/up` works via `bootstrap/app.php`.

---

### B-13 — [RESOLVED] `HasModelStatuses` Support Class Documented but Does Not Exist

The core-reference.md no longer lists `HasModelStatuses`. It was replaced with the actual `Spotlight` support class.

---

### B-14 — `MediaCollection` Enum Does Not Implement `LabelEnum` *(STILL OPEN)*

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Enums/MediaCollection.php` |
| **Pattern** | `docs/architecture/enum-pattern.md` — all enums MUST implement `LabelEnum` |

Violates the Core contract mandate. Causes runtime error if `->label()` is called. Consider adding `LabelEnum` implementation or creating a `->label()` method.

---

## LOW

### B-15 — GDPR Erasure Workflow Incomplete

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/foundation/project-requirements.md` — "data erasure workflows" |
| **Actual** | `GdprDeletionLog` model, migration, and viewer exist, but no full erasure workflow (anonymization, request/approve pipeline). `DeleteUserAction` does a simple hard delete |

---

### B-16 — [RESOLVED] Auth/User/SysAdmin Module References Missing Items

All missing items have been added to their respective reference docs (`2026-06-13`).

---

### B-17 — [RESOLVED] User/Program Reference Docs Use Old Action Names

Both `user-reference.md` and `program-reference.md` have been updated to use the correct `Read*` naming convention.

---

### B-18 — [PARTIALLY RESOLVED] Undocumented Entities Across Modules

All entities are now documented in their respective reference docs. The following entities were added across:
- `user-reference.md`: `AdminEntity`, `StudentEntity`, `SupervisorEntity`, `TeacherEntity`
- `settings-reference.md`: `SettingEntity`
- `sysadmin-reference.md`: `AnnouncementState`
- `auth-reference.md`: `ApiTokenState`

All other entities were already documented per the existing reference docs.

---

## CRITICAL

### C-1 — Broken Import in ApplicationReview (Runtime Error)

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Livewire/ApplicationReview.php:8-9` |
| **Issue** | Imports `App\Program\Actions\ApproveAccountApplicationAction` and `App\Program\Actions\RejectAccountApplicationAction` — the directory `app/Program/Actions/` does **not exist** |
| **Impact** | **Runtime crash.** Any access to this component will throw `ClassNotFoundException` |
| **Fix** | Change to `App\Enrollment\AccountApplication\Actions\ApproveAccountApplicationAction` and `App\Enrollment\AccountApplication\Actions\RejectAccountApplicationAction` |

---

### C-2 — Action Triad Migration: 0% of Actions Use BaseCommandAction/BaseProcessAction

| Attribute | Detail |
|-----------|--------|
| **Scope** | All ~153 Action files under `app/` |
| **Issue** | Only 11 Read Actions have been migrated to `BaseReadAction`. All ~140 Command/Process Actions still extend the legacy `BaseAction` directly. `BaseCommandAction` and `BaseProcessAction` exist in Core but have **zero** subclasses |
| **Fix** | Migrate step by module. Each Command Action gets `extends BaseCommandAction`, each Process Action gets `extends BaseProcessAction`. See `docs/architecture/action-pattern.md` for the contracts |

---

### C-3 — SmartLogger PII Masking: 56 of 60 Calls Missing PII Protection

| Attribute | Detail |
|-----------|--------|
| **Files** | 56 SmartLogger calls across Auth, Settings, Setup, User, Program modules |
| **Issue** | Only 4 of 60 SmartLogger calls chain `->withPiiMasking()`. Critical exposures in login (`app/Auth/Login/Livewire/Login.php:50-55`), password reset (`app/Auth/Password/Actions/ResetPasswordAction.php:24,49,59`), confirm password (`app/Auth/Password/Actions/ConfirmPasswordAction.php:18,33`) |
| **Fix** | Chain `->withPiiMasking()` on every SmartLogger call before `->save()`. Highest priority: Auth module (handles email/password/tokens) |

---

## HIGH

### C-4 — Authorization Gaps in 11 Livewire Components

| Attribute | Detail |
|-----------|--------|
| **Files** | `SubmitAssignment.php`, `CertificateList.php`, `ApplyPage.php`, `SupervisorLogManager.php`, `IncidentForm.php`, `AbsenceRequestForm.php`, `LogbookEntry.php`, `ReportWriter.php`, `ApplicationReview.php`, `AccountLifecycleManager.php`, `ProfileEditor.php` |
| **Issue** | These components have mutation methods (save, submit, approve, reject, lock, unlock, revoke) without `$this->authorize()` or `Gate::authorize()` calls |
| **Fix** | Add authorization checks before every mutation. Use model policies where they exist, or `$this->authorize('create', Model::class)` |

---

### C-5 — 26 RuntimeException Thrown Instead of RejectedException for Business Rules

| Attribute | Detail |
|-----------|--------|
| **Scope** | 26 `throw new RuntimeException(...)` calls in Actions that enforce business rules |
| **Issue** | Architecture mandates `ModuleException → RejectedException` for business rule violations. 6 in Auth (`LoginAction.php:38,46,73,87,92,97`), 4 in Setup (`ValidateSetupTokenAction.php:20,24,30,34`), scattered across Certification, Incident, User, Guidance |
| **Fix** | Replace `throw new RuntimeException(...)` with `throw new RejectedException(...)` in all 26 locations. The exception message becomes user-facing flash feedback |

---

### C-6 — 8 Hardcoded Status Strings in Actions (Enums Exist)

| Attribute | Detail |
|-----------|--------|
| **Files** | `RenewPartnershipAction.php:24`, `TerminatePartnershipAction.php:20`, `ApproveReportAction.php:21`, `SubmitReportAction.php:22`, `CreateLogbookAction.php:30`, `SubmitLogbookAction.php:28,44`, `LogbookPolicy.php:86,95` |
| **Issue** | Actions hardcode `'status' => 'approved'`, `'status' => 'submitted'`, etc. instead of using `Enum::VALUE->value` |
| **Fix** | Replace with enum references: `ReportStatus::APPROVED->value`, `LogbookStatus::SUBMITTED->value`, etc. |

---

### C-7 — 14 More Hardcoded Status Strings (Enums Exist for Most)

| Attribute | Detail |
|-----------|--------|
| **Files** | `AnnouncementForm.php:49,17`, `LogbookManager.php:29,164`, `DirectPlacementAction.php:33`, `ApplyAccountAction.php:24`, `ApproveAccountApplicationAction.php:27`, `RejectAccountApplicationAction.php:24`, `UploadRegistrationDocumentAction.php:34`, `RegistrationState.php:38,43`, `ApprovePlacementChangeAction.php:41`, `RejectPlacementChangeAction.php:21`, `ApplyAccountAction.php:16`, `ReadCloseReadinessAction.php:55`, `ArchiveStudentAccountsAction.php:23`, `ReadUserManagerStatsAction.php:17-18` |
| **Issue** | Hardcoded status strings instead of enum comparisons in Actions, Livewire, and Entities |
| **Fix** | Replace each with the appropriate enum `->value` or enum comparison |

---

### C-8 — Inline DB Mutation in Livewire Component

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Auth/Account/Livewire/ActivateAccount.php:71` |
| **Issue** | `$user->update([...])` called directly in Livewire. All DB mutations must go through Command Actions |
| **Fix** | Extract into an `ActivateAccountAction` or `UpdatePasswordAction` |

---

### C-9 — Missing Action Tests for 6 Actions

| Attribute | Detail |
|-----------|--------|
| **Files** | `ReadRegistrationAvailabilityAction`, `BatchDeleteCompanyAction`, `BatchDeletePartnershipAction`, `DeletePartnershipAction`, `RenewPartnershipAction`, `GenerateDocumentAction` |
| **Issue** | These Actions have no corresponding test file |
| **Fix** | Create test files: `tests/Feature/{Module}/{SubModule}/{Name}Test.php` |

---

### C-10 — 130+ Action Methods Missing Return Type Declarations

| Attribute | Detail |
|-----------|--------|
| **Scope** | ~130+ Action `execute()` methods across all modules |
| **Issue** | `docs/conventions.md` §2 requires explicit return types on every method. Most Action methods omit `: void`, `: Model`, `: ActionResponse`, etc. |
| **Fix** | Add explicit return types to all Action `execute()` methods. E.g., `public function execute(CreateUserData $data): User` |

---

## MEDIUM

### C-11 — 7 Closure-Based Routes Break `route:cache`

| Attribute | Detail |
|-----------|--------|
| **Files** | `routes/web/setup.php:11,14`, `routes/web/sysadmin.php:33,37,62`, `routes/web/user.php:31`, `routes/web/journals.php:35` |
| **Issue** | Closure routes are incompatible with `php artisan route:cache`. Running it will fail while these exist |
| **Fix** | Extract closures into Controllers. Highest priority: `sysadmin.php:62` (`/cron/{secret}`) and `journals.php:35` (report download) |

---

### C-12 — 20+ Destructive Actions Use `wire:confirm` Instead of Two-Step Modal

| Attribute | Detail |
|-----------|--------|
| **Files** | `assessment-grading.blade.php` (finalize), `report-writer.blade.php` (submit), `certificate-list.blade.php` (revoke), all user-management delete operations, rubric removals, placement deletes |
| **Issue** | Browser-native `confirm()` provides no user feedback on failure. Architecture mandates two-step `askAction()` / `confirmAction()` pattern |
| **Fix** | Create a shared `<x-ui::confirm>` dialog component. Replace all `wire:confirm` with the two-step pattern. Prioritize irreversible actions: finalize assessment, submit report, revoke certificate |

---

### C-13 — Models with Business Logic (Should Be in Entities)

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/User/Models/User.php` (7 methods: `setStatus`, `latestStatus`, `initials`, `delete` override, `getActiveRegistration`, `scopeActive`, `scopeRoleType`), `app/Enrollment/Registration/Models/Registration.php` (4 methods), `app/Auth/ApiTokens/Models/ApiToken.php` (7 methods: `isExpired`, `isRevoked`, `isValid`, `generateFor`, `verify`, `revokeFor`, `revokeAllExpired`), `app/Reports/Report/Models/Report.php` (1 method: `captureSnapshot`) |
| **Issue** | Business rule methods on Models. `ApiToken` is the worst — 7 business-logic methods despite already having entity bridges (`asActivationToken()`, `asApiTokenState()`, `asRecoveryCodeState()`) |
| **Fix** | Extract business logic into Entity methods and use existing/pending `as{Entity}()` bridges |

---

### C-14 — 5 Inline `$model->status === X` Checks in Actions Without Entity Bridges

| Attribute | Detail |
|-----------|--------|
| **Files** | `VerifySupervisionLogAction.php:18`, `SubmitAssignmentAction.php:20`, `PublishAssignmentAction.php:16`, `ApproveAccountApplicationAction.php:21`, `RejectAccountApplicationAction.php:19` |
| **Issue** | Actions check status inline instead of using entity bridge methods. Entity bridges exist for some (`asSupervisionStatus`, `asAssignmentRules`) but aren't used |
| **Fix** | Use `$model->as{Entity}()->isVerified()` / `isPublished()` / `isPending()` instead of raw comparison |

---

### C-15 — Orphan View Directories Without Backend Submodule

| Attribute | Detail |
|-----------|--------|
| **Files** | `resources/views/guidance/handbook/`, `resources/views/guidance/mentor/`, `resources/views/user/account-recovery/` (belongs under Auth), `resources/views/user/password/` (belongs under Auth), `resources/views/sysadmin/gdpr-deletion-log/` (nested wrong) |
| **Issue** | View directories with no matching `app/{Module}/{SubModule}/` backend. `guidance/handbook/` and `guidance/mentor/` are genuine business orphans. `user/account-recovery/` and `user/password/` are cross-module misplacement |
| **Fix** | Create backend submodules or move views to correct module. Remove orphan directories if no backend is planned |

---

## LOW

### C-16 — Cache Key Naming Convention Violations in `config/cache-keys.php`

| Attribute | Detail |
|-----------|--------|
| **Keys** | `health_check` (uses underscore, should be `system.health_check`), `recover_admin_attempts_` (uses underscore, should be `auth.recover.attempts:`), `appinfo_metadata` (refers to non-module `appinfo`), `auth_login_lockout` (value `login:*` inconsistent with `auth_login_failures` value `auth.login-failures:*`) |
| **Issue** | 4 keys deviate from `{module}.{purpose}` convention or have inconsistent naming |
| **Fix** | Standardize to `{module}.{purpose}[.{qualifier}]` naming. Update all `config('cache-keys.*')` references if keys are renamed |

---

### C-17 — `Event::fake()` Ordering Violation in Test

| Attribute | Detail |
|-----------|--------|
| **File** | `tests/Feature/Enrollment/Registration/RegisterInternshipActionTest.php` |
| **Issue** | `Event::fake()` is called in `beforeEach()` which runs **before** factory creation. Convention requires Event::fake AFTER factory setup |
| **Fix** | Move `Event::fake()` into each test method, after factory/model creation |

---

### C-18 — `assertDatabaseHas()` Used Instead of `assertModelExists()`

| Attribute | Detail |
|-----------|--------|
| **Files** | 19 calls across 14 test files in Academics, Enrollment, Journals, Program modules |
| **Issue** | Convention prefers `assertModelExists($model)` over `assertDatabaseHas('table', ['id' => $model->id])` |
| **Fix** | Replace 19 calls with `assertModelExists($model)` |

---

### C-19 — `DB::raw()` for Computed Value in Livewire

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Enrollment/Placement/Livewire/PlacementIndex.php:91` |
| **Issue** | `Placement::sum(DB::raw('quota - filled_quota'))` — should use a model accessor or virtual column |
| **Fix** | Add `availableSlots()` computed attribute on Placement model, or a DB virtual column |

---

### C-20 — 57 Environment Variables Missing from `.env.example`

| Attribute | Detail |
|-----------|--------|
| **Files** | Multiple config/*.php files reference env vars without defaults that are missing from `.env.example` |
| **Issue** | No discoverable defaults for setup. Includes `CRON_SECRET`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `PUSHER_APP_*`, `REDIS_*`, etc. |
| **Fix** | Add commented defaults to `.env.example` with explanatory comments for each optional/alternative service |

---

### C-21 — Post-Setup Wizard Step Numbers Inconsistent

| Attribute | Detail |
|-----------|--------|
| **Files** | `docs/getting-started.md` (step 5 = Finalize, step 6 = Complete), `docs/foundation/setup-wizard.md` (step 5 = Finalize, step 6 = Complete) |
| **Issue** | Setup wizard is documented as 6 steps in both files. The view file list in `setup-wizard.md` shows `admin-step.blade.php` in step 2 position which may indicate a previous reordering. Verify step order matches actual Livewire component sequence |
| **Fix** | Align documentation step numbering with the actual `SetupWizard` Livewire component render sequence |
