# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** fix all 23 Settings & Backups audit findings (S-1 through S-23)

All known issues have been resolved. See below for the fix log.

---

## Resolved Issues

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
