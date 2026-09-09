# Module Discovery — Feature Specification

> **Spec ID:** I1BCV
> **Status:** Full
> **Owner:** Core
> **Depends on:** FB792, SE5Q9

## Description

Defines Internara's centralized module registry and runtime discovery system: the frozen
19-module roster, the single-source-of-truth config in `config/module.php`, boot-time
auto-discovery of Livewire components, policies, and Blade namespaces, route auto-inclusion,
and test-directory registration. Adding a capability means creating directories, not editing
wiring — the registry discovers, the config filters, and the cache keeps boot fast.

The runtime discovery API (`ModuleService`, `ModuleManager`) is implemented under the
[module-manager](B114U-module-manager.md) spec (B114U) — this spec remains the source of truth
for the roster contract and discovery conventions. Registry order also feeds the dependency
graph in [modules/index.md](../refs/modules/index.md) and the colocation rules in the
[architecture spec](D2FT3-architecture.md) (FR-ARC-005, FR-ARC-031).

---

## 1. Problem Statements

### PS-1 — Single Source of Truth

Without a centralized registry, module lists duplicate across `config/module.php`,
`tests/Pest.php`, and `routes/web.php`. Adding a module means editing 3+ files, risking
inconsistencies where a module exists in one list but not the others.
**→ Requirement:** FR-MOD-003 (registry contract), FR-MOD-038/039 (Pest sync).

### PS-2 — Discovery Performance

Scanning the entire `app/` tree for Livewire components and policies on every boot is
expensive. Discovery must scope itself to registered modules only, with results cached.
**→ Requirement:** FR-MOD-017/023/029 (24-hour caches), FR-MOD-013/019/025 (registered-only scans).

### PS-3 — Config-Driven Filtering

Only registered modules may be scanned. Unregistered directories (draft modules, test helpers)
must never leak into discovery, route auto-inclusion, or view namespaces.
**→ Requirement:** FR-MOD-013 (scan only registered), FR-MOD-031 (route inclusion by registry),
NFR-MOD-007 (no unregistered classes).

### PS-4 — Test Directory Registration

Pest discovers test directories at boot time, before `config()` is available. Module test
directories must be registered in `tests/Pest.php` — a second list that has to stay
synchronized with the config, with the sync documented where drift would hide.
**→ Requirement:** FR-MOD-038–041, NFR-MOD-004 (sync comment).

### PS-5 — Roster Drift

Module renames cascade into Livewire aliases, routes, policies, config, tests, and docs. Without
a frozen roster and an amendment process, a casual rename silently breaks half the system.
**→ Requirement:** FR-MOD-001 (frozen roster), FR-MOD-002 (amendment process).

---

## 2. Goals & Non-Goals

### Goals

- **`config/module.php` as the single source of truth** — one registry drives discovery, routes, and tests. *Why:* one list to trust eliminates the 3-file inconsistency class from PS-1.
- **Boot-time auto-discovery** — Livewire components, policies, and Blade namespaces register themselves. *Why:* adding a component means creating a file, not editing wiring; mechanical module addition per the action-based-MVC ADR.
- **Config-driven filtering** — only registered modules are scanned. *Why:* draft and helper directories stay invisible to the runtime (PS-3).
- **Cached discovery** — results cached with a 24-hour TTL, busted on demand. *Why:* boot stays fast in production while development refreshes explicitly (PS-2).
- **Route auto-inclusion** — route files load by registry convention. *Why:* no manual `require` edits when a module gains routes.
- **Explicit cache clearing** — `module:discover` refreshes every discovery cache. *Why:* one command restores a known-good discovery state after structural changes.

### Non-Goals

