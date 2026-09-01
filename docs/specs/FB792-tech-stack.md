# Tech Stack — Language, Framework & Dependency Manifest

> **Spec ID:** FB792

## Description

Technology stack and **dependency manifest** for Internara. Defines the minimum PHP version,
framework and package versions, and the lockfile contract that makes deployments reproducible.
Every runtime service built on these dependencies (database, cache, session, queue, mail,
filesystem/storage) is specified in [core-infra-services.md](ZT6VS-core-infra-services.md). The
architectural model these dependencies serve is defined in
[architecture-design.md](D2FT3-architecture.md). Base classes and shared utilities are separate
initiatives — see [base-classes.md](SE5Q9-base-classes.md) and
[shared-utilities.md](C8F0D-shared-utilities.md).

---

## 1. Problem Statements

### PS-1 — Version-Pinned Dependencies

A self-hosted application deployed on diverse school infrastructure (shared hosting, VPS, local
servers) must pin its technology versions to avoid "works on my machine" issues. PHP 8.4 features
(readonly properties, enums, fibers) are used throughout; deploying on PHP 8.1 causes silent
failures. Framework version mismatches cause breaking changes in middleware registration, queue
configuration, and migration syntax. The stack must be documented as the single source of truth
for deployment requirements.

### PS-2 — Dependency Registry

With 25+ Composer packages and a JS toolchain, undeclared or un-pinned dependencies produce
non-reproducible builds. The lockfile must be the contract: `composer install --locked` must yield
the exact tested dependency set, and every package a module uses must be registered in the
manifest (no undeclared direct dependencies).

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Pin PHP 8.4, Laravel 13, Livewire 4, Tailwind CSS v4, TallstackUI v4 as minimum versions (DaisyUI/MaryUI/PHPFlasher removed in 0.15.0) |
| G2  | Complete, registered dependency manifest (Composer runtime + dev, JS toolchain) |
| G3  | Reproducible installs via the committed `composer.lock` (and `package-lock.json`) |
| G4  | Security: dependency vulnerabilities are scanned and resolved before release |
| G5  | TallstackUI-only UI stack (no DaisyUI/MaryUI/PHPFlasher) — shims bridge legacy class tokens until x-ts-* migration completes |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Runtime service behavior (drivers, lifetimes, security flags) — owned by [core-infra-services.md](ZT6VS-core-infra-services.md) |
| NG2  | Logging pipelines and error handling — owned by [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) |
| NG3  | Queue/job lifecycle, retries, batches — owned by [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) |
| NG4  | Real-time WebSocket infrastructure (out of scope per product definition) |
| NG5  | GraphQL or REST API layer (Livewire-only frontend) |
| NG6  | Message queue abstraction beyond Laravel's built-in queue drivers |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Reproduces the Tested Environment

**Actor:** Developer
**Preconditions:** Git checkout, PHP 8.4+, Composer, Node available
**Flow:**
1. Developer runs `composer install --locked --optimize-autoloader`
2. Composer resolves the exact versions recorded in `composer.lock`
3. Developer runs `npm ci` for the locked JS toolchain
4. The resulting environment matches the tested dependency set
**Postconditions:** Identical dependency set on every machine — no version drift

### UC-2 — Release Gate Scans for Vulnerable Dependencies

**Actor:** Developer / CI
**Preconditions:** Release candidate branch
**Flow:**
1. CI runs `composer audit` and `npm audit` against the manifest
2. Any known vulnerability fails the gate until upgraded or accepted
3. Version bumps are recorded here before release
**Postconditions:** No known-vulnerable dependencies in a release

---

## 4. Functional Requirements

### Technology Versions

| ID     | Requirement |
| ------ | ----------- |
| FR-TS1 | PHP >= 8.4 required (readonly properties, enums, fibers used throughout) |
| FR-TS2 | Laravel >= 13.0 required (Livewire 4 integration, Folio routing, Volt) |
| FR-TS3 | Livewire >= 4.0 required (Livewire::handle(), property binding, polling) |
| FR-TS4 | Tailwind CSS >= 4.3 required (v4 `@theme` directive, CSS-first config) |
| FR-TS5 | **REMOVED in 0.15.0** — DaisyUI/MaryUI/PHPFlasher deleted; `x-mary-*`, `flash()->`, `@flasher_render`, `@plugin daisyui` must be 0 |
| FR-TS6 | TallstackUI >= 4.0 required (TALL stack UI kit, `tallstackui/tallstackui`); **always use TallstackUI** |
| FR-TS6a | TallstackUI-only — every UI need must use TallstackUI components (`alert`, `toast`, `modal`, `form`, `table`, `badge`, etc.); custom Blade/Tailwind only if TallstackUI cannot achieve the design |

