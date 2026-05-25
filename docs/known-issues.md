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



## Backlog — Unresolved Items

### Feature Test Coverage (139 uncovered Actions)

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

### Cross-Domain Event Flow Documentation 🟢

Which events fire and which listeners react is not documented. Needed for understanding side effects when modifying Actions.

### Real-Time Features (Future) 🟢

Laravel Echo and Reverb are installed but no real-time channels are active. Candidates: notification delivery, dashboard updates, attendance confirmations.

### Queue Job Formalization (Future) 🟢

Evaluate which operations should be queued: certificate generation, report rendering, batch notifications. Currently all notifications use `ShouldQueue`.

### Livewire Form Object Migration 🟡

**Problem:** 81 Livewire components still manage form state via flat `public` properties scattered across the component class.

**Completed:**
- ✅ `SetupWizard`, `ProfileEditor`, `Login`, `SystemSetting`
- ✅ Admin user managers (7 forms)
- ✅ `AnnouncementManager`, `AcademicYearManager`, `DepartmentManager`

**Remaining priority:**
- 🟠 P2: Registration (`RegistrationWizard`, `RegistrationDocumentUpload`)
- 🟡 P4: ~60 remaining components

**Convention:** See `docs/conventions.md` Section 9a — Form Objects.

---

## Internship Management — Audit Findings

### IM1–IM6, IM8. Internship Management Fixes 🟡 *(✅ Fixed)*

- **IM1:** `InternshipManager::boot()` — `abort(403)` → `$this->authorize('viewAny', Internship::class)`
- **IM2:** Created 4 Form Objects (`InternshipForm`, `InternshipGroupForm`, `InternshipPhaseForm`, `InternshipRequirementForm`), migrated all managers + views
- **IM3:** Route Model Binding → `string $id` in all 4 managers (Internship, Group, Phase, Requirement)
- **IM4:** Added `boot()` authorization to `RequirementManager`
- **IM5:** Added `$this->authorize('delete')` to `GroupManager::confirmAction()`, fixed `InternshipGroupPolicy` delete scope to `isAdmin()`
- **IM6:** Created translation keys for all hardcoded English flash messages (group, phase, requirement)
- **IM8:** `InternshipGroupPolicy::delete()` — `super_admin` only → `isAdmin()`

### IM7. Zero Livewire Feature Tests for All Internship Managers 🔴

**Directory:** `tests/Feature/Internship/` (does not exist)

Zero tests cover `InternshipManager`, `InternshipGroupManager`, `InternshipPhaseManager`, or `RequirementManager`. Mounting, CRUD operations, modals, validation, authorization, import/export are untested.

**Impact:** 🔴 Refactoring carries high regression risk.

**Fix:** Add feature tests for each manager covering render, authorization, create, edit, delete, validation, search.

*Status: ⏳ Pending — Priority P1.*

---

## Guidance Domain — Audit Findings

### GD2. No `UpdateHandbookAction` or `DeleteHandbookAction` 🟡

**Files:** `app/Domain/Guidance/Actions/`

Admin can create handbooks (`CreateHandbookAction`) but there is no action to update or delete them. To fix content or deactivate a handbook, one must go directly to the database.

**Fix:** Create `UpdateHandbookAction` and wire into admin Livewire component.

*Status: ⏳ Pending — Priority P4.*

---

### GD3. `HandbookIndex` Uses Flat Form State Instead of Form Object 🟡

**File:** `app/Domain/Admin/Livewire/HandbookIndex.php`

Uses public properties `$title`, `$content`, `$version` with inline validation instead of a dedicated `HandbookForm` Form Object. Same pattern as previously fixed in Admin and Internship managers.

**Fix:** Extract `HandbookForm` Form Object.

*Status: ⏳ Pending — Priority P4.*

---

### GD4. `HandbookIndex` Uses `Gate::authorize()` Instead of `$this->authorize()` 🟡

**File:** `app/Domain/Admin/Livewire/HandbookIndex.php:37,64`

Uses `Gate::authorize('create', Handbook::class)` and `Gate::authorize('viewAny', Handbook::class)` instead of `$this->authorize()`. Inconsistent with the rest of the codebase.

**Fix:** Replace with `$this->authorize()`.

*Status: ⏳ Pending — Priority P4.*

---

### GD5. No `boot()` Authorization in Both Livewire Components 🟡

**Files:** `HandbookIndex.php`, `StudentHandbookIndex.php`

