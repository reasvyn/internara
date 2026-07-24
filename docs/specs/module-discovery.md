# Module Discovery — Feature Specification

> **Last updated:** 2026-07-22 **Changes:** feat — new spec for module registry and discovery
> system

## Description

Specification for Internara's centralized module registry and runtime discovery system.
Covers the single-source-of-truth config, app boot auto-discovery (Livewire, Policies, Blade
namespaces), route auto-inclusion, and test suite registration.

---

## 1. Problem Statements

### PS-1 — Single Source of Truth

Without a centralized registry, module lists are duplicated across `config/module.php`,
`tests/Pest.php`, and `routes/web.php`. Adding a module requires editing 3+ files, risking
inconsistencies.

### PS-2 — Discovery Performance

Scanning the entire `app/` directory for Livewire components and policies is expensive.
Discovery should be scoped to registered modules only, with results cached.

### PS-3 — Config-Driven Filtering

Only registered modules should be scanned. Unregistered directories (e.g., draft modules,
test helpers) must not be included in discovery or route auto-inclusion.

### PS-4 — Test Directory Registration

Pest discovers test directories at boot time, before `config()` is available. Module test
directories must be registered in `tests/Pest.php`, which creates a second source of truth
that must stay synchronized with the config.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | `config/module.php` is the single source of truth for module names |
| G2  | Livewire components, policies, and Blade namespaces are auto-discovered at boot |
| G3  | Only registered modules are scanned (config-driven filtering)      |
| G4  | Discovery results are cached for performance (24-hour TTL)         |
| G5  | Route files are auto-included from `routes/web/` based on registry |
| G6  | CLI `module:discover` clears cache and re-runs discovery           |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Runtime module hot-loading (discovery only runs at boot/cache clear) |
| NG2  | Cross-module dependency resolution (modules are independent)     |
| NG3  | Auto-discovery of Entity, DTO, Action, or Model classes (these use namespaces, not registration) |
| NG4  | Module enable/disable at runtime (all registered modules are active) |
| NG5  | Refactoring `tests/Pest.php` to use `config()` (Pest boots before Laravel) |

---

## 3. User Stories / Use Cases

### UC-1 — Adding a New Module

**Actor:** Developer

**Flow:**
1. Create module directory under `app/{ModuleName}/` with standard layers
2. Add module key to `$modules` array in `config/module.php` with optional submodules
3. Add test directory name to `$modules` array in `tests/Pest.php` (alphabetical order)
4. Create route file at `routes/web/{lowercase_module}.php` (optional)
5. Run `php artisan module:discover` to clear cache and verify registration

**Postconditions:** Module's Livewire components, policies, and Blade views are auto-discovered
on next boot.

### UC-2 — Adding a Submodule to Existing Module

**Actor:** Developer

**Flow:**
1. Create submodule directory under `app/{ModuleName}/{SubmoduleName}/`
2. Add submodule name to the module's array in `config/module.php`
3. Run `php artisan module:discover`

**Postconditions:** Submodule's Livewire components and policies are discovered with
kebab-case submodule prefix in alias (e.g., `enrollment.placement.show`).

### UC-3 — App Boot Discovery

**Actor:** Laravel framework (automatic)

**Flow:**
1. `AppServiceProvider::boot()` fires
2. If `module.policies.enabled`, runs `ModuleDiscoverService::discoverPolicies()`
3. If `module.livewire.enabled`, runs `ModuleDiscoverService::discoverLivewireComponents()`
4. If `module.views.enabled`, runs `ModuleDiscoverService::registerBladeNamespaces()`
5. Each method reads `config('module.list')`, scans only registered module directories,
   caches results for 24 hours

**Postconditions:** All Livewire components registered with aliases, all policies bound to
models, all Blade view namespaces registered.

### UC-4 — Route Auto-Inclusion

**Actor:** Laravel router (automatic)

**Flow:**
1. `routes/web.php` loads `config('module.list')`
2. For each module name, checks if `routes/web/{lowercase}.php` exists
3. If file exists, `require`s it

