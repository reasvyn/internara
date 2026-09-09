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

**Actor:** Developer
**Preconditions:** Git checkout, PHP 8.4+, Composer, Node available.
**Flow:**
1. Developer runs `composer install --locked --optimize-autoloader`
2. Composer resolves the exact versions recorded in `composer.lock`
3. Developer runs `npm ci` for the locked JS toolchain
4. The resulting environment matches the tested dependency set
**Postconditions:** Identical dependency set on every machine — no version drift.
**Governing guidance:** §6 lockfile contract; [installation](8NZAU-installation.md) for the full setup flow.

#### UC-STACK-002 — Release Gate Scans for Vulnerable Dependencies

**Actor:** Developer / CI
**Preconditions:** Release candidate branch.
**Flow:**
1. CI runs `composer audit` and `npm audit` against the manifest
2. Any known vulnerability fails the gate until upgraded or explicitly accepted
3. Version bumps are recorded in the manifest before release
**Postconditions:** No known-vulnerable dependencies in a release.
**Governing guidance:** FR-STACK-013, DD-STACK-003.

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

- `composer.json` requires `php: ^8.4`; CI installs and tests on PHP 8.4.
- **Edge case:** a host offering only PHP 8.1/8.3 is an unsupported environment per [system-requirements](J68GZ-system-requirements.md) — fail the install check, not the runtime.
- **Verification:** `composer.json` require block + CI matrix (layer `A`).

#### FR-STACK-002 — Laravel 13 floor

- Middleware registration (`bootstrap/app.php`), queue configuration, and migration syntax follow Laravel 13 conventions.
- **Verification:** `composer.json` + `composer.lock` (layer `A`).

#### FR-STACK-003 — Livewire 4 floor

- Components use Livewire 4 APIs (`Livewire::handle()`, property binding, polling); no Livewire 3 shims.
- **Verification:** `composer.json` + component smoke tests in owning specs (layer `A`).

#### FR-STACK-004 — Tailwind CSS v4 floor

- Styling uses the v4 `@theme` directive and CSS-first config; no v3 `tailwind.config.js` semantics.
- **Verification:** `package.json` + `npm run build` (layer `A`).

#### FR-STACK-005 — TallstackUI v4 kit

- `tallstackui/tallstackui: ^4.0` is the UI component kit (`alert`, `toast`, `modal`, `form`, `table`, `badge`, …); new components use `<x-ts-*>`.
- **Verification:** `composer.json` + `grep -R "x-ts-" resources/views` (layer `A`).

#### FR-STACK-006 — TallstackUI-only with documented exceptions

- Custom Blade/Tailwind is a fallback, not a parallel system; each fallback carries a comment naming the TallstackUI gap it works around.
- **Verification:** review gate + `scan_ui_consistency.py` (layer `A`).

#### FR-STACK-007 — Zero legacy UI tokens

- `grep -R "x-mary" resources/ app/` returns 0; `grep -R "flash()->" app/` returns 0; no `@plugin daisyui` in CSS entrypoints. Self-hosted palette shims in `app.css` bridge remaining legacy class tokens until `x-ts-*` migration completes.
- **Verification:** grep gates in CI (layer `A`).

### 4.2 Dependency Manifest & Reproducibility

#### FR-STACK-008 — Registered manifest with committed lockfile

- Full runtime/dev tables in §6; the lockfile diff is reviewed on every dependency change.
- **Verification:** `git ls-files | grep lock` + `composer validate --strict` (layer `A`).

#### FR-STACK-009 — Locked installs

- `--locked` fails the install when `composer.json` and `composer.lock` disagree, instead of silently resolving.
- **Verification:** CI install step uses the exact §6 command (layer `A`).

#### FR-STACK-010 — Pinned JS toolchain

- `vite`, `laravel-vite-plugin`, `tailwindcss` + `@tailwindcss/vite`, and formatter plugins are pinned in `package.json`; `npm ci` (never `npm install`) reproduces the toolchain.
- **Verification:** CI frontend step + `npm run build` (layer `A`).

#### FR-STACK-011 — No undeclared direct packages

- A module importing a package not listed in `composer.json`/`package.json` is a manifest defect, fixed by declaring the dependency — not by relying on a transitive copy.
- **Verification:** `composer show --direct` vs manifest review (layer `A`).

