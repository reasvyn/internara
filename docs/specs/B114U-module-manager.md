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

**Actor:** Laravel router (automatic)
**Preconditions:** Registry populated from `config/module.php`.
**Flow:**
1. `routes/web.php` resolves `ModuleManager::names()`
2. For each module name, resolves `ModuleManager::routeFilePath($module)`
3. If the file exists, `require`s it
**Postconditions:** Module routes load without any direct `config('module.*')` access.
**Governing guidance:** FR-MGR-001 (names), FR-MGR-011 (route path).

#### UC-MGR-002 — Boot-Time Discovery

**Actor:** Laravel framework (automatic)
**Preconditions:** Application booting; gateway flags set.
**Flow:**
1. `AppServiceProvider::boot()` fires
2. Gates on `ModuleManager::policiesEnabled()` → `ModuleService::discoverPolicies()`
3. Gates on `ModuleManager::livewireEnabled()` → `ModuleService::discoverLivewireComponents()`
4. Gates on `ModuleManager::viewsEnabled()` → `ModuleService::registerBladeNamespaces()`
**Postconditions:** All Livewire components, policies, and Blade namespaces registered; all
module config reads go through `ModuleManager`.
**Governing guidance:** FR-MGR-007 (flags), FR-MGR-016/017/018 (discovery).

#### UC-MGR-003 — CLI Rediscovery

**Actor:** Developer via CLI
**Preconditions:** Container booted.
**Flow:**
1. Developer runs `php artisan module:discover`
2. Command resolves `ModuleService` from the container
3. Runs the three discovery methods; each writes results to cache
4. Command logs completion/failure via SmartLogger
**Postconditions:** All discovery caches refreshed through the single service.
**Governing guidance:** FR-MGR-015 (injection), FR-MGR-020 (registered cache keys).

#### UC-MGR-004 — Checking a Module Flag

**Actor:** Any consumer (e.g., `AppServiceProvider`, tests)
**Preconditions:** Config loaded.
**Flow:**
1. Consumer calls `ModuleManager::policiesEnabled()` (or `livewireEnabled()`, `viewsEnabled()`)
2. `ModuleManager` reads the typed accessor from `config/module.php`
**Postconditions:** Callers never reference `config('module.*')` directly.
**Governing guidance:** FR-MGR-007; unit-testable per NFR-MGR-003.

#### UC-MGR-005 — Adding a New Module

**Actor:** Developer
**Preconditions:** None beyond a working checkout.
**Flow:**
1. Add the module directory under `app/Modules/` with its domains
2. Registry picks it up from the directory listing (auto-discovered, deterministic order)
3. Add the test directory to `tests/Pest.php` (manual — Pest boots before Laravel)
4. Create the route file at `routes/web/{lowercase_module}.php` (optional)
5. Run `php artisan module:discover`
**Postconditions:** Naming rules (route file path, view directory, Livewire alias) apply
automatically; roster-manual steps stay in I1BCV, not here.
**Governing guidance:** FR-MGR-033/034 (registry fidelity); manual journey, verified by review
rather than a pest layer — hence `—`.

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

- `names()` is the single list every scanner and the router consume; dependency order comes from
  the registry, never from call-site sorting.
- **Verification:** unit test asserting registry order passthrough (layer `U`).

#### FR-MGR-002 — Strict membership

- Identity comparison only — case variants and substrings never match, so `isModule('user')`
  stays false next to `User`.
- **Verification:** unit test with near-miss inputs (layer `U`).

#### FR-MGR-003 — Module-to-domain map

- Full registry passthrough for scanners that scope walks per module.
- **Verification:** unit test asserting map equality (layer `U`).

#### FR-MGR-004 — Domain accessor

- `domains()` is the canonical accessor; `submodules()` survives only as a deprecated alias
  from the submodule era — new code must call `domains()` (see §10 A-1).
- **Verification:** unit test known module plus unknown-module empty array (layer `U`).