**Postconditions:** Module routes are available without manual editing of `routes/web.php`.

### UC-5 — Cache Clearing and Rediscovery

**Actor:** Developer via CLI

**Flow:**
1. Developer runs `php artisan module:discover`
2. Command resolves `ModuleDiscoverService` from container
3. Runs `discoverLivewireComponents()`, `discoverPolicies()`, `registerBladeNamespaces()`
4. Each method writes results to cache (overwriting previous)
5. Command logs completion via SmartLogger

**Postconditions:** All discovery caches are refreshed.

### UC-6 — Disabling Discovery for a Subsystem

**Actor:** Developer (rare)

**Flow:**
1. Set `module.livewire.enabled = false` (or `policies.enabled`, `views.enabled`) in config
2. AppServiceProvider skips that discovery method

**Postconditions:** Specific subsystem discovery is skipped (useful for testing or partial setups).

---

## 4. Functional Requirements

### 4.1 Module Registry

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-MR1 | `config/module.php` must define a `$modules` array with module keys and submodule arrays |
| FR-MR2 | Module names must be PascalCase (e.g., `Core`, `Enrollment`, `SysAdmin`) |
| FR-MR3 | Module names must be in dependency order (foundation → lifecycle → administration) |
| FR-MR4 | Config must export `list` key (array of module names)                   |
| FR-MR5 | Config must export `registry` key (full module → submodule mapping)     |
| FR-MR6 | Config must export `test_dirs` key (non-module test directories)        |
| FR-MR7 | Config must define `paths.base`, `paths.views`, `paths.routes`         |
| FR-MR8 | Config must define `livewire`, `policies`, `views` discovery settings  |

### 4.2 Livewire Component Discovery

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-LW1 | Discovery must scan `app/{Module}/Livewire/**/*.php` for each registered module |
| FR-LW2 | Discovery must skip `Concerns/` and `Traits/` subdirectories            |
| FR-LW3 | Discovered components must be registered with `{kebab-module}.{kebab-class}` alias |
| FR-LW4 | Submodule components must use `{kebab-module}.{kebab-submodule}.{kebab-class}` alias |
| FR-LW5 | Only classes extending `Livewire\Component` must be registered          |
| FR-LW6 | Discovery results must be cached for 24 hours under `module.discovered_livewire` |
| FR-LW7 | Only PHP files within registered modules must be scanned                |

### 4.3 Policy Discovery

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-P1 | Discovery must scan `app/{Module}/Policies/**/*.php` for each registered module |
| FR-P2 | Discovery must skip `Concerns/` and `Traits/` subdirectories            |
| FR-P3 | Only classes ending in `Policy` and extending `BasePolicy` must be registered |
| FR-P4 | Policy must be bound to its corresponding model in `app/{Module}/Models/` |
| FR-P5 | Submodule policies must bind to `app/{Module}/{Submodule}/Models/`      |
| FR-P6 | Discovery results must be cached for 24 hours under `module.discovered_policies` |
| FR-P7 | Cross-module policies must be registered manually in `AppServiceProvider` |

### 4.4 Blade View Namespace Registration

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-V1 | Discovery must scan `resources/views/{Module}/` for each registered module |
| FR-V2 | Non-module directories must be excluded (`components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor`) |
| FR-V3 | Registered modules must be registered as anonymous component paths and view namespaces |
| FR-V4 | Discovery results must be cached for 24 hours under `module.discovered_views` |
| FR-V5 | Only directories within `registeredModules` must be registered          |

### 4.5 Route Auto-Inclusion

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-R1 | `routes/web.php` must auto-include route files from `config('module.list')` |
| FR-R2 | Route file path must be `routes/web/{lowercase_module}.php`             |
| FR-R3 | Non-existent route files must be silently skipped                        |
| FR-R4 | Module names must be lowercased for file lookup                         |

