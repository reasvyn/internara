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

### Feature Test Coverage (~117 uncovered Actions)

| Domain | Actions | Feature Tests | Gap |
|---|---|---|---|---|
| Assessment | 17 | 0 | 🔴 |
| Internship | 16 | 7 | 🟡 |
| Auth | 12 | 0 | 🔴 |
| Admin | 9 | 9 | 🟢 ✅ |
| Attendance | 8 | 0 | 🔴 |
| Partnership | 8 | 8 | 🟢 ✅ |
| Mentor | 8 | 0 | 🔴 |
| Placement | 7 | 7 | 🟢 ✅ |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Registration | 5 | 5 | 🟢 ✅ |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Guidance | 2 | 2 | 🟢 ✅ |
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

**Problem:** Livewire components still manage form state via flat `public` properties scattered across the component class.

**Completed:**
- ✅ `SetupWizard`, `ProfileEditor`, `Login`, `SystemSetting`
- ✅ Admin user managers (7 forms)
- ✅ `AnnouncementManager`, `AcademicYearManager`, `DepartmentManager`
- ✅ Internship managers (4 forms: `InternshipForm`, `InternshipGroupForm`, `InternshipPhaseForm`, `InternshipRequirementForm`)
- ✅ Guidance `HandbookForm`
- ✅ Registration Forms: `AccountApplicationForm`, `RegistrationWizardForm`

**Remaining priority:**
- 🟠 P2: Placement (`PlacementIndex`, `DirectPlacementManager`, `StudentPlacementChangeRequest`)
- 🟡 P4: ~50 remaining components

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

### IM7. Zero Livewire Feature Tests for All Internship Managers 🟢 *(✅ Fixed)*

**Directory:** `tests/Feature/Internship/`

Created `InternshipManagersTest.php` with 16 tests covering `InternshipGroupManager`, `InternshipPhaseManager`, and `RequirementManager` (render, CRUD, validation, member management).

*Status: ✅ Fixed — Priority P1.*

---

## Guidance Domain — Audit Findings

### GD2–GD7, GD9–GD10. Guidance Domain Fixes 🟢 *(✅ Fixed)*

- **GD2:** Created `UpdateHandbookAction` and `DeleteHandbookAction`
- **GD3:** Extracted `HandbookForm` Form Object, migrated `HandbookIndex`
- **GD4:** Replaced `Gate::authorize()` → `$this->authorize()` via `AuthorizesRequests` trait
- **GD5:** Added `boot()` authorization to both `HandbookIndex` and `StudentHandbookIndex`
- **GD6:** Changed `acknowledge(string $id, ...)` signatures (no Route Model Binding)
- **GD7:** Added `target_audience` field to Handbook model + role-based filtering
- **GD9:** Replaced hardcoded English with `__('handbook.*')` translation keys
- **GD10:** Added 11 Livewire tests (14 total Guidance tests)

### GD8. No Gate / Acknowledgement as Requirement 🟢

Handbook acknowledgement is purely informational — it does not block any action. Registration, attendance clock-in, logbook submission all work without having acknowledged any handbook.

**Fix:** Add configurable gating logic (e.g., must acknowledge specific handbooks before registration).

*Status: ⏳ Pending — Priority P4.*

---

## Registration Domain — Audit Findings

### RD1–RD10. Registration Domain Fixes *(See status per item below)*

### RD1. Documentation vs Implementation Mismatch 🔴 *(✅ Fixed)*

- **Fix applied:** Rewrote `registration.md` to match the actual 2-status implementation (`pending` → `active`), accurate flow descriptions, and proper technical reference.

### RD2. Unreachable Livewire Components 🔴 *(✅ Fixed)*

- **Fix applied:** Added routes for `RegistrationCenter` (`/registration`), `RegistrationWizard` (`/register`), and `RegistrationDocumentUpload` (`/registration/documents`) in `routes/web/registration.php`.

