# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** audit Settings & Backups — 23 findings recorded (S-1 through S-23)

This document catalogs known gaps between documented requirements and actual implementation, as well as code quality issues found during systematic audits.

---

## CRITICAL

### C-2 — [RESOLVED] Action Triad Migration: All Actions Use Correct Base Classes

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — 139 Command → `BaseCommandAction`, 12 Read → `BaseReadAction`, 1 Process → `BaseProcessAction` |

---

### C-3 — [RESOLVED] SmartLogger PII Masking: All Calls Protected

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — All 40+ unprotected SmartLogger calls across app/ now chain `->withPiiMasking()` before `->save()` |

---

## MEDIUM

### C-12 — [RESOLVED] 20+ Destructive Actions Use `wire:confirm` Instead of Two-Step Modal

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — All 29 wire:confirm usages across 14 Livewire components replaced with x-core::ui.confirm modal + confirmAction() pattern |

---

### C-13 — [RESOLVED] Models with Business Logic (Should Be in Entities)

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — `ApiToken`: removed `isExpired/isRevoked/isValid` (use entity); `Registration`: removed `currentPhaseIndex/currentPhase` (entity exists); `User`: removed `latestStatus()`; `Report`: `captureSnapshot` is an action, out of scope |

---

### D-1 — [RESOLVED] Activation Code Input `maxlength="6"` Conflicts with Validation (16–19 chars)

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Changed `maxlength="6"` to `maxlength="19"` in `activate-account.blade.php:22` |

---

### D-2 — [RESOLVED] No Super Admin–Specific Dashboard

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added 3 super_admin system cards (Audit Overview, System Info, Platform Overview) to admin dashboard gated by `hasRole('super_admin')` |

---

### D-3 — [RESOLVED] Student Dashboard Timeline Section Has No Data Backend

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Removed empty timeline placeholder card from student dashboard |

---

### D-4 — [RESOLVED] No First-Login Onboarding for Non–Super-Admin Users

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — `SendSuperAdminWelcomeNotification` now sends role-specific welcome messages for all roles |

---

### D-5 — [RESOLVED] `_sidebar.blade.php` Removed

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — `_sidebar.blade.php` deleted; inline markup restored to each dashboard view directly |

---

## Issues

| Severity | Count |
|----------|-------|
| CRITICAL | 2 |
| HIGH     | 8 |
| MEDIUM   | 7 |
| LOW      | 6 |
| **Total** | **23** |

---

### Audit Scope: Settings Module + Backups Submodule

Audit conducted 2026-06-15 covering `app/Settings/`, `app/SysAdmin/Backups/`, their views, routes,
config, and tests.

---

### S-1 — CRITICAL: SystemSetting Livewire — 4 Hook Methods Missing Authorization

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/Settings/Livewire/SystemSetting.php:105,117,129,148` |
| **Pattern violated** | `docs/architecture/policy-pattern.md` — every mutation method must authorize |
| **What's wrong** | `updatedBrandingFormBrandLogo()`, `updatedBrandingFormSiteFavicon()`, `confirmRemoveBrandLogo()`, `confirmRemoveFavicon()` perform destructive writes (file upload, media deletion) without any `$this->authorize()` call. Only `mount()`, `save()`, and `testEmail()` have authorization. |
| **Fix** | Add `$this->authorize('update', Setting::class)` to all 4 methods |
| **Impact** | Security — any authenticated user can upload/remove brand assets |

---

### S-2 — CRITICAL: SystemSetting Livewire — Inline DB Mutations

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/Settings/Livewire/SystemSetting.php:131,150,215` |
| **Pattern violated** | `docs/architecture/livewire-pattern.md` — no inline DB/Model calls in components |
| **What's wrong** | Three locations call `Setting::firstOrCreate()`, `$media->delete()`, and `AcademicYear::where(...)` directly instead of delegating to Actions. |
| **Fix** | Extract logo/favicon removal into `RemoveBrandAssetAction`; extract academic year activation into existing `ActivateAcademicYearAction` |
| **Impact** | Maintainability — business logic leaks into presentation layer |

---