### 4.6 CLI Cache Clearing

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-CLI1 | `php artisan module:discover` must clear all three discovery caches     |
| FR-CLI2 | Command must verify `AppServiceProvider` is loaded before discovery      |
| FR-CLI3 | Command must log completion/failure via SmartLogger                     |
| FR-CLI4 | Command must display task progress with translated status messages      |

### 4.7 Test Directory Registration

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-T1 | `tests/Pest.php` must register test directories for all modules         |
| FR-T2 | Module list in `tests/Pest.php` must be kept in sync with `config/module.php` |
| FR-T3 | Non-module test directories (`Providers`, `Stubs`, `Support`) must also be registered |
| FR-T4 | `config()` must not be used in `tests/Pest.php` (Pest boots before Laravel) |

---

## 5. Non-Functional Requirements

### 5.1 Performance

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-P1 | Livewire discovery must complete within 2 seconds on a cold cache    |
| NFR-P2 | Policy discovery must complete within 1 second on a cold cache       |
| NFR-P3 | View namespace registration must complete within 1 second            |
| NFR-P4 | Cached discovery must have zero filesystem overhead                  |
| NFR-P5 | All three discovery methods must share the same module list from config (single read) |

### 5.2 Reliability

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-R1 | Discovery must not crash on malformed PHP files (graceful skip)      |
| NFR-R2 | Cache must be cleared on `module:discover` and `config:clear`        |
| NFR-R3 | Duplicate alias registration must not throw (last-write-wins)        |

### 5.3 Maintainability

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-M1 | `tests/Pest.php` sync comment must reference `config/module.php`    |
| NFR-M2 | `ModuleDiscoverService` must use `getModuleNames()` for all module checks |
| NFR-M3 | All discovery methods must be individually testable                   |

### 5.4 Security

| ID     | Requirement                                                          |
| ------ | -------------------------------------------------------------------- |
| NFR-S1 | Discovery must not register classes from unregistered directories    |
| NFR-S2 | Policy discovery must only bind policies extending `BasePolicy`      |

---

## 6. API / Data Contracts

### 6.1 Config Structure

```php
// config/module.php
[
    'list' => ['Core', 'Setup', 'Settings', /* ... */], // array_keys($modules)
    'registry' => [
        'Core' => ['Channels', 'Console', 'Contracts', 'Exceptions'],
        'Setup' => ['Installation', 'SetupWizard'],
        // ...
    ],
    'test_dirs' => ['Providers', 'Stubs', 'Support'],
    'paths' => [
        'base' => app_path(),
        'views' => resource_path('views'),
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

### 6.2 ModuleDiscoverService API

```php
class ModuleDiscoverService
{
    /** Get list of registered module names. */
    public static function getModuleNames(): array;

    /** Check if a directory name is a registered module. */
    public static function isModule(string $name): bool;

    /** Scan and register Livewire components from registered modules. */
    public function discoverLivewireComponents(): void;

    /** Scan and register policies from registered modules. */
    public function discoverPolicies(): void;

