# Modular Pattern Reference — Design Patterns, Conventions & Architecture Rules

> **Last updated:** 2026-07-21 **Changes:** sync — update route naming convention to flexible (describe URL path)

## Description

Complete catalog of design patterns, conventions, and architectural rules used across all 19
business modules.

## 1. Architectural Patterns

### 1.1 Module-Colocated Vertical Slicing (Action-Based MVC)

Organize code by business domain, not by technical layer. Each module groups all components under a
single root. See: `docs/architecture.md`.

### 1.2 4-Layer Architecture

Strict downward-only dependency graph. Core (Layer 1) depends on nothing except Laravel and Spatie.
No business module may be imported by Core. See: `docs/architecture.md`.

### 1.3 Action Triad (Command / Read / Process)

Three distinct contracts: **Command** mutations with transactions and logging, **Read** queries
without transactions, **Process** orchestration of multiple Commands. See: `docs/architecture.md`.

### 1.4 Cross-Module Communication

Direct imports for no-side-effect access, Action delegation for cross-module operations, events for
fire-and-forget, core contracts for broad abstractions. See:
`docs/adr/adr-cross-module-communication.md`.

### 1.5 Gradual Migration Path

Three-phase migration: `execute(array $data)` → `execute(Data|array $data)` → `execute(Data $data)`.
See: `docs/adr/adr-gradual-migration.md`.

---

## 2. Base Class Patterns (Layer 1 — Framework/Infra)

Every architectural layer has one Core base class: `BaseModel`, `BaseAction`, `BaseEntity`,
`BasePolicy`, `BaseRecordManager`, `BaseController`, `BaseFormRequest`, `BaseData`, `BaseEvent`, and
contracts `LabelEnum`/`StatusEnum`. See: `docs/conventions.md` §1.

---

## 3. Contract Patterns (Layer 1 — Framework/Infra)

**LabelEnum** — every enum provides a human-readable label. **StatusEnum** — state machine enums
with `isTerminal()`, `canTransitionTo()`, `validTransitions()`. **ColorableEnum** — UI color/badge
variants. **SendsNotifications / SettingsStore** — infrastructure contracts bound via container.
See: `docs/architecture/enum-pattern.md`.

---

## 4. Action Patterns

**Action Injection** — Livewire components inject Actions as method parameters via DI.
**ActionResponse** — standardized return envelope with `ok()`, `created()`, `updated()`,
`deleted()`, `error()`, `withRedirect()`. **DTO Input** — Command/Process Actions accept `BaseData`
DTO as primary parameter, never raw `array`. **Transaction Safety** — `BaseAction::transaction()`
auto-detects nesting, queues events until commit, retries on deadlock. **Single execute()** — every
Action has exactly one public method. See: `docs/architecture/action-pattern.md`.

---

## 5. Entity-Model Separation Patterns

**Bridge Pattern** — `Entity::fromModel(Model)` and `Model::as{Entity}(): Entity` connect
persistence to business rules. **Entity Purity** — `final readonly`, zero I/O, zero persistence.
**Shared Validation Rules** — entities expose static `rules()` for Form Objects. See:
`docs/adr/adr-entity-model-separation.md`.

---

## 6. Enum Patterns

String-backed, UPPER_SNAKE case. Business logic methods (e.g., `isActive()`) live directly on the
enum. Model defaults use `EnumCase::value`. See: `docs/architecture/enum-pattern.md`.

---

## 7. Policy & Authorization Patterns

**Flat RBAC** — roles with no inheritance, functional roles derived at runtime. **Three-Layer
Authorization** — route middleware, Livewire `authorize()`, Policy gates. **Super Admin
Gate::before** — single bypass callback. **AuthorizesRoles Trait** — role-checking methods.
**AuthorizesOwnership Trait** — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()`. See:
`docs/architecture/policy-pattern.md`, `docs/foundation/rbac.md`.

---

## 8. Livewire Component Patterns

**Thin Component Rule** — UI state and validation only; no `Model::create/update/delete`, `DB::`
queries, or business logic. **Action Injection** — Actions are method parameters. **Confirmation
Dialog** — `actionTarget`/`confirmingAction` state with `askAction()`/`confirmAction()`. **Form
Object** — complex forms extracted into `Livewire\Form` subclasses. **Component Alias** — submodule:
`{mod}.{sub}.{name}`, cross-module: `{mod}.{name}`, shared: `{component-name}`. See:
`docs/architecture/livewire-pattern.md`.

---

## 9. Model Patterns

UUID primary keys via `HasUuids`. `#[Fillable]` PHP 8.4 attribute. Named entity bridge accessors.
Singular `BelongsTo`/`HasOne` and plural `HasMany`/`BelongsToMany` relationships. Common scopes:
`scopeActive()`, `scopeInactive()`, `scopeRecent()`, `scopeCreatedAfter()`, `scopeCreatedBefore()`,
`scopeOrdered()`. See: `docs/architecture/model-pattern.md`.

---

## 10. Logging & Error Handling Patterns

**SmartLogger** — fluent dual-channel (system + activity) logging with PII masking. **Dual Exception
Hierarchy** — `AppException` and `ModuleException` sibling trees. **HandlesActionErrors** — catches
unexpected `Throwable`, logs, rethrows. **HasExceptionContext** — `withHint()`, `withContext()`,
`toCliOutput()`, `getSanitizedContext()`. See: `docs/architecture/logging-pattern.md`,
`docs/architecture/exception-pattern.md`.

---

## 11. Testing Patterns

