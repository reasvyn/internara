# Known Issues and Gotchas

## SQLite vs MySQL Differences

The application defaults to SQLite in development, but production usually
runs MySQL or PostgreSQL. This difference causes several gotchas.

SQLite requires an explicit `PRAGMA foreign_keys = ON` to enforce foreign key
constraints. Without this, orphaned records can accumulate silently. The
database configuration enables this by default, but custom raw SQL queries
must set the pragma manually.

SQLite has limited `ALTER TABLE` support. Most schema changes require
recreating the table. This means migration order matters more — adding a
column to a table that another migration just modified may fail. Check
`Schema::hasColumn()` before adding columns that might already exist.

SQLite does not support `ENUM` types. Enum columns in MySQL are represented
as `TEXT` columns with `CHECK` constraints in SQLite. The migration syntax
differs, and the `check()` method must be used when adding enum-like columns.

SQLite writes lock the entire database file. Under concurrent write load,
"database is locked" errors will occur. This is expected behavior — the
solution is to use MySQL or PostgreSQL in production.

## UUID Considerations

UUID primary keys are larger than integer keys (16 bytes vs 4-8 bytes). This
means indexes are larger and joins are slightly slower. At the expected data
volumes this is not a problem, but it is worth noting for tables that will
grow very large.

UUIDs make database dumps and manual queries less convenient — you cannot
guess a record's ID or iterate through them sequentially. All queries should
use meaningful criteria (email, name, date) rather than relying on ID
ordering.

## Queue Worker Requirement

The queue worker is not optional. Without it, notifications are never sent,
media conversions never happen, mail never goes out, and scheduled tasks
accumulate. In development, the queue can run synchronously (via the `sync`
driver) or by running `php artisan queue:work` in a terminal. In production,
Supervisor or systemd must keep the worker running.

If jobs appear stuck in the "processing" state, the worker likely crashed.
Run the prune-failed command to reset them. If jobs are never picked up,
check that the queue connection in `.env` matches the worker's connection.

## Storage Permissions

The `storage/` and `bootstrap/cache/` directories must be writable by the web
server user. This includes subdirectories for logs, framework files, views,
and cache. On Linux, this typically means `chown -R www-data:www-data storage
bootstrap/cache`. Without correct permissions, the application returns blank
pages or file upload errors.

SELinux on RHEL-based distributions adds another layer of permissions. The
storage directory needs the `httpd_sys_rw_content_t` context label.

The public storage symlink (`public/storage` -> `storage/app/public`) must
exist for uploaded files and brand assets to be accessible. This is created
by `php artisan storage:link`. If media URLs return 404, the symlink is
likely missing.

## Development Workflow Gotchas

If you see "Unable to locate file in Vite manifest," the frontend assets have
not been built. Run `npm run build` or `npm run dev` (or `composer run dev`
which starts everything).

If configuration changes do not take effect, run `php artisan optimize:clear`
to flush cached config, routes, and views. The config cache must be
regenerated after any change to `config/*.php` files.

If Livewire components do not update after data changes, check that the
component has reactive properties and that `$this->dispatch()` is being used
for inter-component communication.

## Empty Exception Handling

`bootstrap/app.php:39` has an empty exception handler:
```php
->withExceptions(function (Exceptions $exceptions) {
    //
})
```

No custom rendering, reporting, or error page customization is configured.
This means:
- HTTP error pages use Laravel's stock `errors::minimal` layout (no branding)
- Exception reporting goes only to the default log channel
- No Slack/Discord/email notifications for critical errors
- No `dontReport()` or `dontFlash()` customization

**Fix:** Configure exception handling before production deployment.

## Translation Gaps — Indonesian (id)

The `lang/id/` directory is missing translations compared to `lang/en/`:

| File | en Keys | id Keys | Gap |
|---|---|---|---|
| `internship.php` | 184 | 74 | **110 keys missing** (registration center, wizard, verification, direct placement, applications — entire sections) |
| `logbook.php` | 28 | **FILE MISSING** | Entire file absent |

