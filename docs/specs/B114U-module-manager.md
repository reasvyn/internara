# Module Manager & Service — Single Gateway for Module Infrastructure

> **Spec ID:** B114U

## Description

Consolidates all module infrastructure and configuration access behind two Core classes:
`Support\ModuleManager` (static config gateway) and `Services\ModuleService` (instance discovery
orchestrator). The gateway fronts the frozen 19-module roster and the auto-discovered registry in
`config/module.php`, owns the route/view/Livewire naming conventions, and keeps every
`config('module.*')` read and every filesystem scan behind one typed surface. Depends on the
registry contract in [module-discovery.md](I1BCV-module-discovery.md), which owns roster changes.

---

## 1. Problem Statements

### PS-1 — Scattered Direct Config Access

`config('module.*')` is read directly in multiple places: `routes/web.php` (module list),
`AppServiceProvider` (three `enabled` flags), and discovery services. Keys and defaults are
duplicated at each call site. A typo silently falls back to a default, and there is no type
safety or single point documenting what the module configuration exposes.
**→ Requirement:** FR-MGR-001/002/003 (gateway accessors), FR-MGR-026 (caller migration).

### PS-2 — Mixed Static/Instance Responsibilities

Discovery mixes static config reads (`getModuleNames()`, `isModule()`) with instance discovery
operations (`discoverLivewireComponents()`, `discoverPolicies()`, `registerBladeNamespaces()`).
Per the service pattern, Services use instance methods with constructor injection — static methods
fit only framework hooks and pure config reads. The split below gives each responsibility its
lawful home.
**→ Requirement:** FR-MGR-014 (Support rules), FR-MGR-015 (injected Service).

### PS-3 — Scattered Filesystem Scanning & Naming Drift

PHP file scanning lives beside callers while path/naming conventions are re-implemented at each
one: route files use `Str::lower()`, view directories are lowercase, Livewire aliases use
`Str::kebab()`. The `registerBladeNamespaces()` bug — comparing lowercase view directory names
against PascalCase module names — proves these conventions drift without a single owner.
**→ Requirement:** FR-MGR-021/022/023 (scan confinement), FR-MGR-035 (centralized conventions).

### PS-4 — No Central Naming Conventions

Each consumer re-derives module names into route file names, view directory names, and Livewire
aliases independently. There is no shared definition of these transformations, so they can
disagree — as the view-directory mismatch above demonstrated.
**→ Requirement:** FR-MGR-011/012 (gateway naming), FR-MGR-024 (alias rules), FR-MGR-035.

---

## 2. Goals & Non-Goals

### Goals

- **Single static gateway** — `Support\ModuleManager` fronts every module config read with typed accessors. *Why:* one typed surface kills key-typo defaults and documents the registry in code.
- **Injected discovery orchestrator** — `Services\ModuleService` owns scanning with constructor injection. *Why:* cache and filesystem dependencies become explicit, mockable, and lawful per the service pattern.
- **Caller migration** — all `config('module.*')` readers move to the gateway. *Why:* the gateway guarantee is void while any direct reader remains.
- **Legacy removal** — the old discover service is superseded and removed. *Why:* two scanners means two owners and duplicated logic.
- **Confined scanning** — filesystem walks happen only inside `ModuleService`. *Why:* bounded blast radius for malformed files and unregistered directories.
- **Centralized naming** — route file, view directory, and Livewire alias rules live in one place. *Why:* the case-mismatch bug class dies when only one function spells each rule.
- **Registry fidelity** — gateway output always matches the on-disk modules and the frozen roster. *Why:* a registry that drifts from reality silently drops routes, policies, and components.

### Non-Goals

- **Runtime module hot-loading or enable/disable**. *Why:* the registry stays config-driven; runtime mutability adds cache-invalidation complexity with no verified need.
- **Cross-module dependency resolution**. *Why:* modules remain independent; ordering comes from the registry, not a resolver.
- **Refactoring `tests/Pest.php` to use `config()`**. *Why:* Pest boots before Laravel, so the test bootstrap keeps its manual directory list (per I1BCV DD-1).
- **Domain business logic in either class**. *Why:* both classes are infrastructure only; business rules belong in Entities and Actions.
- **New runtime configuration surface beyond `config/module.php`**. *Why:* the config file is the single source of truth; a second surface splits ownership.
- **Roster changes in this spec**. *Why:* the 19-module roster is frozen and owned by [module-discovery.md](I1BCV-module-discovery.md) (FR-MOD-001/002) — this spec consumes the roster, never amends it.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-MGR-001 | Router auto-includes each module's route file through the gateway naming rule | P0 | A | Full |
| UC-MGR-002 | Framework boot discovers policies, Livewire components, and Blade namespaces behind gateway flags | P0 | F | Full |
| UC-MGR-003 | Developer refreshes all discovery caches from the CLI through the single service | P1 | F | Full |
| UC-MGR-004 | Consumer reads a module flag through a typed accessor, never `config()` directly | P0 | U | Full |
| UC-MGR-005 | Developer adds a new module directory and the naming rules apply automatically | P1 | — | — |