### S-3 — HIGH: BatchSetSettingAction Uses `DB::transaction()` Instead of `$this->transaction()`

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Actions/BatchSetSettingAction.php:18` |
| **Pattern violated** | `docs/architecture/action-pattern.md` — must use `$this->transaction()` |
| **What's wrong** | Uses raw `DB::transaction()` which bypasses `BaseAction::transaction()` providing nested transaction detection, deferred event dispatch, and serialization failure retry. |
| **Fix** | Replace `DB::transaction()` with `$this->transaction()` |
| **Impact** | Maintainability — bypasses Action transaction safety features |

---

### S-4 — HIGH: UploadBrandAssetAction Missing Transaction, Log, and Event

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Branding/Actions/UploadBrandAssetAction.php` |
| **Pattern violated** | `docs/architecture/action-pattern.md` — Command Actions must have transaction + log |
| **What's wrong** | No `$this->transaction()`, no `$this->log()`, no event dispatched. Media writes to both DB and filesystem without atomicity guarantee. |
| **Fix** | Add `$this->transaction()`, `$this->log('brand_asset_uploaded', ...)`, and dispatch `SettingUpdated` event |
| **Impact** | Maintainability — missing mandatory Action contract elements |

---

### S-5 — HIGH: SaveSystemSettingsAction Missing Transaction and Uses Direct SmartLogger

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Actions/SaveSystemSettingsAction.php:20,58` |
| **Pattern violated** | `docs/architecture/action-pattern.md` — must use `$this->transaction()` and `$this->log()` |
| **What's wrong** | No `$this->transaction()` wrapping the composite write. Uses `SmartLogger::info()->module('Setting')->activityOnly()` instead of `$this->log()` which auto-derives module and uses both channels. |
| **Fix** | Wrap in `$this->transaction()`; replace SmartLogger call with `$this->log()` |
| **Impact** | Maintainability — missing transaction boundary; custom log bypasses standard format |

---

### S-6 — HIGH: TestMailSettingsAction Missing Transaction and Log

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Actions/TestMailSettingsAction.php:15,30` |
| **Pattern violated** | `docs/architecture/action-pattern.md` — must use `$this->log()` |
| **What's wrong** | No `$this->transaction()`, no `$this->log()` call. Uses `SmartLogger::error()` directly on failure path; no logging at all on success path. |
| **Fix** | Add `$this->log()` on both success and failure paths |
| **Impact** | Maintainability — missing audit trail for sensitive mail test operation |

---

### S-7 — HIGH: DeleteSettingAction Missing `$this->log()` Call

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Actions/DeleteSettingAction.php:14` |
| **Pattern violated** | `docs/architecture/action-pattern.md` — Command Actions MUST call `$this->log()` |
| **What's wrong** | Calls `$this->transaction()` and `$this->dispatchEvent()` but no `$this->log()` |
| **Fix** | Add `$this->log('setting.deleted', ...)` |
| **Impact** | Maintainability — deletion not recorded in activity log |

---

### S-8 — HIGH: SettingValueCast Throws RuntimeException Instead of RejectedException

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Casts/SettingValueCast.php:102,144` |
| **Pattern violated** | `docs/conventions.md` §2 — Business logic violations use `RejectedException` |
| **What's wrong** | Two locations throw `new RuntimeException(...)` instead of `RejectedException`. Loses contextual hints and HTTP status mapping. |
| **Fix** | Replace with `new RejectedException(...)` |
| **Impact** | Maintainability — wrong exception type loses context |

---

### S-9 — HIGH: BackupCompleted Event Dispatched but No Listener Registered

| Attribute | Detail |
|-----------|--------|
| **File** | `config/event.php` |
| **Pattern violated** | `docs/architecture/event-pattern.md` — dispatched events should have registered listeners |
| **What's wrong** | `BackupCompleted` is dispatched in `CreateBackupAction::execute()` but has no listener in `config/event.php`. The event fires silently with no side effects (no notification, no cache invalidation). |
| **Fix** | Register a listener for `BackupCompleted` in `config/event.php`, or document why it's intentionally fire-and-forget |
| **Impact** | Functionality — backup success events are not actionable |

---

### S-10 — HIGH: Hardcoded Language Names in LangSwitcher View

| Attribute | Detail |
|-----------|--------|
| **File** | `resources/views/settings/livewire/lang-switcher.blade.php:8,12` |
| **Pattern violated** | `docs/conventions.md` — all user-facing strings use `__()` helper |
| **What's wrong** | `title="Bahasa Indonesia"` and `title="English"` are hardcoded. A user browsing in Indonesian would see "English" untranslated. No translation keys exist for language names. |
| **Fix** | Create `__('common.language.indonesian')` and `__('common.language.english')` translation keys; use in view |
| **Impact** | UX — language names don't respect current locale |

---

