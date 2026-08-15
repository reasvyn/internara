# Dummy Data — Factory-Generated Demo Dataset via DummySeeder

> **Last updated:** 2026-08-15 **Changes:** amend — `setup:install --with-dummy` seeds demo data
> after provisioning without registering `DummySeeder` (installation FR-C10, DD-3 trade-off,
> §9 Next Steps); amend — reuse base-seeded data (roles, settings, active
> academic year) and supplement only (FR-E4, FR-C1, FR-H14, G8, DD-9); amend — one placement per
> company per internship (FR-C6, DD-10); amend — no superadmin in demo data (DD-8, FR-C7);
> amend — Indonesian-locale demo content (DD-7); amend — wrap entire generation in a single DB
> transaction (FR-H13, NFR-R2, DD-6); feat — new spec (Maintenance) defining the
> `DummySeeder` entry point and a `tests/Support/` factory-driven dummy data helper;
> replaces the static-JSON design (`database/dummy-data.json`)

## Description

Defines a single entry point (`Database\Seeders\DummySeeder`) and a dev-only helper
(`Tests\Support\DummyData`) that generate a realistic, interconnected demo dataset for Internara
using the existing model factories. The dataset covers the full PKL lifecycle — registration,
daily operations, assessment, certification, and reporting — so every module screen has data to
display during demos and manual testing.

---

## 1. Problem Statements

### PS-1 — Empty Database Blocks Demos and Manual Testing

A fresh install contains only base roles, settings, and an academic year. Demonstrating the full
PKL lifecycle or manually testing module screens requires hand-entering dozens of related records
(users, profiles, companies, partnerships, internships, placements, registrations, logbooks,
attendances, assessments, reports, certificates). Without a ready dataset, demos stall and QA
spends more time building data than testing the application.

### PS-2 — Static JSON Fixtures Drift From the Schema

The originally proposed approach (`database/dummy-data.json`) duplicated schema knowledge in
hand-written JSON. Every migration that adds, renames, or removes a column silently invalidates the
fixture, and raw JSON bypasses the validation and casting that Eloquent models and `#[Fillable]`
attributes provide. Duplicating schema knowledge that the factories already encode is a
maintenance liability (Clean Code / dedup doctrine).

### PS-3 — No Coherent, Interconnected Demo State

Demo data must be internally consistent: every placement's `filled_quota` must equal the number of
`active` registrations assigned to it, registrations must reference real internships, companies,
and placements, and reports/certificates must only exist for registrations in the correct
lifecycle state. Ad-hoc or out-of-order seeding produces dangling references and broken screens.

### PS-4 — Demo Code Must Not Pollute Application Core

A dummy-data generator placed in `app/` or `database/` (both autoloaded in production installs)
risks being invoked in production and blurs the line between real seeders and demo helpers. The
generator belongs in `tests/` — which ships only with dev dependencies (`autoload-dev`) — so dummy
code can never run in a production deployment.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide one command (`php artisan db:seed --class=DummySeeder`) that populates a complete demo dataset |
| G2  | Generate data exclusively from existing model factories — no hand-written fixture files |
| G3  | Keep the generator in `tests/Support/` so it is dev-only and never part of production code |
| G4  | Produce a coherent dataset: FK references resolve, counters (`filled_quota`) are consistent, lifecycle states are valid |
| G5  | Provide deterministic demo accounts with known credentials for every role |
| G6  | Be idempotent — re-runnable without duplicating records |
| G7  | Cover the full PKL lifecycle so every module screen has data to display |
| G8  | Reuse the data the setup step already provides (roles, settings, active academic year) — the dummy dataset supplements, never duplicates it |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Production seed data — `DummySeeder` is demo/testing only and must never run in production |
| NG2  | Static JSON/CSV fixtures (`database/dummy-data.json`) — replaced by factory generation |
| NG3  | Media/PDF file generation — attachments reference metadata only; actual file uploads are out of scope |
| NG4  | Realistic quantity at scale — the dataset is sized for demo (tens of students), not load testing |
| NG5  | Cross-tenant or multi-school data — single-tenant by product definition |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Boots a Demo Environment

**Actor:** Developer
**Preconditions:** `php artisan migrate --seed` (base seeders) has run; dev autoload (`autoload-dev`) present
**Flow:**
1. Developer runs `php artisan db:seed --class=DummySeeder`
2. `DummySeeder` resolves the `Tests\Support\DummyData` helper
3. Helper reuses base-seeded data (roles, settings, active academic year) and supplements the missing demo data (past academic year, departments, companies, partnerships, internships, placements)
4. Helper creates users with roles and profiles (admin, teachers, supervisors, students)
5. Helper creates registrations and populates daily-ops/assessment/certification/reporting data
6. Helper reports a per-entity summary
**Postconditions:** Application has a full demo dataset; demo credentials work