    /** Scan and register Blade view namespaces from registered modules. */
    public function registerBladeNamespaces(): void;
}
```

### 6.3 Livewire Alias Convention

| Structure                        | Alias Pattern                            | Example                    |
| -------------------------------- | ---------------------------------------- | -------------------------- |
| `Module/Livewire/Class.php`     | `module.class`                           | `auth.login-form`          |
| `Module/Submodule/Livewire/Class.php` | `module.submodule.class`           | `enrollment.placement.show`|

### 6.4 Policy Binding Convention

| Structure                        | Model Path                               | Example                    |
| -------------------------------- | ---------------------------------------- | -------------------------- |
| `Module/Policies/ModelPolicy.php` | `App\Module\Models\Model`              | `Journals\Models\Attendance` |
| `Module/Submodule/Policies/ModelPolicy.php` | `App\Module\Submodule\Models\Model` | `Partners\Company\Models\Company` |

### 6.5 Cache Keys

| Key                        | Config Reference              | TTL  |
| -------------------------- | ----------------------------- | ---- |
| `module.discovered_livewire` | `cache-keys.module_livewire` | 86400 |
| `module.discovered_policies` | `cache-keys.module_policies` | 86400 |
| `module.discovered_views`    | `cache-keys.module_views`    | 86400 |

---

## 7. Design Decisions

### DD-1 — Config-Only Discovery, No Pest Integration

**Decision:** `tests/Pest.php` maintains a hardcoded module list synchronized with
`config/module.php`, rather than using `config()`.

**Rationale:** Pest's test discovery runs before Laravel boots, so `config()` is unavailable.
The sync comment in `tests/Pest.php` documents the dependency, and the list must be updated
manually when modules change. This is the only acceptable deviation from the single-source
principle.

### DD-2 — Submodule Alias Naming

**Decision:** Livewire aliases for submodule components use three-part names:
`module.submodule.class`.

**Rationale:** Prevents alias collisions between submodules (e.g., `enrollment.placement.show`
vs `enrollment.registration.show`). The kebab-case convention (`placement-list`) matches
Livewire's standard naming.

### DD-3 — Static Methods for Module Checks

**Decision:** `getModuleNames()` and `isModule()` are `public static` on `ModuleDiscoverService`.

**Rationale:** These are pure config reads that don't require instance state or I/O. Static
access allows use in contexts where the container isn't available (e.g., route files, model
boot methods) without violating the service pattern.

### DD-4 — 24-Hour Cache TTL

**Decision:** Discovery results cached for 24 hours (86400 seconds).

**Rationale:** Module structure changes are infrequent (code deployments). During development,
`module:discover` or `config:clear` busts the cache. In production, 24 hours is a reasonable
balance between boot performance and freshness.

### DD-5 — Route Auto-Inclusion Pattern

**Decision:** `routes/web.php` loops through `config('module.list')` and requires route files
by convention (`routes/web/{lowercase}.php`).

**Rationale:** Eliminates manual `require` statements when adding modules. The `file_exists`
check silently skips missing files, so modules without routes don't need empty route files.

---

## 8. Success Metrics

### 8.1 Correctness

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Registered modules discovered   | 100%        | All modules in config have discovery  |
| Unregistered dirs excluded      | 100%        | No discovery from non-module dirs     |
| Alias collision rate            | 0%          | All aliases unique                    |

### 8.2 Performance

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Cold cache Livewire discovery   | < 2s        | Time to scan and register all         |
| Warm cache overhead             | ~0ms        | Cache hit, no filesystem access       |

### 8.3 Maintainability

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Files to edit for new module    | 2           | `config/module.php` + `tests/Pest.php`|
| Pest.php sync drift             | Never       | Manual review on module changes       |

---

## 9. Roadmap

### Prerequisites
No prerequisites — this is a foundational spec.

### Build Guide
After implementing this spec, the system automatically discovers Livewire components, authorization policies, and Blade view namespaces at boot time. Discovery results are cached for 24 hours. The next step is to build the logging and error handling infrastructure that these discovered components will use.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [logging-and-error-handling.md](logging-and-error-handling.md) | Discovered Livewire components use `SmartLogger` and exception hierarchy |

## Quick References

- `config/module.php` — Module registry (single source of truth)
- `config/cache-keys.php` — Cache key definitions
- `app/Core/Services/ModuleDiscoverService.php` — Discovery implementation
- `app/Core/Console/Commands/ModuleDiscoverCommand.php` — CLI cache clear
- `app/Providers/AppServiceProvider.php` — Boot-time discovery registration
- `routes/web.php` — Route auto-inclusion
- `tests/Pest.php` — Test directory registration
- `docs/modules/core.md` — Core module conceptual overview
- `docs/modules/core-reference.md` — Core module technical reference
- `docs/architecture/service-pattern.md` — Service pattern documentation
- `docs/architecture/modular-pattern.md` — Modular architecture documentation
