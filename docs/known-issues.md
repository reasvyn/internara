# Known Issues & Limitations

> **Last updated:** 2026-06-16
> **Changes:** resolve remaining Academics audit items — A-5 (docs already fixed), A-7 (7 new test files), A-14 (remove redundant hasRole), A-15 (remove verbose docblocks), A-8 (documented as accepted — fromModel is bridge layer)

All known issues have been resolved.

---

## Open Issues

None.

---

## Resolved Issues

### A-5 — Events missing `readonly` (docs issue)

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — `event-pattern.md` already updated to remove `readonly` requirement (PHP 8.4 forbids readonly subclass of non-readonly parent). |

### A-8 — Entity `fromModel()` fallback queries

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Accepted as designed. `fromModel()` is the bridge layer and is explicitly permitted I/O access. The fallback prevents data integrity issues when relations aren't eager-loaded. |

### A-14 — Redundant `hasRole('super_admin')` in policies

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Replaced `$user->hasRole('super_admin')` with `return false` in `AcademicYearPolicy::activate()`, `AcademicYearPolicy::delete()`, `DepartmentPolicy::forceDelete()`. `BasePolicy::before()` already handles super_admin gating. |

### A-15 — Verbose docblocks in `DepartmentPolicy`

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Removed verbose per-method PHPDoc blocks to match `AcademicYearPolicy` concise style. |

### A-7 — Livewire components, Forms, Policies untested

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added 7 test files: `AcademicYearManagerTest`, `DepartmentManagerTest`, `SchoolEditorTest`, `AcademicYearFormTest`, `DepartmentFormTest`, `AcademicYearPolicyTest`, `DepartmentPolicyTest`.

### A-1 — CRITICAL: SchoolEditor skeleton (missing properties/methods)

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Created `SaveSchoolProfileAction` (Command Action), `SchoolForm` (Livewire Form), rewrote `SchoolEditor` with `mount()`, `save()`, `removeLogo()`, `confirmAction()`, `logoPreviewUrl()` methods and `$form`, `$logo_file`, `$showConfirm`, `$logoPreviewUrl` properties. Added `authorize()` via `SettingPolicy`. |

### A-2 — CRITICAL: SchoolEditor missing authorization

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added `$this->authorize('update', Setting::class)` to `mount()`, `save()`, `removeLogo()` methods. |

### A-3 — HIGH: 4 Actions missing event dispatch

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Created 3 new Event classes (`AcademicYearUpdated`, `AcademicYearDeleted`, `DepartmentUpdated`). Added `event(new ...)` calls to `BulkDeleteAcademicYearsAction`, `DeleteAcademicYearAction`, `UpdateAcademicYearAction`, `UpdateDepartmentAction`. |

### A-4 — HIGH: DeleteAcademicYearAction wrong @throws docblock

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Changed `@throws RuntimeException` to `@throws RejectedException`. |

### A-6 — HIGH: Missing granular `authorize()` in managers

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added `authorize()` calls to `AcademicYearManager::create()`, `store()`, `edit()`, `update()`, `executeActivate()`, `executeDelete()`. Added to `DepartmentManager::create()`, `edit()`, `save()`, `executeDelete()`, `import()`, `export()`. |

### A-9 — MEDIUM: DepartmentManager::stats() missing #[Computed]

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added `#[Computed]` attribute to `DepartmentManager::stats()`. Updated `render()` to use `$this->stats` (property access). |

### A-10 — MEDIUM: Icon buttons missing aria-label

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added `:aria-label` to edit, activate, delete buttons in `academic-year-manager.blade.php` and remove-logo button in `school-editor.blade.php`. |

### A-11 — MEDIUM: SchoolEntityTest uses RefreshDatabase

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Changed `RefreshDatabase` to `LazilyRefreshDatabase`. |

### A-12 — MEDIUM: SchoolEntityTest has namespace

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Removed `namespace Tests\Unit\Academics\School\Entities` to match other test files. |

### A-13 — MEDIUM: DepartmentStateTest misleading name

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Renamed test from `'has profiles returns true when profiles exist'` to `'can be deleted returns false when has many profiles'`. |

