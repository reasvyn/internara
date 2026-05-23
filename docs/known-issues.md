# Known Issues and Gotchas

## SQLite vs MySQL Differences

The application defaults to SQLite in development, but production usually runs MySQL or PostgreSQL. This difference causes several gotchas.

SQLite requires an explicit `PRAGMA foreign_keys = ON` to enforce foreign key constraints. Without this, orphaned records can accumulate silently. The database configuration enables this by default, but custom raw SQL queries must set the pragma manually.

SQLite has limited `ALTER TABLE` support. Most schema changes require recreating the table. This means migration order matters more — adding a column to a table that another migration just modified may fail. Check `Schema::hasColumn()` before adding columns that might already exist.

SQLite does not support `ENUM` types. Enum columns in MySQL are represented as `TEXT` columns with `CHECK` constraints in SQLite. The migration syntax differs, and the `check()` method must be used when adding enum-like columns.

SQLite writes lock the entire database file. Under concurrent write load, "database is locked" errors will occur. This is expected behavior — the solution is to use MySQL or PostgreSQL in production.

## UUID Considerations

UUID primary keys are larger than integer keys (16 bytes vs 4-8 bytes). This means indexes are larger and joins are slightly slower. At the expected data volumes this is not a problem, but it is worth noting for tables that will grow very large.

UUIDs make database dumps and manual queries less convenient — you cannot guess a record's ID or iterate through them sequentially. All queries should use meaningful criteria (email, name, date) rather than relying on ID ordering.

## Queue Worker Requirement

The queue worker is not optional. Without it, notifications are never sent, media conversions never happen, mail never goes out, and scheduled tasks accumulate. In development, the queue can run synchronously (via the `sync` driver) or by running `php artisan queue:work` in a terminal. In production, Supervisor or systemd must keep the worker running.

If jobs appear stuck in the "processing" state, the worker likely crashed. Run the prune-failed command to reset them. If jobs are never picked up, check that the queue connection in `.env` matches the worker's connection.

## Storage Permissions

The `storage/` and `bootstrap/cache/` directories must be writable by the web server user. This includes subdirectories for logs, framework files, views, and cache. On Linux, this typically means `chown -R www-data:www-data storage bootstrap/cache`. Without correct permissions, the application returns blank pages or file upload errors.

SELinux on RHEL-based distributions adds another layer of permissions. The storage directory needs the `httpd_sys_rw_content_t` context label.

The public storage symlink (`public/storage` -> `storage/app/public`) must exist for uploaded files and brand assets to be accessible. This is created by `php artisan storage:link`. If media URLs return 404, the symlink is likely missing.

## Development Workflow Gotchas

If you see "Unable to locate file in Vite manifest," the frontend assets have not been built. Run `npm run build` or `npm run dev` (or `composer run dev` which starts everything).

If configuration changes do not take effect, run `php artisan optimize:clear` to flush cached config, routes, and views. The config cache must be regenerated after any change to `config/*.php` files.

If Livewire components do not update after data changes, check that the component has reactive properties and that `$this->dispatch()` is being used for inter-component communication.

## Translation Gaps — Indonesian (id)

The `lang/id/` directory is missing translations compared to `lang/en/`:

| File | en Keys | id Keys | Gap |
|---|---|---|---|
| `internship.php` | 184 | 74 | **110 keys missing** (registration center, wizard, verification, direct placement, applications — entire sections) |

Additionally, `user.php` has different key ordering/structure between en and id, and `placement.php` uses different key names (`add_placement` vs `add`).

All Indonesian text that falls through missing keys renders in English (Laravel fallback behavior). This affects the admin panel and student-facing features.

## Dead Helper Functions

`app/Support/helpers.php` defines 7 helper functions but 4 are never called:

| Helper | Defined | Used |
|---|---|---|
| `setting()` | ✅ | ✅ Yes |
| `is_debug_mode()` | ✅ | ❌ Never |
| `is_development()` | ✅ | ❌ Never |
| `is_testing()` | ✅ | ❌ Never |
| `is_maintenance()` | ✅ | ❌ Never |
| `brand()` | ✅ | ✅ Yes |
| `app_info()` | ✅ | ✅ Yes |

These 4 dead helpers can be removed without impacting functionality.

## MCP redirect_domains Wildcard

`config/mcp.php` has `'redirect_domains' => ['*']` which allows any redirect URI. For OAuth security, this should be restricted to known application domains.

## Undocumented Environment Variables

7 Boost configuration variables are missing from `.env.example`:

