# Tech Stack — Language, Framework & Dependency Manifest

> **Spec ID:** FB792

## Description

Technology stack and **dependency manifest** for Internara: the minimum PHP version, framework
and package versions, and the lockfile contract that makes deployments reproducible on diverse
school infrastructure. This is a manifest, not a behavior contract — it pins what the platform
is built from, while runtime service behavior lives elsewhere.

The architectural model these dependencies serve is defined in
[architecture-design](D2FT3-architecture.md). Every runtime service built on these dependencies
(database, cache, session, queue, mail, filesystem) is specified in
[core-infra-services](ZT6VS-core-infra-services.md). Base classes and shared utilities are
separate initiatives — see [base-classes](SE5Q9-base-classes.md) and
[shared-utilities](C8F0D-shared-utilities.md).

---

## 1. Problem Statements

### PS-1 — Version Drift on Heterogeneous Hosts

A self-hosted application deployed on diverse school infrastructure (shared hosting, VPS, local
servers) must pin its technology versions to avoid "works on my machine" issues. PHP 8.4 features
(readonly properties, enums, fibers) are used throughout; deploying on PHP 8.1 causes silent
failures. Framework version mismatches cause breaking changes in middleware registration, queue
configuration, and migration syntax.
**→ Requirement:** FR-STACK-001/002/003/004/005 (version pins), FR-STACK-012 (Tier-1 defaults).

### PS-2 — Undeclared Dependencies Break Reproducibility

With 25+ Composer packages and a JS toolchain, undeclared or un-pinned dependencies produce
non-reproducible builds. Every package a module uses must be registered in the manifest — no
undeclared direct dependencies — and the lockfile must be the install contract.
**→ Requirement:** FR-STACK-008/009/010/011 (manifest and locked installs).

### PS-3 — Known-Vulnerable Dependencies Reach Releases

Without a gate, a release can ship a dependency with a published advisory. Auditing the manifest
at release time is the cheapest class of vulnerability fix available.
**→ Requirement:** FR-STACK-013 (audit gate), DD-STACK-003.

---

## 2. Goals & Non-Goals

### Goals

- **Pin the language and framework floor** — PHP 8.4, Laravel 13, Livewire 4, Tailwind CSS v4, TallstackUI v4 as minimum versions. *Why:* the codebase uses PHP 8.4 features throughout; anything lower fails silently, and framework drift breaks middleware, queue, and migration behavior.
- **Keep a complete, registered dependency manifest** — every Composer runtime/dev package and the JS toolchain declared with constraints. *Why:* undeclared direct dependencies are the root cause of non-reproducible builds across school hosts.
- **Make installs reproducible via committed lockfiles** — `composer.lock` and the JS lockfile are the install contract. *Why:* heterogeneous school infrastructure must converge on the exact tested dependency set.
- **Gate releases on vulnerability scans** — `composer audit` / `npm audit` must be clean or explicitly accepted. *Why:* known-vulnerable dependencies are the cheapest vulnerabilities to fix; the gate makes the fix routine.
- **Enforce a TallstackUI-only UI stack** — no DaisyUI/MaryUI/PHPFlasher remnants. *Why:* one component kit means one toast path, one theme system, and a smaller bundle.

### Non-Goals

- **Runtime service behavior** (drivers, lifetimes, security flags). *Why:* owned by [core-infra-services](ZT6VS-core-infra-services.md); manifest and behavior evolve at different cadences.
- **Logging pipelines and error handling**. *Why:* owned by [logging-and-error-handling](89SRA-logging-and-error-handling.md).
- **Queue and job lifecycle, retries, batches**. *Why:* owned by [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).
- **Real-time WebSocket infrastructure**. *Why:* out of scope per the product definition; broadcasting stays on the log driver by default.
- **GraphQL or REST API layer**. *Why:* the frontend is Livewire-only; no API surface exists to version.
- **Message-queue abstraction beyond Laravel's queue drivers**. *Why:* sync-by-default with optional Redis covers Tier 1 through Tier 3 via `.env` swaps (see FR-STACK-012).

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). Both rows below are verified by
CI and repo inspection rather than application tests, so `Layer`/`Status` are filled at `A`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-STACK-001 | Developer reproduces the tested environment from documented versions and locked install commands | P0 | A | Full |
| UC-STACK-002 | Release gate scans the manifest for vulnerable dependencies and blocks release on critical findings | P0 | A | Full |

### 3.1 Environment & Release

#### UC-STACK-001 — Developer Reproduces the Tested Environment