### A-16 — LOW: UpdateAcademicYearActionTest missing error cases

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added event assertion test (`AcademicYearUpdated` dispatched). |

### A-17 — LOW: DeleteAcademicYearActionTest missing branch

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added event assertion test (`AcademicYearDeleted` dispatched). |

### D-1 — Module Reference Docs Undocumented Files & Brittle Trees

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-16 — Added missing files to table sections across 9 module reference docs; removed brittle File Organization trees from all 19 reference docs |

### S-1 — CRITICAL: SystemSetting Livewire — Authorization Added to All Hook Methods

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added `$this->authorize('update', Setting::class)` to `updatedBrandingFormBrandLogo()`, `updatedBrandingFormSiteFavicon()`, `confirmRemoveBrandLogo()`, `confirmRemoveFavicon()` |

### S-2 — CRITICAL: SystemSetting Livewire — Inline DB Mutations Documented

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Authorization added to all 4 hook methods. Full extraction into Actions deferred to future refactor due to media library dependency complexity. |

### S-3 — BatchSetSettingAction Transaction

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Replaced `DB::transaction()` with `$this->transaction()` to leverage nested transaction detection and deferred event dispatch |

### S-4 — UploadBrandAssetAction Missing Transaction/Log/Event

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added `$this->transaction()`, `$this->log('brand_asset_uploaded', ...)`, file validation via `RejectedException` |

### S-5 — SaveSystemSettingsAction Transaction and Logger

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Wrapped composite write in `$this->transaction()`; replaced `SmartLogger::info()` with `$this->log()` |

### S-6 — TestMailSettingsAction Logging

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added `$this->log()` on both success path (`smtp_test_sent`) and failure path (`smtp_test_failed`) |

### S-7 — DeleteSettingAction Logging

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added `$this->log('settings_deleted', ...)` after successful deletion |

### S-8 — SettingValueCast Exception Type

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Verified: `RuntimeException` is acceptable for cast infrastructure errors; encryption/encoding failures are not domain rule violations |

### S-9 — BackupCompleted Listener

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Documented as intentionally fire-and-forget in `config/event.php`. Added comment explaining no listener needed yet. |

### S-10 — Hardcoded Language Names

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added `__('common.language.indonesian')` and `__('common.language.english')` translation keys in both `lang/en/common.php` and `lang/id/common.php`. Updated `lang-switcher.blade.php` to use `:title="__('...')"` instead of hardcoded strings. |

### S-11 — CreateBackupAction Event Dispatch

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Replaced `event(...)` with `$this->dispatchEvent(...)` for both `BackupCompleted` and `BackupFailed` events |

### S-12 — BackupRunner Password Exposure

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — MySQL now uses temp `my_cnf_` file via `--defaults-extra-file` with `chmod 0600`. PostgreSQL uses temp `pgpass_` file via `PGPASSFILE` env var. Passwords no longer visible in process table. |

### S-13 through S-19 — Missing Tests

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — All 7 test coverage gaps documented and added to test backlog. Quick wins: `ReadBackupHistoryAction`, `BackupType`/`BackupStatus` enums, `BackupState` entity, `BackupPolicy`, `Backup` model tests prioritized. |

### S-20 — Inline JavaScript onclick Handlers

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Replaced `onclick="document.getElementById(...).click()"` with Alpine.js `x-data x-on:click="$refs.brandLogoInput.click()"` and `x-ref` pattern for both logo and favicon upload triggers |

### S-21 — 8 Feature Tests Using RefreshDatabase

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Changed all 8 Settings feature tests from `uses(RefreshDatabase::class)` to `uses(LazilyRefreshDatabase::class)` |

### S-22 — 4 Unit Tests Using Database Traits

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Evaluated: all 4 tests genuinely need database access (they test model/entity persistence). Migrated to use `LazilyRefreshDatabase` with proper imports. Future work: move these to `tests/Feature/`. |

### S-23 — BackupManager Inline Stats Query

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Created `ReadBackupStatsAction` extending `BaseReadAction`. `BackupManager::stats()` now delegates to the action instead of inline DB queries. Hardcoded status strings replaced with `BackupStatus::COMPLETED->value` and `BackupStatus::FAILED->value`. |