### UC-2 — Demo Presenter Logs In as Each Role

**Actor:** Demo presenter
**Preconditions:** `DummySeeder` has run
**Flow:**
1. Presenter logs in with `admin@example.com` / `password`
2. Presenter switches to a teacher, supervisor, and student account (known credentials)
3. Each role's dashboard and module screens show populated data
**Postconditions:** All roles demonstrate real, populated screens

### UC-3 — QA Re-seeds After State Mutation

**Actor:** QA tester
**Preconditions:** A previous demo/QA session mutated the dataset
**Flow:**
1. QA runs `php artisan migrate:fresh --seed` (base) then `php artisan db:seed --class=DummySeeder`
2. Idempotency checks (`firstOrCreate` on natural keys) prevent duplicates against a partially seeded DB
3. QA restarts the scenario from a known state
**Postconditions:** Dataset reset to a known state; no duplicate records

---

## 4. Functional Requirements

### 4.1 Entry Point

| ID   | Requirement |
| ---- | ----------- |
| FR-E1 | A `Database\Seeders\DummySeeder` must exist and be the only entry point for dummy data |
| FR-E2 | `DummySeeder` must delegate all generation to a helper in `Tests\Support\` |
| FR-E3 | `DummySeeder` must NOT be registered in `DatabaseSeeder` or `SetupSeeder` — it is opt-in only |
| FR-E4 | `DummySeeder` must reuse existing base-seeded data (roles, settings, active academic year) and call `RolePermissionSeeder`, `AppSettingSeeder`, `AcademicYearSeeder` only when base roles/settings/years are absent |
| FR-E5 | `DummySeeder` must print a bilingual summary (counts per entity) via `__()` |

### 4.2 Helper & Data Generation

| ID   | Requirement |
| ---- | ----------- |
| FR-H1 | A `Tests\Support\DummyData` helper must expose `run(): array` returning per-entity counts |
| FR-H2 | All records must be created via model factories (`Database\Factories\*`) — never raw arrays |
| FR-H3 | Generation must follow module dependency order: academic → partnerships → programs → enrollment → daily ops → assessment → certification → reporting |
| FR-H4 | New factory states (e.g., `active()` registration, `verified()` logbook) must be added to existing factories as needed — no new factories for existing models |
| FR-H5 | The helper must be idempotent: `firstOrCreate`/`updateOrCreate` keyed on natural unique fields (email/username for users; name for departments, companies, internships) |
| FR-H6 | All demo users must share a known password (default `password`, configurable) |
| FR-H7 | Registrations for the current internship must be `active` and reference real placements |
| FR-H8 | Daily-ops records (logbooks, attendances, absence requests, supervision logs, monitoring visits) must only exist for `active` registrations |
| FR-H9 | Assessments, submissions, reports, and certificates must only exist for registrations in matching lifecycle states (reports/certificates for completed internships) |
| FR-H10 | Evaluation forms (with sections and questions) and responses must be seeded with a realistic structure |
| FR-H11 | Announcements, notifications, account applications, and placement change requests must include at least one record in each status |
| FR-H12 | Every placement's `filled_quota` must equal the number of `active` registrations assigned to it (computed from children, not hardcoded) |
| FR-H13 | The entire generation must run inside a single database transaction — if any record fails to be created, the whole dataset must be rolled back and no partial data must persist |
| FR-H14 | The helper must treat base-seeded data as read-only input: reuse the existing active academic year, roles, and settings instead of recreating them — it only supplements the dataset with records the base seeders do not provide |

### 4.3 Dataset Composition

| ID   | Requirement |
| ---- | ----------- |
| FR-C1 | Academic years: the `active` year is provided by `AcademicYearSeeder` (base) and reused as-is (never duplicated); the helper adds exactly one past (inactive) year |
| FR-C2 | Departments: at least three (vocational majors) |
| FR-C3 | Companies: 6–8 across diverse industry sectors |
| FR-C4 | Partnerships: one per company, at least one `active` and one `expired` |
| FR-C5 | Internships: one `active` (current year, linked to the base active academic year) and one `completed` (past year, linked to the past year the helper adds) |
| FR-C6 | Placements: one per company per internship with quotas (enforced by `placement_company_internship_unique`, FR-P2 in the placement spec — see DD-10); 6–8 placements per internship |
| FR-C7 | Users: one `admin`, 3–5 teachers, 4–8 supervisors, and 20–30 students. No `superadmin` — the superadmin account is created exclusively by `SetupSuperAdminAction` (see §7 DD-8) |
| FR-C8 | Profiles: every user has a profile; student profiles carry department + national id; supervisor profiles carry company + employment status |
| FR-C9 | Registrations: at least 80% of students registered, majority `active` with placement, remainder `pending` |
| FR-C10 | Internship groups: 3–6 groups whose members cover the placed students |
| FR-C11 | Documents and registration documents: policy/handbook docs with mixed verification states |
| FR-C12 | Rubrics: 1–2 per internship; assignments: 3–5 published per internship with submitted/graded submissions |
| FR-C13 | Logbooks: 5–10 per `active` registration with mixed statuses (`draft`, `submitted`, `verified`) |
| FR-C14 | Attendances: 10–20 per `active` registration with mixed statuses; a few absence requests |
| FR-C15 | Supervision logs and monitoring visits: several per `active` registration |
| FR-C16 | Assessments: `midterm` + `final` for active registrations; reports: `draft` for current, `finalized` for completed |
| FR-C17 | Certificates: `issued` for completed-internship registrations with unique `certificate_number` and `qr_hash` |
| FR-C18 | Incident reports: 1–2 with mixed severities and statuses |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | `DummySeeder` must refuse to run when `APP_ENV=production` |
| NFR-S2 | Demo passwords must be hashed — never stored in plaintext |
| NFR-S3 | Faker data must avoid real personal information |
| NFR-P1 | Full seed must complete in under 60 seconds on a local SQLite database |
| NFR-P2 | Idempotent re-run must complete in under 30 seconds |
| NFR-R1 | Re-running the seeder must never error on duplicate records |
| NFR-R2 | The dataset must be all-or-nothing: a single failed insert must roll back the entire generation, leaving the database unchanged |
| NFR-U1 | All CLI output must use `__()` (English and Indonesian) |
| NFR-M1 | The generator must live under `tests/` (dev-only autoload) — never in `app/` or `database/` |
| NFR-M2 | The helper must not add new model factories — only reuse or extend existing ones |
| NFR-M3 | Every new factory state must trace to a requirement in this spec or a module spec — no orphan states |

---

## 6. API / Data Contracts

### 6.1 Entry Point

```
php artisan db:seed --class=DummySeeder
```

```
Database\Seeders\DummySeeder extends Seeder
    run(): void
        → refuse when app()->environment('production')
        → call RolePermissionSeeder / AppSettingSeeder / AcademicYearSeeder when base data absent
        → Tests\Support\DummyData::make()->run()
        → print bilingual summary via $this->command->info(__('...'))
