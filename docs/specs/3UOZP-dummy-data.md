# Dummy Data — Factory-Generated Demo Dataset via DummySeeder

> **Spec ID:** 3UOZP
> **Status:** Full
> **Owner:** Core
> **Depends on:** [rbac-and-authorization](T4B26-rbac-and-authorization.md) (T4B26), [department-management](4HWSB-department-management.md) (4HWSB), [academic-year-management](XW6F5-academic-year-management.md) (XW6F5), [company-management](XI3LB-company-management.md) (XI3LB), [partnership-management](NTHQA-partnership-management.md) (NTHQA), [internship-lifecycle](7C5WM-internship-lifecycle.md) (7C5WM), [internship-groups](IT0OE-internship-groups.md) (IT0OE), [registration](MBB5R-registration.md) (MBB5R), [placement](J9GBH-placement.md) (J9GBH), [daily-activity](1KSWL-daily-activity.md) (1KSWL), [supervision](2EHSE-supervision.md) (2EHSE), [incident](3RU9S-incident.md) (3RU9S), [assessment](ARDA6-assessment.md) (ARDA6), [evaluation](AXKZW-evaluation.md) (AXKZW), [assignment](T657Z-assignment.md) (T657Z), [certification](J0M04-certification.md) (J0M04), [reports](R6BMW-reports.md) (R6BMW)

## Description

Defines a single entry point (`Database\Seeders\DummySeeder`) and a dev-only helper (`Tests\Support\DummyData`) that generate a realistic, interconnected demo dataset from the existing model factories. The dataset spans the full PKL lifecycle — registration, daily operations, assessment, certification, and reporting — so every module screen has data during demos and manual testing. Seeding is strictly opt-in and never runs as part of provisioning or production setup.

---

## 1. Problem Statements

### PS-1 — Empty Database Blocks Demos and Manual Testing

A fresh install holds only roles, settings, and an academic year. Demonstrating the lifecycle or testing module screens by hand means entering dozens of related records — users, companies, partnerships, internships, placements, registrations, logbooks, attendances, assessments, reports, certificates. Without a ready dataset, demos stall and QA spends more time building data than testing the application.
**→ Requirement:** FR-SEED-001 (one-command dataset), FR-SEED-022/040 (full-lifecycle composition).

### PS-2 — Static JSON Fixtures Drift From the Schema

A hand-written fixture file duplicates schema knowledge outside the models: every migration that adds, renames, or removes a column silently invalidates it, and raw JSON bypasses the validation and casting that Eloquent and `#[Fillable]` attributes provide.
**→ Requirement:** FR-SEED-009 (factories only), FR-SEED-011 (extend states, never new factories for existing models).

### PS-3 — No Coherent, Interconnected Demo State

Demo data must be internally consistent: placement counters equal their live children, registrations reference real internships and placements, reports and certificates exist only in matching lifecycle states. Out-of-order seeding yields dangling references and broken screens.
**→ Requirement:** FR-SEED-010 (dependency order), FR-SEED-014/016 (state-scoped records), FR-SEED-019 (derived counters), FR-SEED-020 (single transaction).

### PS-4 — Demo Code Must Not Pollute Application Core

A generator inside autoloaded production paths blurs real seeders from demo helpers and risks shipping demo identities to a live school. The generator belongs where production never looks.
**→ Requirement:** FR-SEED-002 (helper in `Tests\Support`), FR-SEED-003/007 (opt-in only, production guard), NFR-SEED-009 (dev-only autoload).

---

## 2. Goals & Non-Goals

### Goals

- **One-command demo dataset** — `php artisan db:seed --class=DummySeeder` populates the full lifecycle. *Why:* demos and QA start from a known state in under a minute instead of an afternoon of hand entry.
- **Factories exclusively** — every record comes from an existing model factory, never raw arrays or fixture files. *Why:* factories already encode schema-faithful, validated data; anything else drifts on the next migration.
- **Dev-only generator** — the helper lives under `tests/` so production code never imports demo logic. *Why:* the boundary between real provisioning and demo data must be structural, not conventional.
- **Coherent by construction** — foreign keys resolve, counters derive from children, lifecycle states stay valid. *Why:* a demo with broken screens is worse than no demo.
- **Deterministic role accounts** — known credentials for every role. *Why:* presenters log in without guessing, and idempotency keys off stable identities.
- **Idempotent re-runs** — re-seeding converges instead of duplicating. *Why:* QA mutates state constantly and must restart scenarios cheaply.
- **Full-lifecycle coverage** — every module screen shows data. *Why:* partial datasets leave whole modules undemonstrable.
- **Additive over base seeders** — roles, settings, and the active year are reused, never duplicated. *Why:* provisioning already establishes them; recreating them corrupts the boundary between base and demo data.

### Non-Goals

- **Automatic production seeding**. *Why:* opt-in only — the seeder never joins `DatabaseSeeder`/`SetupSeeder` and refuses production without an explicit flag.
- **Static JSON/CSV fixtures**. *Why:* replaced by factory generation; fixtures duplicate schema knowledge.
- **Media/PDF file generation**. *Why:* attachments reference metadata only; real uploads are out of scope.
- **Realistic quantity at scale**. *Why:* tens of students serve demos; load testing is a different dataset with different tooling.
- **Cross-tenant or multi-school data**. *Why:* single-tenant by product definition.

---

## 3. User Stories / Use Cases

Demo and QA journeys, each verified by running the seeder and asserting the resulting state.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SEED-001 | Developer boots a demo environment with one command and receives a populated, coherent dataset with working credentials | P0 | F | Full |
| UC-SEED-002 | Demo presenter logs in as each role and finds every role-specific screen populated | P0 | F | Full |
| UC-SEED-003 | QA re-seeds after state mutation and returns to a known state with zero duplicate records | P1 | F | Full |

### 3.1 Demo and QA Workflows

