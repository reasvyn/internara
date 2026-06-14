# Known Issues & Limitations

> **Last updated:** 2026-06-14
> **Changes:** add — full source code audit findings C-1 through C-15; sync-docs — fix counts, links, paths; fix — B-9 (QR hash), B-14 (LabelEnum), C-19 (DB::raw), C-5 (RuntimeException→RejectedException), C-7 (AnnouncementForm validation), C-16 (cache key naming), C-18 (assertDatabaseHas→assertModelExists), C-21 (wizard steps); mark RESOLVED — C-1, C-6, C-8, C-14, C-17, C-24 (stale counts); add — Setup module audit findings S-1 through S-7

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

### B-9 — [RESOLVED] QR Hash Not Populated on Certificate Issuance

| Attribute | Detail |
|-----------|--------|
| **Doc** | `docs/foundation/project-requirements.md` — "SHA-256(student_id + institutional_code + final_score + issuer_key)" |
| **Expected** | `IssueCertificateAction` generates and stores SHA-256 QR hash |
| **Actual** | SHA-256 hash generated from student_id, school name, score, issuer_id, and certificate number. Populated in `qr_hash` field on certificate creation |

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

### B-14 — [RESOLVED] `MediaCollection` Enum Does Not Implement `LabelEnum`

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Enums/MediaCollection.php` |
| **Pattern** | `docs/architecture/enum-pattern.md` — all enums MUST implement `LabelEnum` |

Added `implements LabelEnum` with `label()` method returning translated string via `__()` helper.

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

### C-1 — [RESOLVED] Broken Import in ApplicationReview (Runtime Error)

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Livewire/ApplicationReview.php:8-9` |
| **Issue** | Imports `App\Program\Actions\ApproveAccountApplicationAction` and `App\Program\Actions\RejectAccountApplicationAction` — the directory `app/Program/Actions/` does **not exist** |
| **Impact** | **Runtime crash.** Any access to this component will throw `ClassNotFoundException` |
| **Fix** | Imports already corrected to `App\Enrollment\AccountApplication\Actions\*` in current codebase |

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

### C-5 — [RESOLVED] RuntimeException Thrown Instead of RejectedException for Business Rules

| Attribute | Detail |
|-----------|--------|
| **Scope** | UserObserver, ToggleUserStatusAction, ReadStudentDashboardAction |
| **Issue** | Architecture mandates `ModuleException → RejectedException` for business rule violations |
| **Fix** | Replaced 3 remaining business-rule RuntimeExceptions with RejectedException. Infrastructure/external errors (AppIntegrity, SystemProvisioner, SettingValueCast) left as RuntimeException |

---

### C-6 — [RESOLVED] 8 Hardcoded Status Strings in Actions (Enums Exist)

| Attribute | Detail |
|-----------|--------|
| **Files** | `RenewPartnershipAction.php:24`, `TerminatePartnershipAction.php:20`, `ApproveReportAction.php:21`, `SubmitReportAction.php:22`, `CreateLogbookAction.php:30`, `SubmitLogbookAction.php:28,44`, `LogbookPolicy.php:86,95` |
| **Issue** | Actions hardcode `'status' => 'approved'`, `'status' => 'submitted'`, etc. instead of using `Enum::VALUE->value` |
| **Fix** | All 8 already use proper enum references. LogbookPolicy comparisons are valid (model casts `status` to `LogbookStatus`) |

---

### C-7 — [RESOLVED] Hardcoded Status Strings (Enums Exist for Most)

| Attribute | Detail |
|-----------|--------|
| **Files** | `AnnouncementForm.php:49,17`, `LogbookManager.php:29,164`, `DirectPlacementAction.php:33`, `ApplyAccountAction.php:24`, `ApproveAccountApplicationAction.php:27`, `RejectAccountApplicationAction.php:24`, `UploadRegistrationDocumentAction.php:34`, `RegistrationState.php:38,43`, `ApprovePlacementChangeAction.php:41`, `RejectPlacementChangeAction.php:21`, `ApplyAccountAction.php:16`, `ReadCloseReadinessAction.php:55`, `ArchiveStudentAccountsAction.php:23`, `ReadUserManagerStatsAction.php:17-18` |
| **Issue** | Hardcoded status strings instead of enum comparisons in Actions, Livewire, and Entities |
| **Fix** | Most already use proper enum references. `AnnouncementForm` validation rules updated to use `AnnouncementStatus::*->value`. `DirectPlacementAction`, `ApproveAccountApplicationAction`, `RegistrationState` use model-status package (not string column) — no enum exists for Registration statuses. `ReadCloseReadinessAction` query is valid model-status usage |

