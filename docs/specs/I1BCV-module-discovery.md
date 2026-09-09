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

A new intern joining an SMK deployment team expects adding a capability to mean creating directories, not rewiring the framework. She creates `app/Modules/{Module}/` with the standard layers, confirms the registry in `config/module.php` picks it up from the filesystem — remembering that a genuinely new name still needs a spec amendment first under the frozen roster in FR-MOD-001 — adds the test directory name alphabetically in `tests/Pest.php`, drops an optional route file at `routes/web/{lowercase_module}.php`, and runs `php artisan module:discover` to clear caches and verify registration. On the next boot the module's Livewire components, policies, and Blade views are discovered without further wiring. The walk exercises FR-MOD-001 through FR-MOD-003 alongside FR-MOD-034 and FR-MOD-038.

#### UC-MOD-002 — Adding a Submodule to an Existing Module

Inside `ModuleService::discoverLivewireComponents()` the scanner descends into `Domain/` subdirectories, so a submodule is just a new folder. The developer creates `app/Modules/{Module}/Domain/{Submodule}/`, runs `php artisan module:discover`, and the components surface with the kebab-case submodule prefix — `enrollment.placement.show` rather than a colliding bare `show`. The flow exercises FR-MOD-014 and FR-MOD-020.

#### UC-MOD-005 — Cache Clearing and Rediscovery

After renaming a policy file, a developer once spent an hour wondering why the old binding still resolved — the 24-hour cache had kept the stale map. The fix is one command: `php artisan module:discover` resolves `ModuleService` from the container, runs `discoverLivewireComponents()`, `discoverPolicies()`, and `registerBladeNamespaces()`, overwrites each cache entry, exits `0`, and logs completion through SmartLogger. Every discovery cache comes back fresh. The path exercises FR-MOD-034 through FR-MOD-037.

#### UC-MOD-006 — Disabling Discovery for a Subsystem

Rarely — usually while isolating a broken view namespace in a partial test setup — a developer needs one subsystem quiet while the rest discovers normally. Setting `module.livewire.enabled` to false, or the `policies.enabled` or `views.enabled` siblings, makes `AppServiceProvider` skip that discovery method entirely. Everything else boots untouched. The toggle exercises FR-MOD-010.

### 3.2 System Flows

#### UC-MOD-003 — App Boot Discovery

`AppServiceProvider::boot()` fires and three guarded calls follow: policies when `ModuleManager::policiesEnabled()` says so, Livewire components when `livewireEnabled()` agrees, Blade namespaces when `viewsEnabled()` does. Each method reads `ModuleManager::names()`, walks only registered module directories, and caches the result for 24 hours. When boot completes, every Livewire alias is registered, every policy bound, every Blade namespace available. The sequence exercises FR-MOD-011 through FR-MOD-029.

#### UC-MOD-004 — Route Auto-Inclusion

This convention was born the third time someone forgot a manual `require` and a whole module's pages 404'd after deploy. Now `routes/web.php` loads `ModuleManager::names()`, resolves each module through `ModuleManager::routeFilePath($module)`, requires the files that exist, and silently skips the ones that do not. No hand-edited wiring means no forgotten wiring. The loop exercises FR-MOD-030 through FR-MOD-033.

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

A new contributor joining an SMK deployment team once asked why she could not simply rename `Journals` to `DailyLogs` to match her ticket wording. The answer is the cascade she could not see: that single rename rewrites Livewire aliases, route filenames, policy bindings, config keys, Pest directories, and the module docs. The roster of 19 names fixed in §6.1 is therefore frozen, and the on-disk `app/Modules/` listing — checked during this rewrite at exactly 19 directories with identical names — must mirror it name for name, a correspondence the arch-layer directory-versus-roster comparison keeps proving on every structural review.

#### FR-MOD-002 — Amendment before rename

When a module-name diff arrives in review, the gate walks it backwards: the reviewer looks first for the linked spec amendment and ADR, then for the synchronized edits to `config/module.php`, `tests/Pest.php`, and `docs/refs/modules/index.md`, and only then at the code itself. A rename with no amendment paper trail is rejected at review before any runtime is exercised, which is why the check lives at the arch layer rather than in a test — the discipline is procedural, and the review gate is its enforcement.

### 4.2 Registry Contract

#### FR-MOD-003 — Filesystem-derived single source