Additionally, `user.php` has different key ordering/structure between en and id,
and `placement.php` uses different key names (`add_placement` vs `add`).

All Indonesian text that falls through missing keys renders in English (Laravel
fallback behavior). This affects the admin panel and student-facing features.

## Error Pages Without Branding

All 8 HTTP error pages (`401`, `402`, `403`, `404`, `419`, `429`, `500`, `503`)
use the stock Laravel `errors::minimal` layout with hardcoded CSS. They do not
use the application's `x-layouts::base` layout, so error pages have no theme
support, no navigation, and no brand styling.

The `__()` helper calls in error pages (`__('Unauthorized')`, `__('Not Found')`,
etc.) have no corresponding translation keys in any language file. These strings
render as-is (English only).

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

`config/mcp.php` has `'redirect_domains' => ['*']` which allows any redirect URI.
For OAuth security, this should be restricted to known application domains.

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

`storage/framework/testing/` contains leftover test sessions and disk directories.
These are generated by `LazilyRefreshDatabase` and should not be committed or
deployed. Ensure `.gitignore` covers these paths.

## Pest Plugin Arch — `toUse()` constraint bug

`pestphp/pest-plugin-arch` has a known issue where the `toUse()` / `not->toUse()`
constraint can cause internal assertion failures on certain PHP/Pest combinations,
resulting in a silent exit code 2 with no visible error message. This is a bug
in the pest arch plugin's reflection-based import scanner, not in the application
code.

**Impact:** Arch tests that use `toUse()` (like `DomainBoundariesArchTest.php`)
may report false failures or fail to produce output. The constraints themselves
are correct — the plugin's assertion runner sometimes misbehaves.

**Mitigation:**
- Run affected arch tests in isolation to verify the intent is correct
- Inspect the source code directly to confirm no unwanted imports exist
- Skip these particular arch tests if the plugin continues to malfunction,
  provided manual verification has been done

This does not create a security gap — the arch tests are structural safeguards,
not runtime protections. The actual dependency graph is enforced at the code
level through namespace conventions and code review.

## Backlog — Unresolved Items

### Feature Test Coverage (147 uncovered Actions)

Only 4 of 151 Actions have feature tests. Critical for stability before
production deployment.

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

**Target:** Minimum 1 feature test per Action in Assessment (17), Internship (16),
and Auth (12).

### Cross-Domain Event Flow Documentation

Which events fire and which listeners react is not documented. Needed for
understanding side effects when modifying Actions.

### Real-Time Features (Future)

Laravel Echo and Reverb are installed but no real-time channels are active.
Candidates: notification delivery, dashboard updates, attendance confirmations.

### Queue Job Formalization (Future)

Evaluate which operations should be queued: certificate generation, report
rendering, batch notifications. Currently all notifications use `ShouldQueue`.

## Where to Find It

Troubleshooting sections for specific subsystems are in their respective
documentation files. The health check command
(`app/Domain/Core/Console/Commands/HealthCommand.php`) verifies most
environment prerequisites and will identify common misconfigurations.

---

## Persistence Layer (Layer 2)

### Missing Foreign Key Constraints

Three tables have columns that should have FK constraints but don't:

| Table | Column(s) | Issue |
|---|---|---|
| `account_status_history` | `user_id`, `triggered_by_user_id` | Bare `uuid` columns without `foreignUuid()->constrained()`. No referential integrity — records can reference deleted users. |
| `setups` | `school_id`, `department_id` | `foreignUuid` without `->constrained()`. Same issue — soft references that can become dangling. |

*Status: ✅ Fixed — FK constraints added to migration files.*

### Supervision Logs On Delete

`supervision_logs.supervisor_id` FK has no explicit `onDelete`, defaulting to
RESTRICT. When a user who is a supervisor is deleted, their supervision logs
will block the deletion.

*Status: ✅ Fixed — `cascadeOnDelete()` added.*

### Missing PartnershipFactory

The `Partnership` model (`app/Domain/Partnership/Models/Partnership.php`) has
no factory file. Standalone integration tests cannot create partnership records.