### RD3. RegistrationWizard Filters by Wrong Status 🔴 *(✅ Fixed)*

- **Fix applied:** Changed `where('status', 'active')` → `InternshipStatus::PUBLISHED->value`.

### RD4. Authorization Gaps Across All Livewire Components 🟡 *(✅ Fixed)*

- **Fix applied:** Added `boot()` with `$this->authorize()` to `RegistrationCenter`, `RegistrationDocumentUpload`, `RegistrationWizard`, and `RegistrationVerification`. Replaced `abort_if()` with `$this->authorize()` in `RegistrationVerification::process()`. Removed `boot()` from `AccountApplicationForm` (guest-facing — authorization handled by route middleware).

### RD5. No Livewire Feature Tests 🔴 *(✅ Fixed)*

- **Fix applied:** Created `RegistrationLivewireTest.php` with 13 tests covering all 5 Registration components (render, CRUD, step navigation, form submission, document upload, verification/placement flow).

### RD6. Form Object Migration Not Started 🟡 *(✅ Fixed)*

- **Fix applied:** Created `AccountApplicationFormData` and `RegistrationWizardForm` Form Objects. Migrated `AccountApplicationForm` and `RegistrationWizard` to use Form Objects.

### RD7. Translation Keys Use Wrong Prefix 🟡 *(✅ Fixed)*

- **Fix applied:** Created `lang/en/registration.php` with all registration keys under proper `registration.*` prefix. Updated all 5 Livewire components and 2 Blade views. Removed duplicated keys from `lang/en/internship.php`.

### RD8. AccountApplicationStatus Missing StatusEnum Implementation 🟡 *(✅ Fixed)*

- **Fix applied:** Added `StatusEnum` implementation with `isTerminal()`, `validTransitions()`, and `canTransitionTo()` methods. Updated `registration-reference.md`.

### RD9. Hardcoded English Strings 🟡 *(✅ Fixed)*

- **Fix applied:** Replaced hardcoded `'No active or pending registration found.'` and `'Documents uploaded successfully.'` with `__('registration.document_upload.*')` translation keys.

### RD10. RegistrationDocumentFactory Incomplete 🟡 *(✅ Fixed)*

- **Fix applied:** Added `registration_id`, `internship_document_requirement_id`, and `status` fields to the factory definition.

---

## Placement Domain — Audit Findings

### PD1. Documentation vs Implementation Mismatch 🔴

**File:** `docs/domain/placement.md`

The documentation describes an aspirational system with auto-matching algorithm, waitlist management, placement status lifecycle (PENDING, CONFIRMED, IN_CHANGE, CHANGED, CANCELLED), student/company confirmation workflow, placement reporting, and auto-release of pending assignments. The actual implementation has simple slot CRUD, direct placement by admin, and manual change requests — none of the aspirational features exist.

**Impact:** 🔴 Anyone reading the docs will have a fundamentally wrong understanding of the system.

**Fix:** Rewrite `placement.md` to match actual implementation (slot CRUD, direct placement, change requests).

*Status: ⏳ Pending — Priority P1.*

---

### PD2. PlacementIndex Uses Flat formData Array 🔴

**File:** `app/Domain/Placement/Livewire/PlacementIndex.php`

Uses `public array $formData [...]` with inline `$this->validate()` and array key access (`$this->formData['name']`) instead of a dedicated Form Object.

**Fix:** Extract `PlacementForm` Form Object.

*Status: ⏳ Pending — Priority P1.*

---

### PD3. PlacementIndex Uses Route Model Binding 🔴

**File:** `app/Domain/Placement/Livewire/PlacementIndex.php:112,131`

Uses `edit(Placement $placement)` and `delete(Placement $placement, ...)` instead of `edit(string $id)` with `findOrFail()`. Same issue as previously fixed in all other domains (IM3, GD6, UC4, RD4).