- **Runtime module hot-loading (discovery runs at boot/cache-clear only)**. *Why:* school-scale deploys restart cleanly; hot-loading adds invalidation complexity with no product need.
- **Cross-module dependency resolution**. *Why:* modules are independent vertical slices; ordering comes from the registry, not a resolver.
- **Auto-discovery of Entity, DTO, Action, or Model classes**. *Why:* these resolve through namespaces, not registration — only framework-facing surfaces (Livewire, policies, views, routes) need discovery.
- **Module enable/disable at runtime**. *Why:* all registered modules are active; feature-flagging is a separate concern.
- **Refactoring `tests/Pest.php` to use `config()`**. *Why:* impossible — Pest boots before Laravel, so the hardcoded list plus sync comment is the final design (DD-MOD-001).

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` stay `—`
here: these developer and system workflows are procedural (create directory, run command), and
their code-testable consequences live on the FR rows they exercise.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-MOD-001 | Developer adds a new module and its surfaces are discovered on next boot | P0 | — | — |
| UC-MOD-002 | Developer adds a submodule to an existing module with prefixed aliases | P1 | — | — |
| UC-MOD-003 | System discovers Livewire components, policies, and view namespaces at boot | P0 | — | — |
| UC-MOD-004 | System auto-includes module route files by registry convention | P0 | — | — |
| UC-MOD-005 | Developer clears discovery caches and rediscovers via CLI | P1 | — | — |
| UC-MOD-006 | Developer disables discovery for one subsystem via config | P2 | — | — |

### 3.1 Developer Workflows

#### UC-MOD-001 — Adding a New Module

**Actor:** Developer.
**Preconditions:** None — greenfield addition.
**Flow:**
1. Create the module directory under `app/Modules/{Module}/` with standard layers
2. Confirm the registry picks it up (`config/module.php` auto-discovers from the filesystem; the frozen roster in FR-MOD-001 still governs — a genuinely *new* name needs a spec amendment first)
3. Add the test directory name to the module list in `tests/Pest.php` (alphabetical order)
4. Create the route file at `routes/web/{lowercase_module}.php` (optional)
5. Run `php artisan module:discover` to clear caches and verify registration
**Postconditions:** The module's Livewire components, policies, and Blade views are auto-discovered on next boot.
**Exercises:** FR-MOD-001–003, FR-MOD-034, FR-MOD-038.

#### UC-MOD-002 — Adding a Submodule to an Existing Module

**Actor:** Developer.
**Preconditions:** Parent module exists and is registered.
**Flow:**
1. Create the submodule directory under `app/Modules/{Module}/Domain/{Submodule}/`
2. Run `php artisan module:discover`
**Postconditions:** Submodule Livewire components and policies are discovered with the kebab-case submodule prefix in the alias (e.g., `enrollment.placement.show`).
**Exercises:** FR-MOD-014, FR-MOD-020.

#### UC-MOD-005 — Cache Clearing and Rediscovery

**Actor:** Developer via CLI.
**Preconditions:** Structural change (new component, policy, view namespace, or module).
**Flow:**
1. Developer runs `php artisan module:discover`
2. The command resolves `ModuleService` from the container
3. Runs `discoverLivewireComponents()`, `discoverPolicies()`, `registerBladeNamespaces()`
4. Each method overwrites its cache entry; the command exits `0` and logs completion via SmartLogger
**Postconditions:** All discovery caches are refreshed.
**Exercises:** FR-MOD-034–037.

#### UC-MOD-006 — Disabling Discovery for a Subsystem

**Actor:** Developer (rare — testing or partial setups).
**Preconditions:** None.
**Flow:**
1. Set `module.livewire.enabled = false` (or `policies.enabled`, `views.enabled`) in config
2. `AppServiceProvider` skips that discovery method
**Postconditions:** That subsystem's discovery is skipped; everything else discovers normally.
**Exercises:** FR-MOD-010.

### 3.2 System Flows

#### UC-MOD-003 — App Boot Discovery

**Actor:** Laravel framework (automatic).
**Preconditions:** Application booting; caches warm or cold.
**Flow:**
1. `AppServiceProvider::boot()` fires
2. If `ModuleManager::policiesEnabled()`, runs `ModuleService::discoverPolicies()`
3. If `ModuleManager::livewireEnabled()`, runs `ModuleService::discoverLivewireComponents()`
4. If `ModuleManager::viewsEnabled()`, runs `ModuleService::registerBladeNamespaces()`
5. Each method reads `ModuleManager::names()`, scans only registered module directories, caches results for 24 hours
**Postconditions:** All Livewire aliases registered, all policies bound, all Blade namespaces available.
**Exercises:** FR-MOD-011–029.

#### UC-MOD-004 — Route Auto-Inclusion

**Actor:** Laravel router (automatic).
**Preconditions:** Routes loading.
**Flow:**
1. `routes/web.php` loads `ModuleManager::names()`
2. For each module, resolves `ModuleManager::routeFilePath($module)`
3. Existing files are `require`d; missing files are silently skipped
**Postconditions:** Module routes are available with no manual edits to `routes/web.php`.
**Exercises:** FR-MOD-030–033.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-MOD-001 | The module roster is frozen to exactly 19 modules — `Core`, `UI`, `Auth`, `User`, `SysAdmin`, `Setup`, `Settings`, `Academics`, `Program`, `Enrollment`, `Assessment`, `Evaluation`, `Assignment`, `Journals`, `Incident`, `Partners`, `Certification`, `Reports`, `Document` — no module may be added, removed, or renamed without amendment | P0 | A | Full |
| FR-MOD-002 | Any rename, addition, or removal passes through a spec amendment (governing-spec update + ADR) and registry sync (`config/module.php`, `tests/Pest.php`, `docs/refs/modules/index.md`) before any code is touched | P0 | A | Full |
| FR-MOD-003 | `config/module.php` is auto-discovered from the `app/Modules/` filesystem listing and exposes the module → domain mapping as the single source of truth | P0 | A | Full |
| FR-MOD-004 | Module names are PascalCase (e.g., `Core`, `Enrollment`, `SysAdmin`) | P0 | A | Full |
| FR-MOD-005 | Registry order is deterministic (alphabetical from discovery; dependency order documented in `docs/refs/modules/index.md`) | P1 | A | Full |
| FR-MOD-006 | Config exports a `list` key (array of module names) | P0 | A | Full |
| FR-MOD-007 | Config exports a `registry` key (full module → domain mapping) | P0 | A | Full |
| FR-MOD-008 | Config exports a `test_dirs` key (non-module test directories) | P1 | A | Full |
| FR-MOD-009 | Config defines `paths.base`, `paths.views`, `paths.routes` | P0 | A | Full |
| FR-MOD-010 | Config defines `livewire`, `policies`, `views` discovery settings including per-subsystem `enabled` flags | P0 | A | Full |
| FR-MOD-011 | Discovery scans `app/Modules/{Module}/Domain/*/Livewire/**/*.php` for each registered module | P0 | A | Full |
| FR-MOD-012 | Discovery skips `Concerns/` and `Traits/` subdirectories | P0 | A | Full |
| FR-MOD-013 | Discovered components register with the `{kebab-module}.{kebab-class}` alias | P0 | A | Full |
| FR-MOD-014 | Submodule components use the `{kebab-module}.{kebab-submodule}.{kebab-class}` alias | P0 | A | Full |
| FR-MOD-015 | Only classes extending `Livewire\Component` are registered | P0 | A | Full |
| FR-MOD-016 | Only PHP files inside registered modules are scanned | P0 | A | Full |
| FR-MOD-017 | Livewire discovery results cache for 24 hours under `module.discovered_livewire` | P1 | A | Full |
| FR-MOD-018 | Discovery scans `app/Modules/{Module}/Domain/*/Policies/**/*.php` for each registered module | P0 | A | Full |
| FR-MOD-019 | Discovery skips `Concerns/` and `Traits/` subdirectories | P0 | A | Full |
| FR-MOD-020 | Only classes ending in `Policy` and extending `BasePolicy` are registered | P0 | A | Full |
| FR-MOD-021 | Each policy binds to its corresponding model in the module's `Models/` | P0 | A | Full |
| FR-MOD-022 | Submodule policies bind to the submodule's `Models/` | P0 | A | Full |
| FR-MOD-023 | Policy discovery results cache for 24 hours under `module.discovered_policies` | P1 | A | Full |
| FR-MOD-024 | Cross-module policies are registered manually in `AppServiceProvider` | P1 | A | Full |
| FR-MOD-025 | Discovery scans `resources/views/{Module}/` for each registered module | P0 | A | Full |
| FR-MOD-026 | Non-module directories are excluded: `components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor` | P0 | A | Full |
| FR-MOD-027 | Registered modules are added as anonymous-component paths and view namespaces | P0 | A | Full |
| FR-MOD-028 | Only directories of registered modules are registered | P0 | A | Full |
| FR-MOD-029 | View discovery results cache for 24 hours under `module.discovered_views` | P1 | A | Full |
| FR-MOD-030 | `routes/web.php` auto-includes route files from `ModuleManager::names()` | P0 | A | Full |
| FR-MOD-031 | Route file path is `routes/web/{lowercase_module}.php` via `ModuleManager::routeFilePath()` | P0 | A | Full |
| FR-MOD-032 | Non-existent route files are silently skipped | P0 | A | Full |
| FR-MOD-033 | Module names are lowercased for file lookup | P0 | A | Full |
| FR-MOD-034 | `php artisan module:discover` clears all three discovery caches and rediscovers | P0 | F | Full |
| FR-MOD-035 | The command verifies `AppServiceProvider` is loaded before discovery | P1 | F | Full |
| FR-MOD-036 | The command logs completion or failure via SmartLogger | P1 | F | Full |
| FR-MOD-037 | The command shows task progress with translated status messages | P2 | F | Full |
| FR-MOD-038 | `tests/Pest.php` registers test directories for all modules | P0 | A | Full |
| FR-MOD-039 | The module list in `tests/Pest.php` stays in sync with `config/module.php` | P0 | A | Full |
| FR-MOD-040 | Non-module test directories (`Providers`, `Stubs`, `Support`) are also registered | P1 | A | Full |
| FR-MOD-041 | `config()` is never used in `tests/Pest.php` — Pest boots before Laravel | P0 | A | Full |

### 4.1 Frozen Roster

#### FR-MOD-001 — Locked roster of 19

- The on-disk `app/Modules/` listing matches this roster exactly — verified during this rewrite (19 directories, names identical). Renaming cascades into Livewire aliases, routes, policies, config, tests, and docs, so the roster is treated as immutable.
- **Verification:** directory listing vs roster (layer `A`); full list in §6.1.

#### FR-MOD-002 — Amendment before rename

- Process requirement enforced at review: a module-name diff without a linked spec amendment + ADR is rejected. Registry sync covers `config/module.php`, `tests/Pest.php`, and `docs/refs/modules/index.md`.
- **Verification:** review gate (layer `A`).

### 4.2 Registry Contract

#### FR-MOD-003 — Filesystem-derived single source

- No hand-maintained module list: `config/module.php` scans `app/Modules/` (PascalCase directories with a `Domain/` subtree) and derives the mapping. Consumers (`ModuleManager`, `ModuleService`, `routes/web.php`, `tests/Pest.php` docs) read the derived keys — never re-list directories themselves.
- **Edge case:** a stray non-module directory under `app/Modules/` is ignored by the PascalCase + `Domain/` guard.
- **Verification:** config review + boot smoke (layer `A`).

#### FR-MOD-004 — PascalCase names

- Directory name is the module name verbatim; lowercase/kebab variants derive by convention (routes, aliases) but never rename the source.
- **Verification:** `scan_naming.py` (layer `A`).

#### FR-MOD-005 — Deterministic order

- `ksort`ed discovery output keeps boot deterministic; the *dependency* order (foundation → lifecycle → administration) is documented in the module graph, not re-encoded in config.
- **Verification:** config review (layer `A`).

#### FR-MOD-006 — `list` key

- `array_keys` of the mapping — the flat name list `ModuleManager::names()` serves.
- **Verification:** tinker/config assertion (layer `A`).

#### FR-MOD-007 — `registry` key

- Full `Module → [domains]` map; submodule-aware discovery (FR-MOD-014/020) reads it.
- **Verification:** config assertion (layer `A`).

#### FR-MOD-008 — `test_dirs` key

- Non-module test directories (`Providers`, `Stubs`, `Support`) registered alongside modules for the Pest side (FR-MOD-040).
- **Verification:** config + `tests/Pest.php` review (layer `A`).

#### FR-MOD-009 — Path keys

- `paths.base` (`app_path()`), `paths.views` (`resource_path('views')`), `paths.routes` (`base_path('routes/web')`) — discovery never hardcodes a path. Full structure in §6.2.
- **Verification:** config review (layer `A`).

#### FR-MOD-010 — Subsystem settings

- Each subsystem (`livewire`, `policies`, `views`) carries `enabled`, its directory name, and exclusions — UC-MOD-006 toggles these.
- **Verification:** config review + disabled-subsystem boot test (layer `A`).

### 4.3 Livewire Component Discovery

#### FR-MOD-011 — Registered-module scan

- Scan roots derive from `ModuleManager::names()` — never a hardcoded path list. (Path shape reflects the `Domain/` layout; the convention, not the depth, is normative.)
- **Verification:** `ModuleService` review + boot smoke (layer `A`).

#### FR-MOD-012 — Concern/trait exclusion

- `Concerns/` and `Traits/` hold shared behavior, not components — registering them would create bogus aliases.
- **Verification:** boot smoke asserting no `*.concerns.*` aliases (layer `A`).

#### FR-MOD-013 — Two-part alias

- `Auth/Livewire/LoginForm.php` → `auth.login-form`. Convention table in §6.4.
- **Verification:** alias assertion after discovery (layer `A`).

#### FR-MOD-014 — Three-part submodule alias

- `Enrollment/…/Placement/…/Show.php` → `enrollment.placement.show` — prevents collisions between submodules (`enrollment.placement.show` vs `enrollment.registration.show`).
- **Verification:** alias assertion with two same-named submodule components (layer `A`).

#### FR-MOD-015 — Component superclass gate

- Plain helpers living under `Livewire/` are skipped; only `Livewire\Component` subclasses register.
- **Verification:** discovery review + alias-list assertion (layer `A`).

#### FR-MOD-016 — Registry-bounded scan

- Unregistered directories are never entered, even if they contain valid components (PS-3).
- **Verification:** fixture unregistered directory asserting zero aliases from it (layer `A`).

#### FR-MOD-017 — 24-hour Livewire cache

- Key `module.discovered_livewire` (registered in `config/cache-keys.php`), TTL 86400; busted by `module:discover` / `config:clear`. Rationale in DD-MOD-004; key table in §6.6.
- **Verification:** cache assertion after discovery (layer `A`).

### 4.4 Policy Discovery

#### FR-MOD-018 — Registered-module policy scan

- Same registry-bounded roots as Livewire, under `Policies/`.
- **Verification:** `ModuleService` review + boot smoke (layer `A`).

#### FR-MOD-019 — Concern/trait exclusion

- Shared authorization helpers under `Concerns/`/`Traits/` are not policies.
- **Verification:** binding-list assertion (layer `A`).

#### FR-MOD-020 — Policy shape gate

- Suffix `Policy` + `extends BasePolicy` (per the base-class mandate) — anything else is skipped, never bound.
- **Verification:** binding assertion + `scan_class_contracts.py` (layer `A`).

#### FR-MOD-021 — Model binding

- `Module/Policies/XPolicy.php` → `Module/Models/X`. Convention table in §6.5.
- **Verification:** `Gate` binding assertion per module (layer `A`).

#### FR-MOD-022 — Submodule model binding

- `Module/Submodule/Policies/XPolicy.php` → `Module/Submodule/Models/X`.
- **Verification:** binding assertion for a submodule policy (layer `A`).

#### FR-MOD-023 — 24-hour policy cache

- Key `module.discovered_policies`, TTL 86400; same bust rules as FR-MOD-017.
- **Verification:** cache assertion after discovery (layer `A`).

#### FR-MOD-024 — Manual cross-module policies

- Discovery only binds within a module; a policy guarding another module's model is explicit in `AppServiceProvider` so the exception is visible.
- **Verification:** provider review (layer `A`).

### 4.5 Blade View Namespace Registration

#### FR-MOD-025 — Registered-module view scan

- View roots derive from the registry, not from listing `resources/views/`.
- **Verification:** namespace assertion after boot (layer `A`).

#### FR-MOD-026 — Shared-directory exclusion

- Framework/shared view directories are not module namespaces — registering them would shadow real namespaces.
- **Verification:** namespace-list assertion excluding all seven (layer `A`).

#### FR-MOD-027 — Dual registration

- Each module directory registers both as an anonymous-component path and as a `Module::view` namespace.
- **Verification:** component + namespaced-view resolution smoke (layer `A`).

#### FR-MOD-028 — Registry-bounded views

- A view directory without a registered module stays unregistered (PS-3).
- **Verification:** fixture directory asserting no namespace (layer `A`).

#### FR-MOD-029 — 24-hour view cache

- Key `module.discovered_views`, TTL 86400; same bust rules as FR-MOD-017.
- **Verification:** cache assertion after discovery (layer `A`).

### 4.6 Route Auto-Inclusion

#### FR-MOD-030 — Convention over requires

- No manual `require` per module — the loop over the registry is the only wiring. Rationale in DD-MOD-005.
- **Verification:** route-list smoke after adding a fixture route file (layer `A`).

#### FR-MOD-031 — Path convention

- Single resolver `ModuleManager::routeFilePath()` so the convention has one definition.
- **Verification:** unit test on the resolver (layer `A`).

#### FR-MOD-032 — Silent skip

- Modules without routes need no empty file — `file_exists` guards the require.
- **Verification:** boot smoke with a routeless registered module (layer `A`).

#### FR-MOD-033 — Lowercase lookup

- `SysAdmin` → `sysadmin.php`; lookup lowercases, so filesystem case never matters.
- **Verification:** resolver unit test with a mixed-case name (layer `A`).

### 4.7 CLI Cache Clearing

#### FR-MOD-034 — One-command refresh

- Clears `module.discovered_livewire`, `module.discovered_policies`, `module.discovered_views`, then re-runs all three discovery methods; exits `0` on success.
- **Verification:** feature test running the command and asserting fresh caches + exit code (layer `F`).

#### FR-MOD-035 — Provider guard

- Discovery without the provider's boot context would register into a half-wired app — the command refuses instead.
- **Verification:** feature test asserting the guard path (layer `F`).

#### FR-MOD-036 — SmartLogger completion log

- Success and failure both log through SmartLogger ([89SRA](89SRA-logging-and-error-handling.md)) so discovery runs appear in the audit trail.
- **Verification:** feature test asserting the completion entry (layer `F`).

#### FR-MOD-037 — Translated progress

- Status messages via `__()` with `en` + `id` lines (D3 invariant).
- **Verification:** command output review + `LangChecker` (layer `F`).

### 4.8 Test Directory Registration

#### FR-MOD-038 — Pest module list

- Every registered module has its `tests/{Type}/{Module}/` directory registered so suites discover it.
- **Verification:** Pest run covering all modules (layer `A`).

#### FR-MOD-039 — Manual sync discipline

- The accepted deviation from single-source (DD-MOD-001): a sync comment in `tests/Pest.php` references `config/module.php`, and module-change reviews check both files.
- **Verification:** review gate + NFR-MOD-004 (layer `A`).

#### FR-MOD-040 — Non-module test dirs

- Mirrors the `test_dirs` config key (FR-MOD-008) so shared test support loads.
- **Verification:** Pest boot smoke (layer `A`).

#### FR-MOD-041 — No config() in Pest.php

- Hard constraint, not style: the config container does not exist at Pest discovery time — calling it fatals the suite.
- **Verification:** static review of `tests/Pest.php` (layer `A`).

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-MOD-001 | Discovery never crashes on malformed PHP files — graceful skip | 0 boot fatals from bad files | P0 | A | Full |
| NFR-MOD-002 | Caches are busted on `module:discover` and `config:clear` | 100% refresh on either command | P0 | A | Full |
| NFR-MOD-003 | Duplicate alias registration never throws (last-write-wins) | 0 duplicate-alias exceptions | P1 | A | Full |
| NFR-MOD-004 | The `tests/Pest.php` sync comment references `config/module.php` | Comment present | P1 | A | Full |
| NFR-MOD-005 | `ModuleService` uses `ModuleManager::names()` for every module check | 0 direct directory listings | P1 | A | Full |
| NFR-MOD-006 | All discovery methods are individually testable | 1 test per method | P1 | A | Full |
| NFR-MOD-007 | Discovery never registers classes from unregistered directories | 0 unregistered registrations | P0 | A | Full |
| NFR-MOD-008 | Policy discovery only binds policies extending `BasePolicy` | 100% bound extend `BasePolicy` | P0 | A | Full |

### 5.1 Reliability

#### NFR-MOD-001 — Malformed-file tolerance

- A developer's half-written component must not take down boot — the scanner skips what it cannot parse. **Verification:** fixture malformed file + boot smoke.

#### NFR-MOD-002 — Deterministic busting

- Both commands guarantee fresh discovery afterward; no stale-alias debugging sessions. **Verification:** cache-absence assertion after each command.

#### NFR-MOD-003 — Collision tolerance

- Two components resolving to one alias resolve last-write-wins instead of throwing — collisions surface via alias audit, not boot crashes. **Verification:** duplicate-alias fixture asserting boot succeeds.

### 5.2 Maintainability

#### NFR-MOD-004 — Sync comment

- The one-line comment is the entire drift defense for the Pest hardcode (DD-MOD-001) — it must name `config/module.php` explicitly. **Verification:** `grep` for the reference in `tests/Pest.php`.

#### NFR-MOD-005 — Single config gateway

- `ModuleService` never lists directories itself — `ModuleManager::names()` / `isModule()` are the only module-config reads (static by design, DD-MOD-003). **Verification:** review + `scan_violations.py`.

#### NFR-MOD-006 — Per-method testability

- `discoverLivewireComponents()`, `discoverPolicies()`, `registerBladeNamespaces()` each run standalone so a regression points at one method. **Verification:** one test per method.

### 5.3 Security

#### NFR-MOD-007 — Registry-bounded registration

- An attacker- or accident-placed class outside registered modules can never gain a Livewire alias, policy binding, or view namespace. **Verification:** fixture outside the registry asserting zero registrations.

#### NFR-MOD-008 — BasePolicy gate

- Only `BasePolicy` subclasses (role + ownership authorization per the base-class mandate) bind — a rogue policy class without the base cannot authorize. **Verification:** `scan_class_contracts.py` + binding assertion.

---

## 6. API / Data Contracts

### 6.1 Frozen 19-Module Roster

`Academics` · `Assessment` · `Assignment` · `Auth` · `Certification` · `Core` · `Document` ·
`Enrollment` · `Evaluation` · `Incident` · `Journals` · `Partners` · `Program` · `Reports` ·
`Settings` · `Setup` · `SysAdmin` · `UI` · `User`

Renaming any entry cascades into Livewire aliases (§6.4), routes (§6.3), policies (§6.5),
config (§6.2), tests (§4.8), and docs — the roster is immutable per FR-MOD-001/002.

### 6.2 `config/module.php` Structure

```php
// config/module.php — auto-discovered from app/Modules/ filesystem listing
[
    'list' => ['Core', 'Setup', 'Settings', /* ... */], // array_keys of the mapping
    'registry' => [
        'Core' => ['Channels', 'Console', 'Contracts', 'Exceptions'],
        'Setup' => ['Installation', 'SetupWizard'],
        // ... one entry per roster module
    ],
    'test_dirs' => ['Providers', 'Stubs', 'Support'],
    'paths' => [
        'base' => app_path(),              // app/Modules/
        'views' => resource_path('views'), // resources/views/
        'routes' => base_path('routes/web'),
    ],
    'livewire' => [
        'enabled' => true,
        'directory' => 'Livewire',
        'exclude_paths' => ['Concerns', 'Traits'],
    ],
    'policies' => [
        'enabled' => true,
        'directory' => 'Policies',
        'exclude_paths' => ['Concerns', 'Traits'],
        'model_namespace' => 'App\\{domain}\\Models\\{model}',
    ],
    'views' => [
        'enabled' => true,
        'exclude_directories' => ['components', 'emails', 'errors', 'layouts', 'mcp', 'pdf', 'vendor'],
    ],
]
```

### 6.3 Discovery Conventions

| Surface | Scan root (per registered module) | Exclusions | Cache key (TTL 86400) |
|---------|-----------------------------------|------------|-----------------------|
| Livewire | `app/Modules/{Module}/Domain/*/Livewire/**/*.php` | `Concerns/`, `Traits/`; non-`Component` classes | `module.discovered_livewire` |
| Policies | `app/Modules/{Module}/Domain/*/Policies/**/*.php` | `Concerns/`, `Traits/`; non-`BasePolicy` classes | `module.discovered_policies` |
| Views | `resources/views/{Module}/` | `components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor` | `module.discovered_views` |
| Routes | `routes/web/{lowercase_module}.php` | Missing files silently skipped | — (loaded per request) |

All module-config reads go through `Support\ModuleManager` (`names()`, `isModule()`,
`routeFilePath()`, `policiesEnabled()`, `livewireEnabled()`, `viewsEnabled()`); the runtime
discovery implementation (`ModuleService::discoverLivewireComponents()`,
`discoverPolicies()`, `registerBladeNamespaces()`) is contracted in
[module-manager.md](B114U-module-manager.md) (B114U) §6.2.

### 6.4 Livewire Alias Convention

| Structure | Alias pattern | Example |
|-----------|---------------|---------|
| `{Module}/Livewire/{Class}.php` | `{kebab-module}.{kebab-class}` | `auth.login-form` |
| `{Module}/{Submodule}/Livewire/{Class}.php` | `{kebab-module}.{kebab-submodule}.{kebab-class}` | `enrollment.placement.show` |

### 6.5 Policy Binding Convention

| Structure | Model path | Example |
|-----------|------------|---------|
| `{Module}/Policies/{Model}Policy.php` | `App\{Module}\Models\{Model}` | `Journals\Models\Attendance` |
| `{Module}/{Submodule}/Policies/{Model}Policy.php` | `App\{Module}\{Submodule}\Models\{Model}` | `Partners\Company\Models\Company` |

### 6.6 Cache-Key Registry

| Key | Config reference | TTL |
|-----|------------------|-----|
| `module.discovered_livewire` | `cache-keys.module_livewire` | 86400 (24h) |
| `module.discovered_policies` | `cache-keys.module_policies` | 86400 (24h) |
| `module.discovered_views` | `cache-keys.module_views` | 86400 (24h) |

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—`;
these are recorded decisions, not test rows.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-MOD-001 | Config-only discovery with a hardcoded, comment-synced `tests/Pest.php` list — no `config()` integration | P0 | — | — |
| DD-MOD-002 | Three-part Livewire aliases for submodule components | P1 | — | — |
| DD-MOD-003 | Static config reads live on `ModuleManager`; orchestration stays instanced on `ModuleService` | P0 | — | — |
| DD-MOD-004 | 24-hour discovery cache TTL | P1 | — | — |
| DD-MOD-005 | Route auto-inclusion by registry loop with silent skip | P0 | — | — |