### Dependency Manifest

| ID     | Requirement |
| ------ | ----------- |
| FR-DEP1 | `composer.json` registers every runtime and dev dependency; `composer.lock` is committed and used for installs |
| FR-DEP2 | Runtime installs use `composer install --locked --optimize-autoloader` |
| FR-DEP3 | The JS toolchain (`package.json` + lockfile) is pinned and installed with `npm ci` |
| FR-DEP4 | A dependency a module uses MUST be declared in the manifest — no undeclared direct packages |
| FR-DEP5 | Package version constraints in `composer.json`/`package.json` are the source of truth for AGENTS.md and install docs |
| FR-DEP6 | Dependency additions/upgrades are recorded in this spec's changelog with their version |

### Verification

| ID     | Requirement |
| ------ | ----------- |
| FR-VER1 | `composer audit` (and `npm audit`) run in CI and must be clean or explicitly accepted |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-DEP1 | Lockfiles (`composer.lock`, package lockfile) are committed to the repository |
| NFR-DEP2 | No end-of-life (EOL) major dependencies; upgrades planned before EOL |
| NFR-DEP3 | Dependency changes land as explicit, reviewable commits — never hidden in feature work |
| NFR-DEP4 | The manifest matches the environment audit (`composer show` = lockfile) |
| NFR-DEP5 | TallstackUI-only: UI must use TallstackUI components; custom only with documented gap (maryUI/DaisyUI removed) |

---

## 6. API / Data Contracts

### Composer Runtime Dependencies

| Package | Constraint | Layer |
| ------- | ---------- | ----- |
| `php` | `^8.4` | Language |
| `laravel/framework` | `^13.0` | Framework |
| `livewire/livewire` | `^4.0` | Frontend |
| `tallstackui/tallstackui` | `^4.0` | UI Component (TallstackUI — replaces DaisyUI/MaryUI/PHPFlasher, FR-TS6) |
| `barryvdh/laravel-dompdf` | `^3.1` | PDF Generation |
| `laravel-lang/lang` | `^15.26` | Localization |
| `laravel/pulse` | `*` | Monitoring |
| `laravel/tinker` | `^3.0` | REPL |
| `spatie/laravel-activitylog` | `^5.0` | Audit Log |
| `spatie/laravel-medialibrary` | `^11.17` | Media Upload |
| `spatie/laravel-model-status` | `^1.18` | Model Status |
| `spatie/laravel-permission` | `^8.0` | RBAC |

### Composer Dev Dependencies

| Package | Constraint | Purpose |
| ------- | ---------- | ------- |
| `pestphp/pest` + `pest-plugin-laravel` | `^4.2` / `^4.0` | Testing |
| `laravel/pint` | `^1.24` | Code Style |
| `mockery/mockery` | `^1.6` | Mocking |
| `fakerphp/faker` | `^1.23` | Test Data |
| `nunomaduro/collision` | `^8.6` | Error Handler |
| `laravel/pail` | `^1.2.2` | Log Viewer |
| `laravel/sail` | `^1.41` | Docker Dev |

### JS Toolchain (`package.json`)

| Package | Constraint | Kind |
| ------- | ---------- | ---- |
| `vite` | `^8.1` | Build Tool |
| `laravel-vite-plugin` | `^3.1` | Build Plugin |
| `tailwindcss` + `@tailwindcss/vite` | `^4.3.3` | CSS |
| `flatpickr` | `^4.6.13` | Date Picker |
| `marked` | `^18.0.7` | Markdown Parser |
| `prettier` + `prettier-plugin-blade` + `prettier-plugin-tailwindcss` | `^3.9.6` / `^3.2` / `^0.8.1` | Formatter |
| `concurrently` | `^10.0` | Task Runner |

### Lockfile Contract

```bash
# Reproducible install (UC-1)
composer install --locked --optimize-autoloader
npm ci
```

---

## 7. Design Decisions

### DD-1 — Committed Lockfiles

**Decision:** `composer.lock` and the JS package lockfile are committed to the repository and are
the source of truth for exact versions.
**Rationale:** Reproducible installs across the school's heterogeneous infrastructure (PS-1).
**Trade-off:** Lockfile churn on upgrades — managed through FR-DEP3/FR-DEP6.