Someone will eventually drop a `Scratch/` folder or a lowercase `notes/` directory into `app/Modules/` during a hectic pilot week at an SMK, and the registry must shrug it off. `config/module.php` earns that resilience by never trusting a hand-maintained list: it scans `app/Modules/`, keeps only PascalCase directories carrying a `Domain/` subtree, and derives the whole mapping from what survives the guard. `ModuleManager`, `ModuleService`, `routes/web.php`, and the `tests/Pest.php` documentation all read those derived keys rather than re-listing directories themselves, so the stray folder stays invisible. Config review plus a boot smoke at the arch layer confirms the derivation still holds.

#### FR-MOD-004 — PascalCase names

Early in the project, route files and Blade aliases each invented their own casing for the same module until `SysAdmin` appeared as `sysadmin`, `sys-admin`, and `Sysadmin` in three different places. The rule that ended that drift is simple: the directory name is the module name verbatim in PascalCase, and every lowercase or kebab variant used by routes and aliases derives from it by convention without ever renaming the source. The `scan_naming.py` arch-layer check keeps that derivation honest.

#### FR-MOD-005 — Deterministic order

If discovery ever returned modules in filesystem whim-order, two identical deploys at two SMKs could boot Livewire aliases in different sequences and turn a duplicate-alias report into a non-reproducible ghost. The `ksort`ed discovery output exists to kill that class of heisenbug: boot order stays deterministic regardless of disk layout. The deeper dependency story — foundation before lifecycle before administration — is deliberately not re-encoded in config; it lives in the module graph documentation where humans reason about it, while config review at the arch layer confirms the sort still holds.

#### FR-MOD-006 — `list` key

During onboarding at an SMK in Bandung, a junior developer looking for "the list of modules" was handed three different files by three teammates. The `list` key ends that confusion: it is simply the `array_keys` of the derived mapping, the flat name list served by `ModuleManager::names()`. A tinker session asserting the config value at the arch layer proves the key tracks the filesystem without manual curation.

#### FR-MOD-007 — `registry` key

`ModuleManager::names()` answers "which modules exist," but submodule-aware discovery needs the deeper question answered: which domains live inside each module. The `registry` key carries that full `Module → [domains]` map, so the FR-MOD-014 and FR-MOD-020 submodule paths can resolve without guessing directory depth. At runtime the mapping flows straight from config into the scanners, and a config assertion at the arch layer confirms the map still reflects the filesystem.

#### FR-MOD-008 — `test_dirs` key

Shared test scaffolding has no module home, so `Providers`, `Stubs`, and `Support` would silently vanish from the suite if Pest only knew about modules. The `test_dirs` key registers those non-module test directories alongside the module list, mirroring what FR-MOD-040 expects on the Pest side. A side-by-side review of the config and `tests/Pest.php` at the arch layer shows the two lists agreeing.

#### FR-MOD-009 — Path keys

Hardcoded `app/Modules` strings scattered across scanners were a quiet portability trap — one relocation of the application root would have broken discovery in four places at once. The three path keys close it: `paths.base` resolving through `app_path()`, `paths.views` through `resource_path('views')`, and `paths.routes` through `base_path('routes/web')`, with the full structure spelled out in §6.2. Discovery reads those keys and never a literal, a habit config review at the arch layer verifies.

#### FR-MOD-010 — Subsystem settings

Leave every discovery subsystem permanently on and the day a broken view namespace blocks a partial test setup becomes a full-boot outage instead of a ten-minute isolation exercise. Each subsystem — `livewire`, `policies`, `views` — therefore carries its own `enabled` flag alongside its directory name and exclusions, the same toggles UC-MOD-006 flips. Booting once with a subsystem disabled and watching the rest discover normally proves the flags work at the arch layer.

### 4.3 Livewire Component Discovery

#### FR-MOD-011 — Registered-module scan

A trainee at an SMK in Surabaya once added a `Livewire/` folder under a draft module and was baffled when its components never appeared. That silence was the design working: scan roots derive from `ModuleManager::names()`, never from a hardcoded path list, so anything outside the registry is simply never entered. What matters is the convention that registered roots follow the `Domain/` layout, not the exact depth of the glob — `ModuleService` review combined with a boot smoke at the arch layer confirms only registered ground gets walked.

#### FR-MOD-012 — Concern/trait exclusion

Inside `ModuleService::discoverLivewireComponents()` the walker explicitly steps around `Concerns/` and `Traits/` directories, because those folders hold shared behavior rather than components and registering them would mint bogus aliases that collide with real screens. The exclusion runs before any class check, so a helper placed beside a component never even reaches the alias stage. A boot smoke at the arch layer asserting that no `*.concerns.*` alias exists shows the guard holding.