#### UC-SEED-001 — From Empty Install to Living School

The developer's Friday task is a Monday demo for three schools: migrate with base seeders, run one command, watch the summary count up users, placements, logbooks, certificates. No hand entry, no SQL paste from a chat log. When the summary prints, the database holds a small but complete vocational school mid-semester — students placed, logbooks flowing, assessments scored — and the credentials on the slide actually work.

#### UC-SEED-002 — Walking Every Role's Day

The presenter starts as admin, glances at the dashboard counts, then steps sideways into a teacher reviewing logbooks, a supervisor verifying attendance at a partner workshop, a student checking their own submissions. Each screen is populated because the dataset was built as one connected story rather than per-screen props. A visitor asking "what does the supervisor see?" gets a login, not an apology.

#### UC-SEED-003 — Reset After Chaos

QA spent the morning trying to break placement reassignment and the dataset now holds the scars — half-moved registrations, orphaned counters. One fresh-migrate plus one seed command later the chaos is gone: same accounts, same placements, zero duplicates, scenario restartable in under a minute. Idempotency turns destructive testing from a cleanup burden into a routine.

---

## 4. Functional Requirements

Entry point, generation discipline, and dataset composition. `Priority` ranks criticality P0–P3; `Layer` declares the verifying test layer; `Status` tracks this requirement independently of the spec's registry status.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SEED-001 | A `Database\Seeders\DummySeeder` exists and is the only entry point for dummy data | P0 | F | Full |
| FR-SEED-002 | `DummySeeder` delegates all generation to a helper in `Tests\Support\` | P0 | A | Full |
| FR-SEED-003 | `DummySeeder` is never registered in `DatabaseSeeder` or `SetupSeeder`; it runs only when explicitly invoked | P0 | A | Full |
| FR-SEED-004 | `DummySeeder` reuses existing base-seeded data (roles, settings, active academic year) and invokes `RolePermissionSeeder`, `AppSettingSeeder`, `AcademicYearSeeder` only when that base data is absent | P0 | F | Full |
| FR-SEED-005 | `DummySeeder` prints a bilingual summary (counts per entity) via `__()` | P1 | F | Full |
| FR-SEED-006 | `DummySeeder` runs in any environment, including production, only when invoked explicitly via `db:seed --class=DummySeeder` or `setup:install --with-dummy` | P0 | F | Full |
| FR-SEED-007 | The seeder refuses to run under production environment unless an explicit demo flag is passed; it never executes as a side effect of provisioning, migration, or deployment | P0 | F | Full |
| FR-SEED-008 | A `Tests\Support\DummyData` helper exposes `run(): array` returning per-entity counts | P0 | F | Full |
| FR-SEED-009 | All records are created via model factories (`Database\Factories\*`), never raw arrays | P0 | F | Full |
| FR-SEED-010 | Generation follows module dependency order: academic → partnerships → programs → enrollment → daily ops → assessment → certification → reporting | P0 | F | Full |
| FR-SEED-011 | New factory states (e.g. `active()` registration, `verified()` logbook) extend existing factories as needed; no new factories are created for existing models | P1 | F | Full |
| FR-SEED-012 | The helper is idempotent: `firstOrCreate`/`updateOrCreate` keyed on natural unique fields (email/username for users; name for departments, companies, internships) | P0 | F | Full |
| FR-SEED-013 | All demo users share a known password (default `password`, configurable) | P0 | F | Full |
| FR-SEED-014 | Registrations for the current internship are `active` and reference real placements | P0 | F | Full |
| FR-SEED-015 | Daily-ops records (logbooks, attendances, absence requests, supervision logs, monitoring visits) exist only for `active` registrations | P0 | F | Full |
| FR-SEED-016 | Assessments, submissions, reports, and certificates exist only for registrations in matching lifecycle states (reports/certificates for completed internships) | P0 | F | Full |
| FR-SEED-017 | Evaluation forms (with sections and questions) and responses are seeded with a realistic structure | P1 | F | Full |
| FR-SEED-018 | Announcements, notifications, account applications, and placement change requests include at least one record in each status | P1 | F | Full |
| FR-SEED-019 | Every placement's `filled_quota` equals the number of `active` registrations assigned to it, computed from children rather than hardcoded | P0 | F | Full |
| FR-SEED-020 | The entire generation runs inside a single database transaction: any failure rolls back the whole dataset and no partial data persists | P0 | F | Full |
| FR-SEED-021 | The helper treats base-seeded data as read-only input: it reuses the active academic year, roles, and settings and only supplements records the base seeders do not provide | P0 | F | Full |
| FR-SEED-022 | Academic years: the `active` year from `AcademicYearSeeder` is reused as-is and never duplicated; the helper adds exactly one past (inactive) year | P0 | F | Full |
| FR-SEED-023 | Departments: at least three vocational majors | P0 | F | Full |
| FR-SEED-024 | Companies: 6–8 across diverse industry sectors | P0 | F | Full |
| FR-SEED-025 | Partnerships: one per company, at least one `active` and one `expired` | P0 | F | Full |
| FR-SEED-026 | Internships: one `active` (current year, linked to the base active academic year) and one `completed` (past year, linked to the helper-added past year) | P0 | F | Full |
| FR-SEED-027 | Placements: one per company per internship with quotas (6–8 placements per internship), honoring the placement uniqueness constraint | P0 | F | Full |
| FR-SEED-028 | Users: one `admin`, 3–5 teachers, 4–8 supervisors, 20–30 students; no `superadmin` — that account comes exclusively from `SetupSuperAdminAction` | P0 | F | Full |
| FR-SEED-029 | Profiles: every user has a profile; student profiles carry department plus national id; supervisor profiles carry company plus employment status | P0 | F | Full |
| FR-SEED-030 | Registrations: at least 80% of students registered, majority `active` with placement, remainder `pending` | P0 | F | Full |
| FR-SEED-031 | Internship groups: 3–6 groups whose members cover the placed students | P1 | F | Full |
| FR-SEED-032 | Documents and registration documents: policy/handbook docs with mixed verification states | P1 | F | Full |
| FR-SEED-033 | Rubrics: 1–2 per internship; assignments: 3–5 published per internship with submitted and graded submissions | P1 | F | Full |
| FR-SEED-034 | Logbooks: 5–10 per `active` registration with mixed statuses (`draft`, `submitted`, `verified`) | P0 | F | Full |
| FR-SEED-035 | Attendances: 10–20 per `active` registration with mixed statuses, plus a few absence requests | P0 | F | Full |
| FR-SEED-036 | Supervision logs and monitoring visits: several per `active` registration | P1 | F | Full |
| FR-SEED-037 | Assessments: `midterm` plus `final` for active registrations; reports: `draft` for current, `finalized` for completed | P1 | F | Full |
| FR-SEED-038 | Certificates: `issued` for completed-internship registrations with unique `certificate_number` and `qr_hash` | P1 | F | Full |
| FR-SEED-039 | Incident reports: 1–2 with mixed severities and statuses | P1 | F | Full |
| FR-SEED-040 | Student names generate without academic titles (no faker `id_ID` suffix like S.Pd/S.Kom); teachers, supervisors, and admin may retain the default titled format | P2 | F | Full |

### 4.1 Entry Point and Production Guard

#### FR-SEED-001 — One Door Into Demo Data

A demo dataset with three entry points becomes three datasets the moment one of them changes. Routing everything through `DummySeeder` means the summary, the guards, and the ordering live in one place — there is exactly one command to document, one class to review, and one behavior to test. Anything generating demo rows outside it is not a shortcut; it is an ungoverned seed.

#### FR-SEED-002 — Thin Seeder, Working Helper

The seeder itself stays a husk: resolve the helper, run it, print the summary. All orchestration — ordering, idempotency, transactions — belongs to the helper where it can be constructed and asserted without artisan ceremony. This split is what keeps the entry point reviewable in one glance while the machinery underneath stays testable like any other class.

#### FR-SEED-003 — Never on the Automatic Path

Provisioning, migration fresh-seeds, and container entrypoints run base seeders on real schools' servers. If the dummy seeder ever joined that chain, the next install would greet a live school with twenty fictional students and known passwords. Exclusion from `DatabaseSeeder` and `SetupSeeder` is the structural guarantee: demo data requires a human typing its name on purpose, in every environment, without exception.

#### FR-SEED-004 — Borrowing the Foundation

Roles, settings, and the active year already exist after provisioning — recreating them would duplicate role rows, fork the active year, and fight the settings the admin just configured. The seeder therefore checks for foundation data first and only fills gaps, treating whatever it finds as ground truth. A demo layered over a real setup week keeps the real setup intact underneath.

#### FR-SEED-005 — A Summary in the Operator's Language

After a minute of inserts, the operator deserves to know what landed — and in the language they deploy in. Counts per entity through the translation helper turn a silent wall of queries into a readable manifest in Indonesian or English. The summary doubles as a smoke signal: a zero where students should be is visible immediately, not discovered mid-demo.

#### FR-SEED-006 — Explicit Invocation Travels Anywhere

Demo and staging instances sometimes run with production-like environment flags, and the seeder must still work there when asked — the operative word being asked. Naming the two legitimate invocations (direct seed command, install-with-dummy flag) draws the boundary precisely: availability everywhere, automatic execution nowhere. Capability without autonomy is the whole posture.

#### FR-SEED-007 — Production Refuses by Default

Some semester, someone will run a seed command on the wrong terminal — it is practically a rite of passage. This guard is what stands between that mistake and twenty fake students inside a live accreditation record: under a production environment flag the seeder aborts unless an explicit demo flag accompanies the call. Provisioning, migration, and deployment pipelines never pass that flag, so no automation can ever trip it by accident. The one time demo data genuinely belongs in production-like settings, the operator says so twice — once with the command, once with the flag.

### 4.2 Helper and Data Generation

#### FR-SEED-008 — Counts Out, Confidence In

Returning per-entity counts turns generation into an observable function: tests assert the shape of a run, the seeder prints it, and drift shows up as a changed number rather than a vague feeling. A stateless helper with a single public method keeps that surface minimal — everything else is private orchestration the tests reach through the counts and the database state they describe.

#### FR-SEED-009 — Factories or Nothing

A raw insert bypasses mutators, casts, and fillable guards — the exact machinery the application relies on — producing rows the app itself could never create. Factories carry all of that machinery plus relationship composition, so a company with an internship and placements reads as one expression instead of three coordinated arrays. The rule is absolute because every exception becomes the fixture that breaks on the next migration.

#### FR-SEED-010 — Seeding Downstream of Reality

Registrations cannot precede the placements they reference; certificates cannot precede the completed internships they certify. The dependency order mirrors the module build order, so each stage queries rows the previous stage actually created. Violating the sequence does not merely look wrong — it fails on foreign keys, loudly, which is the database enforcing narrative discipline.

#### FR-SEED-011 — States Grow, Factories Don't Multiply

A registration needs an `active()` shape and a logbook needs `verified()` — these are lenses on existing models, not new models. Adding states to the established factories keeps every consumer (feature tests, QA scripts, demos) sharing one definition of what "active" means. A second factory for the same model would fork that definition within weeks; the prohibition keeps the fork from ever existing.

#### FR-SEED-012 — Second Runs Converge

Natural keys make identity stable across runs: the same email finds the same user, the same company name finds the same company. Re-seeding after a mutated QA session therefore heals instead of duplicating — updated where changed, created where missing, never doubled. The practical payoff is a reset ritual measured in seconds rather than a fresh-migrate measured in minutes.

#### FR-SEED-013 — Passwords Everyone Knows

Demo credentials live on slides and in chat messages, so they must be trivially memorable and uniform: one shared password, optionally overridden for environments with stricter handling. Hashing still applies — the database never holds it in cleartext — because even throwaway instances get cloned, backed up, and forgotten in places. Known to humans, opaque to storage.

#### FR-SEED-014 — Current Students, Real Placements

The heart of the demo is a cohort of active students sitting in genuine placements — every registration row joined to a placement row that exists with quota to show for it. Dangling registrations (placed nowhere, referencing nothing) would render every placement screen as an error page during the demo's most important five minutes. Currency plus referential truth is the minimum aliveness criterion.

#### FR-SEED-015 — Daily Ops Belong to the Active

Logbooks, attendance rows, absence requests, supervision notes, and visit logs describe lived internship days — they make no sense attached to a pending registration that never started. Scoping them to active registrations keeps every daily-ops screen truthful: counts reconcile, timelines read chronologically, and no teacher opens a logbook for a student who never arrived. Orphaned daily records are the fastest way to make a demo feel fabricated.

#### FR-SEED-016 — Final Artifacts Follow Final States

Certificates for students still mid-internship would be fraud in miniature; draft reports on completed cohorts would confuse the archive story. Gating each final artifact on its matching lifecycle state preserves the narrative the certification and reporting modules assume: current cohorts draft, completed cohorts finalize and issue. The demo then shows both ends of the lifecycle honestly instead of faking completion.

#### FR-SEED-017 — Evaluations With Anatomy

An evaluation form without sections and questions is a title page; responses without that structure are orphaned scores. Seeding the full anatomy — form, sections, questions, then responses against them — exercises the evaluation module's real joins and renders its real screens. Presenters can open a scored form and trace every number to its question, which is the difference between demonstrating software and showing screenshots.

#### FR-SEED-018 — Every Status Has a Witness

Status filters, badges, and queues are core UI, and each needs at least one row to render against. Covering every status across announcements, notifications, applications, and change requests means no filter in the demo returns a suspiciously empty list. Completeness here is cheap — one row per state — and its absence is conspicuous the moment anyone clicks a tab.

#### FR-SEED-019 — Counters Computed, Never Declared

A hardcoded `filled_quota` starts truthful and decays with the first QA mutation; a derived one recomputes from the actual active children every run. Computing from reality keeps placement screens, quota badges, and capacity guards mutually consistent no matter how the dataset was poked. The invariant is a query, not a value — queries stay honest while values rot.

#### FR-SEED-020 — All or Nothing, Enforced by the Database

A half-seeded demo is worse than an empty one: screens render, numbers disagree, and the presenter discovers it live. Wrapping generation in a single transaction converts any mid-run failure into a clean absence — the database returns to its prior state as if the run never happened. Partial demo state is therefore not a degraded mode operators must detect; it is a state that cannot exist.

#### FR-SEED-021 — Reading the Foundation, Not Rewriting It

The helper arrives after provisioning and behaves like a guest: roles attached but never recreated, the active year referenced but never cloned, settings left untouched. This read-only posture toward base data is what keeps the dummy dataset purely additive — removable without a trace, re-runnable without conflict. The boundary also documents itself: base seeders own reality, the helper owns fiction layered on top.

### 4.3 Dataset Composition

#### FR-SEED-022 — Borrowing This Year, Inventing Last Year

Two academic years tell the lifecycle story: the current one, inherited untouched from provisioning so the demo matches a real install's starting point, and one completed year giving certificates and finalized reports somewhere truthful to live. Adding exactly one past year keeps the archive narrative minimal — enough history to demonstrate completion, not so much that the demo becomes a history lesson.

#### FR-SEED-023 — Three Majors Minimum

A single department makes every grouping screen trivial and every filter meaningless. Three vocational majors give internship groups, placement quotas, and reports something to partition by — the smallest dataset in which cross-department behavior is observable rather than assumed. Real schools have more; three is the floor where the UI stops lying by omission.

#### FR-SEED-024 — A Believable Industry Mix

Six to eight companies across different sectors — manufacturing, services, tech, hospitality — make the partnership list look like a real school's portfolio instead of a single employer's roster. Sector diversity exercises category filters and gives placement distribution its characteristic unevenness: some industries absorb many students, others few. A demo with one sector invites the question nobody wants: is this data real.

#### FR-SEED-025 — Partnerships Alive and Lapsed

One partnership per company keeps the mapping legible, while mixing active and expired statuses shows the lifecycle honestly — partnerships lapse, renew, and occasionally die. The expired row matters more than it looks: without it, expiry handling, renewal flows, and status badges have no demo witness. A portfolio with no history reads as a mock; one lapsed record makes it a record.

#### FR-SEED-026 — Two Internships, Two Eras

The active internship anchors the present — current year, current students, daily records accumulating — while the completed one anchors the past that certificates and finalized reports require. Linking each to its correct year (base active year for the present, helper-added past year for history) keeps year-scoped queries truthful. One internship would force the demo to choose between showing activity and showing completion; two shows both.

#### FR-SEED-027 — One Placement Per Company Per Internship

The uniqueness constraint on company-plus-internship is a schema fact, so the dataset honors it: each company hosts one placement per internship, yielding six to eight placements per cycle. Quotas on those placements still demonstrate saturation and competition for slots — the demo narrative needs scarcity, not multiplicity. More placements would require relaxing a deliberate constraint for cosmetic gain, which is never a good trade.

#### FR-SEED-028 — A School in Miniature

One admin, a handful of teachers and supervisors, twenty-odd students: the smallest population in which every role interaction is observable. Teachers have multiple mentees, supervisors span companies, students fill groups — the social graph is dense enough to feel real. The absent superadmin is deliberate: that singleton belongs to installation integrity rules, and seeding one would either duplicate it or violate its immutability contract. Presenters get full-access demos through the admin account instead.

#### FR-SEED-029 — Everybody Gets a Profile

A user without a profile breaks nothing technically and everything presentationally — avatars, department badges, supervisor affiliations all read from it. Students carry department plus national id, supervisors carry company plus employment status, because those are the attributes their respective screens actually display. Completeness here is invisible when present and glaring when missing, which is precisely why it is specified.

#### FR-SEED-030 — Enrollment That Looks Like Enrollment

Eighty percent registration with a majority placed mirrors a real intake week: most students settled, a tail still pending. The pending remainder is not filler — it gives verification queues, approval actions, and pending badges their demo rows. A hundred percent placement would leave the enrollment module's most important workflow (handling the stragglers) with nothing to show.

#### FR-SEED-031 — Groups Covering the Placed

Three to six internship groups whose membership covers the placed students make group screens, group supervision, and group reports demonstrable end to end. Coverage matters: a placed student in no group is a gap the group module cannot explain away. Small groups also mirror reality, where mentors track clusters rather than the whole cohort at once.

#### FR-SEED-032 — Documents Mid-Process

Policy and handbook documents in mixed verification states show the document pipeline honestly — some acknowledged, some pending, some flagged. Registration documents likewise span states so verification queues have content. A documents screen where everything is already verified demonstrates nothing about the workflow that got it there; mixed states show the machine working.

#### FR-SEED-033 — Rubrics, Assignments, and Their Aftermath

One or two rubrics per internship give scoring its criteria; three to five published assignments with submissions in both submitted and graded states give the assignment module its full arc. Grading queues need ungraded work, grade books need graded work, and the demo needs both visible simultaneously. Without this spread, assessment screens show either an empty inbox or a finished past — never a living present.

#### FR-SEED-034 — Logbooks at Every Stage

Five to ten entries per active registration across draft, submitted, and verified states reproduce a student's real semester arc: recent drafts being written, submitted work awaiting review, verified history behind. Review queues need the middle state, timelines need the outer two. Fewer entries would make supervision screens look staged; this density reads as a term in progress.

#### FR-SEED-035 — Attendance With Texture

Ten to twenty attendance rows per active registration with mixed statuses — present, late, verified — plus a few absence requests trace the semester's attendance story truthfully. Verification workflows need unverified rows; statistics need volume; absence handling needs its requests. Sparse attendance makes the anti-fraud narrative (timestamps, immutability after sign-off) abstract; density makes it concrete.

#### FR-SEED-036 — Supervision Leaves Paper

Monitoring visits and supervision logs are the evidence that distance supervision actually happened — the digital answer to the remote-placement problem. Several per active registration give supervisors a visible trail and give the supervision module its substance. A demo without them implicitly claims nobody watched the students all semester.

#### FR-SEED-037 — Scores Now, Records Later

Midterm plus final assessments on active registrations feed the aggregation the grade card will eventually show; draft reports on the current cohort and finalized ones on the completed cohort show reporting at both life stages. The split teaches the lifecycle visually: present-tense scoring alongside past-tense records. Mixing them up would certify the unfinished or leave the finished undocumented.

#### FR-SEED-038 — Certificates That Verify

Issued certificates on completed registrations carry unique numbers and QR hashes — the exact fields the verification flow scans. Uniqueness is load-bearing: duplicate numbers would make verification ambiguous, and a null hash would break the QR path entirely. These rows turn the certificate from a PDF prop into a verifiable credential, which is the module's entire point.

#### FR-SEED-039 — Incidents, Rare but Present

One or two incident reports across severities and statuses acknowledge that internships sometimes go wrong — and that the system tracks it when they do. Rarity is the point: a demo flooded with incidents tells a story no school recognizes. Just enough to open the incident module, filter by severity, and show resolution states.

#### FR-SEED-040 — Students Without Degree Titles

The Indonesian faker locale decorates names with academic suffixes — S.Pd, S.Kom — that no vocational student holds. Stripping them for student names while keeping titled formats for teachers and supervisors preserves demo credibility at zero structural cost: the audience that notices fake titles is exactly the audience being demoed to. Realism here is one string concatenation avoided, and its absence would undermine every student roster shown.

---

## 5. Non-Functional Requirements

Cross-cutting constraints on the seeder and helper. `Target` holds the concrete SLO where one exists; `N/A` marks architecturally-enforced properties verified via tests and review.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SEED-001 | `DummySeeder` runs only when invoked explicitly; it is never registered in `DatabaseSeeder`/`SetupSeeder` | N/A | P0 | A | Full |
| NFR-SEED-002 | Demo passwords are hashed; plaintext never reaches storage | N/A | P0 | F | Full |
| NFR-SEED-003 | Faker output avoids real personal information | N/A | P0 | F | Full |
| NFR-SEED-004 | Full seed completes in under 60 seconds on a local SQLite database | < 60s fresh | P1 | F | Full |
| NFR-SEED-005 | Idempotent re-run completes in under 30 seconds | < 30s re-run | P1 | F | Full |
| NFR-SEED-006 | Re-running the seeder never errors on duplicate records | N/A | P0 | F | Full |
| NFR-SEED-007 | The dataset is all-or-nothing: a single failed insert rolls back the entire generation, leaving the database unchanged | N/A | P0 | F | Full |
| NFR-SEED-008 | All CLI output uses `__()` (English and Indonesian) | N/A | P1 | F | Full |
| NFR-SEED-009 | The generator lives under `tests/` (dev-only autoload), never in `app/` or `database/` | N/A | P0 | A | Full |
| NFR-SEED-010 | The helper adds no new model factories; it reuses or extends existing ones | N/A | P1 | A | Full |
| NFR-SEED-011 | Every new factory state traces to a requirement in this spec or a module spec; no orphan states exist | N/A | P1 | A | Full |

### 5.1 Safety

#### NFR-SEED-001 — Opt-In as Architecture

Registration in a base seeder would execute demo generation on every provisioned school — the failure mode is fictional students with known passwords inside a live accreditation database. Keeping the seeder unregistered makes execution impossible without naming it deliberately, which converts the entire risk class from "misconfiguration" to "explicit intent." Review treats any registration diff touching this boundary as a blocking defect.

#### NFR-SEED-002 — Known Passwords, Hashed Storage

Every demo password is public knowledge by design — printed on slides, shared in chats — so the storage layer must assume the database will be read by strangers. Hashing through the standard credential pipeline keeps even a leaked demo dump from becoming a credential-harvesting source. Convenience for presenters, opacity for storage: both properties hold simultaneously because hashing is not conditional on secrecy.

#### NFR-SEED-003 — Fiction Must Not Collide With People

Generated names, addresses, and phone numbers share a namespace with real Indonesians, and a collision with a real person's identity inside an official-looking system is a harm, not a joke. Faker output stays synthetic — example domains, clearly generated numbers — so no demo row can be mistaken for a real citizen's record. The day a generated value looks real enough to forward is the day the provider configuration gets fixed.

### 5.2 Performance

#### NFR-SEED-004 — A Minute to a Living School

Dozens of entities across the full lifecycle must materialize fast enough that seeding stays a reflex rather than a coffee break. The sixty-second budget on local SQLite covers factories, relationships, and the single wrapping transaction at demo scale (tens of students, not thousands). Slowness here would push developers back toward hand-entry; the budget defends the habit.

#### NFR-SEED-005 — Resets in Half a Minute

The re-run path skips existing rows by natural key, so it should cost roughly half the fresh run — lookups instead of inserts for most of the dataset. Thirty seconds keeps the mutate-and-reset QA loop tight enough to run between test sessions rather than overnight. A re-run approaching fresh-run cost signals the idempotency lookups degraded into full regeneration.

### 5.3 Reliability

#### NFR-SEED-006 — Duplicates Are Impossible, Not Just Unlikely

Natural-key upserts make the second run find the first run's rows instead of colliding with them — no unique-constraint explosions mid-demo-prep, no manual cleanup before re-seeding. The guarantee covers every entity the helper touches, because one duplicating table poisons the whole reset ritual. QA learns to trust the command only when it has never once failed them.

#### NFR-SEED-007 — Failure Leaves No Footprint

Some semester the seed run will fail halfway — a changed enum, a tightened validation, a full disk. The single wrapping transaction ensures that failure reads as "nothing happened" rather than "something half-happened": no orphaned users, no dangling registrations, no counters pointing at missing children. Recovery is fixing the cause and re-running, never auditing wreckage.

### 5.4 Usability

#### NFR-SEED-008 — Output in the Operator's Language

The run summary and progress lines pass through the translation helper with both locales shipped, like every other user-facing string in the project. An Indonesian operator seeding before a demo reads Indonesian; the summary never assumes otherwise. Untranslated seeder output would be a localization gap in exactly the workflow shown to new schools first.

### 5.5 Maintainability

#### NFR-SEED-009 — Demo Logic Ships With Dev Dependencies

Placing the generator under the dev autoload means production installs (built without dev packages) physically cannot load it — the class does not exist there. This beats any amount of documentation warning against production seeding, because structure enforces what convention merely requests. The seeder entry point's dev-class dependency is the point, not a wart: it makes the opt-in boundary load-bearing.

#### NFR-SEED-010 — Factories Are Shared Property

A helper-private factory would fork the definition of a model within months — different defaults, different states, different bugs. Reusing the suite's factories (extending states where the spec demands) keeps demo data, feature tests, and QA scripts speaking one dialect. The factory directory stays the single place where "what does a valid registration look like" is answered.

#### NFR-SEED-011 — States With Provenance

Every factory state exists because a requirement names the status it represents; a state no requirement traces to is either speculative or leftover. Provenance keeps the factory surface reviewable — each state points at its justification — and gives cleanup a criterion: untraced states delete without debate. The alternative is a states list that grows until nobody dares touch it.

---

## 6. API / Data Contracts

### 6.1 Entry Point

```text
php artisan db:seed --class=DummySeeder
```

```text
Database\Seeders\DummySeeder extends Seeder
    run(): void
        → refuse when production environment without explicit demo flag (FR-SEED-007)
        → call RolePermissionSeeder / AppSettingSeeder / AcademicYearSeeder when base data absent
        → Tests\Support\DummyData::make()->run()
        → print bilingual summary via $this->command->info(__('...'))