| Variable | Config File | Default |
|---|---|---|
| `BOOST_ENABLED` | `config/boost.php` | `true` |
| `BOOST_BROWSER_LOGS_WATCHER` | `config/boost.php` | `true` |
| `BOOST_PHP_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_COMPOSER_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_NPM_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_VENDOR_BIN_EXECUTABLE_PATH` | `config/boost.php` | `null` |
| `BOOST_CURRENT_DIRECTORY_EXECUTABLE_PATH` | `config/boost.php` | `base_path()` |

Without these in `.env.example`, developers cannot discover or configure Boost.

## Test Artifacts in Storage

`storage/framework/testing/` contains leftover test sessions and disk directories. These are generated by `LazilyRefreshDatabase` and should not be committed or deployed. Ensure `.gitignore` covers these paths.

## Pest Plugin Arch — `toUse()` constraint bug

`pestphp/pest-plugin-arch` has a known issue where the `toUse()` / `not->toUse()` constraint can cause internal assertion failures on certain PHP/Pest combinations, resulting in a silent exit code 2 with no visible error message. This is a bug in the pest arch plugin's reflection-based import scanner, not in the application code.

**Impact:** Arch tests that use `toUse()` (like `DomainBoundariesArchTest.php`) may report false failures or fail to produce output. The constraints themselves are correct — the plugin's assertion runner sometimes misbehaves.

**Mitigation:**
- Run affected arch tests in isolation to verify the intent is correct
- Inspect the source code directly to confirm no unwanted imports exist
- Skip these particular arch tests if the plugin continues to malfunction, provided manual verification has been done

This does not create a security gap — the arch tests are structural safeguards, not runtime protections. The actual dependency graph is enforced at the code level through namespace conventions and code review.

---

## Settings Domain — Known Issues

### SE1. Broken Layout Reference in SystemSetting 🔴 *(✅ Fixed)*

**File:** `app/Domain/Settings/Livewire/SystemSetting.php:286`

The layout namespace `layouts::app` was still used after layouts moved to `resources/views/shared/layouts/`. Fixed to `shared::layouts.app`.

---

### SE2. SystemSetting Uses `rules()` Instead of `#[Validate]` 🟡

**File:** `app/Domain/Settings/Livewire/SystemSetting.php`

The component uses a `rules(): array` method for validation instead of `#[Validate]` attributes. Inconsistent with 5 Auth components (Login, ForgotPassword, ResetPassword, ConfirmPassword, AccountRecovery) that were migrated.

```php
public function rules(): array
{
    return [
        'brand_name' => 'required|string|max:50',
        // ... 18 more rules
    ];
}
```

**Impact:** Minor inconsistency. Both patterns work functionally, but prevents inline per-property validation visibility. Affects 19 properties.

**Fix:** Migrate to `#[Validate]` attributes on each property (`#[Validate('required|string|max:50')] public string $brand_name`).

*Status: ⏳ Pending — Priority P1.*

---

### SE3. No Form Objects for Settings Groups 🟡

**File:** `app/Domain/Settings/Livewire/SystemSetting.php`

The component has 25+ inline public properties mixing three distinct concerns:

```php
// General
public string $brand_name = '';
public string $site_title = '';
public string $default_locale = 'id';

// Branding colors
public string $primary_color = '';
public string $secondary_color = '';

// Mail
public string $mail_host = '';
public string $mail_port = '587';
```

**Impact:** Harder to test, harder to maintain. `validate()` validates all 19+ fields even when only 1 section changes. Bulk errors — no granular feedback per section.

**Fix:** Extract to `BrandSettingsForm`, `ColorSettingsForm`, `MailSettingsForm`. Split `save()` into `saveGeneral()`, `saveColors()`, `saveMail()` per form.

*Status: ⏳ Pending — Priority P1.*

---

### SE4. UploadBrandAssetAction Bypasses Media Library 🟡

**File:** `app/Domain/Settings/Actions/UploadBrandAssetAction.php`

Uploads directly to `Storage::put('brand/', 'public')` and returns a URL string, bypassing spatie/laravel-medialibrary entirely:

```php
public function execute(UploadedFile $file, string $type = 'logo'): string
{
    $path = $file->store('brand', 'public');
    return Storage::url($path);
}
```

**Impact:** No image optimization, no conversion (thumbnails), no responsive variants. Domain docs mention "image-type settings store media library references; the uploaded file is versioned and optimized automatically" — the code does not match the documentation. Inconsistent with School domain (school logo uses media library with `thumb` webp conversion).