#### FR-MOD-013 — Two-part alias

Picture an SMK operator typing `<livewire:auth.login-form />` into a Blade file and trusting it resolves to `Auth/Livewire/LoginForm.php` on every deploy. That trust rests on the two-part alias convention — kebab-cased module plus kebab-cased class — tabulated in §6.4. The mapping is mechanical enough that an alias assertion after discovery at the arch layer catches any drift the moment a class is renamed without updating its consumers.

#### FR-MOD-014 — Three-part submodule alias

Two submodules each grew a `Show` component — one for placement, one for registration — and the bare two-part alias would have forced them to fight over a single name. The three-part form resolves it: `Enrollment/…/Placement/…/Show.php` surfaces as `enrollment.placement.show`, keeping it distinct from `enrollment.registration.show`. Proving it takes two same-named submodule components and asserting both aliases resolve at the arch layer.

#### FR-MOD-015 — Component superclass gate

Without a superclass check, every plain helper class parked under a `Livewire/` directory would gain a phantom alias and clutter the component table until a typo'd tag resolved to dead code. Discovery avoids that rot by registering only subclasses of `Livewire\Component` and skipping everything else in silence. Discovery review paired with an alias-list assertion at the arch layer confirms the helpers stay out while genuine components register.

#### FR-MOD-016 — Registry-bounded scan

An intern at a vocational school in Yogyakarta once scaffolded an experimental module directly on the staging server to demo a new attendance idea, complete with valid components — and was alarmed when nothing appeared. That invisibility is PS-3 working as intended: unregistered directories are never entered even when they contain perfectly valid components. A fixture directory left deliberately unregistered, asserting zero aliases from it at the arch layer, locks the boundary in place.

#### FR-MOD-017 — 24-hour Livewire cache

Resolving the Livewire map means walking dozens of directories on every boot, a cost a $5 shared-hosting SMK deploy feels on each request without caching. The result therefore persists for 86400 seconds under `module.discovered_livewire`, a key registered in `config/cache-keys.php` and tabulated in §6.6, with `module:discover` and `config:clear` as the explicit bust paths whose rationale DD-MOD-004 records. A cache assertion after discovery at the arch layer shows the entry landing with the right TTL.

### 4.4 Policy Discovery

#### FR-MOD-018 — Registered-module policy scan

Policy discovery follows the same registry-bounded roots as Livewire, only under `Policies/` instead of component directories. The symmetry is deliberate: one registry feeds both walkers, so a module that gains authorization coverage cannot be discovered for UI but forgotten for gates. `ModuleService` review together with a boot smoke at the arch layer watches both walkers stay in step.

#### FR-MOD-019 — Concern/trait exclusion

A shared ownership helper tucked under `Policies/Concerns/` looks, to a naive glob, exactly like a policy — same directory, same suffix habits, no model of its own. Binding it would register a gate with no backing model and fail obscurely at authorization time. The scanner therefore skips `Concerns/` and `Traits/` before shape-checking, and a binding-list assertion at the arch layer confirms those helpers never appear as gates.

#### FR-MOD-020 — Policy shape gate

Let any class ending in `Policy` bind and an SMK deploy eventually authorizes through a helper that never learned the role-plus-ownership contract, silently letting students see each other's records. The shape gate prevents that drift: only classes carrying the `Policy` suffix and extending `BasePolicy` register, everything else is skipped without binding. The binding assertion backed by `scan_class_contracts.py` at the arch layer proves every bound policy honors the base-class mandate.

#### FR-MOD-021 — Model binding

When an onboarding developer at an SMK in Semarang asked where authorization for attendance actually lives, the answer needed no search: `Module/Policies/XPolicy.php` always guards `Module/Models/X`, exactly as the convention table in §6.5 lays out. That adjacency means finding the policy finds the model and vice versa. A per-module `Gate` binding assertion at the arch layer walks the pairing and reports any orphan on either side.

#### FR-MOD-022 — Submodule model binding

As submodules multiply, the flat module-level pairing would start binding a submodule policy to the wrong model namespace and authorize against a sibling's table. The submodule rule keeps the binding local: a policy at `Module/Submodule/Policies/XPolicy.php` resolves to `Module/Submodule/Models/X`, never upward. A binding assertion exercised against a real submodule policy at the arch layer demonstrates the locality holding.