#### FR-MGR-005 — Test directories

- Non-module test roots (`Providers`, `Stubs`, `Support`) stay visible to the bootstrap without
  polluting the module list.
- **Verification:** unit test (layer `U`).

#### FR-MGR-006 — Path accessors

- Base, views, and routes roots come from config so a relocated tree updates in one place.
- **Verification:** unit test with overridden config (layer `U`).

#### FR-MGR-007 — Typed feature flags

- Boolean gates for the three discovery subsystems plus factories; no caller reads a raw flag
  key.
- **Verification:** unit test per flag on/off (layer `U`).

#### FR-MGR-008 — Livewire settings

- Discovery directory plus exclusion paths (e.g. shared concerns) flow through the gateway so
  the scanner never hardcodes them.
- **Verification:** unit test (layer `U`).

#### FR-MGR-009 — Policy settings

- Discovery directory, exclusions, and the model namespace used to bind policies to same-module
  models.
- **Verification:** unit test (layer `U`).

#### FR-MGR-010 — View exclusions

- Non-module view directories excluded from namespace registration.
- **Verification:** unit test (layer `U`).

#### FR-MGR-011 — Route file path

- `{routesPath}/{Str::lower(module)}.php` — the one spelling of the route-file rule; the router
  obeys it blindly.
- **Verification:** unit test with mixed-case module (layer `U`).

#### FR-MGR-012 — Case-insensitive directory check

- Lowercase view directory names match PascalCase registry names — the exact comparison that
  fixes the `registerBladeNamespaces()` case-mismatch bug class.
- **Verification:** unit test lowercase vs PascalCase (layer `U`).

#### FR-MGR-013 — No scanning in the gateway