A technician at an SMK in Cimahi once set up Internara on a refurbished admin PC and spent an afternoon chasing a Livewire error that nobody in Bandung could reproduce, only to discover his Composer had resolved newer minor versions than the tested set. That incident is why reproduction starts from a plain Git checkout with PHP 8.4+, Composer, and Node available and proceeds through two locked commands: `composer install --locked --optimize-autoloader` pulls exactly the versions recorded in `composer.lock`, then `npm ci` rebuilds the locked JS toolchain.

When both steps complete, the machine holds an identical dependency set to every other machine, with no drift to debug. The §6 lockfile contract owns that guarantee, and the full setup flow lives in [installation](8NZAU-installation.md), where a passing locked install is the observable proof the environment matches.

#### UC-STACK-002 — Release Gate Scans for Vulnerable Dependencies

When a release candidate branch is cut, CI loads the manifest and runs `composer audit` and `npm audit` against it before anything is tagged. If either scanner reports a known vulnerability, the gate fails and the release waits while the package is upgraded to a fixed version or the risk is explicitly accepted with its reasoning recorded; the version bump itself lands in the manifest first so the lockfile tells the same story as the release notes. A green gate therefore means the shipped archive carries no known-vulnerable dependency, a property governed jointly by FR-STACK-013 and DD-STACK-003.

---

## 4. Functional Requirements

A manifest entry is a verifiable declaration: the constraint is in `composer.json` /
`package.json`, the exact version is in the lockfile, and CI installs from the lockfile.

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-STACK-001 | PHP `^8.4` is required (readonly properties, enums, fibers used throughout) | P0 | A | Full |
| FR-STACK-002 | `laravel/framework` `^13.0` is required (Livewire 4 integration, Folio routing, Volt) | P0 | A | Full |
| FR-STACK-003 | `livewire/livewire` `^4.0` is required (Livewire::handle(), property binding, polling) | P0 | A | Full |
| FR-STACK-004 | `tailwindcss` `^4.3` is required (v4 `@theme` directive, CSS-first config) | P0 | A | Full |
| FR-STACK-005 | `tallstackui/tallstackui` `^4.0` is required (TALL-stack UI kit); every UI need uses TallstackUI components first | P0 | A | Full |
| FR-STACK-006 | TallstackUI-only rule — custom Blade/Tailwind is allowed only where TallstackUI cannot achieve the design, with the gap documented at the call site | P1 | A | Full |
| FR-STACK-007 | Legacy UI tokens are zero: no `x-mary-*` components, no `flash()->` calls, no `@flasher_render`, no `@plugin daisyui` (DaisyUI/MaryUI/PHPFlasher removed in 0.15.0) | P0 | A | Full |
| FR-STACK-008 | `composer.json` registers every runtime and dev dependency; `composer.lock` is committed and is the install contract | P0 | A | Full |
| FR-STACK-009 | Runtime installs use `composer install --locked --optimize-autoloader` | P0 | A | Full |
| FR-STACK-010 | The JS toolchain (`package.json` + lockfile) is pinned and installed with `npm ci` | P0 | A | Full |
| FR-STACK-011 | A dependency a module uses MUST be declared in the manifest — no undeclared direct packages | P1 | A | Full |
| FR-STACK-012 | Tier-1 defaults run with zero external services: MySQL/MariaDB + file cache + sync queue + database session + local disk; Redis/S3/Reverb are optional `.env` overrides | P0 | A | Full |
| FR-STACK-013 | `composer audit` (and `npm audit`) run in CI and MUST be clean or explicitly accepted before release | P0 | A | Full |
| FR-STACK-014 | `npm run build` MUST succeed without warnings for a production bundle | P1 | A | Full |

### 4.1 Language & Framework Versions

#### FR-STACK-001 — PHP 8.4 floor

A school host that offers only PHP 8.1 once produced a deployment where readonly properties parsed but behaved subtly wrong, and the failure surfaced weeks later as corrupt placement state rather than a clean error. `composer.json` therefore requires `php: ^8.4` outright, and CI installs and tests on PHP 8.4 so the `composer.json` require block and the CI matrix agree as the layer `A` proof. Per [system-requirements](J68GZ-system-requirements.md) such a host is an unsupported environment, which means the install check fails loudly instead of letting the runtime fail quietly.

#### FR-STACK-002 — Laravel 13 floor