#### FR-MOD-023 — 24-hour policy cache

Policy maps change only on deploys, yet resolving them costs a full directory walk — the same economics that motivated the Livewire cache. The result rests for 86400 seconds under `module.discovered_policies` and busts through the same two commands as FR-MOD-017, keeping all three discovery caches on one freshness story. A cache assertion after discovery at the arch layer confirms the entry and its TTL.

#### FR-MOD-024 — Manual cross-module policies

Discovery deliberately binds only within a module, because an automatic cross-module binding would hide a privilege edge where nobody thinks to look for it. When a policy must guard another module's model, the binding is written explicitly in `AppServiceProvider` so the exception is visible in review and greppable in the codebase. Provider review at the arch layer is the check: anything crossing a boundary without an explicit line is a defect.

### 4.5 Blade View Namespace Registration

#### FR-MOD-025 — Registered-module view scan

Left to list `resources/views/` directly, view registration would pick up half-finished theme experiments an SMK designer left in the directory and expose them as namespaces. Deriving view roots from the registry instead means only modules the application knows about gain namespaces, and draft folders stay inert. A namespace assertion after boot at the arch layer shows registered modules present and nothing else.

#### FR-MOD-026 — Shared-directory exclusion

A trainee once placed a custom login Blade file inside `layouts/` and watched it shadow a module namespace after a naive registration pass. The seven shared directories — `components`, `emails`, `errors`, `layouts`, `mcp`, `pdf`, `vendor` — are framework and cross-cutting ground, never module namespaces, and registering them would let generic names shadow real ones. A namespace-list assertion at the arch layer names all seven exclusions and fails if any of them ever registers.

#### FR-MOD-027 — Dual registration

Registering a module directory only as a view namespace would leave `<x-module::card />` anonymous-component tags unresolved, while registering only the component path would break `Module::view` namespaced references — either half breaks a different author's templates. Discovery therefore performs both registrations for every module directory in one pass. A resolution smoke at the arch layer renders one component tag and one namespaced view to prove both halves landed.

#### FR-MOD-028 — Registry-bounded views

The view layer honors the same PS-3 boundary as Livewire and policies: a view directory without a registered module gains no namespace, no matter how well-formed its Blade files are. This keeps a designer's spike folder from becoming addressable UI simply by existing on disk. A fixture directory left outside the registry, asserting no namespace emerges at the arch layer, guards the rule.

#### FR-MOD-029 — 24-hour view cache

View namespaces are the third leg of the discovery triple, and they share the family's freshness economics: stable across deploys, expensive to re-walk, cheap to cache. The map persists for 86400 seconds under `module.discovered_views` and busts through the same `module:discover` and `config:clear` paths as FR-MOD-017, so no single cache goes stale while its siblings refresh. The arch-layer cache assertion after discovery closes the loop.

### 4.6 Route Auto-Inclusion

#### FR-MOD-030 — Convention over requires

Every hand-written `require` in `routes/web.php` was a deploy landmine: the module worked locally where the developer remembered the line, then 404'd in production where the line never landed. The registry loop removes the whole class — no per-module require exists anymore, and the reasoning DD-MOD-005 records is that wiring nobody can forget beats wiring everyone must remember. A route-list smoke after adding a fixture route file at the arch layer shows the new pages appearing with zero wiring edits.

#### FR-MOD-031 — Path convention

A newcomer onboarding onto an SMK rollout should need exactly one answer to "where do this module's routes live," not a grep across the codebase. The single resolver `ModuleManager::routeFilePath()` is that answer: it owns the convention so callers never reconstruct it. A unit test against the resolver at the arch layer pins the mapping for every registry name.

#### FR-MOD-032 — Silent skip

Forcing every registered module to ship an empty route file just to satisfy the loader would litter the tree with placeholders that confuse the next developer into thinking routes exist where they do not. The loader instead guards each require with a `file_exists` check, so routeless modules boot cleanly and no empty file is ever needed. Booting with a registered-but-routeless module and watching the smoke pass at the arch layer confirms the skip.

#### FR-MOD-033 — Lowercase lookup

`SysAdmin` on disk must resolve to `sysadmin.php` on every filesystem, case-sensitive or not, or a deploy that works on a developer's laptop fails on the school's Linux host. Lowercasing the module name before lookup removes filesystem case from the equation entirely. A resolver unit test feeding a mixed-case name and expecting the lowercase file at the arch layer locks the normalization in.