- Pure config reads; any filesystem touch inside `ModuleManager` is a layering violation
  (NFR-MGR-002's mirror).
- **Verification:** contract scan (layer `A`).

#### FR-MGR-014 — Static Support shape

- `final` class, `public static` methods, no constructor — per the service/support pattern and
  the [base-class-mandate ADR](../adr/adr-base-class-mandate.md).
- **Verification:** class-contract scan (layer `A`).

### 4.2 ModuleService (Discovery Orchestrator)

#### FR-MGR-015 — Injected cache

- `__construct(private Repository $cache)` — the service never touches the `Cache` facade
  directly, so tests inject fakes.
- **Verification:** contract scan + container resolution test (layer `A`).

#### FR-MGR-016 — Livewire discovery

- Scans registered modules for components, registers aliases per FR-MGR-023, caches the map.
- **Verification:** discovery feature test asserting registered aliases (layer `F`).

#### FR-MGR-017 — Policy discovery

- Binds each `BasePolicy` subclass to its same-module model; only `BasePolicy` subclasses bind
  (NFR-MGR-008).
- **Verification:** discovery test asserting gate bindings (layer `F`).

#### FR-MGR-018 — Blade namespaces

- Registers per-module view namespaces plus anonymous component paths with the
  case-insensitive directory check (FR-MGR-012).
- **Verification:** discovery test asserting namespace registration (layer `F`).

#### FR-MGR-019 — Gateway-only config

- A direct `config()` call inside `ModuleService` reintroduces the typo-default hazard the
  gateway exists to kill.
- **Verification:** scan for `config(` inside the service (layer `A`).

#### FR-MGR-020 — Registered cache keys, 24h TTL

- Keys `module.discovered_livewire`, `module.discovered_policies`, `module.discovered_views`
  live in `config/cache-keys.php`; `CACHE_TTL_SECONDS = 86400`.
- **Verification:** registry audit + TTL assertion (layer `F`).

#### FR-MGR-021 — Registered modules only

- Unregistered directories never enter a scan — stray folders cannot inject components,
  policies, or views.
- **Verification:** fixture test with an unregistered directory present (layer `F`).

#### FR-MGR-022 — Skip shared traits

- `Concerns/` and `Traits/` hold shared helpers, never registrable classes.
- **Verification:** fixture test (layer `F`).

#### FR-MGR-023 — Kebab alias rules

- `{kebab-module}.{kebab-class}` for flat modules, with the submodule segment for nested ones;
  the single spelling owned here, not per caller.
- **Verification:** alias assertion per component shape (layer `F`).

#### FR-MGR-024 — Same-module binding

- A policy never binds a foreign module's model; cross-module authorization goes through the
  owning module's policy ([T4B26](T4B26-rbac-and-authorization.md)).
- **Verification:** binding audit in discovery test (layer `F`).

#### FR-MGR-025 — View exclusions honored

- Configured non-module directories stay out of namespace registration.
- **Verification:** fixture test (layer `F`).

### 4.3 Migration

#### FR-MGR-026 — Router migration

- Three call sites collapse to two gateway calls; route loading gains the naming guarantee.
- **Verification:** scan `routes/web.php` for direct `config('module` (layer `A`).

#### FR-MGR-027 — Provider migration

- Boot gates each discovery method on its flag; the provider holds no registry knowledge.
- **Verification:** provider audit (layer `A`).

#### FR-MGR-028 — Command migration

- `module:discover` runs the three discovery methods and logs completion/failure; cache
  clearing rides along.
- **Verification:** command invocation test (layer `F`).

#### FR-MGR-029 — Test migration

- Discovery coverage asserts against the service surface; legacy names appear nowhere.
- **Verification:** test-suite grep for legacy references (layer `A`).

#### FR-MGR-030 — Config untouched

- Registry shape changes belong to [I1BCV](I1BCV-module-discovery.md); this spec adds accessors,
  never keys.
- **Verification:** config diff audit (layer `A`).

#### FR-MGR-031 — Legacy removal

- No class, import, comment, or test may name the superseded discover service.
- **Verification:** codebase scan (layer `A`).

#### FR-MGR-032 — Directory fidelity

- `config/module.php` derives from the `app/Modules/` listing (`scandir`, sorted) — adding a
  module directory is what registers it; no manual list to forget.
- **Verification:** on-disk vs registry comparison test (layer `A`).

### 4.4 Roster and Registry Sync

> Roster ownership: [module-discovery.md](I1BCV-module-discovery.md) FR-MOD-001/002. The rows
> below are this spec's consumption contract — what the gateway guarantees about a roster it
> does not own.

#### FR-MGR-033 — Frozen roster consumption

- The gateway neither adds nor renames modules; its output is the roster, verbatim, in
  dependency order. A 20th on-disk directory fails this row until I1BCV amends the roster.
- **Verification:** roster assertion test against the 19-name list (layer `A`).

#### FR-MGR-034 — Four-way sync

- Config list, Pest directories, module graph doc, and gateway output agree; the amendment-plus-ADR
  rule makes silent drift a process violation, not just a test failure.
- **Verification:** cross-artifact sync check (layer `A`).

#### FR-MGR-035 — Single spelling per convention

- Route path via `routeFilePath()`, view directory via `isRegisteredDirectory()`, aliases via
  the service generator — the §6.4 table is the complete list.
- **Verification:** scan for `Str::lower`/`Str::kebab` module derivations outside the two
  classes (layer `A`).

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

- Enforced by scan script; `tests/Pest.php` is the documented exception (boots before Laravel).
- **Verification:** codebase scan for `config('module` outside the gateway (layer `A`).

#### NFR-MGR-002 — Scanner monopoly on walks

- Directory iteration for discovery lives in one class; ad-hoc `scandir` elsewhere is a finding.
- **Verification:** scan for filesystem walks outside the service (layer `A`).

#### NFR-MGR-003 — Accessor testability

- Pure functions of config — each accessor tested in isolation with overridden config values.
- **Verification:** unit suite per accessor (layer `U`).

#### NFR-MGR-004 — Membership through `names()`

- `isModule()` and `isRegisteredDirectory()` both bottom out in `names()`; no parallel list.
- **Verification:** unit tests + review (layer `U`).

### 5.2 Reliability

#### NFR-MGR-005 — Graceful skip on malformed files

- A broken PHP file in a module directory degrades to a skipped entry with a logged warning —
  never a boot fatal.
- **Verification:** malformed-fixture discovery test (layer `F`).

#### NFR-MGR-006 — Cache clearing

- Rediscovery and config clears flush the three discovery keys; stale bindings never survive a
  deploy.
- **Verification:** clear-then-rediscover test (layer `F`).

### 5.3 Security

#### NFR-MGR-007 — Registered directories only

- Mirrors FR-MGR-021 as a security property: unregistered code cannot gain routes, components,
  or view namespaces.
- **Verification:** unregistered-directory fixture test (layer `F`).

#### NFR-MGR-008 — BasePolicy-only binding

- A policy outside the `BasePolicy` chain (no `before()` bypass, no role traits) must never
  receive a gate binding.
- **Verification:** non-conforming-policy fixture test (layer `A`).

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

**Decision:** Two classes: `Support\ModuleManager` (static config gateway) and
`Services\ModuleService` (instance discovery orchestrator).
**Rationale:** Mirrors the service-pattern rules — pure config reads with no state are
`public static` (Support); orchestration with cache and filesystem dependencies uses constructor
injection (Service). Each class has one responsibility and is independently testable.
**Trade-off:** Two classes instead of one; callers must choose the correct one.

#### DD-MGR-002 — ModuleService Supersedes the Legacy Discover Service

**Decision:** The new service replaces the legacy discover service; the old class is removed and
its callers migrate.
**Rationale:** Keeping both classes would duplicate scanning logic and confuse ownership.
Migration is small (three call sites, one test file).
**Trade-off:** Callers must learn the new surface; mitigated by the small call-site count.

#### DD-MGR-003 — Centralized Naming Conventions

**Decision:** Route file paths and view directory checks are owned by `ModuleManager`
(`routeFilePath()`, `isRegisteredDirectory()`); Livewire alias generation stays in
`ModuleService`.
**Rationale:** The `registerBladeNamespaces()` case-mismatch bug proves conventions drift without
a single owner. Centralizing them makes the rules testable in isolation.
**Trade-off:** Convention changes touch shared code — accepted, since changes are rare and now
visible.

#### DD-MGR-004 — Typed Accessors Over Generic `config()`

**Decision:** `ModuleManager` exposes granular, typed methods (e.g., `policiesEnabled()`,
`livewireDirectory()`) rather than a generic `config(string $key)` wrapper.
**Rationale:** Prevents key typos (which silently return defaults), enables static analysis, and
makes the available module configuration discoverable. A generic wrapper would just relocate the
typo problem.
**Trade-off:** More surface area; adding a config key requires a new accessor.

#### DD-MGR-005 — Config Remains the Single Source of Truth

**Decision:** No runtime enable/disable of modules or discovery subsystems is introduced.
`config/module.php` flags continue to gate discovery.
**Rationale:** Consistent with [module-discovery.md](I1BCV-module-discovery.md) NG4 and DD-1.
Runtime mutability adds cache-invalidation complexity without a verified need.
**Trade-off:** Disabling a module requires a config change plus rediscovery — acceptable at this
scale.

#### DD-MGR-006 — Roster Ownership in I1BCV

**Decision:** This spec consumes the frozen roster and enforces sync (FR-MGR-033/034) but never
amends membership; additions, removals, and renames go through I1BCV FR-MOD-001/002.
**Rationale:** One owner per fact — the roster lives in module-discovery, the gateway fronts it.
Two amendment paths would let them disagree.
**Trade-off:** Roster-adjacent work in this area requires touching two specs — the cost of no
duplication.

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