**Fix:** Integrate with spatie/laravel-medialibrary. Upload as media collection, generate `thumb` webp conversion, store media ID/URL instead of raw storage path.

*Status: ⏳ Pending — Priority P2.*

---

### SE5. Mail Password Exposed as Public Livewire Property 🟡

**File:** `app/Domain/Settings/Livewire/SystemSetting.php:51`

```php
public string $mail_password = '';
```

**Risk:** Livewire 4 serializes all public properties to the frontend. While initialized as empty string, if the property is set before save, its value could leak to the client during Livewire updates. The password IS stored as `type: 'encrypted'` in the database, but the in-transit exposure via the Livewire payload is unnecessary.

**Mitigation:** Mark with `#[Locked]` attribute, or handle via a separate setter method. Leave as empty string in `mount()` so it never carries the actual value (current code already does this, but future refactors might accidentally set it).

*Status: ⏳ Pending — Priority P2.*

---

### SE6. No SettingPolicy Exists 🟢

**Directory:** `app/Domain/Settings/Policies/`

Settings domain has no Policy class. Authorization is handled inline via `abort_unless(auth()->user()->hasRole('super_admin'), 403)` inside `boot()`. All other domains extend `BasePolicy`.

**Impact:** Inconsistent with project conventions. Policy-based authorization is the standard pattern — inline guards are harder to test, audit, and override.

**Fix:** Create `SettingPolicy` extending `BasePolicy`.

*Status: ⏳ Pending — Priority P2.*

---

### SE7. AppMetadata Queries `setups` Table on Every Call 🟡

**File:** `app/Domain/Settings/Support/AppMetadata.php:25`

```php
DB::table('setups')->value('is_installed');
```

Called by every `brandName()`, `siteTitle()`, `brandLogo()`, `favicon()` method — each fires a separate query. No caching.

**Impact:** Every `brand()` call (used on nearly every page load) triggers at least one database query. In pages with multiple brand lookups (sidebar, header, title tags), this multiplies.

**Fix:** Cache `is_installed` result for the request duration (or forever, with invalidation).

*Status: ⏳ Pending — Priority P3.*

---

### SE8. `active_academic_year` Stored as Unvalidated String 🟢

**File:** `app/Domain/Settings/Livewire/SystemSetting.php:91`

The `active_academic_year` setting is stored as a plain string `"2025/2026"` in the settings key-value store. There is no FK relationship to the `academic_years` table and no existence validation.

**Impact:** It is possible to set an academic year that does not exist in the School domain. Downstream queries that join against `academic_years` will silently produce no results.

**Fix:** Validate against the `academic_years` table, or reference the School domain's `activeAcademicYear()` method instead. Alternatively, enforce via `exists:academic_years,year` validation rule.

*Status: ⏳ Pending — Priority P3.*

---

### SE9. Setting Groups Are String Literals (No Enum) 🟢

**Files:** `app/Domain/Settings/Actions/SetSettingAction.php`, `app/Domain/Settings/Models/Setting.php`

Setting `group` column stores raw strings like `'general'`, `'mail'`, `'system'`. Default value `'general'` is a string literal in `SetSettingAction::execute()`:

```php
?string $group = 'general',
```

**Impact:** No type safety, no autocomplete, no discoverability. A mistyped group string silently creates a new group instead of belonging to an existing one.

**Fix:** Create `SettingGroup` enum with known groups (GENERAL, MAIL, SYSTEM, BRANDING, FEATURES, LOCALIZATION, NOTIFICATIONS).

*Status: ⏳ Pending — Priority P4.*

---

### SE10. BatchSetSettingAction Not Wrapped in DB Transaction 🟡

**File:** `app/Domain/Settings/Actions/BatchSetSettingAction.php`

The `execute()` method loops through settings calling `SetSettingAction::execute()` per item. If one setting fails (e.g., validation error on key), previous settings remain committed.

```php
foreach ($settings as $key => $config) {
    $setting = $this->setSettingAction->execute($key, $value, ...);
    // If this throws, prior settings are already saved
}
```

**Impact:** Partial writes on batch failure. The settings store could be left in an inconsistent state.

**Fix:** Wrap the loop in `DB::transaction()`.

*Status: ⏳ Pending — Priority P4.*

---

### SE11. `Settings::forget()` Fires Extra Query for Group Name 🟢

**File:** `app/Domain/Settings/Support/Settings.php:312`

```php
$setting = Setting::where('key', $key)->first();
if ($setting?->group) {
    Cache::forget(self::CACHE_PREFIX.'group.'.$setting->group);
}
```