### 7.1 Structure

#### DD-MOD-001 — Config-Only Discovery, No Pest Integration

**Decision:** `tests/Pest.php` keeps a hardcoded module list synchronized via a sync comment referencing `config/module.php`, instead of calling `config()`.
**Rationale:** Pest's test discovery runs before Laravel boots, so `config()` is unavailable — calling it fatals the suite. The comment plus review discipline is the only viable sync.
**Trade-off:** The single-source principle bends in exactly one place; every module change must touch two files (Success Metrics tracks the count at 2).

#### DD-MOD-002 — Submodule Alias Naming

**Decision:** Submodule component aliases use three parts: `module.submodule.class` in kebab-case.
**Rationale:** Prevents collisions between same-named components in different submodules (`enrollment.placement.show` vs `enrollment.registration.show`); kebab-case matches Livewire's standard naming.
**Trade-off:** Longer aliases — accepted for collision-proofing.

#### DD-MOD-003 — Static Reads on ModuleManager

**Decision:** `ModuleManager::names()` and `ModuleManager::isModule()` are `public static` on `Core\Support\ModuleManager` (see B114U DD-1); `ModuleService` keeps instanced methods with constructor injection for orchestration.
**Rationale:** Pure config reads need no instance state or I/O — static access serves contexts without a container (route files, model boot methods) without violating the service pattern.
**Trade-off:** Static surface on one narrow gateway — contained by keeping all other discovery logic instanced and injected.