**Fix:** Change signatures to `edit(string $id)` and `delete(string $id, ...)` with `findOrFail()`.

*Status: ⏳ Pending — Priority P1.*

---

### PD4. No boot() Authorization in PlacementIndex 🔴

**File:** `app/Domain/Placement/Livewire/PlacementIndex.php`

No `boot()` method with `$this->authorize()`. Relies entirely on route middleware `role:super_admin|admin`.

**Fix:** Add `boot()` with `$this->authorize('viewAny', Placement::class)`.

*Status: ⏳ Pending — Priority P1.*

---

### PD5. DirectPlacementManager Uses Flat Properties + Inline Blade 🔴

**File:** `app/Domain/Placement/Livewire/DirectPlacementManager.php`

Uses 4 flat public properties (`$student_id`, `$placement_id`, `$academic_year`, `$mentor_ids`) with inline validation and inline Blade template. No Form Object.

**Fix:** Extract `DirectPlacementForm` Form Object. Move Blade template to view file.

*Status: ⏳ Pending — Priority P1.*

---

### PD6. No boot() Authorization in DirectPlacementManager 🔴

**File:** `app/Domain/Placement/Livewire/DirectPlacementManager.php`

No `boot()` method with `$this->authorize()`.

**Fix:** Add `boot()` with `$this->authorize('create', Registration::class)`.

*Status: ⏳ Pending — Priority P1.*

---

### PD7. DirectPlacementManager Uses Wrong Translation Prefix 🔴

**File:** `app/Domain/Placement/Livewire/DirectPlacementManager.php`

Uses `__('internship.direct_placement.*')` and `__('internship.registration_wizard.label_academic_year')` instead of `__('placement.direct_placement.*')`.

**Fix:** Move keys to `placement.php` language file, update component references.

*Status: ⏳ Pending — Priority P1.*

---

### PD8. StudentPlacementChangeRequest Uses abort_unless() 🔴

**File:** `app/Domain/Placement/Livewire/StudentPlacementChangeRequest.php:28`

Uses `abort_unless(auth()->user()->hasRole('student'), 403)` instead of `$this->authorize('create', PlacementChangeRequest::class)`.

**Fix:** Replace with `$this->authorize()` via `AuthorizesRequests`.

*Status: ⏳ Pending — Priority P1.*

---

### PD9. StudentPlacementChangeRequest Uses Flat Properties 🔴

**File:** `app/Domain/Placement/Livewire/StudentPlacementChangeRequest.php`

Uses 3 flat public properties (`$registrationId`, `$toPlacementId`, `$reason`) with inline validation.

**Fix:** Extract `PlacementChangeForm` Form Object.

*Status: ⏳ Pending — Priority P1.*

---

### PD10. No boot() Authorization in PlacementChangeManager 🔴

**File:** `app/Domain/Placement/Livewire/PlacementChangeManager.php`

No `boot()` method, despite extending `BaseRecordManager`. Relies entirely on route middleware.

**Fix:** Add `boot()` with `$this->authorize('viewAny', PlacementChangeRequest::class)`.

*Status: ⏳ Pending — Priority P1.*

---

### PD11. PlacementChangeManager Uses Raw Join Queries 🟡

**File:** `app/Domain/Placement/Livewire/PlacementChangeManager.php:37-50`

The `query()` method uses raw `DB::join()` and `DB::raw()` with `select()` to join 5 tables instead of using Eloquent relationships and `with()`.

**Fix:** Refactor to use Eloquent relationships: `PlacementChangeRequest::with(['registration.mentee.user', 'fromPlacement.company', 'toPlacement.company'])`.

*Status: ⏳ Pending — Priority P2.*

---

### PD12. PlacementChangeRequestFactory Incomplete 🟡

**File:** `database/factories/PlacementChangeRequestFactory.php`

Only defines `reason`, missing required FK fields (`registration_id`, `from_placement_id`, `requested_by`). Using the factory without explicit overrides would fail.