### 4.7 CLI Cache Clearing

#### FR-MOD-034 — One-command refresh

Stale discovery caches were once a rite of passage: rename a component, spend an afternoon wondering why the old alias still resolves. The `php artisan module:discover` command collapses that debugging session into one step — it clears `module.discovered_livewire`, `module.discovered_policies`, and `module.discovered_views`, then re-runs all three discovery methods and exits `0` on success. A feature-layer test running the command and asserting fresh caches plus the exit code proves the refresh is real.

#### FR-MOD-035 — Provider guard

Running discovery without the provider's boot context would register aliases, policies, and namespaces into a half-wired application — bindings that look healthy until the first real request fails. The command refuses that path outright instead of producing a plausible-but-broken map. A feature-layer test exercising the guard path shows the refusal firing before any registration happens.

#### FR-MOD-036 — SmartLogger completion log

A discovery run at an SMK in Medan once silently fixed a missing-policy outage, and nobody could later answer when the map had healed. Both success and failure now log through SmartLogger as specified in [89SRA](89SRA-logging-and-error-handling.md), so every discovery run leaves its mark in the audit trail. A feature-layer test asserting the completion entry lands turns that trail from intention into guarantee.

#### FR-MOD-037 — Translated progress

Inside the command, each status line passes through `__()` with matching `en` and `id` entries, honoring the D3 invariant that no user-facing string ships in one language only. An Indonesian operator running the command during a school rollout therefore sees the same guidance as the English-speaking developer who wrote it. Command output review together with `LangChecker` at the feature layer confirms both locales resolve.

### 4.8 Test Directory Registration

#### FR-MOD-038 — Pest module list

A module whose tests Pest never discovers is worse than a module with no tests — it reports green while covering nothing. Every registered module therefore has its `tests/{Type}/{Module}/` directory registered, so each suite run actually walks the module's tests. A full Pest run spanning all modules at the arch layer demonstrates that no registered module is left unscanned.

#### FR-MOD-039 — Manual sync discipline

The dual listing — `config/module.php` for runtime, `tests/Pest.php` for the suite — is the one place the single-source principle knowingly bends, and DD-MOD-001 owns that compromise. What keeps the bend from becoming a break is humble process: a sync comment in `tests/Pest.php` naming `config/module.php` explicitly, plus a standing review habit of checking both files on every module change. The review gate backed by NFR-MOD-004 at the arch layer is where that habit is enforced.

#### FR-MOD-040 — Non-module test dirs

Skip the shared scaffolding and the suite boots without its own helpers — `Providers`, `Stubs`, and `Support` must load or nothing else can run. Their registration mirrors the `test_dirs` config key from FR-MOD-008, keeping the Pest side and the config side telling the same story about what "everything" means. A Pest boot smoke at the arch layer fails fast if any of the three stops loading.

#### FR-MOD-041 — No config() in Pest.php

An eager contributor once "cleaned up" `tests/Pest.php` by replacing the hardcoded module list with a tidy `config()` call, and the entire suite died before running a single test. The constraint is structural, not stylistic: Pest discovers test directories before Laravel boots, so the config container simply does not exist yet and the call fatals. Static review of `tests/Pest.php` at the arch layer keeps the hardcode — and the suite — intact.

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

Inside the scanner, each candidate file parses defensively: what cannot be parsed is skipped while the rest of the map still builds. That restraint matters on a school deployment where a developer's half-written component saved at 17:55 must never take down the evening attendance boot. A deliberately malformed fixture file paired with a boot smoke proves the tolerance — boot succeeds and the bad file simply contributes nothing.

#### NFR-MOD-002 — Deterministic busting

A cache that sometimes busts is worse than no cache: developers learn to distrust discovery and restart servers ritualistically before every demo at the SMK. Both `module:discover` and `config:clear` therefore guarantee a full refresh of every discovery entry, with no stale-alias debugging sessions afterward. Asserting cache absence after each command shows the freshness promise holding on both paths.

#### NFR-MOD-003 — Collision tolerance

Two components resolving to one alias once threatened to halt boot entirely — a hard throw that turned a naming accident into a school-wide outage during enrollment week. The tolerant behavior resolves last-write-wins instead: boot succeeds, and the collision surfaces later through an alias audit rather than a crash. A duplicate-alias fixture asserting that boot still succeeds pins the tolerance in place.

### 5.2 Maintainability

#### NFR-MOD-004 — Sync comment