```

### 6.2 Helper Contract

```text
Tests\Support\DummyData (final, stateless)
    public function run(): array
        // Wraps the entire generation in a single DB::transaction() (FR-SEED-020, NFR-SEED-007).
        // Any exception aborts the closure and rolls back all inserts.
        // Returns ['users' => n, 'registrations' => n, ...] for the summary

    // Internal orchestration, in module dependency order (FR-SEED-010), all inside one transaction.
    // seedAcademicYears reuses the active year from AcademicYearSeeder and adds only the past year (FR-SEED-022).
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
    private function seedIncidents(): void        // incident reports (FR-SEED-039)
```

### 6.3 Demo Accounts (deterministic)

| Role | Email | Password |
| ---- | ----- | -------- |
| admin | `admin@example.com` | `password` |
| teacher | `teacher1@example.com` … | `password` |
| supervisor | `supervisor1@example.com` … | `password` |
| student | `student1@example.com` … | `password` |

> The `superadmin` role is **not** seeded by the dummy data — the single superadmin account is created exclusively by `SetupSuperAdminAction` during installation (`setup:install`). Demo presenters log in with `admin@example.com` instead.
>
> Roles and permissions are **not** created by the helper — they come from `RolePermissionSeeder` (base). The helper only attaches existing roles via `assignRole` and reuses the active academic year from `AcademicYearSeeder` (FR-SEED-021).

### 6.4 Required Factory State Additions (to be implemented)

| Factory | New state(s) |
| ------- | ------------ |
| `RegistrationFactory` | `active()`, `pending()` |
| `LogbookFactory` | `submitted()`, `verified()` |
| `AttendanceFactory` | `late()`, `verified()` |
| `AssignmentFactory` | `closed()` |
| `ReportFactory` | `finalized()` |

The exact list is finalized during implementation by mapping each state to the status enum from the governing module spec (e.g. `LogbookStatus::VERIFIED`).

---

## 7. Design Decisions

Recorded choices behind the generator. These rows carry no test layer — they explain intent so future maintainers extend the dataset without re-litigating the reasoning.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SEED-001 | Records build exclusively through existing model factories; the hand-written fixture-file approach is dropped | P0 | — | Full |
| DD-SEED-002 | The generator lives at `Tests\Support\DummyData` under the dev autoload mapping; the `DummySeeder` entry point invokes it | P0 | — | Full |
| DD-SEED-003 | `DummySeeder` is never added to `DatabaseSeeder` or `SetupSeeder`; developers invoke it explicitly | P0 | — | Full |
| DD-SEED-004 | Demo accounts use predictable `role{n}@example.com` addresses with a shared `password` | P1 | — | Full |
| DD-SEED-005 | Idempotency uses `firstOrCreate`/`updateOrCreate` on natural unique fields, with derived counters computed from real children | P0 | — | Full |
| DD-SEED-006 | `run()` wraps the entire generation in a single `DB::transaction()` for all-or-nothing results | P0 | — | Full |
| DD-SEED-007 | Generation temporarily sets the faker locale to `id_ID` (restored in a `finally` block) so free-form content reads Indonesian | P1 | — | Full |
| DD-SEED-008 | `DummyData` never seeds `superadmin`; the sole account comes from `SetupSuperAdminAction` with its integrity rules intact | P0 | — | Full |
| DD-SEED-009 | The helper reuses base-seeded data as read-only input and only supplements records the base seeders do not provide | P0 | — | Full |
| DD-SEED-010 | Exactly one placement per company per internship, honoring the placement uniqueness constraint | P1 | — | Full |
| DD-SEED-011 | Student names generate without the faker `id_ID` suffix provider; teachers, supervisors, and admin keep the default format | P2 | — | Full |

### 7.1 Generation Strategy

#### DD-SEED-001 — Factories Instead of Fixture Files

There was once a JSON file holding the demo school — hand-written rows that rotted with every migration until seeding failed on columns that no longer existed. Factories ended that maintenance tax: they encode the same validation and casting the application uses, compose relationships natively, and evolve with the schema because tests use them daily. Losing the auditability of an explicit fixture hurt briefly; the per-entity summary plus deterministic accounts replaced it with something better — a manifest of what actually seeded.

#### DD-SEED-002 — Living With the Test Suite

Housing the generator beside the test supports was a placement with teeth: dev-only autoloading makes production physically unable to import it, so the "never in production" rule is enforced by the autoloader rather than by discipline. The convention mirrors the existing settings-seed trait, so newcomers find it where they already look. Depending on a dev class from a database seeder means `--no-dev` installs cannot run it — the correct behavior for an opt-in demo tool, and the reason the production guard has structural backing.

#### DD-SEED-003 — The Door That Stays Closed

Base seeders run wherever installs happen — including live schools, Docker entrypoints, and CI. Registering a dev seeder in that chain would fire demo generation on every fresh install, salting real deployments with fictional students. Leaving it unregistered costs developers one extra command (or one install flag) and buys every school the certainty that provisioning never invents people. Cheap door, valuable lock.

#### DD-SEED-004 — Credentials on the Slide

Predictable addresses and one shared password remove every login friction from demos — no password manager, no guessing, no "let me check the notes." Deterministic identities double as idempotency keys, so the convenience pays for the re-run guarantee too. Known credentials are a theoretical surface and a practical necessity; since the seeder is opt-in and barred from silent production runs, the necessity wins and the surface is documented rather than hidden.

### 7.2 Consistency and Safety

#### DD-SEED-005 — Identity by Nature, Counts by Query

Natural keys turn re-seeding into convergence: the same email resolves the same user, the same name the same company, and derived counters recount their children instead of restating stale values. Consistency invariants then hold because they are computed from the rows they describe, not remembered alongside them. Query overhead at demo scale is noise — tens of records — against the alternative of duplicate-choked resets.

#### DD-SEED-006 — One Transaction for the Whole School

The dataset is a single connected story, and a story told halfway is misinformation: users without placements, certificates without internships, counters without children. Holding one transaction across generation makes partial state unrepresentable — failure returns the database to its prior shape, never to a confusing middle. Long-held locks would matter at production scale; at demo scale with a sub-minute budget, atomicity is pure win. Per-entity commits with manual cleanup were considered and rejected: cleanup that can itself fail is not a safety net.

#### DD-SEED-007 — Indonesian by Default

An Indonesian vocational product demoed in English lorem ipsum feels imported; the same demo in Indonesian names, companies, and addresses feels built for the room. The `id_ID` faker locale provides exactly that for free-form fields, switched on for the run and restored afterward so nothing leaks into the surrounding process. Enum-governed fields ignore locale by construction, so statuses stay canonical while prose turns native. The fallback for long prose is English lorem — acceptable bilingual texture for data nobody reads aloud.

#### DD-SEED-008 — No Second Superadmin

The superadmin singleton carries integrity rules — immutable name and username, single-account invariant, undeletable — that a seeded second account would violate on sight. Since installation already creates the real one, seeding another buys nothing and corrupts the setup contract. Demo presenters lose nothing: the admin account opens every screen a demo needs, and anything genuinely requiring superadmin runs against the installed account.

### 7.3 Dataset Shape

#### DD-SEED-009 — Additive, Never Duplicative

Base seeders own roles, settings, and the active year; the helper owns everything they do not provide. Recreating foundation rows would duplicate roles, fork the active year away from its setting, and blur the line between provisioning and fiction. Coupling to the base output shape is the accepted cost, contained by gap-filling invocation and natural-key idempotency. The dataset completes the install rather than competing with it.

#### DD-SEED-010 — The Constraint the Schema Insists On

An early draft imagined several placements per company per internship until the placement module's uniqueness constraint settled the argument: one placement per company per internship, full stop. With six to eight companies that still yields six to eight placements per cycle — ample room for the placed cohort — while quotas and derived counters carry the saturation narrative. Relaxing a deliberate database constraint for demo variety would have been tail-wagging-schema; the dataset follows the schema instead.

#### DD-SEED-011 — Titles Only Where Earned

Nobody in the demo room believes a sixteen-year-old holds a master's suffix, and one titled student name on a roster slide undermines every real number around it. Constructing student names from first plus last name dodges the faker suffix provider deterministically — no brittle post-hoc regex, no future-suffix surprises. Teachers and supervisors keep their titled formats because there the titles are plausible, and plausibility is the entire criterion.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Seed runtime (fresh) | < 60s | `time php artisan db:seed --class=DummySeeder` |
| Seed runtime (re-run) | < 30s | Same command on an already seeded DB |
| FK consistency | 0 broken refs | Every registration resolves to a valid internship/placement; `filled_quota` equals active registration count |
| Demo accounts | all roles work | Login succeeds for each account in §6.3 |
| Module screen coverage | all modules | Manual walkthrough of each module's list screen |
| Duplicate records on re-run | 0 | `firstOrCreate` on natural keys |
| Opt-in only | unregistered | Not present in `DatabaseSeeder`/`SetupSeeder` (FR-SEED-003) |
| Partial data after failed seed | 0 records | Inject a failure mid-generation; verify no records persist (FR-SEED-020, NFR-SEED-007) |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [rbac-and-authorization](T4B26-rbac-and-authorization.md) (T4B26) | Roles (`superadmin`, `admin`, `teacher`, `student`, `supervisor`) used by `assignRole` |
| [department-management](4HWSB-department-management.md) (4HWSB), [academic-year-management](XW6F5-academic-year-management.md) (XW6F5) | `DepartmentFactory`, `AcademicYearFactory` and the active-year state |
| [company-management](XI3LB-company-management.md) (XI3LB), [partnership-management](NTHQA-partnership-management.md) (NTHQA) | `CompanyFactory`, `PartnershipFactory` with status states |
| [internship-lifecycle](7C5WM-internship-lifecycle.md) (7C5WM), [internship-groups](IT0OE-internship-groups.md) (IT0OE) | `InternshipFactory`, `InternshipGroupFactory`, `phases`/`grading_weights` contract |
| [registration](MBB5R-registration.md) (MBB5R), [placement](J9GBH-placement.md) (J9GBH) | `RegistrationFactory`, `PlacementFactory`, the `filled_quota` invariant, status lifecycle |
| [daily-activity](1KSWL-daily-activity.md) (1KSWL), [supervision](2EHSE-supervision.md) (2EHSE), [incident](3RU9S-incident.md) (3RU9S) | Logbook/Attendance/Absence/Supervision/Visit/Incident factories and status enums |
| [assessment](ARDA6-assessment.md) (ARDA6), [evaluation](AXKZW-evaluation.md) (AXKZW), [assignment](T657Z-assignment.md) (T657Z) | Rubric/Assessment/Evaluation/Assignment/Submission factories and `assessment_type` contract |
| [certification](J0M04-certification.md) (J0M04), [reports](R6BMW-reports.md) (R6BMW) | Certificate/Report factories, `grading_weights`, grade letter thresholds |

### Build Guide

Implement `Tests\Support\DummyData` (orchestrating the existing factories in dependency order, wrapped in a single transaction, with the factory states in §6.4), add the thin `Database\Seeders\DummySeeder` entry point with the opt-in guard (FR-SEED-003, NFR-SEED-001), the production refusal (FR-SEED-007), and the bilingual summary, then document usage in the database guide (Seeders section) and register the new factory states in their module reference docs.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ) | `system:health` can assert demo dataset integrity after a dummy seed |
| 2 | [installation](8NZAU-installation.md) (8NZAU) | `setup:install --with-dummy` invokes `DummySeeder` after provisioning (FR-SEED-006) — keeps the DD-SEED-003 trade-off to a single flag on fresh installs |
| 3 | — | End of lifecycle — no downstream consumers |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If a governing module spec renames a status enum, the mapped factory states in §6.4 break at generation time; mitigated by the all-or-nothing transaction (FR-SEED-020) surfacing the failure with zero partial state | Open | Maintainer | — |
| A-1 | We assume base seeders (`RolePermissionSeeder`, `AppSettingSeeder`, `AcademicYearSeeder`) have run before `DummySeeder`, with gap-filling invocation (FR-SEED-004) covering a bare database | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Maintenance phase: 06IB6 (Full), 06IB7 (Full), 06IB8 (Full), 3UOZP (Full)
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — roles attached via `assignRole`, never recreated
- [Registration](MBB5R-registration.md) — registration lifecycle and status states
- [Placement](J9GBH-placement.md) — placement uniqueness constraint and `filled_quota` invariant
- [Daily activity](1KSWL-daily-activity.md) — logbook edit window and attendance integrity
- [Installation](8NZAU-installation.md) — `setup:install --with-dummy` invocation path
- [System maintenance](E1MSJ-system-maintenance.md) — `system:health` dataset integrity assertion
- [MVP trim ADR](../adr/adr-mvp-spec-trim.md) — scope discipline: demo scale only, no load-testing depth