### 3.1 Discovery Journeys

#### UC-MGR-001 — Route Auto-Inclusion

When Laravel boots its routes, nobody wants a manual include list that rots with every new module. `routes/web.php` resolves `ModuleManager::names()`, resolves `ModuleManager::routeFilePath($module)` per name, and requires each file that exists — so module routes load with no direct `config('module.*')` access. That round trip exercises FR-MGR-001 for the names with FR-MGR-011 for the route path, proven by the routing arch tests.

#### UC-MGR-002 — Boot-Time Discovery

Framework boot fans out into three gated discoveries: `AppServiceProvider::boot()` fires, then the `policiesEnabled()` gate leads to `ModuleService::discoverPolicies()`, the `livewireEnabled()` gate to `discoverLivewireComponents()`, and the `viewsEnabled()` gate to `registerBladeNamespaces()`. Afterwards every Livewire component, policy, and Blade namespace is registered while every module config read has gone through `ModuleManager`. The journey binds FR-MGR-007 for the flags with FR-MGR-016, FR-MGR-017, and FR-MGR-018 for the three discoveries, proven by the boot feature tests.

#### UC-MGR-003 — CLI Rediscovery

The edge case is the developer who edits a component path and then wonders why staging still serves the old alias for a day. Running `php artisan module:discover` resolves `ModuleService` from the container, runs the three discovery methods with each writing its results to cache, and logs completion or failure via SmartLogger — all discovery caches refreshed through the single service. FR-MGR-015 owns the injection with FR-MGR-020 owning the registered cache keys, proven by the command feature test.

#### UC-MGR-004 — Checking a Module Flag

This requirement exists because the hundredth `config('module.policies_enabled')` call site is where the typo finally lands. Any consumer — `AppServiceProvider`, a test, a command — calls `ModuleManager::policiesEnabled()` (or `livewireEnabled()`, or `viewsEnabled()`), and the gateway reads the typed accessor from `config/module.php`, so callers never reference `config('module.*')` directly. FR-MGR-007 owns the flags, and the per-accessor unit tests prove the callers stay honest.

#### UC-MGR-005 — Adding a New Module

