# Module Manager & Service — Single Gateway for Module Infrastructure

> **Spec ID:** B114U

## Description

Consolidates all module infrastructure and configuration access behind two Core classes:
`Support\ModuleManager` (static config gateway) and `Services\ModuleService` (instance discovery
orchestrator). Eliminates scattered `config('module.*')` reads and filesystem scanning across
`routes/web.php`, `AppServiceProvider`, and `ModuleDiscoverService`.

---

## 1. Problem Statements

### PS-1 — Scattered Direct Config Access

`config('module.*')` is read directly in multiple places: `routes/web.php` (module list),
`AppServiceProvider` (three `enabled` flags), and `ModuleDiscoverService` (seven reads). Keys and
defaults are duplicated at each call site. A typo silently falls back to a default, and there is no
type safety or single point to document what the module configuration exposes.

### PS-2 — Mixed Static/Instance Responsibilities

`ModuleDiscoverService` mixes static config reads (`getModuleNames()`, `isModule()`) with instance
discovery operations (`discoverLivewireComponents()`, `discoverPolicies()`,
`registerBladeNamespaces()`). Per the service pattern, Services must use instance methods with
constructor injection — static methods are only permitted for framework hooks. The class also has no
constructor injection despite being documented as a Service, and its static config reads leak the
module registry beyond the class that owns it.

### PS-3 — Scattered Filesystem Scanning & Naming Drift

PHP file scanning lives inside `ModuleDiscoverService`, while path/naming conventions are
re-implemented at each caller: route files use `Str::lower()`, view directories are lowercase, and
Livewire aliases use `Str::kebab()`. The `registerBladeNamespaces()` bug — comparing lowercase view
directory names against PascalCase module names — demonstrates that these conventions drift without
a single owner.

### PS-4 — No Central Naming Conventions

Each consumer re-derives module names into route file names, view directory names, and Livewire
aliases independently. There is no shared definition of these transformations, so they can disagree
(as with the view directory mismatch above).

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                                        |
| --- | --------------------------------------------------------------------------- |
| G1  | `Support\ModuleManager` is the single static gateway for all module config reads |
| G2  | `Services\ModuleService` is the instance orchestrator for module discovery with constructor injection |
| G3  | All `config('module.*')` callers migrate to `ModuleManager`                |
| G4  | `ModuleDiscoverService` is superseded by `ModuleService` and removed        |
| G5  | Filesystem scanning is confined to `ModuleService`                          |
| G6  | Naming conventions (route file, view directory, Livewire alias) are centralized |

### Non-Goals

| ID   | Non-Goal                                                              |
| ---- | --------------------------------------------------------------------- |
| NG1  | Runtime module hot-loading or enable/disable (registry stays config-driven) |
| NG2  | Cross-module dependency resolution (modules remain independent)       |
| NG3  | Refactoring `tests/Pest.php` to use `config()` (Pest boots before Laravel) |
| NG4  | Domain business logic in either class (infrastructure only)            |
| NG5  | New runtime configuration surface beyond `config/module.php`           |

---

## 3. User Stories / Use Cases

### UC-1 — Route Auto-Inclusion

**Actor:** Laravel router (automatic)

**Flow:**
1. `routes/web.php` resolves `ModuleManager::names()`
2. For each module name, resolves `ModuleManager::routeFilePath($module)`
3. If the file exists, `require`s it

**Postconditions:** Module routes load without any direct `config('module.*')` access.

### UC-2 — Boot-Time Discovery

**Actor:** Laravel framework (automatic)

**Flow:**
1. `AppServiceProvider::boot()` fires
2. Gates on `ModuleManager::policiesEnabled()` → `ModuleService::discoverPolicies()`
3. Gates on `ModuleManager::livewireEnabled()` → `ModuleService::discoverLivewireComponents()`
4. Gates on `ModuleManager::viewsEnabled()` → `ModuleService::registerBladeNamespaces()`

