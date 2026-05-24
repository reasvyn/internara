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

## Settings Domain — Remaining Issues

### SE12. No Feature Test for SystemSetting Livewire Component 🟡

**Directory:** `tests/Feature/Settings/`

Only `SettingsActionsTest.php` exists (unit-level tests for 4 Actions). Zero tests cover the Livewire component lifecycle.

**Impact:** Refactoring `SystemSetting` carries high risk of regressions.

**Fix:** Add feature test (`tests/Feature/Settings/SystemSettingTest.php`) covering mount, validation, save, testEmail, preset application, file uploads, cache invalidation.

*Status: ⏳ Pending — Priority P4.*

---

### SE13. AppMetadata Has Zero Test Coverage 🟢

**File:** `app/Domain/Settings/Support/AppMetadata.php` (252 lines)

Overlapping logic with `Settings` and `Theme`. Zero tests.

**Fix:** Add unit tests for each public method.

*Status: ⏳ Pending — Priority P4.*

---

## Notification Domain — Known Issues

### N1. IncidentReportedNotification Uses Wrong Channel (Laravel `database`, Not Custom) 🔴

**File:** `app/Domain/Incident/Notifications/IncidentReportedNotification.php`

The `via()` method returns `['database', 'broadcast']` instead of `['mail', 'broadcast', CustomDatabaseChannel::class]`. It uses Laravel's native `database` channel (stored in the default `notifications` table from package migration) instead of the custom `notifications` table with the domain-defined schema.

The data structure is also incompatible: `toDatabase()` returns type/incident_id/severity/description/link while the custom channel expects type/title/message/data/link via `toCustomDatabase()`.

**Impact:** 🔴 Incident notifications are invisible in the Notification Center — they go to the wrong table entirely.

**Fix:** Migrate to `CustomDatabaseChannel::class`. Implement `toCustomDatabase()` returning the expected schema (type, title, message, data, link). Add `ShouldQueue`.

*Status: ⏳ Pending — Priority P1.*

---

### N2. No Notification Cleanup / Pruning Mechanism 🔴

**File:** `app/Domain/Admin/Models/Notification.php`

The `notifications` table has no built-in retention policy. Read notifications accumulate indefinitely. No scheduled job or Artisan command prunes old records.

**Impact:** 🔴 Unbounded table growth over time. Performance degradation on queries, backup bloat.

**Fix:** Add a scheduler task or command to delete notifications read more than N days ago (configurable, default 30/60/90). Run via `app/Console/Kernel::schedule()`.

*Status: ⏳ Pending — Priority P1.*

---

### N3. Notification Center Should Be in User Domain (Not Admin) 🟡

**File:** `app/Domain/Admin/Livewire/NotificationCenter.php`

NotificationCenter and NotificationBell live in the Admin domain, but notifications are not an admin-only feature — every authenticated user (student, teacher, supervisor, admin) needs access to their notifications.

The route at `routes/web/user.php` also places `/notifications` in the User domain's route file, but the component itself is in Admin, creating a split identity.

Additionally, the Notification Center should show role-filtered content: all users see their own notifications, but admins may see additional system-level notifications.

**Impact:** Architectural misplacement. Notifications are conceptually per-user, not per-admin. The Admin domain should only own admin-specific notification features (announcements, broadcast).

**Fix Plan — 4 Fase:**

**Fase 1 — Pindahkan files dari Admin ke User domain (app/, views/, tests/):**

Move (7 files):
| Dari (Admin) | Ke (User) |
|---|---|
| `app/Domain/Admin/Models/Notification.php` | `app/Domain/User/Models/Notification.php` |
| `app/Domain/Admin/Livewire/NotificationCenter.php` | `app/Domain/User/Livewire/NotificationCenter.php` |
| `app/Domain/Admin/Livewire/NotificationBell.php` | `app/Domain/User/Livewire/NotificationBell.php` |
| `app/Domain/Admin/Livewire/ActivityFeedManager.php` | `app/Domain/User/Livewire/ActivityFeedManager.php` |
| `app/Domain/Admin/Actions/SendNotificationAction.php` | `app/Domain/User/Actions/SendNotificationAction.php` |
| `app/Domain/Admin/Actions/MarkAsReadAction.php` | `app/Domain/User/Actions/MarkAsReadAction.php` |
| `app/Domain/Admin/Actions/MarkAllAsReadAction.php` | `app/Domain/User/Actions/MarkAllAsReadAction.php` |
| `app/Domain/Admin/Actions/DeleteNotificationAction.php` | `app/Domain/User/Actions/DeleteNotificationAction.php` |
| `app/Domain/Admin/Policies/NotificationPolicy.php` | `app/Domain/User/Policies/NotificationPolicy.php` |
| `resources/views/admin/notification-center.blade.php` | `resources/views/user/notification-center.blade.php` |
| `resources/views/admin/notification-bell.blade.php` | `resources/views/user/notification-bell.blade.php` |
| `resources/views/admin/activity-feed.blade.php` | `resources/views/user/activity-feed.blade.php` |