Laravel 13 moved middleware registration into `bootstrap/app.php` and tightened queue configuration and migration syntax, so code written against Laravel 11 idioms silently registers nothing on the new bootstrap. The floor exists to end that class of ghost failure: every middleware registration, queue definition, and migration in the codebase follows Laravel 13 conventions, pinned by `laravel/framework ^13.0`. The `composer.json` constraint together with the resolved `composer.lock` entry is the layer `A` evidence, and any drift shows up there first.

#### FR-STACK-003 — Livewire 4 floor

Mixing Livewire 3 shims into a Livewire 4 tree leaves components that mount fine but stop polling under load, and the resulting stale attendance dashboard looks correct while showing yesterday's numbers. Components therefore use only Livewire 4 APIs — `Livewire::handle()`, property binding, polling — with no compatibility shims smuggled in. `composer.json` pins `livewire/livewire ^4.0`, and the component smoke tests in the owning specs confirm the running behavior at layer `A`.

#### FR-STACK-004 — Tailwind CSS v4 floor

During the v3-to-v4 migration at an SMK in Yogyakarta, a teacher's laptop rendered the supervision dashboard with no spacing at all because the old `tailwind.config.js` semantics were silently ignored by the v4 compiler. Styling now uses only the v4 `@theme` directive and CSS-first configuration, so there is no v3 config file for a deploy to misread. The `package.json` pin on `tailwindcss ^4.3` plus a passing `npm run build` is the layer `A` confirmation that the stylesheet compiled under the intended compiler.

#### FR-STACK-005 — TallstackUI v4 kit

When a new dialog or data table is needed, the code reaches for `tallstackui/tallstackui ^4.0` first — `alert`, `toast`, `modal`, `form`, `table`, `badge`, and the rest render as `<x-ts-*>` tags in `resources/views`. That single-kit habit is what keeps the toast path and the theme system unified instead of fragmenting into per-module widgets. The `composer.json` pin proves the kit is present, and a repository search for `x-ts-` usage shows the convention is actually followed at layer `A`.

#### FR-STACK-006 — TallstackUI-only with documented exceptions

A placement table at one school grew a hand-rolled Blade paginator because TallstackUI's table could not yet render merged supervision cells, and within a month two more screens had copied the pattern into a shadow design system. Custom Blade or Tailwind is therefore a fallback rather than a parallel system: it is permitted only where TallstackUI cannot achieve the design, and each fallback carries an inline comment naming the exact gap it works around so the workaround can retire when the kit catches up. Reviewers read those comments alongside the `scan_ui_consistency.py` output as the layer `A` check that no undocumented parallel UI has taken root.

#### FR-STACK-007 — Zero legacy UI tokens

The DaisyUI, MaryUI, and PHPFlasher era ended in 0.15.0, when the manifests dropped the old packages and the great rename swept every `x-mary-*` component and `flash()->` call out of the tree. What remains is enforced absence: searching `resources/` and `app/` for `x-mary` returns nothing, searching `app/` for `flash()->` returns nothing, and no CSS entrypoint carries `@plugin daisyui`. Only the self-hosted palette shims in `app.css` survive, bridging legacy class tokens until the last `x-ts-*` migration lands. CI grep gates hold that zero at layer `A`, so a reintroduced legacy token fails the build instead of quietly coexisting.

### 4.2 Dependency Manifest & Reproducibility

#### FR-STACK-008 — Registered manifest with committed lockfile

Without a committed lockfile, two schools installing the same release get two different dependency trees, and the bug report from one cannot be reproduced on the other. The full runtime and dev tables in §6 declare every allowed package, `composer.lock` pins the exact tested versions, and the lockfile diff is reviewed on every dependency change like any other code. Listing tracked files for a lock entry and running `composer validate --strict` confirms the manifest is well-formed at layer `A`.

#### FR-STACK-009 — Locked installs

An operator at an SMK in Semarang once ran a plain `composer install` after editing a version constraint and unknowingly upgraded three transitive packages, turning a routine deploy into a morning of red attendance pages. The `--locked` flag exists for that moment: when `composer.json` and `composer.lock` disagree, the install fails instead of silently resolving something new. CI runs the exact §6 command, so the layer `A` evidence is simply that the pipeline installs the locked way every time.

#### FR-STACK-010 — Pinned JS toolchain

When `npm install` runs on the frontend tree, it happily floats Vite or the Tailwind plugin forward and the resulting bundle differs from the one QA approved. The toolchain therefore pins `vite`, `laravel-vite-plugin`, `tailwindcss` with `@tailwindcss/vite`, and the formatter plugins in `package.json`, and reproduction always goes through `npm ci`, never `npm install`. CI's frontend step followed by `npm run build` exercises that exact path, which is the layer `A` proof the shipped assets came from the pinned set.

