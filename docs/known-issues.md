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



## Settings Domain — Remaining Issues

### SE13. AppMetadata Test Coverage 🟢

**File:** `app/Domain/Settings/Support/AppMetadata.php` (252 lines)

15 unit tests covering all public methods including fallback paths for `brandName`, `siteTitle`, `brandLogo`, `favicon`, `colors`, and all `get()` key mappings. Database-dependent rendering paths not tested (require integration setup).

**Fix:** None needed — adequate smoke coverage.

*Status: ✅ Adequate — Priority P4.*

---

## Notification Domain — Known Issues

### N2. No Notification Cleanup / Pruning Mechanism 🔴 *(✅ Fixed)*

Created `PruneNotificationsCommand` (daily via `routes/console.php`, default 30-day retention for read notifications).

*Status: ✅ Fixed — Priority P1.*

---

## Domain Models (Layer 5) & Domain Rules (Layer 6)



## User Registration Area — Audit Findings

---

## Backlog — Unresolved Items

### Feature Test Coverage (139 uncovered Actions)

Only 4 of 143 Actions have feature tests (excluding Setup which is fully covered). Critical for stability before production deployment.

| Domain | Actions | Feature Tests | Gap |
|---|---|---|---|
| Assessment | 17 | 0 | 🔴 |
| Internship | 16 | 0 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Admin | 9 | 9 | 🟢 ✅ |
| Attendance | 8 | 0 | 🔴 |
| Partnership | 8 | 8 | 🟢 ✅ |
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
| Evaluation | 3 | 1 | 🟡 |
| User | 8 | 2 | 🟢 |
| Setup | 9 | 9 | 🟢 |
| Settings | 6 | 6 | 🟢 |

**Target:** Minimum 1 feature test per Action in Assessment (17), Internship (16), Auth (12), Settings (6).

### Shared Domain Issues

**CsvHandler Uses Fragile Magic String Protocol 🟡** *(✅ Fixed)*
Created `CsvRowResult` enum (`CREATED`/`SKIPPED`). Handler accepts both enum and legacy string. `DepartmentManager` updated to use enum.

**LangChecker Contradicts "Stateless" Rule 🟡** *(✅ Fixed)*
Made `final`. True stateless refactor (decoupling from `Translator`) deferred — utility naturally requires mutability to intercept translation resolution.

### HandlesActionErrors — Passes Through Framework Exceptions 🟡 *(✅ Fixed)*

Added `ValidationException`, `AuthorizationException`, `ModelNotFoundException`, and `NotFoundHttpException` to the pass-through list so they are not wrapped as `RuntimeException`.

### SmartLogger — IP/UA Masked with PII Flag 🟡 *(✅ Fixed)*

Added `PiiMasker::maskIp()` and `PiiMasker::maskUserAgent()`. Logged IP and user agent in activity logs are now masked when `withPiiMasking()` is enabled.

### App Version in UI Footer 🟢 *(✅ Fixed)*

Footer now only shows version in `local` environment via `app()->environment('local')`.

### Rate Limiting on RecoverSuperAdminAction 🟢 *(✅ Fixed)*

Added per-email throttle (3 attempts, 15-minute cooldown) using Cache.

### Cross-Domain Event Flow Documentation 🟢

Which events fire and which listeners react is not documented. Needed for understanding side effects when modifying Actions.

### Real-Time Features (Future) 🟢

Laravel Echo and Reverb are installed but no real-time channels are active. Candidates: notification delivery, dashboard updates, attendance confirmations.

### Queue Job Formalization (Future) 🟢

Evaluate which operations should be queued: certificate generation, report rendering, batch notifications. Currently all notifications use `ShouldQueue`.

### FK Columns Without Individual Indexes 🟡 *(✅ Fixed)*

Added `$table->index()` calls for 37 FK columns across 24 original table creation migrations (separate from `foreignUuid()` chains — SQLite ignores chained `->index()`).

### UC7 — Complete Test Coverage for Admin User Management 🔴 *(✅ Fixed)*

**Livewire tests:** 45 tests across all 7 admin managers (render, authorization, create, edit, delete, validation, search, status toggle).

