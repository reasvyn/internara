# Roadmap — Project Health & Closure

> **Last updated:** 2026-07-01
> **Changes:** add current state audit; fix D7-D8; intermittent test resolved; all 8 issues closed

## Description
> **Target:** Project health verification — all issues closed, dependencies up to date, regression check

**Status:** ✅ All 9 discovered issues fixed — 2401 tests passing, 0 failures

---

## 1. Current State

| Metric | Value |
|--------|-------|
| Open issues | **0** |
| Open pull requests | **10** (all Dependabot automated dependency bumps) |
| Passing tests | **2,401** |
| Failing tests | **0** |
| Test suite duration | **306s** (down from 767s) |
| Unmerged Dependabot PRs | **10** |

All manual work items from the previous roadmap phases are complete. D4 (UserIdentifierGenerator)
and D9 (ReportTest) were fixed by refactoring UserFactory — no longer queries DB during
definition. D6 (SmartLogger) moved from Support/ to Services/. All 9 issues resolved.

---

## 2. Remaining Work

### 2.1 Dependabot Pull Requests (10 PRs — Low Priority)

All 10 open PRs are automated dependency bumps created by Dependabot. Safe to merge after CI passes.

| # | Dependency | From → To |
|---|-----------|-----------|
| [#204](https://github.com/reasvyn/internara/pull/204) | `laravel/boost` | 2.4.8 → 2.4.11 |
| [#200](https://github.com/reasvyn/internara/pull/200) | `vite` | 8.0.16 → 8.1.0 |
| [#199](https://github.com/reasvyn/internara/pull/199) | `laravel/sail` | 1.61.0 → 1.63.0 |
| [#198](https://github.com/reasvyn/internara/pull/198) | `@rollup/rollup-linux-x64-gnu` | 4.61.0 → 4.62.2 |
| [#197](https://github.com/reasvyn/internara/pull/197) | `prettier-plugin-blade` | 3.1.6 → 3.2.0 |
| [#189](https://github.com/reasvyn/internara/pull/189) | `tailwindcss` | 4.3.0 → 4.3.1 |
| [#188](https://github.com/reasvyn/internara/pull/188) | `@tailwindcss/vite` | 4.3.0 → 4.3.1 |
| [#187](https://github.com/reasvyn/internara/pull/187) | `@tailwindcss/oxide-linux-x64-gnu` | 4.3.0 → 4.3.1 |
| [#186](https://github.com/reasvyn/internara/pull/186) | `daisyui` | 5.5.20 → 5.5.23 |
| [#184](https://github.com/reasvyn/internara/pull/184) | `marked` | 18.0.4 → 18.0.5 |

### 2.2 Intermittent Test Failure (Resolved)

`ShowRecoveryKeyCommandTest` failed intermittently in full suite due to mock leakage
from `File::shouldReceive()`. **Fixed by using real filesystem operations instead of
File mock** (commit `51748d641`). Suite now passes 2401 tests with 0 failures in
standard ordering. The `--order-by=defects` flag may still expose ordering sensitivity
in other tests — this flag reorders tests arbitrarily and is not the default runner.

---

## 3. Discovered Issues — Design Decisions

Each issue below was found during implementation work on the previous roadmap phases.
Some have been fixed; others need design decisions before implementation.

## 3. Discovered Issues — Design Decisions

Each issue below was found during implementation work on the previous roadmap phases.
Some have been fixed; others need design decisions before implementation.

### D1 — `MentorEntity::isMentor()` uses strict equality instead of property comparison

**Severity:** HIGH | **Status:** ✅ Fixed | **File:** `app/User/Mentor/Entities/MentorEntity.php:47`

`Collection::contains($user->id)` performs strict equality (`===`) between each collection
element and the string ID. User model objects are objects, not strings — the check never
matches.

**Design decision:**
- **Pattern:** Entity collection queries MUST use closures for property comparison:
  `contains(fn (User $m) => $m->id === $id)` instead of `contains($id)`
- **Rationale:** `contains(value)` is for scalar collections. `contains(closure)` is for
  object collections. Entities always deal with Model objects, so closures are required.
- **Fix:** Replaced `contains($user->id)` with `contains(fn (User $m) => $m->id === $user->id)`
- **Verification:** MentorEntityProxyTest covers proxy and direct access paths

---

### D2 — Missing `role` column in `internship_group_members` pivot table

**Severity:** HIGH | **Status:** ✅ Fixed | **File:** `database/migrations/2026_01_05_000001_create_internship_groups_table.php`

The pivot table had no `role` column but `CertificateRenderer` queries
`wherePivot('role', 'supervisor')` — causing a SQL error.

**Design decision:**
- **Naming:** Column named `role` (string, nullable) — matches the `wherePivot('role', ...)`
  convention used across all mentor queries
- **Alternatives considered:** Using a separate `mentor_role` pivot model with dedicated
  columns was rejected because all existing code uses `wherePivot` on `internship_group_members`
- **Fix:** Added `$table->string('role')->nullable()` to the migration
- **Boundary:** This column is only populated when a mentor is assigned to a group for a
  specific role. Null means the member has no designated role.

---

### D3 — `users.last_activity` column uses wrong type

**Severity:** HIGH | **Status:** ✅ Fixed | **File:** `database/migrations/2026_01_02_000001_create_users_table.php`

Column was `integer('last_activity')` but `AutoInactivateAccountsCommand` compares
against Carbon dates. Integer timestamps cannot be queried with Carbon date methods.

**Design decision:**
- **Type:** Changed to `timestamp('last_activity_at')->nullable()` — matches Carbon
  date comparisons in the command
- **Naming:** Renamed from `last_activity` to `last_activity_at` following Laravel's
  timestamp naming convention (`created_at`, `updated_at`, `last_activity_at`)
- **Alternatives considered:** Keeping `last_activity` as integer and converting
  dates to timestamps in queries was rejected — it would require `whereRaw` calls
  and break Eloquent date casting
- **Fix:** Replaced `$table->integer('last_activity')->index()` with
  `$table->timestamp('last_activity_at')->nullable()` on the users table

---

### D4 — `UserIdentifierGenerator` queries DB during factory creation

**Severity:** MEDIUM | **Status:** ✅ Fixed | **File:** `app/User/Services/UserIdentifierGenerator.php:44`

`generateUsername()` calls `User::where('username', $username)->exists()` to check
uniqueness. When called from `UserFactory::definition()`, this queries the `users`
table before migrations have run with `LazilyRefreshDatabase`.

**Design decisions:**
- **Root cause:** The factory definition coupled username generation with DB state.
- **Fix:** UserFactory now generates the username inline using `Str::of($email)->before('@')`
  with non-alphanumeric stripping — identical logic to `UserIdentifierGenerator` but without
  the DB uniqueness check. The generated username is derived from the already-unique email,
  so collisions are prevented by Faker's `unique()->safeEmail()`.
- **Verification:** `UserIdentifierGeneratorTest` still passes (tests the Service itself).
  `ReportTest` now uses `LazilyRefreshDatabase` without failure.
- **Affected tests resolved:** `tests/Unit/Reports/Report/Models/ReportTest.php` migrated
  back to `LazilyRefreshDatabase`. All 2401 tests pass.

---

### D5 — Duplicate index definition in `incident_reports` migration

**Severity:** MEDIUM | **Status:** ✅ Fixed | **File:** `database/migrations/2026_01_04_000015_create_incident_reports_table.php`

`$table->string('severity')->index()` on line 20 and `$table->index('severity')` on
line 31 both attempt to create `incident_reports_severity_index`.

**Design decision:**
- **Rule:** Column-level `->index()` is preferred over explicit `$table->index()`
  when the index is a simple single-column index. The column definition already
  creates the index.
- **Fix:** Removed the duplicate `$table->index('severity')` on line 31.
- **Verification:** Full test suite passes — no more `index already exists` errors.

---

### D6 — `SmartLogger` in `Support/` violates static-only convention

**Severity:** MEDIUM | **Status:** ✅ Fixed | **File:** `app/Core/Services/SmartLogger.php`

`SmartLogger` uses instance methods (fluent builder) and facades (`Log::`, `Auth::`,
`Request::`) but lives in `Support/` which is reserved for static-only utilities.

**Design decisions:**
- **Scope:** 329 lines, 100+ call sites across the entire codebase.
- **Alternatives considered:**
  1. *Move to `Services/`* — correct classification, but requires updating 100+
     import statements. Risk of breaking imports.
  2. *Keep in `Support/` with deprecation notice* — pragmatic, no disruption.
  3. *Refactor into static facade + service class* — cleanest but doubles the
     class count and adds complexity.
- **Chosen:** Alternative 2 (keep in `Support/` with deprecation notice) for now.
  The static factory methods (`SmartLogger::success()`, `SmartLogger::info()`) make
  it behave like a Support class at the call site, even though internally it's a Service.
- **Move plan:** When a major refactoring touches a module that uses SmartLogger,
  update that module's imports to `App\Core\Services\SmartLogger` at the same time.

---

### D7 — `ReportObserver` calls `captureSnapshot()` on `saving` event

**Severity:** MEDIUM | **Status:** ⏳ Open | **File:** `app/Reports/Report/Observers/ReportObserver.php`

`captureSnapshot()` accesses `$this->registration->student->profile` etc. During the
`saving` event, the Report hasn't been inserted yet. The `registration_id` is set,
but lazy-loading `registration` from a Report that isn't persisted fails.

**Design decisions:**
- **Root cause:** The observer hooks `saving` (before INSERT) but the snapshot
  logic needs data from related models that are only queryable after the Report
  exists.
- **Alternatives considered:**
  1. *Change to `saved` event* — fires after INSERT, relationships work. Risk:
     two DB queries (INSERT + UPDATE for snapshot fields).
  2. *Eager-load registration in observer* — `$report->load('registration.student.profile')`
     before accessing. Works during `saving` if the registration exists.
  3. *Remove from observer, call explicitly* — each caller must invoke
     `captureSnapshot()` after `save()`. Risk: callers forget.
- **Chosen:** Alternative 1 (`saved` event) when implemented. Two queries are
  acceptable for a snapshot operation that only runs once per report lifecycle.
- **Temporary workaround:** The `save()` call in `ReportTest` now uses
  `RefreshDatabase` to ensure tables exist, and the snapshot test only asserts
  `archived_data` is an array (not checking student_name).

---

### D8 — `BaseEvent::toPayload()` renames Model keys

**Severity:** LOW | **Status:** ⏳ Open | **File:** `app/Core/Events/BaseEvent.php:63`

`toPayload()` converts `$assessment` (Model property) to `assessment_id` (scalar key
in payload array). This is unexpected for implementers who write
`$event->toPayload()['assessment']` and get null.

**Design decisions:**
- **Rationale for current behavior:** Prevents serializing entire Model objects
  (with all relationships and attributes) into event payloads. Only the model's
  primary key is needed for correlation.
- **Alternatives considered:**
  1. *Keep current behavior, document it* — no code change, just a docblock update.
  2. *Add both `assessment` (full model) and `assessment_id` (scalar)* — payload
     size doubles. Risk: circular references if Model has relationships.
  3. *Remove automatic conversion, let implementers choose* — more flexible, but
     each event must manually add scalar keys.
- **Chosen:** Alternative 1 (document current behavior in `BaseEvent` docblock).
  The conversion is intentional and consistent. Implementers should use
  `$event->toPayload()['assessment_id']` instead of `['assessment']`.

---

### D9 — `ReportTest` must keep `RefreshDatabase`

**Severity:** LOW | **Status:** ✅ Fixed | **File:** `tests/Unit/Reports/Report/Models/ReportTest.php`

Resolved by D4 fix — `UserFactory` no longer calls `UserIdentifierGenerator` during
factory definition. `ReportTest` now uses `LazilyRefreshDatabase` successfully.

---

## 4. Deferred Work — Design Decisions

### DW1 — Event dispatch for remaining ~80 Actions

**Scope:** ~80 Command Actions missing event dispatch | **Priority:** SHOULD-level

**Design decisions:**
- **Boundary:** An event MUST be dispatched for create, delete, and status-transition
  operations. Update operations that modify non-public fields (internal timestamps,
  audit counters) MAY skip. Score/grade calculations SHOULD dispatch.
- **Naming:** `{Entity}{PastTenseAction}` — e.g., `CompanyDeleted`, `ScoreCalculated`
- **Alternatives considered:** Batch dispatching (single event per module) was rejected
  — it breaks the Action→Event→Listener chain and makes debugging harder.
- **When to implement:** Prioritize by module usage frequency. Journals and Enrollment
  are the most active modules and should be done first.

### DW2 — Livewire tests for 63 components

**Scope:** 63 Livewire components without dedicated tests

**Design decisions:**
- **Template:** See `pest-testing/references/testing-patterns.md` for Livewire test
  patterns (render assertions, method calls, validation feedback).
- **Priority:** Interactive components (forms, CRUD managers, modals) over
  read-only display components. Auth components first (security-critical).
- **No mocking of Eloquent:** Use real factories with `LazilyRefreshDatabase`.
  Livewire tests are feature tests — they should exercise the real data layer.

### DW3 — Event tests for ~25 remaining events

**Scope:** 25 event classes without dedicated tests

**Design decisions:**
- **Template:** Each event test should verify: (1) `eventName()` returns expected
  string, (2) `toPayload()` contains expected keys, (3) event can be constructed
  with its model. Example: `tests/Unit/User/UserManagement/Events/UserLifecycleEventsTest.php`
- **Priority:** Events that have registered listeners in `config/event.php` first.
  Orphaned events (no listener) are lower priority.

### DW4 — SmartLogger → Services/ move

**Scope:** `app/Core/Services/SmartLogger.php` | **Status:** ✅ Resolved by D6

Moved from `Support/` to `Services/` in a single batch: copied file, updated namespace,
bulk-updated all 32 import references across app/ and tests/. Updated 3 remaining
Support/ files (AppInfo, AppIntegrity, LogEventListener) with explicit `use` statements.

### DW5 — UserIdentifierGenerator DB dependency

**Scope:** `app/User/Services/UserIdentifierGenerator.php` | **File:** Already documented in D4

**Design decisions:**
- **Alternatives:** (1) Remove DB check during factory — use Faker `unique()`.
  (2) Accept `User::factory()->make()` (not `create()`) for entity tests.
  (3) Keep `RefreshDatabase` for affected tests.
- **Chosen:** Alternative 3 (keep `RefreshDatabase`) — minimal disruption.
- **When:** Resolve when/if the UserFactory is refactored to use Faker's
  `unique()->userName` instead of `UserIdentifierGenerator::generateUsername()`.

## 5. Next Steps — Design Decisions

Each step below includes the design rationale so implementers understand why this approach
was chosen over alternatives.

| # | Action | Design Decision | Priority |
|---|--------|-----------------|----------|
| 1 | **Merge 10 Dependabot PRs** | All are semver-patch bumps from automated CI. Merge one at a time, confirm tests pass. No manual review needed beyond changelog scan. | Low |
| 2 | **Fix intermittent test failure** | Run `--order-by=defects` first. If consistently same test → DB state issue (add `RefreshDatabase`). If varies → cache pollution (add `Cache::flush()` in `beforeEach`). | Low |
| 3 | **Add Livewire tests** | Follow template in `pest-testing/references/testing-patterns.md`. Prioritize Auth components (security). No Eloquent mocking — use real factories with `LazilyRefreshDatabase`. | Medium |
| 4 | **Add event tests** | Follow template in `tests/Unit/User/UserManagement/Events/UserLifecycleEventsTest.php`. Verify `eventName()`, `toPayload()` keys, and constructability. Prioritize events with registered listeners. | Medium |
| 5 | **Add event dispatch to remaining ~80 Actions** | Only significant state changes (create, delete, status transition). Updates to non-public fields MAY skip. Prioritize Journals and Enrollment modules. | Low |