**Module-First** — `tests/{Feature,Unit}/{Module}/{SubModule}/{Name}Test.php`. **Scope Isolation** —
one test file per Action/component. **Layer Strategy** — enums/entities/DTOs/policies: unit (no DB);
Actions/Livewire: feature (with DB). **Action Testing** — test DTO construction, ActionResponse
handling, Entity rule enforcement, event dispatch. **Performance** — `LazilyRefreshDatabase`,
`assertModelExists()`. **TDD Order** — Enum → Entity → DTO → Command → Read → Process → Livewire →
Policy → Console. See: `docs/architecture/testing-pattern.md`, `docs/infrastructure/testing.md`.

---

## 12. Cache Patterns

**Centralized Key Registry** — all keys in `config/cache-keys.php`. **Event-Driven Invalidation** —
Command → event → listener → `Cache::forget()`. **TTL Categorization** — Short (<5m), Medium
(5m-1h), Long (1h-24h), Forever. See: `docs/infrastructure/cache.md`.

---

## 13. Route & Controller Patterns

Module-split route files under `routes/web/`. Module-level: `{module}.php`. Submodule-level: `{submodule}.php`
(no module prefix). Route names: flexible, describe the URL path. Middleware
groups: `auth`, `guest`, `role:{roles}`, `auth.throttle`. Controller suffix required, delegate to
Actions. See: `docs/infrastructure/routes.md`.

---

## 14. Notification Patterns

Multi-channel via `mail`, `broadcast`, `CustomDatabaseChannel`. Naming:
`{Entity}{Type}Notification`. All user-facing strings via `__()`. See:
`docs/architecture/event-pattern.md`.

---

## 15. Migration & Database Patterns

`foreignUuid()->constrained()` with explicit `onDelete()`/`onUpdate()`. Composite indexes defined
explicitly. Seeders: `firstOrCreate()` for reference, `create()` for test data, respecting module
dependencies. One table per migration. See: `docs/conventions.md` §7.

---

## 16. Naming Conventions

| Element           | Convention                                            |
| ----------------- | ----------------------------------------------------- |
| Command Action    | `{Verb}{Entity}Action`                                |
| Read Action       | `Read{Entity}Action`                                  |
| Process Action    | `Process{Entity}Action`                               |
| Entity            | `{Name}`                                              |
| DTO               | `{Verb}{Entity}Data`                                  |
| Event             | `{Entity}{PastTenseAction}`                           |
| Listener          | `{Verb}{Entity}`                                      |
| Notification      | `{Entity}{Type}Notification`                          |
| Livewire          | `{Name}Manager`/`{Name}Editor`                        |
| Policy            | `{Model}Policy`                                       |
| Controller        | `{Name}Controller`                                    |
| Console command   | `{module}:{action}`                                   |
| Route / Cache key | descriptive (mirror URL path) / `{module}.{purpose}` |
| Column / table    | `snake_case`                                          |
| Boolean method    | `is`/`has`/`can`/`should`                             |
| Test / Factory    | `{Name}Test.php` / `{Name}Factory`                    |
| Migration         | `YYYY_MM_DD_HHMMSS_create_{table}_table.php`          |

See: `docs/conventions.md` §4.

---

## 17. PHP Language Conventions

`declare(strict_types=1)` in all files except migrations/config. Constructor property promotion.
Explicit return/parameter types. `===` over `==`. `match()` over `switch()`.
`str_contains()`/`str_starts_with()`/`str_ends_with()`. Null-safe `?->` and `??`. Trailing commas.
`__()` for user-facing strings. No `dd()`, `dump()`, `ray()`, `var_dump()`, `die()` in committed
code. See: `docs/conventions.md` §2.

---

## 18. Quality Enforcement

Pint (PSR-12), PHPStan (type safety), Prettier (formatting), Code Review (architecture). Pre-commit:
strict_types, no debug calls, `__()` for strings, correct Action triad, cache keys in config, tests
pass, Pint clean. See: `docs/conventions.md` §11.

---

## 19. Cross-Cutting Patterns

**Static Utilities** — `Color`, `PiiMasker`, `CsvHandler`, `PasswordRules`, `Environment`,
`AppInfo`, `AppIntegrity`, `LangChecker`. **Livewire Concerns** — `WithRecordSelection`,
`WithSorting`. **CustomDatabaseChannel** — in-app DB notifications. **Security Middleware** —
`SecurityHeaders` (CSP, X-Frame-Options, etc.), `LogContext` (request tracing).

---

## 20. Cross-Role Proxy Protocol

Teachers can proxy as supervisor; admins can proxy both teacher and supervisor. Implemented at the
application layer — no multi-role assignment. See [ADR-014](../adr/adr-cross-role-proxy.md) and
`docs/conventions.md` §8.

---

## 21. Workflow Patterns

**Feature Building:** Docs → Migration/Model → Enum → Entity → DTO → Action → Policy → Livewire →
Blade → Routes → Translations → Tests → Quality. **Action Extraction:** Identify inline
`Model::create/update/delete` → Create Action (accept DTO, return ActionResponse) → Move validation
→ Transaction → Log → Entity rules → Event → Inject. **Entity Extraction:** Identify conditionals →
Create Entity → Extract state → `fromModel()` → Named bridge → Update callers. **Livewire
Refactoring:** Logic → Action, Rules → Action (not Entity directly), UI patterns → Component/Trait,
Utilities → Support. **Data Flow:** Mutations go UI→DTO→Action(Entity
check→Transaction→Log→Event)→Model; simple queries go directly to Model; complex queries through
Read Action with DTO.