#### FR-STACK-011 — No undeclared direct packages

A module once imported a date helper that happened to arrive transitively through a PDF package, and the next PDF upgrade silently removed it, breaking certificate generation the night before graduation prints. A module that imports a package not listed in `composer.json` or `package.json` carries exactly that defect, and the fix is to declare the dependency rather than lean on the transitive copy. Comparing `composer show --direct` against the manifest during review catches the gap at layer `A`, before an unrelated upgrade turns it into an outage.

### 4.3 Tier Defaults & Release Gates

#### FR-STACK-012 — Tier-1 zero-external-services defaults

The tiering story predates the current hosting guide: early pilots showed small schools stalling because the install assumed Redis and S3 that their $5 shared hosting simply did not have. Per the [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md) and the [performance-optimization ADR](../adr/adr-performance-optimization.md), Tier 1 with up to 500 users needs nothing beyond MySQL or MariaDB plus file cache, sync queue, database sessions, and local disk, and moving to Tier 2 or 3 is a set of `.env` swaps with zero code changes. No feature is disabled in any tier, so the same release runs everywhere. A fresh shared-hosting deploy smoke per the [deployment](../guides/infra/deployment.md) guide is the layer `A` demonstration.

#### FR-STACK-013 — Audit gate

Shipping with a published critical advisory is the cheapest vulnerability to prevent and the most embarrassing to explain to a school principal. The gate blocks the release until the affected package is upgraded or the acceptance is recorded alongside its rationale, so silence can never pass as approval. The CI audit job is the layer `A` witness that the check ran and the manifest it checked was the one being released.

#### FR-STACK-014 — Clean production bundle

At an SMK in Surabaya the production bundle built fine but carried a dozen Vite warnings about unresolved chunks, and the certificate page loaded its styling a full second late on lab PCs. Warnings now fail the frontend gate outright, so the shipped bundle leaves the pipeline warning-free. Running `npm run build` in CI is both the build and the layer `A` test: a warning is a failure, not a footnote.

---

## 5. Non-Functional Requirements

`Target` is the concrete check. All rows are repo/CI-verifiable, so `Layer` is `A`.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-STACK-001 | Lockfiles are committed to the repository | `composer.lock` + JS lockfile in VCS | P0 | A | Full |
| NFR-STACK-002 | No end-of-life (EOL) major dependencies; upgrades planned before EOL | 0 EOL majors in manifest | P1 | A | Full |
| NFR-STACK-003 | Dependency changes land as explicit, reviewable commits — never hidden in feature work | lockfile diff isolated per upgrade | P1 | A | Full |
| NFR-STACK-004 | The manifest matches the environment audit | `composer show` == lockfile | P0 | A | Full |
| NFR-STACK-005 | UI uses TallstackUI components; custom UI only with a documented gap | 0 undocumented custom UI paths | P1 | A | Full |

### 5.1 Manifest Hygiene

#### NFR-STACK-001 — Committed lockfiles

When the build runs, it resolves dependencies from the repository rather than from whatever the registry offers that morning. Both lockfiles — the Composer lock and the JS lock — live in version control, and listing tracked files for a lock entry shows them present, which is the standing proof installs converge.

#### NFR-STACK-002 — No EOL majors

A framework major that goes end-of-life stops receiving security fixes, and a school server running it becomes an unpatchable target during exam season when nobody dares upgrade. The manifest therefore carries zero end-of-life majors, checked through audit output plus an EOL calendar review at each minor release, with the upgrade planned before upstream support ends rather than after an advisory forces it.

#### NFR-STACK-003 — Explicit dependency commits

Dependency upgrades used to hide inside feature commits, so reverting a broken feature also reverted a security fix nobody knew was bundled with it. Upgrades now land as isolated commits touching the lockfiles on their own, and the history of `composer.lock` and `package-lock.json` reads as a clean sequence of deliberate bumps, each reviewable and revertible without collateral.

#### NFR-STACK-004 — Manifest matches audit

If the manifest claims one set of direct dependencies while the installed tree contains another, every audit result is fiction. CI compares `composer show --direct` against `composer.json` on every run, so a package that is installed but undeclared — or declared but never installed — surfaces as a mismatch long before release.

#### NFR-STACK-005 — TallstackUI-only UI