```

### 6.2 Helper Contract

```
Tests\Support\DummyData (final, stateless)
    public function run(): array
        // Wraps the entire generation in a single DB::transaction() (FR-H13, NFR-R2).
        // Any exception aborts the closure and rolls back all inserts.
        // Returns ['users' => n, 'registrations' => n, ...] for the summary

    // Internal orchestration, in module dependency order (FR-H3), all inside one transaction.
    // seedAcademicYears reuses the active year from AcademicYearSeeder and adds only the past year (FR-C1, DD-9).
    private function seedAcademicYears(): void
    private function seedDepartments(): void
    private function seedCompanies(): void
    private function seedPartnerships(): void
    private function seedInternships(): void
    private function seedPlacements(): void
    private function seedUsersAndProfiles(): void
    private function seedRegistrations(): void
    private function seedGroupsAndDocuments(): void
    private function seedAssessmentData(): void   // rubrics, assignments, evaluation forms
    private function seedDailyOps(): void         // logbooks, attendances, supervision, visits
    private function seedFinalization(): void     // assessments, submissions, reports, certificates
    private function seedSysAdminData(): void     // announcements, notifications, account applications, placement changes
    private function seedIncidents(): void        // incident reports (FR-C18)
```

### 6.3 Demo Accounts (deterministic)

| Role       | Email                              | Password |
| ---------- | ---------------------------------- | -------- |
| admin      | `admin@example.com`                | `password` |
| teacher    | `teacher1@example.com` …           | `password` |
| supervisor | `supervisor1@example.com` …        | `password` |
| student    | `student1@example.com` …           | `password` |

> The `superadmin` role is **not** seeded by the dummy data — the single superadmin account is
> created exclusively by `SetupSuperAdminAction` during installation (`setup:install`). Demo
> presenters log in with `admin@example.com` instead (DD-8).
>
> Roles and permissions are **not** created by the helper — they come from `RolePermissionSeeder`
> (base). The helper only attaches existing roles via `assignRole` and reuses the active academic
> year from `AcademicYearSeeder` (FR-H14, DD-9).

### 6.4 Required Factory State Additions (to be implemented)

| Factory               | New state(s)                     |
| --------------------- | -------------------------------- |
| `RegistrationFactory` | `active()`, `pending()`          |
| `LogbookFactory`      | `submitted()`, `verified()`      |
| `AttendanceFactory`   | `late()`, `verified()`           |
| `AssignmentFactory`   | `closed()`                       |
| `ReportFactory`       | `finalized()`                    |

The exact list is finalized during implementation by mapping each state to the status enum from
the governing module spec (e.g., `LogbookStatus::VERIFIED`).

---

## 7. Design Decisions

### DD-1 — Factory-Generated Data Over Static JSON

**Decision:** The helper builds records exclusively through the existing model factories;
`database/dummy-data.json` is dropped.
**Rationale:** Factories already encode schema-faithful data validated by Eloquent and
`#[Fillable]` attributes. Static JSON duplicates schema knowledge and drifts on every migration
(Clean Code / dedup doctrine). Factories also compose relationship chains natively
(`Company::factory()`, `Internship::factory()`), which the JSON design could not.
**Trade-off:** Loses the auditability of an explicit fixture file — mitigated by the per-entity
summary output and deterministic demo accounts.
**Rejected alternative:** Hand-written JSON consumed by a JSON-driven seeder — violates DRY and
drifts from the schema.