Neither component has a `boot()` method. `HandbookIndex` puts authorization inline in `render()`. `StudentHandbookIndex` has no authorization at all — relies entirely on route middleware.

**Fix:** Add `boot()` with `$this->authorize()`.

*Status: ⏳ Pending — Priority P4.*

---

### GD6. Route Model Binding in `acknowledge()` (Both Components) 🟡

**Files:** `HandbookIndex.php:56`, `StudentHandbookIndex.php:14`

Both use `acknowledge(Handbook $handbook, AcknowledgeHandbookAction $action)` instead of `acknowledge(string $id, ...)`. Same issue as previously fixed in all other domains.

**Fix:** Change signatures to `acknowledge(string $id, ...)` with `findOrFail()`.

*Status: ⏳ Pending — Priority P4.*

---

### GD7. No `target_audience` Concept 🔴

**Model:** `app/Domain/Guidance/Models/Handbook.php`

Handbooks have no target audience field. All handbooks are shown to all users regardless of role (student, teacher, supervisor). Users need role-specific handbooks — e.g., handbooks for teachers/supervisors should not appear in student lists.

**Impact:** 🔴 Wrong handbooks shown to wrong users. No way to have role-specific guidance materials.

**Fix:** Add `target_audience` field to Handbook model, filter by user role in queries.

*Status: ⏳ Pending — Priority P1.*

---

### GD8. No Gate / Acknowledgement as Requirement 🟢

Handbook acknowledgement is purely informational — it does not block any action. Registration, attendance clock-in, logbook submission all work without having acknowledged any handbook.

**Fix:** Add configurable gating logic (e.g., must acknowledge specific handbooks before registration).

*Status: ⏳ Pending — Priority P4.*

---

### GD9. Hardcoded English Flash Messages 🟢

**Files:** `HandbookIndex.php:53,59`

Uses `'Handbook created successfully.'` and `'Handbook acknowledged.'` instead of translation keys like `__('handbook.created')`.

**Fix:** Replace with `__()` translation keys.

*Status: ⏳ Pending — Priority P4.*

---

### GD10. Zero Livewire Feature Tests 🔴

**Directory:** `tests/Feature/Guidance/` (exists, but only action tests)

`GuidanceActionsTest.php` covers `CreateHandbookAction` (2 tests) and `AcknowledgeHandbookAction` (1 test). Zero tests for `HandbookIndex` or `StudentHandbookIndex` — mounting, form submission, acknowledgement flow, authorization.

**Impact:** 🔴 Refactoring carries high regression risk.

**Fix:** Add feature tests for both Livewire components.

*Status: ⏳ Pending — Priority P1.*

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|
| 🔴 | Feature tests missing for ~110 of 143 Actions | Testing | ⏳ |
| 🔴 | Indonesian `internship.php` missing 110 keys | Translation | ⏳ |
| 🔴 | **GD7** target_audience field added | Guidance | ✅ Fixed |
| 🔴 | **GD10** Livewire tests for Guidance components | Guidance | ✅ Fixed |
| 🟡 | **GD1** Documentation vs implementation gap | Guidance | ✅ Fixed |
| 🟢 | **GD8** Acknowledgement not used as gate | Guidance | ⏳ |
| 🟢 | **GD9** Hardcoded English flash messages | Guidance | ✅ Fixed |
| 🟡 | **IM1** InternshipManager uses abort(403) instead of authorize | Internship | ✅ Fixed |
| 🟡 | **IM2** Flat formData arrays instead of Form Objects (4 components) | Internship | ✅ Fixed |
| 🟡 | **IM3** Route Model Binding in edit() (4 components) | Internship | ✅ Fixed |
| 🟡 | **IM4** RequirementManager had no boot() authorization | Internship | ✅ Fixed |
| 🟡 | **IM5** InternshipGroupManager confirmAction no auth guard | Internship | ✅ Fixed |
| 🟡 | **IM8** InternshipGroupPolicy delete restricted to super_admin | Internship | ✅ Fixed |
| 🟢 | **IM6** Hardcoded English flash messages (4 components) | Internship | ✅ Fixed |
| 🟢 | Cross-domain event flow undocumented | Documentation | ⏳ |
| 🟢 | Real-time features (Echo + Reverb) not yet active | Future | ⏳ |
| 🟢 | Queue job formalization not evaluated | Future | ⏳ |
| 🟡 | Livewire Form Object migration (~60 components remaining) | Architecture | ⏳ |
| 🟡 | BaseAction cannot enforce execute() — signatures vary | Architecture | ⏸️ |