An SMK admin once filed a bug that toasts looked different on the placement page than everywhere else, and the cause was a surviving MaryUI dialog that had escaped the migration. The UI stays TallstackUI-only with zero undocumented custom paths: the FR-STACK-006 and FR-STACK-007 grep gates catch strays mechanically, and review of the documented gaps confirms each remaining fallback still earns its place.

---

## 6. API / Data Contracts

Exact pins — precise enough to install against without asking. Runtime service behavior
(drivers, lifetimes, flags) is contracted in [core-infra-services](ZT6VS-core-infra-services.md).

### 6.1 Composer Runtime Dependencies

| Package | Constraint | Layer |
| ------- | ---------- | ----- |
| `php` | `^8.4` | Language |
| `laravel/framework` | `^13.0` | Framework |
| `livewire/livewire` | `^4.0` | Frontend |
| `tallstackui/tallstackui` | `^4.0` | UI Component (TallstackUI — replaces DaisyUI/MaryUI/PHPFlasher, FR-STACK-005) |
| `barryvdh/laravel-dompdf` | `^3.1` | PDF Generation |
| `laravel-lang/lang` | `^15.26` | Localization |
| `laravel/pulse` | `*` | Monitoring |
| `laravel/tinker` | `^3.0` | REPL |
| `spatie/laravel-activitylog` | `^5.0` | Audit Log |
| `spatie/laravel-medialibrary` | `^11.17` | Media Upload |
| `spatie/laravel-model-status` | `^1.18` | Model Status |
| `spatie/laravel-permission` | `^8.0` | RBAC |

### 6.2 Composer Dev Dependencies

| Package | Constraint | Purpose |
| ------- | ---------- | ------- |
| `pestphp/pest` + `pest-plugin-laravel` | `^4.2` / `^4.0` | Testing |
| `laravel/pint` | `^1.24` | Code Style |
| `mockery/mockery` | `^1.6` | Mocking |
| `fakerphp/faker` | `^1.23` | Test Data |
| `nunomaduro/collision` | `^8.6` | Error Handler |
| `laravel/pail` | `^1.2.2` | Log Viewer |
| `laravel/sail` | `^1.41` | Docker Dev |

### 6.3 JS Toolchain (`package.json`)

| Package | Constraint | Kind |
| ------- | ---------- | ---- |
| `vite` | `^8.1` | Build Tool |
| `laravel-vite-plugin` | `^3.1` | Build Plugin |
| `tailwindcss` + `@tailwindcss/vite` | `^4.3.3` | CSS |
| `flatpickr` | `^4.6.13` | Date Picker |
| `marked` | `^18.0.7` | Markdown Parser |
| `prettier` + `prettier-plugin-blade` + `prettier-plugin-tailwindcss` | `^3.9.6` / `^3.2` / `^0.8.1` | Formatter |
| `concurrently` | `^10.0` | Task Runner |

### 6.4 Lockfile Contract

```bash
# Reproducible install (UC-STACK-001)
composer install --locked --optimize-autoloader
npm ci
```

### 6.5 Tier-1 Default Stack (FR-STACK-012)