#### DD-MOD-004 — 24-Hour Cache TTL

**Decision:** Discovery results cache for 86400 seconds.
**Rationale:** Module structure changes only on deploys; development busts explicitly via `module:discover` / `config:clear`. 24 hours balances production boot speed against freshness.
**Trade-off:** A mid-day structural deploy needs an explicit cache clear — documented in the deploy flow, not hidden.

#### DD-MOD-005 — Route Auto-Inclusion Pattern

**Decision:** `routes/web.php` loops `ModuleManager::names()` and requires by convention (`ModuleManager::routeFilePath()`), silently skipping missing files.
**Rationale:** Eliminates manual `require` edits per module; routeless modules need no empty placeholder files.
**Trade-off:** A typo'd route filename fails silently — mitigated by the `module:discover` verification step in the add-module workflow (UC-MOD-001).

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Roster fidelity | On-disk `app/Modules/` matches the 19-name roster exactly | Directory listing vs §6.1 |
| Alias collisions | 0% duplicate aliases | Alias uniqueness audit after discovery |
| Files edited per new module | 2 (`config/module.php` note + `tests/Pest.php`) | Workflow audit |
| Pest sync drift | Never diverged | Review on every module change |
| Discovery boot cost | No full-tree scan — registered modules only, cached 24h | Cache-hit assertion + boot profile |
| Unregistered leakage | 0 classes/namespaces from unregistered directories | Fixture-directory smoke |