*Status: ✅ Fixed — `PartnershipFactory.php` created.*

### ERD Documentation Discrepancies

The ERD docs have minor inaccuracies compared to the actual schema:

| Doc | Claim | Actual |
|---|---|---|
| `00-erd-index.md` | 75 tables | 74 tables |
| `01-auth.md` | `user_agent` = varchar(255) | `text` |
| `13-admin.md` | `id` = integer | `bigIncrements` |
| `13-admin.md` | `deleted_by` = integer | `unsignedBigInteger` |

*Status: ✅ Fixed — docs updated.*

---

## Core Foundation (Layers 3 & 4)

### SecurityHeaders Middleware Never Registered 🔴

**File:** `app/Domain/Core/Http/Middleware/SecurityHeaders.php`

*Status: ✅ Fixed — registered in `bootstrap/app.php` web group.*

---

### LogContext Middleware Never Registered 🔴

**File:** `app/Domain/Core/Http/Middleware/LogContext.php`

*Status: ✅ Fixed — registered in `bootstrap/app.php` web group.*

---

### Docs Claim "Zero `Log::` Facade Calls" Is False 🔴

**File:** `docs/en/domain/core.md`, line 58

*Status: ✅ Fixed — updated docs to reflect LogContext middleware usage.*

---

### Dead Contracts: DomainEvent, Filterable, Searchable, Sortable 🟡

**Directory:** `app/Domain/Core/Contracts/`

Four contracts are defined but have **zero implementations** across the entire
codebase:

| Contract | Method | Implementations |
|---|---|---|
| `DomainEvent` | `occurredAt(): DateTimeImmutable` | 0 |
| `Filterable` | `applyFilters(Builder): Builder` | 0 |
| `Searchable` | `applySearch(Builder): Builder` | 0 |
| `Sortable` | `applySorting(Builder): Builder` | 0 |

The `BaseRecordManager` has `applySearch()`/`applyFilters()`/`applySorting()`
methods but they are concrete (not contract-bound). These interfaces were
apparently created for future use but never adopted.

*Status: ⏳ Pending — either implement or remove.*

---

### Dead Trait: RespondsWithHttp 🟡

**File:** `app/Domain/Core/Http/Concerns/RespondsWithHttp.php`

A trait providing 6 response helpers (`respond()`, `respondSuccess()`,
`respondCreated()`, `respondError()`, `respondNoContent()`,
`respondValidationError()`). It is **never used** by any controller — zero
`use` statements across the entire codebase. Only a unit test references it.

*Status: ⏳ Pending — either integrate into controllers or remove.*

---

### Dead Trait: HasAuditTrail 🟡

**File:** `app/Domain/Core/Models/Concerns/HasAuditTrail.php`

A 128-line trait that hooks into Eloquent lifecycle events (`created`,
`updated`, `deleted`, `restored`, `forceDeleted`) and writes audit logs via
`SmartLogger`. It is **never used** by any model across all 24 domains.
The trait is well-documented and functional but has zero consumers.

*Status: ⏳ Pending — either apply to key models or remove.*

---

### HandlesActionErrors Can Swallow Custom Exceptions 🟡

**File:** `app/Domain/Core/Support/HandlesActionErrors.php`

The trait catches all `Throwable` except `RuntimeException`, logs the error,
and rethrows as a generic `RuntimeException`. This means **all custom exception
types** (`AppException`, `DomainException`, `ValidationFailedException`,
`NotFoundException`, etc.) are caught and converted to a generic
`RuntimeException` if an action wraps its body in `withErrorHandling()`.

This defeats the purpose of the exception hierarchy — a controller catching
`ValidationFailedException` to return a 422 response will never receive it
if the action used `withErrorHandling()`.

*Status: ⏳ Pending — exclude `AppException` and `DomainException` from the catch.*

---

### BaseAction Does Not Enforce execute() Method 🟡

**File:** `app/Domain/Core/Actions/BaseAction.php`