**Postconditions:** All Livewire components, policies, and Blade namespaces are registered; all
module config reads go through `ModuleManager`.

### UC-3 — CLI Rediscovery

**Actor:** Developer via CLI

**Flow:**
1. Developer runs `php artisan module:discover`
2. Command resolves `ModuleService` from the container
3. Runs the three discovery methods; each writes results to cache
4. Command logs completion/failure via SmartLogger

**Postconditions:** All discovery caches are refreshed through the single service.

### UC-4 — Checking a Module Flag

**Actor:** Any consumer (e.g., `AppServiceProvider`, tests)

**Flow:**
1. Consumer calls `ModuleManager::policiesEnabled()` (or `livewireEnabled()`, `viewsEnabled()`)
2. `ModuleManager` reads the typed accessor from `config/module.php`

**Postconditions:** Callers never reference `config('module.*')` directly.

### UC-5 — Adding a New Module

**Actor:** Developer

**Flow:**
1. Add module key to `$modules` in `config/module.php` (with submodules)
2. Add test directory to `tests/Pest.php` (manual, per `module-discovery.md` #5 DD-1)
3. Create route file at `routes/web/{lowercase_module}.php` (optional)
4. Run `php artisan module:discover`

**Postconditions:** Naming rules (route file path, view directory, Livewire alias) are applied
automatically by `ModuleManager`/`ModuleService`.

---

## 4. Functional Requirements

### 4.1 ModuleManager (Support gateway)

| ID     | Requirement                                                                 |
| ------ | --------------------------------------------------------------------------- |
| FR-MG1 | `ModuleManager::names(): array` must return the registered module list from `config('module.list')` |
| FR-MG2 | `ModuleManager::isModule(string $name): bool` must be a strict membership check against `names()` |
| FR-MG3 | `ModuleManager::registry(): array` must return the module → submodule mapping |
| FR-MG4 | `ModuleManager::submodules(string $module): array` must return the module's submodule list (empty array if unknown) |
| FR-MG5 | `ModuleManager::testDirs(): array` must return `config('module.test_dirs')` |
| FR-MG6 | `ModuleManager::basePath()`, `viewsPath()`, `routesPath()` must return the configured paths |
| FR-MG7 | `ModuleManager` must expose typed boolean accessors: `policiesEnabled()`, `livewireEnabled()`, `viewsEnabled()`, `factoriesEnabled()` |
| FR-MG8 | `ModuleManager::livewireDirectory(): string` and `livewireExcludePaths(): array` must return the Livewire discovery settings |
| FR-MG9 | `ModuleManager::policiesDirectory()`, `policiesExcludePaths()`, `policyModelNamespace()` must return the policy discovery settings |
| FR-MG10 | `ModuleManager::viewsExcludeDirectories(): array` must return the view namespace exclusions |
| FR-MG11 | `ModuleManager::routeFilePath(string $module): string` must return the route file path using the `Str::lower()` convention |
| FR-MG12 | `ModuleManager::isRegisteredDirectory(string $directoryName): bool` must compare case-insensitively against `names()` |
| FR-MG13 | `ModuleManager` must not perform any filesystem scanning (config reads only) |
| FR-MG14 | All `ModuleManager` methods must be `public static` with no constructor (Support rules) |

### 4.2 ModuleService (discovery orchestrator)

| ID     | Requirement                                                                 |
| ------ | --------------------------------------------------------------------------- |
| FR-MS1 | `ModuleService` must accept a cache repository via constructor injection     |
| FR-MS2 | `ModuleService::discoverLivewireComponents(): void` must register discovered Livewire components |
| FR-MS3 | `ModuleService::discoverPolicies(): void` must bind discovered policies to models |
| FR-MS4 | `ModuleService::registerBladeNamespaces(): void` must register view namespaces and anonymous component paths |
| FR-MS5 | All `ModuleService` config reads must go through `ModuleManager` (no direct `config()`) |
| FR-MS6 | Discovery caching must use keys from `config/cache-keys.php` (registered keys, 24-hour TTL) |
| FR-MS7 | Discovery must scan only registered modules (`ModuleManager::names()`) |
| FR-MS8 | Livewire/policy discovery must skip `Concerns/` and `Traits/` subdirectories |
| FR-MS9 | Livewire aliases must use `{kebab-module}.{kebab-class}` and `{kebab-module}.{kebab-submodule}.{kebab-class}` |
| FR-MS10 | Policies must bind to models in the same module (or submodule) `Models/` directory |
| FR-MS11 | View registration must exclude the configured non-module directories |

### 4.3 Migration

| ID      | Requirement                                                                 |
| ------- | --------------------------------------------------------------------------- |
| FR-MIG1 | `ModuleDiscoverService` must be removed; no remaining code may reference it  |
| FR-MIG2 | `routes/web.php` must use `ModuleManager::names()` and `ModuleManager::routeFilePath()` |
| FR-MIG3 | `AppServiceProvider` must inject `ModuleService` and gate on `ModuleManager` flags |
| FR-MIG4 | `ModuleDiscoverCommand` must resolve `ModuleService` from the container      |
| FR-MIG5 | Tests for discovery must target `ModuleService`                             |
| FR-MIG6 | `config/module.php` must remain the single source of truth (no schema change) |

---

## 5. Non-Functional Requirements

### 5.1 Maintainability

| ID     | Requirement                                                                 |
| ------ | --------------------------------------------------------------------------- |
| NFR-M1 | No `config('module.*')` calls may exist outside `ModuleManager` (enforced by scan script) |
| NFR-M2 | No filesystem scanning may exist outside `ModuleService`                    |
| NFR-M3 | Every `ModuleManager` accessor must be individually unit-testable           |
| NFR-M4 | `ModuleManager` must use `names()` for all module membership checks         |

### 5.2 Performance

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-P1 | Livewire discovery must complete within 2 seconds on a cold cache    |
| NFR-P2 | Policy discovery must complete within 1 second on a cold cache       |
| NFR-P3 | Cached discovery must have zero filesystem overhead                  |

### 5.3 Reliability

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-R1 | Discovery must not crash on malformed PHP files (graceful skip)      |
| NFR-R2 | Cache must be cleared on `module:discover` and `config:clear`        |

### 5.4 Security

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-S1 | Discovery must not register classes from unregistered directories    |
| NFR-S2 | Policy discovery must only bind policies extending `BasePolicy`      |

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

    /** @return array<string, list<string>> Module → submodule mapping. */
    public static function registry(): array;

    /** @return list<string> Submodules for a module (empty if unknown). */
    public static function submodules(string $module): array;

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

- Config structure: `config/module.php` — see `module-discovery.md` (I1BCV) §6.1 (unchanged; FR-MIG6).
- Cache keys: `config/cache-keys.php` keys `module_livewire`, `module_policies`, `module_views`,
  TTL 86400 — see `module-discovery.md` (I1BCV) §6.5 (unchanged).

### 6.4 Naming Conventions (centralized)

| Artifact              | Rule                      | Owner                 |
| --------------------- | ------------------------- | --------------------- |
| Route file            | `routes/web/{Str::lower(module)}.php` | `ModuleManager::routeFilePath()` |
| View directory        | lowercase module name      | `ModuleManager::isRegisteredDirectory()` |
| Livewire alias        | `{Str::kebab(module)}.{class}` / `{module}.{submodule}.{class}` | `ModuleService` |

---

## 7. Design Decisions

### DD-1 — Support vs Service Split

**Decision:** Two classes: `Support\ModuleManager` (static config gateway) and
`Services\ModuleService` (instance discovery orchestrator).

**Rationale:** Mirrors the service-pattern rules — pure config reads with no state are
`public static` (Support); orchestration with cache and filesystem dependencies uses constructor
injection (Service). Each class has one responsibility and is independently testable.

**Trade-off:** Two classes instead of one; callers must choose the correct one.

### DD-2 — ModuleService Supersedes ModuleDiscoverService

**Decision:** `ModuleService` replaces `ModuleDiscoverService`; the old class is removed and its
callers migrate.

**Rationale:** The "Discover" name undersells the class's role as the module infrastructure gateway.
Keeping both classes would duplicate scanning logic and confuse ownership. Migration is small (three
call sites, one test file).

### DD-3 — Centralized Naming Conventions

**Decision:** Route file paths and view directory checks are owned by `ModuleManager`
(`routeFilePath()`, `isRegisteredDirectory()`); Livewire alias generation stays in `ModuleService`.

**Rationale:** The `registerBladeNamespaces()` case-mismatch bug proves conventions drift without a
single owner. Centralizing them makes the rules testable in isolation.

### DD-4 — Typed Accessors Over Generic `config()`

**Decision:** `ModuleManager` exposes granular, typed methods (e.g., `policiesEnabled()`,
`livewireDirectory()`) rather than a generic `config(string $key)` wrapper.

**Rationale:** Prevents key typos (which silently return defaults), enables static analysis, and
makes the available module configuration discoverable. A generic wrapper would just relocate the
typo problem.

**Trade-off:** More surface area; adding a config key requires a new accessor.

### DD-5 — Config Remains the Single Source of Truth

**Decision:** No runtime enable/disable of modules or discovery subsystems is introduced.
`config/module.php` flags continue to gate discovery.

**Rationale:** Consistent with `module-discovery.md` (I1BCV) NG4 and DD-1. Runtime mutability adds
cache-invalidation complexity without a verified need.

---

## 8. Success Metrics

| Metric                                       | Target             | Measurement                              |
| -------------------------------------------- | ------------------ | ---------------------------------------- |
| `config('module.*')` access outside ModuleManager | 0 occurrences      | Codebase scan (see NFR-M1)               |
| Remaining `ModuleDiscoverService` references | 0                  | Codebase scan (FR-MIG1)                  |
| Registered modules discovered                | 100%               | All modules in config have discovery     |
| Unregistered dirs excluded                   | 100%               | No discovery from non-module dirs        |
| Cold-cache Livewire discovery                | < 2s               | Time to scan and register all            |
| Targeted discovery tests pass                | 100%               | ModuleService test suite                 |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | Class conventions (Support static / Service instance) and test patterns |
| [shared-utilities.md](C8F0D-shared-utilities.md) (C8F0D) | `SmartLogger` used by `ModuleDiscoverCommand` for discovery logging |
| [module-discovery.md](I1BCV-module-discovery.md) (I1BCV) | `config/module.php` registry, `config/cache-keys.php` (`module_livewire`, `module_policies`, `module_views`), discovery baseline and conventions |

### Build Guide

Implement `App\Core\Support\ModuleManager` and `App\Core\Services\ModuleService`, migrate the three
callers (`routes/web.php`, `AppServiceProvider`, `ModuleDiscoverCommand`), and remove
`ModuleDiscoverService`. The next step is to build the Configuration phase, whose setup flows verify
module registration through the consolidated gateway.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [installation.md](8NZAU-installation.md) (8NZAU) | Setup/installation flows verify module registration and rely on the consolidated gateway (`module:discover`) |

## Quick References

- `config/module.php` — Module registry (single source of truth)
- `config/cache-keys.php` — Cache key definitions
- `app/Core/Support/ModuleManager.php` — Static module config gateway
- `app/Core/Services/ModuleService.php` — Discovery orchestrator
- `app/Core/Console/Commands/ModuleDiscoverCommand.php` — CLI cache clear
- `app/Providers/AppServiceProvider.php` — Boot-time discovery registration
- `routes/web.php` — Route auto-inclusion
- `docs/specs/module-discovery.md` (I1BCV) — Registry, discovery conventions, config contract
- `docs/guides/arch/service-pattern.md` — Service vs Support boundaries
- `docs/refs/modules/core.md` — Core module conceptual overview