A developer adding the twentieth module directory expects the machinery to notice: she adds the module directory under `app/Modules/` with its domains, the registry picks it up from the directory listing in auto-discovered deterministic order, she adds the test directory to `tests/Pest.php` by hand since Pest boots before Laravel, creates the optional route file at `routes/web/{lowercase_module}.php`, and runs `php artisan module:discover`. Naming rules for route file path, view directory, and Livewire alias apply automatically while roster-manual steps stay in I1BCV rather than here. FR-MGR-033 and FR-MGR-034 govern the fidelity, and since the journey is manual it is verified by review rather than a pest layer — hence the `—`.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch
(structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-MGR-001 | `ModuleManager::names(): array` MUST return the registered module list from `config('module.list')` | P0 | U | Full |
| FR-MGR-002 | `ModuleManager::isModule(string $name): bool` MUST be a strict membership check against `names()` | P0 | U | Full |
| FR-MGR-003 | `ModuleManager::registry(): array` MUST return the module → domain mapping | P0 | U | Full |
| FR-MGR-004 | `ModuleManager::domains(string $module): array` MUST return the module's domain list (empty array if unknown); `submodules()` remains only as a deprecated alias | P0 | U | Full |
| FR-MGR-005 | `ModuleManager::testDirs(): array` MUST return `config('module.test_dirs')` | P1 | U | Full |
| FR-MGR-006 | `ModuleManager::basePath()`, `viewsPath()`, `routesPath()` MUST return the configured paths | P0 | U | Full |
| FR-MGR-007 | `ModuleManager` MUST expose typed boolean accessors: `policiesEnabled()`, `livewireEnabled()`, `viewsEnabled()`, `factoriesEnabled()` | P0 | U | Full |
| FR-MGR-008 | `ModuleManager::livewireDirectory(): string` and `livewireExcludePaths(): array` MUST return the Livewire discovery settings | P1 | U | Full |
| FR-MGR-009 | `ModuleManager::policiesDirectory()`, `policiesExcludePaths()`, `policyModelNamespace()` MUST return the policy discovery settings | P1 | U | Full |
| FR-MGR-010 | `ModuleManager::viewsExcludeDirectories(): array` MUST return the view namespace exclusions | P1 | U | Full |
| FR-MGR-011 | `ModuleManager::routeFilePath(string $module): string` MUST return the route file path using the `Str::lower()` convention | P0 | U | Full |
| FR-MGR-012 | `ModuleManager::isRegisteredDirectory(string $directoryName): bool` MUST compare case-insensitively against `names()` | P0 | U | Full |
| FR-MGR-013 | `ModuleManager` MUST NOT perform any filesystem scanning (config reads only) | P0 | A | Full |
| FR-MGR-014 | All `ModuleManager` methods MUST be `public static` with no constructor (Support rules) | P0 | A | Full |
| FR-MGR-015 | `ModuleService` MUST accept a cache repository via constructor injection | P0 | A | Full |
| FR-MGR-016 | `ModuleService::discoverLivewireComponents(): void` MUST register discovered Livewire components | P0 | F | Full |
| FR-MGR-017 | `ModuleService::discoverPolicies(): void` MUST bind discovered policies to models | P0 | F | Full |
| FR-MGR-018 | `ModuleService::registerBladeNamespaces(): void` MUST register view namespaces and anonymous component paths | P0 | F | Full |
| FR-MGR-019 | All `ModuleService` config reads MUST go through `ModuleManager` (no direct `config()`) | P0 | A | Full |
| FR-MGR-020 | Discovery caching MUST use keys from `config/cache-keys.php` (`module_livewire`, `module_policies`, `module_views`) with 24-hour TTL | P0 | F | Full |
| FR-MGR-021 | Discovery MUST scan only registered modules (`ModuleManager::names()`) | P0 | F | Full |
| FR-MGR-022 | Livewire/policy discovery MUST skip `Concerns/` and `Traits/` subdirectories | P1 | F | Full |
| FR-MGR-023 | Livewire aliases MUST use `{kebab-module}.{kebab-class}` and `{kebab-module}.{kebab-submodule}.{kebab-class}` | P0 | F | Full |
| FR-MGR-024 | Policies MUST bind to models in the same module (or submodule) `Models/` directory | P0 | F | Full |
| FR-MGR-025 | View registration MUST exclude the configured non-module directories | P1 | F | Full |
| FR-MGR-026 | `routes/web.php` MUST use `ModuleManager::names()` and `ModuleManager::routeFilePath()` with no direct `config('module.*')` access | P0 | A | Full |
| FR-MGR-027 | `AppServiceProvider` MUST inject `ModuleService` and gate discovery on `ModuleManager` flags | P0 | A | Full |
| FR-MGR-028 | `ModuleDiscoverCommand` (`module:discover`) MUST resolve `ModuleService` from the container and log via SmartLogger | P1 | F | Full |
| FR-MGR-029 | Discovery tests MUST target `ModuleService` (no legacy references) | P1 | A | Full |
| FR-MGR-030 | `config/module.php` MUST remain the single source of truth (no schema change in this spec) | P0 | A | Full |
| FR-MGR-031 | The legacy discover service MUST be removed; no remaining code may reference it | P0 | A | Full |
| FR-MGR-032 | `ModuleManager::names()` MUST match the directory listing of `app/Modules/` (auto-discovered, deterministic order) | P0 | A | Full |
| FR-MGR-033 | `ModuleManager::names()` MUST resolve exactly the frozen 19-module roster — `Core`, `UI`, `Auth`, `User`, `SysAdmin`, `Setup`, `Settings`, `Academics`, `Program`, `Enrollment`, `Assessment`, `Evaluation`, `Assignment`, `Journals`, `Incident`, `Partners`, `Certification`, `Reports`, `Document` — no addition, removal, or rename without an I1BCV amendment | P0 | A | Full |
| FR-MGR-034 | Registry sync: `config/module.php` list, `tests/Pest.php` directories, and `docs/refs/modules/index.md` MUST agree with `names()`; any change passes through spec amendment plus ADR before code | P0 | A | Full |
| FR-MGR-035 | Discovery conventions (route file `Str::lower`, view directory lowercase, Livewire alias `Str::kebab`) MUST be centralized in `ModuleManager`/`ModuleService` with no caller re-deriving them | P0 | A | Full |

### 4.1 ModuleManager (Support Gateway)

#### FR-MGR-001 — Registered module list

Every scanner and the router consume one list, and when two call sites each sort it their own way the boot order stops being deterministic. `ModuleManager::names(): array` returns the registered module list from `config('module.list')` in registry order, and dependency order comes from the registry rather than call-site sorting. A unit test asserting registry order passthrough (layer U) proves the list is never re-sorted downstream.

#### FR-MGR-002 — Strict membership

A substring match once let `isModule('user')` return true inside a deployment where only `User` and `SuperUser` tooling existed, and a feature flag leaked onto the wrong module for a day. Membership is therefore identity comparison only — case variants and substrings never match, so `isModule('user')` stays false next to `User`. A unit test with near-miss inputs (layer U) proves the strictness.

#### FR-MGR-003 — Module-to-domain map

At runtime each scanner needs to know which domains belong to which module before it walks the filesystem, and re-deriving that map per scanner invites drift. `ModuleManager::registry(): array` returns the full module-to-domain mapping as a passthrough for scanners that scope their walks per module. A unit test asserting map equality (layer U) proves the passthrough is verbatim.

#### FR-MGR-004 — Domain accessor

The edge case here is legacy: `submodules()` dates from the submodule era and still has callers, while `domains()` is the canonical accessor new code must call. `ModuleManager::domains(string $module): array` returns the module's domain list and an empty array for unknown modules, with `submodules()` surviving only as a deprecated alias per §10 A-1. Unit tests covering a known module plus the unknown-module empty array (layer U) prove both behaviours.

#### FR-MGR-005 — Test directories

Test roots such as `Providers`, `Stubs`, and `Support` are not modules, yet the bootstrap must see them — dropping them from the directory scan would silently skip whole test suites. `ModuleManager::testDirs(): array` returns `config('module.test_dirs')` so non-module roots stay visible without polluting the module list. A unit test (layer U) pins the separation.

#### FR-MGR-006 — Path accessors

When the tree gets relocated — a Docker image laying modules under a different prefix, for instance — every hardcoded `app_path('Modules')` becomes a landmine. Base, views, and routes roots therefore come from config through `ModuleManager::basePath()`, `viewsPath()`, and `routesPath()`, so a relocated tree updates in one place. A unit test with overridden config (layer U) proves the indirection holds.

#### FR-MGR-007 — Typed feature flags

Raw flag keys invite the classic typo-default: `config('module.enable_policies')` misspelled returns false and silently disables policy discovery for a deploy. The gateway instead exposes typed boolean accessors — `policiesEnabled()`, `livewireEnabled()`, `viewsEnabled()`, `factoriesEnabled()` — covering the three discovery subsystems plus factories, and no caller reads a raw flag key. One unit test per flag on and off (layer U) proves each gate.

#### FR-MGR-008 — Livewire settings

The scanner once hardcoded its discovery directory and exclusion paths, so moving shared concerns broke exclusion silently. The discovery directory plus exclusion paths — covering shared concerns — now flow through `ModuleManager::livewireDirectory(): string` and `livewireExcludePaths(): array`, and the scanner never hardcodes them. A unit test (layer U) proves the gateway values reach the scan.

#### FR-MGR-009 — Policy settings

Binding a policy to the wrong model is the failure this guards: without an explicit model namespace the binder once paired two same-named models across modules. `ModuleManager::policiesDirectory()`, `policiesExcludePaths()`, and `policyModelNamespace()` return the policy discovery settings — directory, exclusions, and the model namespace used to bind policies to same-module models. A unit test (layer U) locks the three values.

#### FR-MGR-010 — View exclusions

Vendor and tooling view directories sitting inside the scan root would gain Blade namespaces they were never meant to have, leaking internal partials into the component namespace. `ModuleManager::viewsExcludeDirectories(): array` returns the view namespace exclusions keeping non-module directories out of registration. A unit test (layer U) proves the exclusion list flows through.

#### FR-MGR-011 — Route file path

A vocational school deploy once failed to load the Partners routes because one caller spelled the file `Partners.php` while the router looked for `partners.php` on a case-sensitive disk. There is now exactly one spelling of the rule — `{routesPath}/{Str::lower(module)}.php` via `ModuleManager::routeFilePath(string $module): string` — and the router obeys it blindly. A unit test with a mixed-case module (layer U) proves the lowering.

#### FR-MGR-012 — Case-insensitive directory check

This is the comparison that fixes the `registerBladeNamespaces()` case-mismatch bug class outright: lowercase view directory names on disk must match PascalCase registry names, or whole modules lose their views on Linux while working on the developer's Mac. `ModuleManager::isRegisteredDirectory(string $directoryName): bool` compares case-insensitively against `names()`. A unit test pairing lowercase against PascalCase (layer U) proves the fix.

#### FR-MGR-013 — No scanning in the gateway

The day the gateway starts walking the filesystem, every config read can suddenly block on I/O and the purity guarantee reviewers rely on evaporates. `ModuleManager` performs config reads only, and any filesystem touch inside it counts as a layering violation mirroring NFR-MGR-002. A contract scan (layer A) proves no scan call lives in the gateway.

#### FR-MGR-014 — Static Support shape

Support classes with constructors accumulate state, and stateful gateways get mocked, subclassed, and drifted. Every `ModuleManager` method is `public static` on a `final` class with no constructor, per the service and support pattern and the [base-class-mandate ADR](../adr/adr-base-class-mandate.md). A class-contract scan (layer A) proves the shape.

### 4.2 ModuleService (Discovery Orchestrator)

#### FR-MGR-015 — Injected cache

At boot the container hands the service its cache dependency, which is what makes the discovery tests fast: `__construct(private Repository $cache)` means the service never touches the `Cache` facade directly, so tests inject fakes instead of flushing a real store. A contract scan plus a container resolution test (layer A) prove injection is the only path.

#### FR-MGR-016 — Livewire discovery

A new Livewire component that never registers might as well not exist — its route renders a missing-component error during enrollment week. `ModuleService::discoverLivewireComponents(): void` scans registered modules for components, registers aliases per FR-MGR-023, and caches the map. A discovery feature test asserting the registered aliases (layer F) proves components appear where the router expects them.

#### FR-MGR-017 — Policy discovery

An unbound policy is a silent hole: the endpoint falls back to default-deny and coordinators file access bugs nobody can reproduce. `ModuleService::discoverPolicies(): void` binds each discovered policy to its same-module model, and only `BasePolicy` subclasses bind per NFR-MGR-008. A discovery test asserting the gate bindings (layer F) proves every policy lands on its model.

#### FR-MGR-018 — Blade namespaces

When per-module view namespaces go missing, Blade falls back to the wrong module's partial and the page renders with another program's header. `ModuleService::registerBladeNamespaces(): void` registers per-module view namespaces plus anonymous component paths, applying the case-insensitive directory check from FR-MGR-012. A discovery test asserting namespace registration (layer F) proves the views resolve.

#### FR-MGR-019 — Gateway-only config

A single direct `config()` call inside the service reintroduces the typo-default hazard the gateway exists to kill, and reviewers cannot spot it without reading every line. All `ModuleService` config reads therefore go through `ModuleManager`, with no direct `config()` anywhere in the service. A scan for `config(` inside the service (layer A) proves the monopoly holds.

#### FR-MGR-020 — Registered cache keys, 24h TTL

Uncached discovery would re-walk hundreds of files on every request, turning boot into the bottleneck during the morning attendance rush. Discovery caching uses the registered keys `module.discovered_livewire`, `module.discovered_policies`, and `module.discovered_views` from `config/cache-keys.php` with `CACHE_TTL_SECONDS = 86400`, a 24-hour TTL. A registry audit plus a TTL assertion (layer F) prove the keys and the expiry.

#### FR-MGR-021 — Registered modules only

A stray directory dropped into `app/Modules/` by an experiment once injected its components into production because the scanner walked everything on disk. Discovery scans only registered modules from `ModuleManager::names()`, so unregistered directories never contribute components, policies, or views. A fixture test with an unregistered directory present (layer F) proves the stray stays invisible.

#### FR-MGR-022 — Skip shared traits

Shared helpers in `Concerns/` and `Traits/` look like classes to a naive scanner, and registering them as components produces phantom aliases that collide with real ones. Livewire and policy discovery skip both subdirectories since they hold shared helpers rather than registrable classes. A fixture test (layer F) proves the skip.

#### FR-MGR-023 — Kebab alias rules

Two callers generating Livewire aliases with different kebab rules once produced `partners.CreateCompany` in one place and `partners.create-company` in another, and half the links broke. There is now a single spelling owned here: `{kebab-module}.{kebab-class}` for flat modules with the submodule segment for nested ones, via `Str::kebab()`. An alias assertion per component shape (layer F) proves the rule.

#### FR-MGR-024 — Same-module binding

A policy bound to a foreign module's model would let one module's authorization dialect override another's — the exact cross-module tangle the module boundaries forbid. Policies bind only to models in the same module or submodule `Models/` directory, and cross-module authorization goes through the owning module's policy per [T4B26](T4B26-rbac-and-authorization.md). A binding audit inside the discovery test (layer F) proves no foreign binding exists.

#### FR-MGR-025 — View exclusions honored

Without honoured exclusions, tooling and vendor view trees gain namespaces and their internal partials become addressable from application Blade. Configured non-module directories stay out of namespace registration. A fixture test (layer F) proves the exclusions hold.

### 4.3 Migration

#### FR-MGR-026 — Router migration

Three call sites reading `config('module.*')` directly meant three places a key rename had to land, and the third was always forgotten. `routes/web.php` now resolves `ModuleManager::names()` and `ModuleManager::routeFilePath()` with no direct `config('module.*')` access, collapsing three readers into two gateway calls with the naming guarantee attached. A scan of `routes/web.php` for direct `config('module` reads (layer A) proves the migration.

#### FR-MGR-027 — Provider migration

A provider that knows registry internals starts branching on module names, and boot logic drifts out of review. `AppServiceProvider` instead injects `ModuleService` and gates each discovery method on its `ModuleManager` flag, holding no registry knowledge itself. A provider audit (layer A) proves the separation.

#### FR-MGR-028 — Command migration

Rediscovery used to mean remembering three method names and the right cache keys, so nobody ran it and stale bindings lingered for weeks. `ModuleDiscoverCommand` (`module:discover`) resolves `ModuleService` from the container, runs the three discovery methods, logs completion or failure via SmartLogger, and clears cache along the way. A command invocation test (layer F) proves the single command refreshes everything.

#### FR-MGR-029 — Test migration

Coverage asserting against the superseded service would keep the dead class alive through its tests long after its callers migrated. Discovery tests target `ModuleService`, and legacy names appear nowhere. A test-suite grep for legacy references (layer A) proves the cut is complete.

#### FR-MGR-030 — Config untouched

A second configuration surface for modules would split ownership: half the flags in one file, half in another, disagreeing by Friday. `config/module.php` remains the single source of truth with no schema change in this spec — this spec adds accessors, never keys, with the registry shape owned by [I1BCV](I1BCV-module-discovery.md). A config diff audit (layer A) proves the shape is untouched.

#### FR-MGR-031 — Legacy removal

Dead code with a live name gets imported by the next contributor who greps for discovery and picks the first match. The legacy discover service is removed outright: no class, import, comment, or test may name it. A codebase scan (layer A) proves zero remaining references.

#### FR-MGR-032 — Directory fidelity

A hand-maintained module list rots the week someone adds a directory and forgets the list — routes, policies, and components for the new module silently never load. `config/module.php` instead derives from the `app/Modules/` listing via `scandir` in sorted order, so adding a module directory is what registers it and `ModuleManager::names()` always matches the on-disk listing in deterministic order. An on-disk versus registry comparison test (layer A) proves the fidelity.

### 4.4 Roster and Registry Sync

> Roster ownership: [module-discovery.md](I1BCV-module-discovery.md) FR-MOD-001/002. The rows
> below are this spec's consumption contract — what the gateway guarantees about a roster it
> does not own.

#### FR-MGR-033 — Frozen roster consumption

The gateway neither adds nor renames modules: its output is the frozen nineteen-module roster verbatim in dependency order — `Core`, `UI`, `Auth`, `User`, `SysAdmin`, `Setup`, `Settings`, `Academics`, `Program`, `Enrollment`, `Assessment`, `Evaluation`, `Assignment`, `Journals`, `Incident`, `Partners`, `Certification`, `Reports`, `Document` — and a twentieth on-disk directory fails this row until I1BCV amends the roster. A roster assertion test against the nineteen-name list (layer A) proves consumption is exact.

#### FR-MGR-034 — Four-way sync

Four artifacts describe the same roster — the config list, the `tests/Pest.php` directories, the module graph doc at `docs/refs/modules/index.md`, and the gateway output — and any two disagreeing means a test runs against a module boot never loads. All four must agree with `names()`, and any change passes through spec amendment plus ADR before code, which makes silent drift a process violation rather than just a test failure. A cross-artifact sync check (layer A) proves the agreement.

#### FR-MGR-035 — Single spelling per convention

The §6.4 table is the complete list of spelling rules — route path via `routeFilePath()`, view directory via `isRegisteredDirectory()`, aliases via the service generator — using route-file `Str::lower`, lowercase view directories, and Livewire alias `Str::kebab`, centralized in `ModuleManager` and `ModuleService` with no caller re-deriving them. A scan for `Str::lower` and `Str::kebab` module derivations outside the two classes (layer A) proves the single spelling per convention.

---

## 5. Non-Functional Requirements

`Target` = `N/A` means the requirement is enforced structurally and verified via scans/tests
rather than a runtime measurement.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-MGR-001 | No `config('module.*')` calls may exist outside `ModuleManager` | N/A | P0 | A | Full |
| NFR-MGR-002 | No filesystem scanning may exist outside `ModuleService` | N/A | P0 | A | Full |
| NFR-MGR-003 | Every `ModuleManager` accessor is individually unit-testable | N/A | P1 | U | Full |
| NFR-MGR-004 | `ModuleManager` uses `names()` for all module membership checks | N/A | P1 | U | Full |
| NFR-MGR-005 | Discovery never crashes on malformed PHP files (graceful skip) | N/A | P1 | F | Full |
| NFR-MGR-006 | Discovery cache clears on `module:discover` and `config:clear` | N/A | P1 | F | Full |
| NFR-MGR-007 | Discovery never registers classes from unregistered directories | N/A | P0 | F | Full |
| NFR-MGR-008 | Policy discovery only binds policies extending `BasePolicy` | N/A | P0 | A | Full |

### 5.1 Structural Purity

#### NFR-MGR-001 — Gateway monopoly on config reads

Every direct `config('module.*')` reader outside the gateway is a future typo-default waiting for enrollment week. No such call may exist outside `ModuleManager`, with `tests/Pest.php` as the single documented exception since Pest boots before Laravel. A codebase scan for `config('module` outside the gateway (layer A) enforces the monopoly.

#### NFR-MGR-002 — Scanner monopoly on walks

At SMK Negeri 2 Yogyakarta an ad-hoc `scandir` in a reporting job started walking module trees on every export, and nobody found it for a term because scanning had no single owner. Directory iteration for discovery lives in `ModuleService` alone, and any filesystem walk elsewhere is a finding. A scan for walks outside the service (layer A) proves the confinement.

#### NFR-MGR-003 — Accessor testability

Accessors that cannot be tested in isolation get tested nowhere, and untested accessors drift. Every `ModuleManager` accessor is a pure function of config, each tested alone with overridden config values. The per-accessor unit suite (layer U) proves the testability.

#### NFR-MGR-004 — Membership through `names()`

Two parallel membership lists disagree by definition eventually — one updated, the other forgotten. `isModule()` and `isRegisteredDirectory()` both bottom out in `names()`, so no parallel list exists. Unit tests plus review (layer U) prove the single source.

### 5.2 Reliability

#### NFR-MGR-005 — Graceful skip on malformed files

A broken PHP file dropped into a module directory during a bad merge once took down the entire boot with a fatal, locking every user out until an engineer with SSH arrived. Discovery now degrades to a skipped entry with a logged warning instead of a boot fatal. A malformed-fixture discovery test (layer F) proves the graceful skip.

#### NFR-MGR-006 — Cache clearing

Stale discovery bindings surviving a deploy mean new components 404 while removed ones linger — the deploy looks green while serving yesterday's map. Rediscovery through `module:discover` and config clears therefore flush the three discovery keys. A clear-then-rediscover test (layer F) proves stale bindings cannot survive.

### 5.3 Security

#### NFR-MGR-007 — Registered directories only

If unregistered code could gain routes, components, or view namespaces, any directory dropped on disk would become executable surface without review. Discovery never registers classes from unregistered directories, mirroring FR-MGR-021 as a security property. An unregistered-directory fixture test (layer F) proves the boundary.

#### NFR-MGR-008 — BasePolicy-only binding

A policy outside the `BasePolicy` chain carries no `before()` bypass and none of the role traits, so binding it to a gate would silently downgrade authorization for its model. Policy discovery binds only policies extending `BasePolicy`. A non-conforming-policy fixture test (layer A) proves outsiders never receive a binding.

---

## 6. API / Data Contracts

### 6.1 ModuleManager API

```php
namespace App\Core\Support;

final class ModuleManager
{
    /** @return list<string> Registered module names in dependency order. */
    public static function names(): array;

    /** Strict membership check against registered module names. */
    public static function isModule(string $name): bool;

    /** @return array<string, list<string>> Module → domain mapping. */
    public static function registry(): array;

    /** @return list<string> Domains for a module (empty if unknown). */
    public static function domains(string $module): array;

    /** @return list<string> Non-module test directories. */
    public static function testDirs(): array;

    public static function basePath(): string;
    public static function viewsPath(): string;
    public static function routesPath(): string;

    public static function policiesEnabled(): bool;
    public static function livewireEnabled(): bool;
    public static function viewsEnabled(): bool;
    public static function factoriesEnabled(): bool;

    public static function livewireDirectory(): string;
    /** @return list<string> */
    public static function livewireExcludePaths(): array;

    public static function policiesDirectory(): string;
    /** @return list<string> */
    public static function policiesExcludePaths(): array;
    public static function policyModelNamespace(): string;

    /** @return list<string> */
    public static function viewsExcludeDirectories(): array;

    /** Route file path for a module: {routesPath}/{lowercase}.php. */
    public static function routeFilePath(string $module): string;

    /** Case-insensitive membership check for lowercase view directory names. */
    public static function isRegisteredDirectory(string $directoryName): bool;
}
```

### 6.2 ModuleService API

```php
namespace App\Core\Services;

use Illuminate\Contracts\Cache\Repository;

final readonly class ModuleService
{
    public function __construct(private Repository $cache) {}

    /** Scan and register Livewire components from registered modules. */
    public function discoverLivewireComponents(): void;

    /** Scan and register policies from registered modules. */
    public function discoverPolicies(): void;

    /** Scan and register Blade view namespaces from registered modules. */
    public function registerBladeNamespaces(): void;
}
```

### 6.3 Config & Cache Contracts

- Config structure: `config/module.php` — auto-derived from the `app/Modules/` listing
  (`scandir`, sorted); keys `list`, `registry`, `test_dirs`, paths, flags, discovery settings.
  Shape owned by [module-discovery.md](I1BCV-module-discovery.md) §6.1 (unchanged; FR-MGR-030).
- Cache keys: `config/cache-keys.php` entries `module_livewire`, `module_policies`,
  `module_views` (stored as `module.discovered_*`), TTL 86400 — see
  [module-discovery.md](I1BCV-module-discovery.md) §6.5 (unchanged).

### 6.4 Naming Conventions (Centralized)

| Artifact | Rule | Owner |
| -------- | ---- | ----- |
| Route file | `routes/web/{Str::lower(module)}.php` | `ModuleManager::routeFilePath()` |
| View directory | lowercase module name | `ModuleManager::isRegisteredDirectory()` |
| Livewire alias | `{Str::kebab(module)}.{class}` / `{module}.{submodule}.{class}` | `ModuleService` |

### 6.5 Frozen Roster

`Core`, `UI`, `Auth`, `User`, `SysAdmin`, `Setup`, `Settings`, `Academics`, `Program`,
`Enrollment`, `Assessment`, `Evaluation`, `Assignment`, `Journals`, `Incident`, `Partners`,
`Certification`, `Reports`, `Document` — 19 modules, owned by I1BCV FR-MOD-001/002.

---

## 7. Design Decisions

Decisions are recorded rationale, not test rows — `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-MGR-001 | Support vs Service split | P0 | — | — |
| DD-MGR-002 | ModuleService supersedes the legacy discover service | P0 | — | — |
| DD-MGR-003 | Centralized naming conventions | P0 | — | — |
| DD-MGR-004 | Typed accessors over generic `config()` | P0 | — | — |
| DD-MGR-005 | Config remains the single source of truth | P0 | — | — |
| DD-MGR-006 | Roster ownership stays in module-discovery (I1BCV) | P0 | — | — |

### 7.1 Infrastructure Shape

#### DD-MGR-001 — Support vs Service Split

Two responsibilities had been sharing one class: pure config reads that need no state, and discovery orchestration that needs cache plus filesystem. The split gives each its lawful home — `Support\ModuleManager` as the static config gateway with `public static` methods and no constructor, `Services\ModuleService` as the instance orchestrator with constructor injection — mirroring the service-pattern rules so each class holds one responsibility and stays independently testable. Callers must learn which of the two classes to reach for, which is the accepted price of the split.

#### DD-MGR-002 — ModuleService Supersedes the Legacy Discover Service

For one release two scanners coexisted and every scanning fix had to land twice, with reviewers guessing which one boot actually used. The new service therefore replaces the legacy discover service outright: the old class is removed and its callers migrate, since keeping both would duplicate scanning logic and confuse ownership. Migration is small — three call sites plus one test file — and that smallness is what makes the clean break affordable.

#### DD-MGR-003 — Centralized Naming Conventions

The `registerBladeNamespaces()` case-mismatch bug is the exhibit: route file paths derived with one casing rule in one caller, view directory names derived with another elsewhere, until a lowercase directory stopped matching a PascalCase module name. Route file paths and view directory checks now live in `ModuleManager` (`routeFilePath()`, `isRegisteredDirectory()`) while Livewire alias generation stays in `ModuleService`, so each convention has exactly one owner and is testable in isolation. Convention changes now touch shared code, which is accepted because such changes are rare and newly visible.

#### DD-MGR-004 — Typed Accessors Over Generic `config()`

A generic `config(string $key)` wrapper would merely relocate the typo problem — a misspelled key still returns its default silently, just from a different call site. `ModuleManager` instead exposes granular typed methods such as `policiesEnabled()` and `livewireDirectory()`, which prevent key typos, enable static analysis, and make the available module configuration discoverable in code. More surface area is the cost, since every new config key needs a new accessor.

#### DD-MGR-005 — Config Remains the Single Source of Truth

Runtime enable and disable toggles were proposed twice and rejected twice, because every toggle doubles the cache-invalidation states a deploy must reason about. No runtime mutability is introduced: `config/module.php` flags continue to gate discovery, consistent with [module-discovery.md](I1BCV-module-discovery.md) NG4 and DD-1. Disabling a module takes a config change plus rediscovery, which is acceptable at this scale.

#### DD-MGR-006 — Roster Ownership in I1BCV

When two specs can each amend the roster, the gateway claims nineteen modules while discovery claims twenty, and routes silently drop. Membership therefore has exactly one owner: additions, removals, and renames go through I1BCV FR-MOD-001/002, while this spec consumes the frozen roster and enforces sync through FR-MGR-033 and FR-MGR-034 without ever amending it. Roster-adjacent work sometimes touches two specs, which is the standing cost of never duplicating the fact.

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| `config('module.*')` access outside ModuleManager | 0 occurrences | Codebase scan (see NFR-MGR-001) |
| Filesystem walks outside ModuleService | 0 | Codebase scan (see NFR-MGR-002) |
| Remaining legacy discover-service references | 0 | Codebase scan (FR-MGR-031) |
| Registry vs on-disk mismatch | 0 | Sync check (FR-MGR-032/034) |
| Cold-cache Livewire discovery | < 2s | Time to scan and register all |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | Class conventions (Support static / Service instance) and test patterns |
| [shared-utilities.md](C8F0D-shared-utilities.md) (C8F0D) | `SmartLogger` used by `ModuleDiscoverCommand` for discovery logging |
| [module-discovery.md](I1BCV-module-discovery.md) (I1BCV) | `config/module.php` registry, `config/cache-keys.php` discovery keys, discovery baseline and conventions |

### Build Guide

Implement `App\Core\Support\ModuleManager` and `App\Core\Services\ModuleService`, migrate the
three callers (`routes/web.php`, `AppServiceProvider`, `ModuleDiscoverCommand`), and remove the
legacy discover service. Registry fidelity (FR-MGR-032/033/034) is asserted by sync tests, not by
hand-maintained lists.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [installation.md](8NZAU-installation.md) (8NZAU) | Setup/installation flows verify module registration and rely on the consolidated gateway (`module:discover`) |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume `domains()` is the canonical accessor and `submodules()` stays a deprecated alias until its last caller migrates | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Module discovery](I1BCV-module-discovery.md) — roster ownership (FR-MOD-001/002), registry shape, conventions baseline
- [Base classes](SE5Q9-base-classes.md) — Support-static vs Service-instance conventions
- [Shared utilities](C8F0D-shared-utilities.md) — `SmartLogger` for discovery logging
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — policy discovery consumer
- [Installation](8NZAU-installation.md) — setup flows verifying registration
- [ADR: Base-class mandate](../adr/adr-base-class-mandate.md) — one base per layer
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — cache-invalidation phasing
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements these rows serve