The documentation and AGENTS.md mandate that every Action has a single
`execute()` method, but `BaseAction` is not abstract and does not declare
`abstract public function execute()`. A class extending `BaseAction` can
compile without implementing `execute()`.

*Status: ⏳ Pending — needs ADR and careful migration (breaking change).*

---

### AuthorizesOwnership Soft-Depends on AuthorizesRoles 🟢

**File:** `app/Domain/Core/Policies/Concerns/AuthorizesOwnership.php`

`isOwnerOrAdmin()` uses `method_exists($this, 'isAdmin')` to check for the
`AuthorizesRoles` trait. If a policy uses `AuthorizesOwnership` without
`AuthorizesRoles`, `isOwnerOrAdmin()` silently never returns true for admins.
In practice this never happens because `BasePolicy` always bundles both traits.

*Status: ⏸️ Won't fix — acceptable coupling via BasePolicy.*

---

### Integrity::verify() Reads composer.json Without Cache 🟢

**File:** `app/Domain/Core/Support/Integrity.php`

`Integrity::verify()` calls `file_get_contents()` and `json_decode()` on
`composer.json` every time it runs. The file I/O is uncached. However, the
method is gated behind `app()->runningUnitTests()` and only called from
`AppInfo::__construct()`, so the performance impact is negligible.

*Status: ⏸️ Won't fix — minimal impact, intentional attribution check.*

---

## Domain Models (Layer 5) & Domain Rules (Layer 6)

### 13 Models Missing User Import — Runtime Error 🔴

*Status: ✅ Fixed — added `use App\Domain\User\Models\User` to all 13 models.*

---

### ~48 Foreign Key Columns Without Individual Indexes 🟡

Many foreign key columns across the schema lack individual database indexes.
Without indexes, JOINs and WHERE filters on these columns perform full table
scans. The most critical (high-query-frequency) unindexed columns:

| Table | Column |
|---|---|
| `mentees` | `user_id` |
| `mentors` | `user_id` |
| `placements` | `company_id`, `internship_id` |
| `briefings` | `internship_id` |
| `reports` | `registration_id` |
| `report_revisions` | `report_id` |
| `assessments` | `rubric_id` |
| `competencies` | `rubric_id` |
| `indicators` | `competency_id` |
| `rubrics` | `internship_id` |
| `partnerships` | `company_id` |
| `certificates` | `registration_id`, `template_id` |
| `assignments` | `assignment_type_id` |
| `incident_reports` | `registration_id` |
| `presentations` | `registration_id` |
| `account_applications` | `internship_id` |

Note: Some of these may be covered by composite indexes — but individual
indexes on FK columns ensure optimal performance for the most common query
pattern (`WHERE fk_column = ?`).

*Status: ⏳ Pending — add individual indexes to high-query FK columns.*

---

### Assessment Table Has `deleted_at` Without SoftDeletes Trait 🟡

*Status: ✅ Fixed — added `SoftDeletes` trait + `deleted_at` cast.*

---

### Internship Model Has 3 Orphan DB Columns 🟡

*Status: ✅ Fixed — added to `#[Fillable]` and `$casts` (boolean + integers).*

---

### Internship State Machine Is Orphaned Dead Code 🟡

**Directory:** `app/Domain/Internship/States/` (7 files)

A full Spatie ModelStates state machine is defined with `InternshipState`
(abstract), `Draft`, `Published`, `Active`, `Completed`, `Cancelled` concrete
classes, and a `StateConfig` with 6 allowed transitions. However:

- **No model uses the `HasStates` trait** to wire the state machine in
- The `Internship` model casts `status` to `InternshipStatus` (simple enum)
  instead of using the state machine
- The model's `asInternshipState()` accessor returns the **Entity** version
  (`Entities\InternshipState`), not the **State machine** version

The entire state machine under `States/` is defined but orphaned. It was
either created speculatively or is a leftover from a refactoring.

*Status: ⏳ Pending — either wire to model via HasStates, or remove.*

---

### BloodType Enum Value Does Not Match Convention 🔴