### S-11 — HIGH: CreateBackupAction Uses `event()` Instead of `$this->dispatchEvent()`

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Actions/CreateBackupAction.php:50,65` |
| **Pattern violated** | `docs/architecture/action-pattern.md` — should use `$this->dispatchEvent()` |
| **What's wrong** | Uses `event(new BackupCompleted(...))` which fires immediately inside the transaction. The project convention is `$this->dispatchEvent()` which defers dispatch to after the transaction callback completes. |
| **Fix** | Replace `event(...)` with `$this->dispatchEvent(...)` |
| **Impact** | Maintainability — inconsistent event dispatch pattern |

---

### S-12 — HIGH: BackupRunner Exposes Database Password in Process Table

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Support/BackupRunner.php:115-141` |
| **Pattern violated** | Security best practice — credentials must not leak via process table |
| **What's wrong** | Database passwords passed via `MYSQL_PWD=%s` and `PGPASSWORD="%s"` environment variables in `exec()` commands. Visible to any user running `ps aux` on the system. |
| **Fix** | Use `--defaults-extra-file` for MySQL (temp file with 600 permissions) and `.pgpass` for PostgreSQL |
| **Impact** | Security — database credentials exposed to local users |

---

### S-13 — HIGH: SystemSetting Livewire Has No Feature Test

| Attribute | Detail |
|-----------|--------|
| **File** | `app/Settings/Livewire/SystemSetting.php` |
| **Pattern violated** | `docs/infrastructure/testing.md` — every Livewire component should have a feature test |
| **What's wrong** | The largest and most complex Settings component (220-line view, 3 forms, file uploads, mail test) has zero feature tests. |
| **Fix** | Create `tests/Feature/Settings/Livewire/SystemSettingTest.php` covering save, mail test, upload flows |
| **Impact** | Quality — UI logic is untested |

---

### S-14 — HIGH: BackupManager Livewire Has No Feature Test

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Livewire/BackupManager.php` |
| **Pattern violated** | `docs/infrastructure/testing.md` — every Livewire component should have a feature test |
| **What's wrong** | No feature test for the backup creation, filtering, and deletion UI |
| **Fix** | Create `tests/Feature/SysAdmin/Backups/Livewire/BackupManagerTest.php` |
| **Impact** | Quality — backup UI logic is untested |

---

### S-15 — HIGH: ReadBackupHistoryAction Has No Test

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Actions/ReadBackupHistoryAction.php` |
| **Pattern violated** | `docs/architecture/testing-pattern.md` — every Action should have a test |
| **What's wrong** | No feature test for the backup history query action |
| **Fix** | Create `tests/Feature/SysAdmin/Backups/Actions/ReadBackupHistoryActionTest.php` |
| **Impact** | Quality — untested read action |

---

### S-16 — HIGH: SystemBackupCommand Has No Test

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Console/Commands/SystemBackupCommand.php` |
| **Pattern violated** | `docs/infrastructure/testing.md` — console commands should have feature tests |
| **What's wrong** | CLI backup command has no test |
| **Fix** | Create `tests/Feature/SysAdmin/Backups/Console/Commands/SystemBackupCommandTest.php` |
| **Impact** | Quality — untested console command |

---

### S-17 — HIGH: BackupRunner Support Service Has No Test

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Support/BackupRunner.php` |
| **Pattern violated** | `docs/infrastructure/testing.md` — infrastructure services should have tests |
| **What's wrong** | The core backup execution engine (database dump, file archive, combined backup) has zero tests |
| **Fix** | Create `tests/Unit/SysAdmin/Backups/Support/BackupRunnerTest.php` with mocked `exec()` |
| **Impact** | Quality — backup engine untested; failures may go undetected |

---

### S-18 — HIGH: Backup Enums, Entity, Policy, and Model Have No Unit Tests

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/SysAdmin/Backups/Enums/BackupType.php`, `BackupStatus.php`, `Entities/BackupState.php`, `Policies/BackupPolicy.php`, `Models/Backup.php` |
| **Pattern violated** | `docs/infrastructure/testing.md` — every enum/entity/policy should have a unit test |
| **What's wrong** | 5 Backup classes with zero unit tests: `BackupType` (LabelEnum), `BackupStatus` (StatusEnum state machine), `BackupState` (entity methods like `isDeletable()`, `formattedSize()`), `BackupPolicy`, `Backup` model |
| **Fix** | Create unit tests for each class |
| **Impact** | Quality — untested business logic in backup domain |

---

### S-19 — HIGH: Backup Events and Notifications Have No Tests

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/SysAdmin/Backups/Events/BackupCompleted.php`, `BackupFailed.php`, `Listeners/SendBackupFailedNotification.php`, `Notifications/BackupFailedNotification.php` |
| **Pattern violated** | `docs/architecture/testing-pattern.md` — events and notifications should have tests |
| **What's wrong** | 4 backup event/listener/notification classes with zero tests |
| **Fix** | Create feature tests for event dispatch and notification sending |
| **Impact** | Quality — notification logic on backup failures untested |