### 4.3 Tier Defaults & Release Gates

#### FR-STACK-012 — Tier-1 zero-external-services defaults

- Per [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md) and [performance-optimization ADR](../adr/adr-performance-optimization.md): Tier 1 (shared hosting, ≤500 users) needs nothing beyond MySQL/MariaDB; Tier 2/3 transitions are `.env` swaps with zero code changes. No feature is disabled in any tier.
- **Verification:** fresh shared-hosting deploy smoke per [deployment](../guides/infra/deployment.md) (layer `A`).

#### FR-STACK-013 — Audit gate

- A critical advisory blocks the release until the package is upgraded or the acceptance is recorded with its rationale.
- **Verification:** CI audit job (layer `A`).

#### FR-STACK-014 — Clean production bundle

- Warnings fail the frontend gate; the shipped bundle is warning-free.
- **Verification:** `npm run build` in CI (layer `A`).

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

- **Verification:** `git ls-files | grep lock` lists both lockfiles.

#### NFR-STACK-002 — No EOL majors

- **Verification:** audit output plus EOL calendar review at each minor release; upgrade planned before upstream EOL.

#### NFR-STACK-003 — Explicit dependency commits

- **Verification:** `git log -- composer.lock package-lock.json` shows isolated upgrade commits.

#### NFR-STACK-004 — Manifest matches audit

- **Verification:** `composer show --direct` compared against `composer.json` in CI.

#### NFR-STACK-005 — TallstackUI-only UI

- **Verification:** FR-STACK-006/007 grep gates plus review of documented gaps.

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

**Decision:** `composer.lock` and the JS package lockfile are committed to the repository and are
the source of truth for exact versions.
**Rationale:** Reproducible installs across the school's heterogeneous infrastructure (PS-1).
**Trade-off:** Lockfile churn on upgrades — managed through isolated upgrade commits (NFR-STACK-003).

#### DD-STACK-002 — Runtime Services Split into a Dedicated Spec

**Decision:** Database/cache/session/queue/mail/storage behavior moved to
[core-infra-services](ZT6VS-core-infra-services.md); this spec keeps versions and manifest.
**Rationale:** A dependency manifest and a service-behavior contract evolve at different cadences
and serve different readers (PS-2).
**Trade-off:** Service topics now span two specs — mitigated by explicit cross-references in both directions.

#### DD-STACK-003 — Security Scans as a Release Gate

**Decision:** `composer audit` / `npm audit` gate releases (FR-STACK-013).
**Rationale:** Known-vulnerable dependencies are the cheapest class of vulnerability to fix; the
gate makes it routine.
**Trade-off:** Occasionally blocks a release on a transitive advisory — resolved via upgrade or a
recorded acceptance.

#### DD-STACK-004 — DaisyUI/MaryUI/PHPFlasher → TallstackUI Migration (COMPLETE 0.15.0)

**Decision:** UI stack migrated from DaisyUI v5 + MaryUI v2 + `php-flasher` to TallstackUI v4 (TallstackUI-only since 0.15.0; migration complete).
**History:**
1. **Spec & Docs:** Pinned `tallstackui/tallstackui ^4.0`, marked `daisyui`/`mary`/`flasher` as DEPRECATED (coexistence).
2. **Coexistence:** TallstackUI alongside DaisyUI/MaryUI/PHPFlasher; new components used `<x-ts-*>`.
3. **Replacement:** Per-module, replaced `btn`/`card`/`drawer`/`data-theme`, `<x-mary-*>`, `flash()->success()` with TallstackUI.
4. **Removal (0.15.0):** Deleted `daisyui` npm, `robsontenorio/mary`, `php-flasher/flasher-laravel` from manifests, removed `config/mary.php`/`config/flasher.php`, `@plugin daisyui`/`@source mary` from `app.css`, and all `x-mary`/`flash()->` calls (verified `grep -R x-mary` = 0, `grep -R flash()->` = 0). Self-hosted palette + shims in `app.css` bridge remaining legacy class tokens until `x-ts-*` fully replaces them.
**Rationale:** TallstackUI-only reduces bundle size, removes `data-theme`/`fl-dark` / `MutationObserver` legacy, and leaves one toast path (`$this->toast()->send()`).
**Trade-off:** Custom fallbacks remain where TallstackUI has gaps (FR-STACK-006) — each documented at its call site.

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