### DD-2 — Runtime Services Split into a Dedicated Spec

**Decision:** Database/cache/session/queue/mail/storage behavior moved to
[core-infra-services.md](ZT6VS-core-infra-services.md); this spec keeps versions and manifest.
**Rationale:** A dependency manifest and a service-behavior contract evolve at different cadences
and serve different readers (PS-2).
**Trade-off:** Service topics now span two specs — mitigated by explicit cross-references
(DD-8 in [core-infra-services.md](ZT6VS-core-infra-services.md)).

### DD-3 — Security Scans as a Release Gate

**Decision:** `composer audit` / `npm audit` gate releases (FR-VER1).
**Rationale:** Known-vulnerable dependencies are the cheapest class of vulnerability to fix; the
gate makes it routine.
**Trade-off:** Occasionally blocks a release on a transitive advisory — resolved via upgrade or a
recorded acceptance.

### DD-4 — DaisyUI/MaryUI/PHPFlasher → TallstackUI Migration (COMPLETE 0.15.0)

**Decision:** UI stack migrated from DaisyUI v5 + MaryUI v2 + `php-flasher` to TallstackUI v4 (TallstackUI-only since 0.15.0; migration complete).
**History:**
1. **Spec & Docs:** Pinned `tallstackui/tallstackui ^4.0`, marked `daisyui`/`mary`/`flasher` as DEPRECATED (coexistence).
2. **Coexistence:** TallstackUI alongside DaisyUI/MaryUI/PHPFlasher; new components used `<x-ts-*>`.
3. **Replacement:** Per-module, replaced `btn`/`card`/`drawer`/`data-theme`, `<x-mary-*>`, `flash()->success()` with TallstackUI.
4. **Removal (0.15.0):** Deleted `daisyui` npm, `robsontenorio/mary`, `php-flasher/flasher-laravel` from manifests, removed `config/mary.php`/`config/flasher.php`, `@plugin daisyui`/`@source mary` from `app.css`, and all `x-mary`/`flash()->` calls (verified `grep -R x-mary` = 0, `grep -R flash()->` = 0). Self-hosted palette + shims in `app.css` bridge remaining 169 legacy class tokens until `x-ts-*` fully replaces them.
**Rationale:** TallstackUI-only reduces bundle size, removes `data-theme`/`fl-dark` / `MutationObserver` legacy, and leaves one toast path (`$this->toast()->send()`).

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Lockfile committed | `composer.lock` + JS lockfile in VCS | `git ls-files | grep lock` |
| Manifest completeness | 100% of used packages declared | `composer show --direct` vs `composer.json` |
| Vulnerability scan | 0 known vulnerabilities | `composer audit` / `npm audit` |
| Version drift | 0 | `composer install --locked` succeeds in CI |
| Install reproducibility | `composer install --locked` + `npm ci` on fresh checkout | CI job (see [installation.md](8NZAU-installation.md)) |

---

## 9. Roadmap

### Prerequisites

- [architecture-design.md](D2FT3-architecture.md) — defines the layer model these dependencies serve

### Build Guide

This spec establishes the technology platform: PHP 8.4, Laravel 13, and every runtime/dev
dependency with pinned versions and committed lockfiles. It is a manifest — dependencies are
added here, versions are bumped here, and every other spec builds on the resulting platform.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [core-infra-services.md](ZT6VS-core-infra-services.md) | Runtime behavior of the services these packages provide |
| 2 | [base-classes.md](SE5Q9-base-classes.md) | Action Triad, Entity, DTO, Model, Policy base classes extend framework |
| 3 | [shared-utilities.md](C8F0D-shared-utilities.md) | Cross-cutting helpers (AppInfo, Color, PasswordRules) built on PHP/Laravel |

---

## Quick References

- `composer.json` — Composer package and version constraints
- `composer.lock` — Exact resolved dependency versions
- `package.json` — JS toolchain versions
- `.env.example` — Template environment variables
- `docs/architecture.md` — Layer model these dependencies serve
- **Related specs:** [architecture-design.md](D2FT3-architecture.md) — layer model; [core-infra-services.md](ZT6VS-core-infra-services.md) — runtime service behavior; [base-classes.md](SE5Q9-base-classes.md) — base classes; [shared-utilities.md](C8F0D-shared-utilities.md) — utility classes; [system-requirements.md](J68GZ-system-requirements.md) — platform dependencies and details