Update references:
- `routes/web/user.php` — import `App\Domain\User\Livewire\NotificationCenter`
- `DomainServiceProvider` — binding `SendsNotifications::class` ke `\App\Domain\User\Actions\SendNotificationAction::class`
- `app/Domain/User/Livewire/Dashboards/AdminDashboard.php` — update import `SendNotificationAction` dan `Notification` ke User namespace
- `app/Domain/Core/Channels/CustomDatabaseChannel.php` — update import `SendsNotifications` (di Core, binding ke User)
- `app/Domain/Admin/Livewire/AnnouncementManager.php` — update import `Notification` ke User namespace
- `routes/web/admin.php` — update import jika ada yang reference
- Hapus file lama dari Admin domain (after confirming all references updated)

**Fase 2 — Announcements bisa dioperasikan admin biasa:**
- `routes/web/admin.php` — middleware `role:super_admin` → `role:super_admin|admin`
- `AnnouncementManager.php::boot()` — `hasRole('super_admin')` → panggil policy `create`

**Fase 3 — Konten role-filtered di Notification Center:**
- Tambah `scopeForRole()` di `User\Models\Notification`
- Update `NotificationCenter::query()` untuk filter by role

**Fase 4 — Perbaikan tambahan (N1, N4, N5, N6):**
- N4: Buat `MarkBatchAsReadAction`, panggil dari `markSelectedAsRead()`
- N5: Tambah guard di `CustomDatabaseChannel::send()`

*Status: ⏳ Pending — Priority P2.*

---

### N4. `markSelectedAsRead()` Bypasses Action Pattern 🟡 *(✅ Fixed in Phase 1)*

**File:** `app/Domain/Admin/Livewire/NotificationCenter.php:88` → `app/Domain/User/Livewire/NotificationCenter.php`

Refactored as part of the User domain migration. Created `MarkBatchAsReadAction` in User domain. Component delegates to the Action instead of inline query.

*Status: ✅ Fixed as part of N3 migration.*

---

### N5. `CustomDatabaseChannel` No Guard for Empty User ID 🟡

**File:** `app/Domain/Core/Channels/CustomDatabaseChannel.php:33`

```php
$this->sendNotification->execute(
    userId: (string) $notifiable->id,
    // ...
);
```

If `$notifiable->id` is null (edge case), the string cast yields `''`. `SendNotificationAction::execute()` then calls `User::findOrFail('')` which throws `ModelNotFoundException`.

**Fix:** Add guard: `throw_unless($notifiable->id, ...)` or skip notification with SmartLogger warning.

*Status: ⏳ Pending — Priority P3.*

---

### N6. `DeleteNotificationAction` PHPDoc Misleading About Security 🟢

**File:** `app/Domain/Admin/Actions/DeleteNotificationAction.php`

PHPDoc claims "S1 - Secure: Only owner can delete", but the Action itself does not verify ownership. It relies on the caller (Livewire component) to scope the query with `where('user_id', Auth::id())`. If called directly from another context, any notification could be deleted.

**Fix:** Either add ownership verification inside the Action, or update the PHPDoc to reflect that ownership is the caller's responsibility.

*Status: ⏳ Pending — Priority P4.*

---

### N7. `ActivityFeedManager` Queries Inline Instead of Using Action 🟢

**File:** `app/Domain/Admin/Livewire/ActivityFeedManager.php:17`

```php
$activities = auth()->user()->activityLogs()->latest()->paginate(50);
```

Business logic in `render()` instead of delegating to an Action.

**Fix:** Create `GetActivityLogsAction` and use it in the component.

*Status: ⏳ Pending — Priority P4.*

---

### N8. `CustomDatabaseChannel` No Validation on `toCustomDatabase()` Return Value 🟢