An additional database query is executed just to retrieve the group name so the group cache can be invalidated.

**Fix:** Accept an optional `$group` parameter (already partially supported) and prefer it over the DB lookup. The caller (`SetSettingAction`) already has the group available.

*Status: ⏳ Pending — Priority P4.*

---

### SE12. No Feature Test for SystemSetting Livewire Component 🟡

**Directory:** `tests/Feature/Settings/`

Only `SettingsActionsTest.php` exists (unit-level tests for 4 Actions). Zero tests cover the Livewire component lifecycle: mount, render, validation, save, testEmail, preset application, file upload previews.

**Impact:** Refactoring `SystemSetting` (SE2, SE3, SE4) carries high risk of regressions that cannot be automatically detected.

**Fix:** Add feature test (`tests/Feature/Settings/SystemSettingTest.php`) covering:
- Mount loads settings from DB
- Validation errors per field
- Successful save (general, colors, mail)
- testEmail sends and reports result
- applyPreset updates colors
- Brand logo and favicon upload paths
- Settings cache invalidation after save

*Status: ⏳ Pending — Priority P4.*

---

### SE13. AppMetadata Has Zero Test Coverage 🟢

**File:** `app/Domain/Settings/Support/AppMetadata.php` (252 lines)

Overlapping logic with `Settings` and `Theme`. Zero tests. Methods tested implicitly through integration only.

**Impact:** Logic errors in `brandName()`, `favicon()` (which has nested fallback logic), `siteTitle()` go undetected.

**Fix:** Add unit tests for each public method.

*Status: ⏳ Pending — Priority P4.*

---

## Domain Models (Layer 5) & Domain Rules (Layer 6)

### Enum Label Translation Inconsistency 🟡

**Directory:** `app/Domain/*/Enums/`

Only 3 enums use `__()` for translatable labels. The remaining 26 use hardcoded English strings (or Indonesian for `AbsenceReasonType` and `SupervisionType`):

| Pattern | Enums |
|---|---|
| Uses `__()` for labels | `AuditCategory`, `AccountApplicationStatus`, `Role` |
| Hardcoded English | 23 enums (AuditStatus, RegistrationDocumentStatus, etc.) |
| Hardcoded Indonesian | `AbsenceReasonType` (`'Sakit'`, `'Izin'`), `SupervisionType` (`'Bimbingan'`, `'Mentoring'`) |
| Returns key string only | `AccountStatus` (returns `'account_status.status.'.$this->value` without `__()`) |

There is no project-wide rule about whether enum labels should be translatable or hardcoded. This inconsistency means UI elements that render `$enum->label()` may display English in some places and Indonesian in others.

*Status: ⏳ Pending — establish and enforce a consistent label strategy.*

---

## Backlog — Unresolved Items

### Feature Test Coverage (147 uncovered Actions)

Only 4 of 151 Actions have feature tests. Critical for stability before production deployment.

| Domain | Actions | Feature Tests | Gap |
|---|---|---|---|
| Assessment | 17 | 0 | 🔴 |
| Internship | 16 | 0 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Admin | 12 | 2 | 🟡 |
| Attendance | 8 | 0 | 🔴 |
| Partnership | 8 | 0 | 🔴 |
| Mentor | 8 | 0 | 🔴 |
| Placement | 7 | 0 | 🔴 |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Registration | 5 | 0 | 🔴 |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Guidance | 2 | 0 | 🔴 |
| Evaluation | 2 | 1 | 🟡 |
| User | 2 | 2 | 🟢 |

**Target:** Minimum 1 feature test per Action in Assessment (17), Internship (16), Auth (12), Settings (4), and Setup (8).

### Shared Domain Issues

**CsvHandler Uses Fragile Magic String Protocol 🟡**
`app/Domain/Shared/Support/CsvHandler.php:72` interprets callable return values via string comparison (`$result === 'skipped'`). The contract is undocumented and type-unsafe — any truthy non-null value counts as "created".

**LangChecker Contradicts "Stateless" Rule 🟡**
`app/Domain/Shared/Support/LangChecker.php` is the only non-`final` utility in Shared, extending `Translator` with mutable state. The docs mandate "Utilities must be stateless: static methods or immutable objects."

### Cross-Domain Event Flow Documentation 🟢

Which events fire and which listeners react is not documented. Needed for understanding side effects when modifying Actions.

### Real-Time Features (Future) 🟢

Laravel Echo and Reverb are installed but no real-time channels are active. Candidates: notification delivery, dashboard updates, attendance confirmations.

### Queue Job Formalization (Future) 🟢