| Concern | Tier-1 Default | Tier-2/3 Override |
| ------- | -------------- | ----------------- |
| Database | MySQL/MariaDB (SQLite dev/test) | MySQL + read replica |
| Queue | sync | `QUEUE_CONNECTION=redis` + worker |
| Cache | file | `CACHE_STORE=redis` |
| Session | database | `SESSION_DRIVER=redis` |
| Storage | local disk | S3 optional |
| Broadcasting | log driver (disabled) | Reverb optional |

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). These are recorded decisions,
not test rows, so `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-STACK-001 | Committed lockfiles are the source of truth for exact versions | P0 | — | — |
| DD-STACK-002 | Runtime service behavior split into a dedicated spec | P0 | — | — |
| DD-STACK-003 | Security scans as a release gate | P0 | — | — |
| DD-STACK-004 | DaisyUI/MaryUI/PHPFlasher → TallstackUI migration (COMPLETE 0.15.0) | P0 | — | — |

### 7.1 Manifest & Stack

#### DD-STACK-001 — Committed Lockfiles

When a dependency is installed, Composer and npm consult the committed `composer.lock` and JS package lockfile as the source of truth for exact versions rather than re-resolving constraints. That choice answers PS-1 directly: with schools deploying on hardware ranging from shared hosting to local servers, only pinned lockfiles make every install converge on the tested tree. The price is lockfile churn on every upgrade, contained by routing those bumps through isolated upgrade commits per NFR-STACK-003.

#### DD-STACK-002 — Runtime Services Split into a Dedicated Spec

A version bump ships weekly while a queue-driver semantic changes once a year, and keeping both in one document meant every manifest edit forced a re-read of service behavior. Database, cache, session, queue, mail, and storage behavior therefore moved to [core-infra-services](ZT6VS-core-infra-services.md), leaving this spec to own versions and the manifest. Service topics now span two documents, which the explicit cross-references in both directions are meant to bridge.

#### DD-STACK-003 — Security Scans as a Release Gate

The project learned from watching teams debate whether an advisory was serious enough to delay a release, a debate the vulnerable dependency always won by being ignored. `composer audit` and `npm audit` now gate releases under FR-STACK-013 because known-vulnerable dependencies are the cheapest class of vulnerability to fix, and the gate turns the fix into routine. Occasionally the gate blocks a release on a transitive advisory nobody directly chose, and then the path is an upgrade or a recorded acceptance, never silence.

#### DD-STACK-004 — DaisyUI/MaryUI/PHPFlasher → TallstackUI Migration (COMPLETE 0.15.0)

Running three UI kits at once meant three toast paths, a ballooning bundle, and theme attributes fighting each other through `data-theme`, `fl-dark`, and a legacy `MutationObserver`. The migration therefore ran in four phases: first the spec pinned `tallstackui/tallstackui ^4.0` while marking `daisyui`, `mary`, and `flasher` as deprecated for coexistence, then new components shipped as `<x-ts-*>` alongside the old kits, then each module replaced its `btn`, `card`, `drawer`, and `data-theme` pieces plus every `<x-mary-*>` tag and `flash()->success()` call with TallstackUI equivalents. The 0.15.0 removal deleted the `daisyui` npm package, `robsontenorio/mary`, and `php-flasher/flasher-laravel` from the manifests, removed `config/mary.php` and `config/flasher.php`, stripped `@plugin daisyui` and `@source mary` from `app.css`, and eliminated all `x-mary` and `flash()->` calls until both repository searches returned zero. Only the self-hosted palette and shims in `app.css` remain to bridge legacy class tokens until `x-ts-*` fully replaces them, leaving one toast path through `$this->toast()->send()` and a smaller bundle. Where TallstackUI still has gaps, custom fallbacks survive under FR-STACK-006, each documented at its call site.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Lockfile committed | `composer.lock` + JS lockfile in VCS | `git ls-files \| grep lock` |
| Manifest completeness | 100% of used packages declared | `composer show --direct` vs `composer.json` |
| Vulnerability scan | 0 known vulnerabilities | `composer audit` / `npm audit` |
| Version drift | 0 | `composer install --locked` succeeds in CI |
| Legacy UI tokens | 0 | `grep -R "x-mary"` / `grep -R "flash()->"` |
| Install reproducibility | `composer install --locked` + `npm ci` on fresh checkout | CI job (see [installation](8NZAU-installation.md)) |

---

## 9. Roadmap

### Prerequisites

- [architecture-design](D2FT3-architecture.md) — defines the layer model these dependencies serve

### Build Guide

This spec establishes the technology platform: PHP 8.4, Laravel 13, and every runtime/dev
dependency with pinned versions and committed lockfiles. It is a manifest — dependencies are
added here, versions are bumped here, and every other spec builds on the resulting platform.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [core-infra-services](ZT6VS-core-infra-services.md) | Runtime behavior of the services these packages provide |
| 2 | [base-classes](SE5Q9-base-classes.md) | Action Triad, Entity, DTO, Model, Policy base classes extend the framework |
| 3 | [shared-utilities](C8F0D-shared-utilities.md) | Cross-cutting helpers (AppInfo, Color, PasswordRules) built on PHP/Laravel |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume school hosts provide PHP 8.4 and MySQL/MariaDB per [system-requirements](J68GZ-system-requirements.md); hosts that cannot are unsupported, not degraded | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — the layer model these dependencies serve
- [Core & infrastructure services](ZT6VS-core-infra-services.md) — runtime behavior of these packages
- [Base classes](SE5Q9-base-classes.md) — Action Triad and boundary objects on this platform
- [Shared utilities](C8F0D-shared-utilities.md) — cross-cutting helpers built on this stack
- [System requirements](J68GZ-system-requirements.md) — minimum host environment
- [Installation](8NZAU-installation.md) — reproducible install flow (UC-STACK-001)
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — zero-external-services defaults
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — growth tiers
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements this spec's rows serve