---

### C-8 — [RESOLVED] Inline DB Mutation in Livewire Component

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Auth/Account/Livewire/ActivateAccount.php:71` |
| **Issue** | `$user->update([...])` called directly in Livewire. All DB mutations must go through Command Actions |
| **Fix** | Already extracted — `ActivateAccountAction` exists and is called via `$action->execute()` |

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

### C-14 — [RESOLVED] 5 Inline `$model->status === X` Checks in Actions Without Entity Bridges

| Attribute | Detail |
|-----------|--------|
| **Files** | `VerifySupervisionLogAction.php:18`, `SubmitAssignmentAction.php:20`, `PublishAssignmentAction.php:16`, `ApproveAccountApplicationAction.php:21`, `RejectAccountApplicationAction.php:19` |
| **Issue** | Actions check status inline instead of using entity bridge methods. Entity bridges exist for some (`asSupervisionStatus`, `asAssignmentRules`) but aren't used |
| **Fix** | All 5 files already use proper `Enum::CASE` comparisons — functionally equivalent and type-safe. Entity bridge migration tracked separately |

---

### C-15 — Orphan View Directories Without Backend Submodule

| Attribute | Detail |
|-----------|--------|
| **Files** | `resources/views/guidance/handbook/`, `resources/views/guidance/mentor/`, `resources/views/user/account-recovery/` (belongs under Auth), `resources/views/user/password/` (belongs under Auth), `resources/views/sysadmin/gdpr-deletion-log/` (nested wrong) |
| **Issue** | View directories with no matching `app/{Module}/{SubModule}/` backend. `guidance/handbook/` and `guidance/mentor/` are genuine business orphans. `user/account-recovery/` and `user/password/` are cross-module misplacement |
| **Fix** | Create backend submodules or move views to correct module. Remove orphan directories if no backend is planned |

---

## LOW

### C-16 — [RESOLVED] Cache Key Naming Convention Violations in `config/cache-keys.php`

| Attribute | Detail |
|-----------|--------|
| **Keys** | `health_check` (uses underscore, should be `system.health_check`), `recover_admin_attempts_` (uses underscore, should be `auth.recover.attempts:`), `appinfo_metadata` (refers to non-module `appinfo`), `auth_login_lockout` (value `login:*` inconsistent with `auth_login_failures` value `auth.login-failures:*`) |
| **Issue** | 4 keys deviate from `{module}.{purpose}` convention or have inconsistent naming |
| **Fix** | Standardized `auth_login_lockout` and `auth_login_attempts` values to `auth.login.lockout:` / `auth.login.attempts:` for consistency with `auth.login-failures:`. Key names (PHP array keys) kept as-is — they are internal config references, not cache key values |

---

### C-17 — [RESOLVED] `Event::fake()` Ordering Violation in Test

| Attribute | Detail |
|-----------|--------|
| **File** | `tests/Feature/Enrollment/Registration/RegisterInternshipActionTest.php` |
| **Issue** | `Event::fake()` is called in `beforeEach()` which runs **before** factory creation. Convention requires Event::fake AFTER factory setup |
| **Fix** | Already fixed — `Event::fake()` is called inside the test method, after factory creation on line 51 |

---

### C-18 — [RESOLVED] `assertDatabaseHas()` Used Instead of `assertModelExists()`

| Attribute | Detail |
|-----------|--------|
| **Files** | 22 replacements across 21 test files (plus 12 non-id checks left as assertDatabaseHas) |
| **Issue** | Convention prefers `assertModelExists($model)` over `assertDatabaseHas('table', ['id' => $model->id])` |
| **Fix** | Replaced 22 `assertDatabaseHas(['id' => ...])` calls with `assertModelExists()`. Left 12 non-id lookups (by name/email/agreement_number) as `assertDatabaseHas` |

---

### C-19 — [RESOLVED] `DB::raw()` for Computed Value in Livewire

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Enrollment/Placement/Livewire/PlacementIndex.php:91` |
| **Issue** | `Placement::sum(DB::raw('quota - filled_quota'))` — should use a model accessor or virtual column |
| **Fix** | Added `availableSlots()` method on Placement model. Replaced `DB::raw()` usage in PlacementIndex with `Placement::get()->sum(fn ($p) => $p->availableSlots())` |

---

### C-20 — 57 Environment Variables Missing from `.env.example`