---

### S-20 — MEDIUM: Inline JavaScript `onclick` Handlers in SystemSetting View

| Attribute | Detail |
|-----------|--------|
| **File** | `resources/views/settings/system-setting.blade.php:152,180` |
| **Pattern violated** | CSP best practice — inline event handlers violate Content Security Policy |
| **What's wrong** | Two `onclick="document.getElementById(...).click()"` handlers for logo/favicon upload triggers. Breaks CSP if enabled; should use Alpine `x-on:click` or Livewire approach. |
| **Fix** | Replace with Alpine.js `x-on:click` or a Livewire method |
| **Impact** | Security — CSP incompatibility |

---

### S-21 — MEDIUM: 8 Feature Tests Use `RefreshDatabase` Instead of `LazilyRefreshDatabase`

| Attribute | Detail |
|-----------|--------|
| **Files** | Multiple `tests/Feature/Settings/Actions/*Test.php` |
| **Pattern violated** | `AGENTS.md` Testing Essentials — prefer `LazilyRefreshDatabase` |
| **What's wrong** | 8 Settings feature tests use `uses(RefreshDatabase::class)` which always replays migrations, slower than `LazilyRefreshDatabase` |
| **Fix** | Replace with `uses(LazilyRefreshDatabase::class)` |
| **Impact** | Performance — slower test execution |

---

### S-22 — MEDIUM: 4 Unit Tests Incorrectly Use Database Traits

| Attribute | Detail |
|-----------|--------|
| **Files** | `tests/Unit/Settings/Support/SettingsTest.php`, `BrandTest.php`, `Models/SettingModelTest.php`, `Entities/SettingEntityTest.php` |
| **Pattern violated** | `docs/architecture/testing-pattern.md` — unit tests must not touch the database |
| **What's wrong** | Settings/SettingsTest and SettingModelTest use `RefreshDatabase`; BrandTest and SettingEntityTest use `LazilyRefreshDatabase`. Entity tests should be pure unit with no DB trait. |
| **Fix** | Remove database traits; mock dependencies or use `Setting::factory()->make()` |
| **Impact** | Architecture — unit tests depend on database |

---

### S-23 — MEDIUM: BackupManager Livewire Has Inline Stats Query Instead of Read Action

| Attribute | Detail |
|-----------|--------|
| **File** | `app/SysAdmin/Backups/Livewire/BackupManager.php:59-68` |
| **Pattern violated** | `docs/architecture/livewire-pattern.md` — complex queries should be Read Actions |
| **What's wrong** | `#[Computed] stats()` performs `Backup::count()`, `Backup::where('status', ...)->count()`, etc. directly in the Livewire component |
| **Fix** | Extract into `ReadBackupStatsAction` |
| **Impact** | Maintainability — persistence logic in UI layer |

---

### P-1 — [RESOLVED] SuperAdminIntegrityRules Entity Uses `DB::` Facade for Queries

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Removed `countSuperAdmins()` method and `DB` import from entity. `fromModel()` now expects `super_admin_count` to be loaded by caller. Updated `User::asSuperAdminIntegrityRules()` to eagerly load count via `loadCount`. All 9 direct callers migrated to `$user->asSuperAdminIntegrityRules()`. |

---

### P-2 — [RESOLVED] 6 Livewire Components Missing Feature Tests

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Feature tests created for all 6 components: `ProfileEditorTest` (7 tests), `RecoveryCodeTest` (4 tests), `AccountRecoveryTest` (4 tests), `RecoverySlipManagerTest` (6 tests), `NotificationCenterTest` (5 tests), `NotificationBellTest` (5 tests). Total 48 assertions across 31 new tests. |

---

### P-3 — [RESOLVED] GenerateRecoverySlipAction Does Not Dispatch an Event

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Created `RecoverySlipGenerated` event in `app/Auth/AccountRecovery/Events/` with `user` and `codeCount` properties. `GenerateRecoverySlipAction::execute()` now dispatches `RecoverySlipGenerated` after successful log call. Listeners can be registered in `config/event.php`. |