**File:** `app/Domain/Core/Channels/CustomDatabaseChannel.php:31`

```php
$data = $notification->toCustomDatabase($notifiable);
$this->sendNotification->execute(
    type: $data['type'] ?? 'general',
    title: $data['title'] ?? 'Notification',
    // ...
);
```

If a notification's `toCustomDatabase()` returns missing keys, the channel silently uses hardcoded fallback defaults with no warning. Structural errors in notification classes go undetected.

**Fix:** Validate the return array shape, log SmartLogger warning if keys are missing.

*Status: ⏳ Pending — Priority P4.*

---

### N9. Zero Test Coverage for Notification Components 🟢

**Directory:** `tests/Feature/Admin/`, `tests/Feature/User/`

No feature tests exist for `NotificationCenter`, `NotificationBell`, `ActivityFeedManager`, or any of the 5 notification Actions (`SendNotificationAction`, `MarkAsReadAction`, `MarkAllAsReadAction`, `DeleteNotificationAction`, `GetNotificationsAction`).

**Impact:** Refactoring N1-N4 carries high regression risk.

**Fix:** Add tests for notification Livewire components and Actions.

*Status: ⏳ Pending — Priority P4.*

---

### N10. `GetNotificationsAction` Is Dead Code 🟢

**File:** `app/Domain/Admin/Actions/GetNotificationsAction.php`

This Action exists but is never called by any component or controller. `NotificationCenter` uses `BaseRecordManager::query()` directly. `NotificationBell` uses inline `Notification::where(...)`.

**Fix:** Remove dead code or integrate into NotificationCenter.

*Status: ⏳ Pending — Priority P5.*

---

### N11. Unused Email Template `notification.blade.php` 🟢

**File:** `resources/views/emails/notification.blade.php`

This standalone HTML email template is not referenced by any notification class. All notifications use Laravel's built-in `MailMessage` rendering instead.

**Fix:** Remove the unused template.

*Status: ⏳ Pending — Priority P5.*

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
- ✅ `SystemSetting` → `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm`

**Remaining priority:**

| Priority | Domain | Form | Components Affected |
|---|---|---|---|
| 🟠 P2 | Registration | `RegistrationForm`, `DocumentUploadForm` | `RegistrationWizard`, `RegistrationDocumentUpload` |
| 🟠 P3 | User | `ProfileForm` | `ProfileEditor` |
| 🟡 P4 | Admin | `UserForm`, `AnnouncementForm` | `UserManager`, `CreateAdminCommand` |
| 🟡 P5 | School | `AcademicYearForm`, `DepartmentForm` | `AcademicYearIndex`, `DepartmentManager` |
| 🟢 P6 | Settings | `GeneralSettingsForm`, `BrandingForm`, `MailSettingsForm` | `SystemSetting` ✅ |
| 🟢 P6 | All remaining forms | — | ~20 components |

**Convention:** See `docs/conventions.md` Section 9a — Form Objects for the required pattern.

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|
| 🔴 | Feature tests missing for 147 of 151 Actions | Testing | ⏳ |
| 🔴 | Indonesian `internship.php` missing 110 keys | Translation | ⏳ |
| 🔴 | **N1** IncidentReportedNotification uses wrong channel (Laravel `database`) | Notifications | ⏳ |
| 🔴 | **N2** No notification cleanup / pruning mechanism | Notifications | ⏳ |
| 🟡 | **N3** Notification Center should be in User domain (not Admin) | Notifications | ⏳ |
| 🟢 | **N4** `markSelectedAsRead()` bypasses Action pattern | Notifications | ✅ Fixed |
| 🟡 | **N5** CustomDatabaseChannel no guard for empty user ID | Notifications | ⏳ |
| 🟢 | **N6** `DeleteNotificationAction` PHPDoc misleading about security | Notifications | ⏳ |
| 🟢 | **N7** ActivityFeedManager queries inline instead of Action | Notifications | ⏳ |
| 🟢 | **N8** CustomDatabaseChannel no validation on `toCustomDatabase()` return | Notifications | ⏳ |
| 🟢 | **N9** Zero test coverage for notification components | Notifications | ⏳ |
| 🟢 | **N10** `GetNotificationsAction` is dead code | Notifications | ⏳ |
| 🟢 | **N11** Unused email template `notification.blade.php` | Notifications | ⏳ |
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