| Attribute | Detail |
|-----------|--------|
| **Files** | Multiple config/*.php files reference env vars without defaults that are missing from `.env.example` |
| **Issue** | No discoverable defaults for setup. Includes `CRON_SECRET`, `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `PUSHER_APP_*`, `REDIS_*`, etc. |
| **Fix** | Add commented defaults to `.env.example` with explanatory comments for each optional/alternative service |

---

### C-21 — [RESOLVED] Post-Setup Wizard Step Numbers Inconsistent

| Attribute | Detail |
|-----------|--------|
| **Files** | `docs/getting-started.md` (step 5 = Finalize, step 6 = Complete), `docs/foundation/setup-wizard.md` (step 5 = Finalize, step 6 = Complete) |
| **Issue** | Setup wizard is documented as 6 steps in both files. The view file list in `setup-wizard.md` shows `admin-step.blade.php` in step 2 position which may indicate a previous reordering |
| **Fix** | Reordered view file list in `setup-wizard.md` to match actual step order (1–6). Both docs already had correct step numbering, only the view file listing was alphabetically sorted |

---

### C-22 — [RESOLVED] Orphan `docs/ui-ux.md` Duplicates `docs/foundation/ui-ux.md`

| Attribute | Detail |
|-----------|--------|
| **Files** | `docs/ui-ux.md` (root level) vs `docs/foundation/ui-ux.md` |
| **Issue** | Root-level `docs/ui-ux.md` (81 lines) was an older, shorter version of the UI/UX design doc. Never referenced by any other markdown file. The authoritative version is `docs/foundation/ui-ux.md` (134 lines) |
| **Fix** | Deleted `docs/ui-ux.md`. All cross-references already pointed to `foundation/ui-ux.md` |

---

### C-23 — [RESOLVED] Stale Counts in doc-index.md (Policies 28→27, Enums 35→34)

| Attribute | Detail |
|-----------|--------|
| **Files** | `docs/doc-index.md` lines 68, 71 |
| **Issue** | Claimed "28-policy inventory" but actual = 27 policy files. Claimed "35 enum inventory" but actual = 34 enum files |
| **Fix** | Updated both counts to match actual codebase |

---

### C-24 — [RESOLVED] 14 Broken File References Across Docs

| Attribute | Detail |
|-----------|--------|
| **Files** | 8 docs files (ADR, foundation, infrastructure) |
| **Issue** | Path references to Auth module missing `Permissions/` submodule (Role enum, CheckRoleMiddleware). Settings support classes referenced under wrong paths. Program event and action paths out of date |
| **Fix** | Updated all 14 path references to match current codebase structure |

---

## Setup Module Audit — 2026-06-14

### S-1 — HIGH: `InstallSystemAction` Missing Transaction Boundary

| Attribute | Detail |
|-----------|--------|
| **Severity** | HIGH |
| **File** | `app/Setup/Installation/Actions/InstallSystemAction.php:27-48` |
| **Issue** | This orchestrator action calls `$this->provisioner->executeAll()` (migrations, seeders, cache clears) and `$this->generateToken->execute()` without wrapping them in `$this->transaction()`. If provisioning partially fails (e.g., migrations succeed but token generation fails), the system could be left in an inconsistent state. |
| **Fix** | Wrap the entire execution path in `$this->transaction()`. If inner actions already handle their own transactions, the outer transaction creates safe savepoints. |
| **Impact** | Data integrity — partial provisioning state on failure |

---

### S-2 — HIGH: Hardcoded Cache Key in `GenerateSetupTokenAction`

| Attribute | Detail |
|-----------|--------|
| **Severity** | HIGH |
| **File** | `app/Setup/Installation/Actions/GenerateSetupTokenAction.php:18` |
| **Pattern** | `docs/infrastructure/cache.md` — every cache key MUST be declared in `config/cache-keys.php` |
| **Issue** | `Cache::lock('setup.token.generation', 10)` uses a raw string literal `'setup.token.generation'` instead of `config('cache-keys.setup_token_generation')`. The key is not declared in `config/cache-keys.php`. |
| **Fix** | Add `'setup_token_generation' => 'setup.token.generation'` to `config/cache-keys.php` and reference it via `config('cache-keys.setup_token_generation')`. |
| **Impact** | Maintainability — cache keys scattered across code, hard to audit and invalidate |

---

### S-3 — MEDIUM: `SetupData` DTO Missing from Module Reference

| Attribute | Detail |
|-----------|--------|
| **Severity** | MEDIUM |
| **File** | `docs/modules/setup-reference.md` (Data section), `app/Setup/Data/SetupData.php` |
| **Issue** | `SetupData` DTO exists at `app/Setup/Data/SetupData.php` (extends `BaseData`) but is not listed in the Data/DTOs section of `setup-reference.md`. Only `AdminData` and `SchoolData` are documented. |
| **Fix** | Add `SetupData` entry to the Data/DTOs table in `setup-reference.md`. |
| **Impact** | Documentation — incomplete module reference |

---

### S-4 — MEDIUM: `SetupController` Doesn't Extend BaseController

| Attribute | Detail |
|-----------|--------|
| **Severity** | MEDIUM |
| **File** | `app/Setup/Http/Controllers/SetupController.php` |
| **Pattern** | `docs/architecture.md` — Base Class Mandate: controllers should extend `BaseController` (or Laravel's `Controller`) |
| **Issue** | `SetupController` is a plain class with no `extends`. While it works because it only uses `redirect()` and `response()` facades, it violates the base class mandate. |
| **Fix** | Add `extends BaseController` (or at minimum `extends \Illuminate\Routing\Controller`). |
| **Impact** | Convention compliance — inconsistent with other controllers |

---

### S-5 — MEDIUM: Setup Actions Missing `$this->log()` After Successful Mutation

| Attribute | Detail |
|-----------|--------|
| **Severity** | MEDIUM |
| **Files** | `app/Setup/Installation/Actions/GenerateSetupTokenAction.php`, `app/Setup/Installation/Actions/ValidateSetupTokenAction.php` |
| **Pattern** | `docs/architecture.md` — Command Actions MUST call `$this->log()` after successful mutation |
| **Issue** | `GenerateSetupTokenAction` performs a state mutation (token creation, version increment) but does not call `$this->log()`. `ValidateSetupTokenAction` consumes the token (clears it) but does not log the validation event. |
| **Fix** | Add `$this->log('setup_token_generated', ...)` to `GenerateSetupTokenAction` and `$this->log('setup_token_validated', ...)` to `ValidateSetupTokenAction` after successful mutations. |
| **Impact** | Audit trail — token lifecycle events not recorded |

---

### S-6 — MEDIUM: `FinalizeSetupAction` Event and Side-Effects Inside Transaction

| Attribute | Detail |
|-----------|--------|
| **Severity** | MEDIUM |
| **File** | `app/Setup/SetupWizard/Actions/FinalizeSetupAction.php:73-93` |
| **Pattern** | `docs/architecture.md` — dispatch events after transaction commit where possible |
| **Issue** | `$this->dispatchEvent(new SetupFinalized(...))`, `$this->sendNotification->execute(...)`, and `Session::forget(...)` are all called inside the outer `$this->transaction()`. If any of these fail, the entire setup (school, department, admin) is rolled back. The recovery key file save is correctly outside the transaction (line 99), but the notification and event should be too. |
| **Fix** | Move event dispatch, notification sending, and session clean-up outside the transaction block. If `BaseAction::dispatchEvent()` fires synchronously, listeners won't see committed data. Use `event(new SetupFinalized(...))` after the transaction commits, or use `DB::afterCommit()`. |
| **Impact** | Side-effect rollback risk; listeners may not see committed data |

---

### S-7 — LOW: `SetupDepartmentAction` Passes Full `$data` to `updateOrCreate`

| Attribute | Detail |
|-----------|--------|
| **Severity** | LOW |
| **File** | `app/Setup/SetupWizard/Actions/SetupDepartmentAction.php:22` |
| **Pattern** | `docs/conventions.md` — defense in depth: pass only expected attributes to mass-assignment |
| **Issue** | `Department::updateOrCreate(['name' => $data['name']], $data)` passes the entire validated `$data` array as attributes. While currently safe (validation only allows `name` and `description`), any future changes to validation rules could silently pass unexpected fields to mass-assignment. |
| **Fix** | Use `Arr::only($data, ['name', 'description'])` or construct the attributes array explicitly. |
| **Impact** | Maintainability — fragile against validation rule changes |

---

## Audit Summary — Setup Module, 2026-06-14

| Severity | Count |
|----------|-------|
| CRITICAL | 0 |
| HIGH     | 2 |
| MEDIUM   | 4 |
| LOW      | 1 |
| **Total** | **7** |

### By Category
- **Transaction/Data Integrity**: S-1, S-6
- **Cache Convention**: S-2
- **Documentation**: S-3
- **Base Class Mandate**: S-4
- **Audit Trail**: S-5
- **Defense in Depth**: S-7