The entire drift defense for the Pest hardcode from DD-MOD-001 fits on one line: a comment in `tests/Pest.php` that names `config/module.php` outright. Without that pointer, the next developer editing the test list has no reason to suspect a second list exists, and the two silently diverge. A grep for the config reference in `tests/Pest.php` is the whole check — present means defended, absent means drifting.

#### NFR-MOD-005 — Single config gateway

A junior developer's first instinct is to call `scandir()` directly from a new service — faster than learning the manager API, and the start of three divergent notions of "registered." `ModuleService` closes that path by reading module configuration only through `ModuleManager::names()` and `isModule()`, static by design under DD-MOD-003, with zero direct directory listings of its own. Review backed by `scan_violations.py` catches any service that wanders off the gateway.

#### NFR-MOD-006 — Per-method testability

When view namespaces broke the week before an SMK pilot, the team needed to know in minutes whether the fault sat in `discoverLivewireComponents()`, `discoverPolicies()`, or `registerBladeNamespaces()` — not "somewhere in discovery." Each method therefore runs standalone with its own test, so a regression points at exactly one of them. One test per method is both the design and the proof.

### 5.3 Security

#### NFR-MOD-007 — Registry-bounded registration

Imagine a stray class planted outside the registered modules — left by a mistaken copy, or worse, by someone probing the server — quietly gaining a Livewire alias and becoming invocable UI. The registry boundary exists so that story never runs: nothing outside registered directories can earn an alias, a policy binding, or a view namespace. A fixture placed deliberately outside the registry, asserting zero registrations emerge, keeps the boundary provable.

#### NFR-MOD-008 — BasePolicy gate

Authorization uniformity across 18 modules cannot survive on goodwill: one policy written without the role-plus-ownership traits becomes the hole through which a student reaches another student's submission. Only `BasePolicy` subclasses bind, which carries that contract structurally per the base-class mandate. The `scan_class_contracts.py` run combined with a binding assertion shows every bound policy extending the base.

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

Keeping the `tests/Pest.php` module list hardcoded with only a sync comment pointing at `config/module.php` looks like unfinished work until you learn that Pest discovers test directories before Laravel boots, so calling `config()` there fatals the whole suite. That boot-order reality forced the design: a config-only runtime plus a comment-synced hardcode instead of the integration every instinct suggests. The single-source principle bends in exactly this one place, which is why every module change must touch two files — a count the Success Metrics track at 2 so the exception never quietly spreads.

#### DD-MOD-002 — Submodule Alias Naming

An SMK rollout once fielded two `show` screens — placement detail and registration detail — and the flat alias gave both teams the same tag. Submodule aliases answer with three kebab-cased parts, `module.submodule.class`, so `enrollment.placement.show` and `enrollment.registration.show` coexist without negotiation. The aliases run longer than anyone loves, but collision-proofing won over brevity because Livewire's standard naming already leans kebab and the extra segment reads naturally in Blade.

#### DD-MOD-003 — Static Reads on ModuleManager

Route files and model boot methods need the module list in contexts where no container exists to inject, which is why `ModuleManager::names()` and `ModuleManager::isModule()` are public statics on `Core\Support\ModuleManager` while `ModuleService` keeps its orchestration instanced with constructor injection per B114U DD-1. Pure config reads carry no instance state and no I/O, so static access costs nothing and serves everywhere. The static surface stays confined to that narrow gateway — all remaining discovery logic lives instanced and injected, which review confirms.

#### DD-MOD-004 — 24-Hour Cache TTL

Module structure changes only when a deploy lands, so re-walking the tree on every boot spends production cycles relearning what rarely changes. Caching discovery results for 86400 seconds trades a day of staleness risk for fast boots, with development refreshing explicitly through `module:discover` or `config:clear`. A structural deploy landing mid-day therefore needs that explicit clear — a step the deploy flow documents rather than hiding, so freshness stays a conscious act.

#### DD-MOD-005 — Route Auto-Inclusion Pattern

The pattern grew out of embarrassment: three separate deploys 404'd whole modules because a manual `require` was forgotten, the third during a live SMK demo. Now `routes/web.php` loops `ModuleManager::names()` and requires through `ModuleManager::routeFilePath()`, silently skipping modules without files so routeless modules need no placeholders. A mistyped filename still fails silently rather than loudly — the accepted cost, mitigated by running the `module:discover` verification step from the UC-MOD-001 add-module workflow before shipping.

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