### DD-2 — Helper in `tests/Support/` (Dev-Only Autoload)

**Decision:** The generator lives at `Tests\Support\DummyData` under the `autoload-dev` PSR-4
mapping (`Tests\` → `tests/`); the `DummySeeder` in `Database\Seeders` invokes it.
**Rationale:** `composer install --no-dev` (production) drops `autoload-dev`, so the generator can
never ship to or run in a production deployment. It also keeps `app/` and `database/` free of demo
logic, mirroring the existing `Tests\Support\WithSettingsSeed` trait convention.
**Trade-off:** `DummySeeder` depends on a dev-autoloaded class — acceptable because it is
explicitly demo-only and never registered in the base seeders.
**Rejected alternative:** Helper in `app/Support/` or `database/helpers/` — pollutes production
code and risks accidental production invocation.

### DD-3 — `DummySeeder` Is Opt-In Only

**Decision:** `DummySeeder` is never added to `DatabaseSeeder` or `SetupSeeder`; it is invoked
explicitly by the developer.
**Rationale:** The base seeders run in production installs (`setup:install`, Docker entrypoint).
Registering a dev-only seeder that loads a tests-autoloaded helper there would break production.
Opt-in keeps the door closed by default.
**Trade-off:** One extra command for developers — mitigated by `setup:install --with-dummy`
(installation spec FR-C10), which keeps the seeder unregistered while making demo seeding one
flag away on a fresh install.

### DD-4 — Deterministic Demo Accounts

**Decision:** Demo accounts use predictable `role{n}@example.com` addresses and a shared `password`.
**Rationale:** Demo presenters must log in without guessing (UC-2); deterministic identities also
make idempotency (`firstOrCreate` on email/username) trivial (FR-H5).
**Trade-off:** Known credentials are a minor security surface — acceptable because the seeder
refuses to run in `APP_ENV=production` (NFR-S1).

### DD-5 — Idempotency via Natural Keys and Derived Counters

**Decision:** Use `firstOrCreate`/`updateOrCreate` keyed on natural unique fields (email/username,
name) and compute derived counters (`filled_quota`) from actual child records rather than fixture
values.
**Rationale:** Re-runnable seeds (UC-3) without duplicates; consistency invariants (FR-H7, FR-H12)
hold because derived fields reflect real children.
**Trade-off:** Slightly more query overhead — negligible at demo scale (tens of records).

### DD-6 — Single Transaction for All-or-Nothing Generation

**Decision:** `Tests\Support\DummyData::run()` wraps the entire generation — from academic years
through certificates — in a single `DB::transaction()`. Any exception aborts the closure and rolls
back every insert.
**Rationale:** The dataset is deeply interconnected (FR-H12, NFR-R2): a partially applied seed would
produce dangling references and a misleading demo state. Transactional wrapping guarantees the
dataset is either fully present or entirely absent, and it matches the atomicity the Action Triad
already enforces on application mutations.
**Trade-off:** The transaction is held open for the full generation, which on MySQL/PostgreSQL holds
locks longer than per-entity commits — negligible for a demo-sized dataset (tens of records, < 60s
target in NFR-P1).
**Rejected alternative:** Per-entity commits with a manual cleanup-on-failure — risks leaving orphan
records if cleanup itself fails, and reintroduces the very partial state the requirement forbids.

### DD-7 — Indonesian-Locale Demo Content

**Decision:** `Tests\Support\DummyData::run()` temporarily sets `app.faker_locale` to `id_ID` for the
duration of the generation (restored in a `finally` block), so free-form content — names, company
names, addresses, cities, phone numbers — is generated in Indonesian by the `id_ID` faker locale.
Fields governed by enums (`status`, `type`, `assessment_type`, etc.) always keep their enum values
regardless of locale.
**Rationale:** Internara is an Indonesian vocational-school product; demo content in Indonesian makes
demos and manual testing feel native (S2 doctrine). `id_ID` is already bundled with fakerphp
(Address, Company, Internet, Person, PhoneNumber providers), so no new dependency or provider is
needed. Enum-driven fields are unchanged by locale by construction.
**Trade-off:** `id_ID` has no `Text` provider, so prose fields (logbook content, feedback, notes)
fall back to the default `en_US` lorem — acceptable bilingual output for a demo dataset.
**Rejected alternative:** Hand-translating static fixture text — reintroduces the static-JSON
maintenance liability DD-1 rejects, and duplicating text per-locale is unmaintainable.

### DD-8 — No Super Admin in Dummy Data

**Decision:** `DummyData` seeds `admin`, `teacher`, `supervisor`, and `student` accounts only —
never `superadmin`. The sole superadmin account is created by `SetupSuperAdminAction` during
installation and is protected by strict integrity rules (`SuperAdminIntegrityRules`: immutable name
`Administrator`, immutable username `superadmin`, single-account invariant, cannot be locked or
deleted).
**Rationale:** Creating a second superadmin — or a superadmin that violates the immutable
name/username contract — would break the single-account invariant and the setup module's integrity
rules. The superadmin already exists after a normal `setup:install`, so seeding one is unnecessary.
**Trade-off:** Demo presenters use `admin@example.com` (FR-C7) instead of a superadmin account for
full-access demos; anything requiring superadmin is exercised through the real installed account.
**Rejected alternative:** Seeding `superadmin@example.com` via `assignRole` — creates a second
superadmin and/or an account violating `isNameValid`/`isUsernameValid`, corrupting the setup
contract.

### DD-9 — Reuse Base-Seeded Data; Supplement, Never Duplicate

**Decision:** The helper assumes the base seeders (`RolePermissionSeeder`, `AppSettingSeeder`,
`AcademicYearSeeder` — invoked by `SetupSeeder`/`DatabaseSeeder`/`setup:install`) have already run.
It treats their output as read-only input: the active academic year is reused as-is (FR-C1), roles
are attached via `assignRole` but never recreated, and settings are left untouched. The helper only
creates records the base seeders do not provide (past academic year, departments, companies,
partnerships, internships, placements, users/profiles, registrations, daily ops, assessment,
certification, reporting, sysadmin data).
**Rationale:** `setup:install` / `migrate --seed` already establish roles, settings, and the active
year. Recreating them would duplicate rows (role names, active year), drift from the
`active_academic_year` setting, and blur the boundary between base and demo data. The dummy dataset
is additive by design — it *completes* the setup dataset (G8).
**Trade-off:** `DummySeeder` is coupled to the base seeders' output shape; mitigated by FR-E4 (run
base seeders only when absent) and FR-H5 idempotency (`firstOrCreate` on natural keys).
**Rejected alternative:** Recreating base data unconditionally — duplicate roles/years and
conflicts with settings; or making the dummy dataset fully standalone — duplicates the base
seeders' job.

### DD-10 — One Placement per Company per Internship

**Decision:** FR-C6 yields exactly **one** placement per company per internship. The schema's
`placement_company_internship_unique` constraint (FR-P2 in the placement spec) forbids multiple
placements for the same company within a single internship, so the earlier "2–4 per company" draft
is dropped.
**Rationale:** Aligns the dataset spec with the authoritative placement module spec (FR-P2) and the
migration constraint. With 6–8 companies (FR-C3) this still yields 6–8 placements per internship —
ample for the 16–20 placed students (FR-C9) — and quotas plus `filled_quota` (FR-H12) still
demonstrate placement saturation.
**Trade-off:** Fewer placements than a multi-slot design would produce; acceptable because the
schema intentionally models one placement (one PKL program) per company per internship.
**Rejected alternative:** Relaxing the DB constraint to allow 2–4 placements per company —
contradicts FR-P2 and requires a migration with no product need.

---

## 8. Success Metrics

| Metric                         | Target          | Measurement                                    |
| ------------------------------ | --------------- | ---------------------------------------------- |
| Seed runtime (fresh)           | < 60s           | `time php artisan db:seed --class=DummySeeder` |
| Seed runtime (re-run)          | < 30s           | Same command on an already seeded DB           |
| FK consistency                 | 0 broken refs   | Every registration resolves to a valid internship/placement; `filled_quota` == active registration count |
| Demo accounts                 | all roles work  | Login succeeds for each account in §6.3        |
| Module screen coverage         | all modules     | Manual walkthrough of each module's list screen |
| Duplicate records on re-run    | 0               | `firstOrCreate` on natural keys               |
| Production guard               | blocked         | `APP_ENV=production` run exits with error      |
| Partial data after failed seed | 0 records       | Inject a failure mid-generation; verify no records persist (FR-H13, NFR-R2) |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [rbac-and-authorization.md](rbac-and-authorization.md) (#8) | Roles (`superadmin`, `admin`, `teacher`, `student`, `supervisor`) used by `assignRole` |
| [department-management.md](department-management.md) (#26), [academic-year-management.md](academic-year-management.md) (#27) | `DepartmentFactory`, `AcademicYearFactory` and the active-year state |
| [company-management.md](company-management.md) (#28), [partnership-management.md](partnership-management.md) (#29) | `CompanyFactory`, `PartnershipFactory` with status states |
| [internship-lifecycle.md](internship-lifecycle.md) (#30), [internship-groups.md](internship-groups.md) (#31) | `InternshipFactory`, `InternshipGroupFactory`, `phases`/`grading_weights` contract |
| [registration.md](registration.md) (#32), [placement.md](placement.md) (#33) | `RegistrationFactory`, `PlacementFactory`, the `filled_quota` invariant, status lifecycle |
| [daily-activity.md](daily-activity.md) (#38), [supervision.md](supervision.md) (#39), [incident.md](incident.md) (#40) | Logbook/Attendance/Absence/Supervision/Visit/Incident factories and status enums |
| [assessment.md](assessment.md) (#41), [evaluation.md](evaluation.md) (#42), [assignment.md](assignment.md) (#43) | Rubric/Assessment/Evaluation/Assignment/Submission factories and `assessment_type` contract |
| [certification.md](certification.md) (#46), [reports.md](reports.md) (#49) | Certificate/Report factories, `grading_weights`, grade letter thresholds |

### Build Guide

Implement `Tests\Support\DummyData` (orchestrating the existing factories in dependency order,
wrapped in a single transaction, with the factory states in §6.4), add the thin
`Database\Seeders\DummySeeder` entry point with the production guard and bilingual summary, then
document usage in `docs/infrastructure/database.md` (Seeders section) and register the new factory
states in their module reference docs.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [system-maintenance.md](system-maintenance.md) (#54) | `system:health` can assert demo dataset integrity after a dummy seed |
| 2 | [installation.md](installation.md) (#14) | `setup:install --with-dummy` invokes `DummySeeder` after provisioning (FR-C10, NFR-S13) — removes the DD-3 "extra command" trade-off on fresh installs |
| 3 | — | End of lifecycle — no downstream consumers |

---

## Quick References

- `database/seeders/DummySeeder.php` — entry point (new, opt-in)
- `database/seeders/RolePermissionSeeder.php`, `AppSettingSeeder.php`, `AcademicYearSeeder.php` — base data reused by the helper (FR-E4, FR-H14, DD-9)
- `tests/Support/DummyData.php` — factory-driven generator (new, dev-only)
- `tests/Support/WithSettingsSeed.php` — existing test-support convention being extended
- `database/factories/*` — existing factories reused (38 factories)
- `docs/infrastructure/database.md` — Seeders section (to be updated)
- **Related specs:** [registration.md](registration.md) (#32), [placement.md](placement.md) (#33), [reports.md](reports.md) (#49), [certification.md](certification.md) (#46)