*Status: ✅ Fixed — values changed to lowercase (`'a'`, `'b'`, `'ab'`, `'o'`).*

---

### Role Enum Has `func_` Prefix Values Inconsistency 🟡

**File:** `app/Domain/Auth/Enums/Role.php`

The `Role` enum has two functional role cases whose values do not match the
case name in lowercase:

| Case | Value | Expected (per convention) |
|---|---|---|
| `MENTOR` | `'func_mentor'` | `'mentor'` |
| `MENTEE` | `'func_mentee'` | `'mentee'` |

The `func_` prefix is intentional (to distinguish functional roles from user
roles), but it breaks the convention that `$case->value === Str::lower($case->name)`.

*Status: ⏸️ Won't fix — intentional functional role prefix.*

---

### Enum Label Translation Inconsistency 🟡

**Directory:** `app/Domain/*/Enums/`

Only 3 enums use `__()` for translatable labels. The remaining 26 use
hardcoded English strings (or Indonesian for `AbsenceReasonType` and
`SupervisionType`):

| Pattern | Enums |
|---|---|
| Uses `__()` for labels | `AuditCategory`, `AccountApplicationStatus`, `Role` |
| Hardcoded English | 23 enums (AuditStatus, RegistrationDocumentStatus, etc.) |
| Hardcoded Indonesian | `AbsenceReasonType` (`'Sakit'`, `'Izin'`), `SupervisionType` (`'Bimbingan'`, `'Mentoring'`) |
| Returns key string only | `AccountStatus` (returns `'account_status.status.'.$this->value` without `__()`) |

There is no project-wide rule about whether enum labels should be translatable
or hardcoded. This inconsistency means UI elements that render `$enum->label()`
may display English in some places and Indonesian in others.

*Status: ⏳ Pending — establish and enforce a consistent label strategy.*

---

### Entity/State Class Name Collision: `InternshipState` 🟢

**Files:**
- `app/Domain/Internship/Entities/InternshipState.php` (business entity)
- `app/Domain/Internship/States/InternshipState.php` (Spatie state machine base)

Two classes with the same name exist in different namespaces with completely
different purposes. The Entity version provides `canBeDeleted()` for business
rules; the State version provides `config()` for state machine transitions.
This is technically valid (different namespaces) but causes confusion during
imports and code navigation.

*Status: ⏸️ Won't fix — different namespaces, rename if confusion arises.*

---

## Summary

| Severity | Issue | Category | Status |
|---|---|---|---|---|
| 🔴 | Feature tests missing for 147 of 151 Actions | Testing | ⏳ |
| 🔴 | Indonesian `internship.php` missing 110 keys | Translation | ⏳ |
| 🔴 | Indonesian `logbook.php` file missing entirely | Translation | ⏳ |
| 🟡 | Exception handling in `bootstrap/app.php` is empty | Infrastructure | ⏳ |
| 🟡 | Error pages use stock Laravel layout without branding | UI | ⏳ |
| 🟡 | 48 FK columns without individual indexes | Database | ⏳ |
| 🟡 | Internship state machine orphaned (7 files, no model uses) | States | ⏳ |
| 🟡 | Role enum `func_` prefix value inconsistency | Enums | ⏸️ |
| 🟡 | Enum label translation inconsistency | Enums | ⏳ |
| 🟡 | 4 dead contracts (DomainEvent, Filterable, Searchable, Sortable) | Architecture | ⏳ |
| 🟡 | RespondsWithHttp trait never used | Architecture | ⏳ |
| 🟡 | HasAuditTrail trait never used | Architecture | ⏳ |
| 🟡 | HandlesActionErrors swallows custom exceptions | Architecture | ⏳ |
| 🟡 | BaseAction does not enforce execute() method | Architecture | ⏳ |
| 🟡 | Translation structural differences | Translation | ⏳ |
| 🟢 | Cross-domain event flow undocumented | Documentation | ⏳ |
| 🟢 | Real-time features (Echo + Reverb) not yet active | Future | ⏳ |
| 🟢 | Queue job formalization not evaluated | Future | ⏳ |