---

## 9. Roadmap

### Prerequisites

[FB792](FB792-tech-stack.md) (Livewire 4, framework boot) and [SE5Q9](SE5Q9-base-classes.md)
(`BasePolicy`, provider conventions) — discovery registers surfaces those specs define.

### Build Guide

After implementing this spec, the system discovers Livewire components, authorization policies,
and Blade view namespaces at boot, includes routes by convention, and refreshes everything
through `module:discover`. The next step is the logging and error-handling infrastructure these
discovered surfaces run inside.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) | Discovered Livewire components log via SmartLogger and throw the exception hierarchy |
| 2 | [module-manager.md](B114U-module-manager.md) | Runtime `ModuleService`/`ModuleManager` API implementing this spec's conventions |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the 19-module roster stays frozen for the MVP lifetime; a 20th capability arrives as a submodule, not a new top-level module | Accepted | Maintainer | — |
| A-2 | We assume Pest continues to boot before Laravel, keeping the `tests/Pest.php` hardcode (DD-MOD-001) permanently necessary | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture spec](D2FT3-architecture.md) — FR-ARC-005/031 registry rules this spec implements
- [Module manager spec](B114U-module-manager.md) — runtime `ModuleService`/`ModuleManager` API (§6.2)
- [Logging spec](89SRA-logging-and-error-handling.md) — SmartLogger used by `module:discover`
- [ADR: Action-based MVC](../adr/adr-action-based-mvc-architecture.md) — mechanical module addition
- [ADR: Cross-module communication](../adr/adr-cross-module-communication.md) — ranked-hierarchy guidance
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — Start → Stabilize → Final adoption paths
- [Module graph](../refs/modules/index.md) — dependency order + per-module conceptual/reference docs
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements this spec's rows serve