Evaluate which operations should be queued: certificate generation, report rendering, batch notifications. Currently all notifications use `ShouldQueue`.

### Livewire Form Object Migration 🟡

**Problem:** 77 Livewire components still manage form state via flat `public` properties scattered across the component class. The Setup wizard and ProfileEditor have been migrated as reference implementations.

**Completed:**
- ✅ `SetupWizard` → `SchoolForm`, `DepartmentForm`, `AdminForm`, `InternshipForm`
- ✅ `ProfileEditor` → `ProfileForm`, `PasswordForm`
- ✅ `Login` → `LoginForm`, `ForgotPassword` → `ForgotPasswordForm`, `ResetPassword` → `ResetPasswordForm`, `ConfirmPassword` → `ConfirmPasswordForm`, `AccountRecovery` → `AccountRecoveryForm`

**Remaining priority:**

| Priority | Domain | Form | Components Affected |
|---|---|---|---|
| 🟠 P2 | Registration | `RegistrationForm`, `DocumentUploadForm` | `RegistrationWizard`, `RegistrationDocumentUpload` |
| 🟠 P3 | User | `ProfileForm` | `ProfileEditor` |
| 🟡 P4 | Admin | `UserForm`, `AnnouncementForm` | `UserManager`, `CreateAdminCommand` |
| 🟡 P5 | School | `AcademicYearForm`, `DepartmentForm` | `AcademicYearIndex`, `DepartmentManager` |
| 🟢 P6 | Settings | `BrandSettingsForm`, `ColorSettingsForm`, `MailSettingsForm` | `SystemSetting` |
| 🟢 P6 | All remaining forms | — | ~20 components |

**Convention:** See `docs/conventions.md` Section 9a — Form Objects for the required pattern.

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|
| 🔴 | Feature tests missing for 147 of 151 Actions | Testing | ⏳ |
| 🔴 | Indonesian `internship.php` missing 110 keys | Translation | ⏳ |
| 🟡 | **SE2** SystemSetting uses `rules()` instead of `#[Validate]` | Settings | ⏳ |
| 🟡 | **SE3** No Form Objects for Settings groups | Settings | ⏳ |
| 🟡 | **SE4** UploadBrandAssetAction bypasses Media Library | Settings | ⏳ |
| 🟡 | **SE5** Mail password exposed as public Livewire property | Settings | ⏳ |
| 🟢 | **SE6** No SettingPolicy exists | Settings | ⏳ |
| 🟡 | **SE7** AppMetadata queries `setups` table on every call | Settings | ⏳ |
| 🟢 | **SE8** `active_academic_year` stored as unvalidated string | Settings | ⏳ |
| 🟢 | **SE9** Setting groups are string literals (no enum) | Settings | ⏳ |
| 🟡 | **SE10** BatchSetSettingAction not wrapped in DB transaction | Settings | ⏳ |
| 🟢 | **SE11** `Settings::forget()` fires extra query for group | Settings | ⏳ |
| 🟡 | **SE12** No feature test for SystemSetting Livewire component | Settings | ⏳ |
| 🟢 | **SE13** AppMetadata has zero test coverage | Settings | ⏳ |
| 🟡 | HandlesActionErrors swallows custom exceptions | Architecture | ⏳ |
| 🟡 | Livewire Form Object migration (77 components remaining) | Architecture | ⏳ |
| 🟡 | SmartLogger IP/UA without PII mask | Core | ⏳ |
| 🟡 | CsvHandler fragile magic string protocol | Shared | ⏳ |
| 🟡 | LangChecker contradicts "stateless" rule | Shared | ⏳ |
| 🟡 | Enum label translation inconsistency | Enums | ⏳ |
| 🟡 | 48 FK columns without individual indexes | Database | ⏳ |
| 🟡 | Role enum `func_` prefix value inconsistency | Enums | ⏸️ |
| 🟡 | BaseAction does not enforce execute() method | Architecture | ⏸️ |
| 🟢 | Cross-domain event flow undocumented | Documentation | ⏳ |
| 🟢 | Real-time features (Echo + Reverb) not yet active | Future | ⏳ |
| 🟢 | Queue job formalization not evaluated | Future | ⏳ |
| 🟢 | PII in activity logs (partially masked) | Security | ⏳ |
| 🟢 | PHP version in CLI banner | Security | ⏳ |
| 🟢 | Stack trace in system logs | Security | ⏳ |
| 🟢 | App version in UI footer | Security | ⏳ |
| 🟢 | No rate limiting on RecoverSuperAdminAction | Security | ⏳ |