**Fix:** Add FK fields to factory definition.

*Status: ⏳ Pending — Priority P2.*

---

### PD13. Duplicate Routes (Registration Wizard + Document Upload) 🟡

**Files:** `routes/web/registration.php`, `routes/web/mentee.php`

`RegistrationWizard` is registered at both `/register` (name: `registration.wizard`) AND `/student/internships/register` (name: `student.internships.register`). Same for `RegistrationDocumentUpload` at `/registration/documents` AND `/student/documents`. These are leftover from the Registration domain fix (RD2) which added routes that already existed in mentee.php.

**Fix:** Remove duplicate route registrations from `routes/web/registration.php` or `routes/web/mentee.php` and ensure only one canonical route exists.

*Status: ⏳ Pending — Priority P2.*

---

### PD14. Unsorted Translations in placement.php Between en and id 🟡

**Files:** `lang/en/placement.php`, `lang/id/placement.php`

Translation keys have different ordering between English and Indonesian files (`add_placement` vs `add`), as noted in the Translation Gaps section.

**Fix:** Normalize key order across both language files.

*Status: ⏳ Pending — Priority P2.*

---

### PD15. No Livewire Feature Tests 🔴

**Directory:** `tests/Feature/Placement/`

Zero Livewire tests exist for any of the 4 Placement components. `PlacementActionsTest.php` covers 7 Action classes (unit-level), but mounting, CRUD, form submission, and change request flows are completely untested.

**Impact:** 🔴 Any refactoring carries high regression risk.

**Fix:** Add feature tests for each component covering render, authorization, form submission, and processing flows.

*Status: ⏳ Pending — Priority P1.*

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|
| 🔴 | **PD1** Documentation vs implementation mismatch | Placement | ⏳ |
| 🔴 | **PD2** PlacementIndex flat formData array | Placement | ⏳ |
| 🔴 | **PD3** PlacementIndex Route Model Binding | Placement | ⏳ |
| 🔴 | **PD4** No boot() auth in PlacementIndex | Placement | ⏳ |
| 🔴 | **PD5** DirectPlacementManager flat props + inline Blade | Placement | ⏳ |
| 🔴 | **PD6** No boot() auth in DirectPlacementManager | Placement | ⏳ |
| 🔴 | **PD7** Wrong translation prefix (internship.*) | Placement | ⏳ |
| 🔴 | **PD8** StudentPlacementChangeRequest abort_unless() | Placement | ⏳ |
| 🔴 | **PD9** StudentPlacementChangeRequest flat props | Placement | ⏳ |
| 🔴 | **PD10** No boot() auth in PlacementChangeManager | Placement | ⏳ |
| 🔴 | **PD15** Zero Livewire feature tests | Placement | ⏳ |
| 🟡 | **PD11** PlacementChangeManager raw join queries | Placement | ⏳ |
| 🟡 | **PD12** PlacementChangeRequestFactory incomplete | Placement | ⏳ |
| 🟡 | **PD13** Duplicate routes (Reg Wizard + Doc Upload) | Routing | ⏳ |
| 🟡 | **PD14** Unsorted placement.php translation keys | Translation | ⏳ |
| 🔴 | Feature tests missing for ~75 of 143 Actions | Testing | ⏳ |
| 🔴 | Indonesian `internship.php` missing 110 keys | Translation | ⏳ |
| 🟢 | **GD8** Acknowledgement not used as gate | Guidance | ⏳ |
| 🟢 | Cross-domain event flow undocumented | Documentation | ⏳ |
| 🟢 | Real-time features (Echo + Reverb) not yet active | Future | ⏳ |
| 🟢 | Queue job formalization not evaluated | Future | ⏳ |
| 🟡 | Livewire Form Object migration (~50 components remaining) | Architecture | ⏳ |
| 🟡 | BaseAction cannot enforce execute() — signatures vary | Architecture | ⏸️ |