**Action tests:** Fixed pre-existing `BindingResolutionException` failures (missing imports in `AdminActionsTest.php`). Fixed `DeleteUserActionTest` self-deletion test (used `SUPER_ADMIN` which triggers the super_admin guard before self-check). Added `ReadRecoveryKeyActionTest` and `SaveRecoveryKeyActionTest`.

**Result:** All 9 Admin Actions now have feature tests. 21 Admin action tests + 45 Livewire tests = **66 total tests** for user management.

### Enum Labels Use `__()` Consistently 🟡 *(✅ Fixed)*

All 34 enum `label()` methods now wrap return values in `__()`. Indonesian hardcoded strings in `AbsenceReasonType` and `SupervisionType` converted to English (translation keys). 106 string literals updated across 27 files.

### Livewire Form Object Migration 🟡

**Problem:** 81 Livewire components still manage form state via flat `public` properties scattered across the component class. The Setup wizard and ProfileEditor have been migrated as reference implementations.

**Completed:**
- ✅ `SetupWizard` → `SchoolForm`, `DepartmentForm`, `AdminForm`, `InternshipForm`
- ✅ `ProfileEditor` → `ProfileForm`, `PasswordForm`
- ✅ `Login` → `LoginForm`, `ForgotPassword` → `ForgotPasswordForm`, `ResetPassword` → `ResetPasswordForm`, `ConfirmPassword` → `ConfirmPasswordForm`, `AccountRecovery` → `AccountRecoveryForm`
- ✅ `SystemSetting` → `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm`
- ✅ Admin user managers → `UserForm`, `AdminUserForm`, `TeacherForm`, `StudentForm`, `SupervisorForm`, `MentorForm`, `MenteeForm`

**Remaining priority:**

| Priority | Domain | Form | Components Affected |
|---|---|---|---|
| 🟠 P2 | Registration | `RegistrationForm`, `DocumentUploadForm` | `RegistrationWizard`, `RegistrationDocumentUpload` |
| 🟠 P3 | User | `ProfileForm` | `ProfileEditor` |
| 🟡 P4 | Announcement | `AnnouncementForm` | `AnnouncementManager` ✅ |
| 🟡 P5 | School | `AcademicYearForm`, `DepartmentForm` | `AcademicYearManager`, `DepartmentManager` ✅ |
| 🟢 P6 | All remaining forms | — | ~60 components |

**Convention:** See `docs/conventions.md` Section 9a — Form Objects for the required pattern.

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|---|
| 🔴 | Feature tests missing for ~110 of 143 Actions (excluding Setup, Partnership, Admin) | Testing | ⏳ |
| 🔴 | Indonesian `internship.php` missing 110 keys | Translation | ⏳ |
| 🔴 | **UC7** Livewire feature tests for all 7 admin managers | Admin | ✅ Fixed |
| 🟢 | **SE13** AppMetadata test coverage adequate | Settings | ✅ |
| 🟡 | HandlesActionErrors swallows custom exceptions | Architecture | ✅ Fixed |
| 🟡 | Livewire Form Object migration (~60 components remaining) | Architecture | ⏳ |
| 🟡 | SmartLogger IP/UA without PII mask | Core | ✅ Fixed |
| 🟡 | Enum label translation — 34 enums use `__()` consistently | Enums | ✅ Fixed |
| 🟡 | FK columns without individual indexes | Database | ✅ Fixed |
| 🟡 | Role enum `func_` prefix — resolved via translation | Enums | ✅ Fixed |
| 🟡 | BaseAction cannot enforce execute() — signatures vary | Architecture | ⏸️ Cannot enforce |
| 🟢 | Cross-domain event flow undocumented | Documentation | ⏳ |
| 🟢 | Real-time features (Echo + Reverb) not yet active | Future | ⏳ |
| 🟢 | Queue job formalization not evaluated | Future | ⏳ |
| 🟢 | PII in activity logs (IP/UA masked with PII flag) | Security | ✅ Fixed |
| 🟢 | App version in UI footer | Security | ✅ Fixed |
