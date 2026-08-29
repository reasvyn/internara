# Modular Pattern Reference — Design Patterns, Conventions & Architecture Rules

## Description

Complete catalog of design patterns, conventions, and architectural rules used across all 19
business modules.

> **Global standards alignment:** This catalog is the index. Each pattern below links to a dedicated
> deep-dive in `docs/guides/arch/*-pattern.md` that integrates the relevant global industry standard
> (CQRS, DDD, SOLID, PoEAA, PSR-3/16, WCAG 2.2, OWASP, NIST). The cross-cutting foundations are
> **Modular Monolith** (vertical slicing), **Clean Architecture** (dependency rule),
> **Hexagonal Architecture** (ports & adapters), **SOLID**, and **DRY**.

## Global Standards Foundations

Internara's module-first, Action-based architecture intentionally aligns with established industry
standards so the codebase reads like a textbook implementation of well-known patterns.

| Standard | What it prescribes | Where it lives in Internara |
| --- | --- | --- |
| **Modular Monolith** | Decompose by business domain; explicit module boundaries; communicate via public surface only | §1.1 Module-Colocated Vertical Slicing; `docs/architecture.md` |
| **Clean Architecture** | Source dependencies point inward; Core depends on nothing business (Dependency Rule) | §1.2 4-Layer Architecture; §1.6 SRP & Modularity Rules |
| **Hexagonal Architecture** | Core logic isolated from external concerns via ports & adapters | Entities (pure) + Actions (application services) + Livewire/Controllers (adapters) |
| **SOLID** | Single Responsibility, Open/Closed, Liskov, Interface Segregation, Dependency Inversion | §1.6 (SRP); Actions/Entities/Models/DTOs (one reason to change) |
| **DRY** | Reuse/extract instead of copy-paste | §21 Workflow Patterns; shared `app/Core/Support/`, `Base*` classes |

**References**
- [Modular Monolith](https://microservices.io/patterns/monolithic.html) — pattern overview; also see `docs/architecture.md` §1.1
- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html) — Uncle Bob
- [Hexagonal Architecture](https://en.wikipedia.org/wiki/Hexagonal_architecture_%28software%29) — Ports & Adapters
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID) — object-oriented design
- [DRY Principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself) — code reuse

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

### 1.6 Single Responsibility & Modularity Rules

Every unit has exactly one responsibility and one reason to change; every dependency crosses at
most one boundary downward. This section is the canonical reference for SRP and modularity rules —
other docs link here, never duplicate. Enforced by `tools/scan_class_contracts.py` and
`tools/scan_violations.py`.

| Unit                | One responsibility                  | Boundary rules                                                                                              |
| ------------------- | ----------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Module              | One business area (owns 1+ Domains) | Owns its Domains (`app/Modules/{Module}/Domain/{Domain}/`); each Domain owns its full stack (Actions, Entities, Models, Livewire, Policies); other modules reach it only via its public surface — Actions, contracts, events (see 1.4) |
| Domain              | One business domain                 | Exactly one Domain per domain (`app/Modules/{Module}/Domain/{Domain}/`); owns its full stack; never split a domain across Domains nor collapse a Domain-scale domain into a parent catch-all (see `.agents/rules/domain-boundary.md`) |
| Command/Read Action | ONE business operation or query     | Single public `execute()`; DTO for 3+ params (C7); never a second copy of an existing operation             |
| Process Action      | ONE multi-step workflow             | Orchestrates Command/Read Actions; no inline business rules                                                 |
| Entity              | Business invariants of ONE concept  | `final readonly`, pure — no Action/Service/Livewire imports (C5)                                            |
| DTO                 | Data of ONE operation               | Scalars only — no Model/Entity imports (C6)                                                                 |
| Model               | Persistence of ONE table            | No business rules; bridges to its Entity via contract                                                       |
| Livewire component  | ONE screen or widget                | Thin: validation + toast only; delegates mutations to Actions (C1)                                          |
| Event / Listener    | ONE occurrence / ONE reaction       | Side effects live here, not in the dispatching Action beyond `dispatchEvent()`                              |

Prose rules:

- **Colocation over coupling** — code for a domain lives inside its module; code goes to Core only
  when 2+ modules need it, never into cross-module "Utils" grab-bags.
- **Downward-only dependencies** — Core imports nothing business; business modules never import
  each other's internals (4-layer rule).
- **Extraction bias** — when a unit grows past its single responsibility, extract smaller named
  units instead of accumulating branches (DRY-first clean code).
- **Domain decomposition** — a distinct business domain becomes its own Domain
  (`app/Modules/{Module}/Domain/{Domain}/`) when it owns 3+ of the 4 standard CRUD operations or serves a
  role-scoped business operation. A domain must not be split across Domains nor collapsed into a
  parent's catch-all once it reaches Domain-scale cohesion. See `.agents/rules/domain-boundary.md`
  for the full rule and additional cohesion signals.

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
See: `docs/guides/arch/enum-pattern.md`.

---

## 4. Action Patterns

**Action Injection** — Livewire components inject Actions as method parameters via DI.
**ActionResponse** — standardized return envelope with `ok()`, `created()`, `updated()`,
`deleted()`, `error()`, `withRedirect()`. **DTO Input** — Command/Process Actions accept `BaseData`
DTO as primary parameter, never raw `array`. **Transaction Safety** — `BaseAction::transaction()`
auto-detects nesting, queues events until commit, retries on deadlock. **Single execute()** — every
Action has exactly one public method. See: `docs/guides/arch/action-pattern.md`.

---

## 5. Entity-Model Separation Patterns

**Bridge Pattern** — `Entity::fromModel(Model)` and `Model::as{Entity}(): Entity` connect
persistence to business rules. **Entity Purity** — `final readonly`, zero I/O, zero persistence.
**Shared Validation Rules** — entities expose static `rules()` for Form Objects. See:
`docs/adr/adr-entity-model-separation.md`.

---

## 6. Enum Patterns

String-backed, UPPER_SNAKE case. Business logic methods (e.g., `isActive()`) live directly on the
enum. Model defaults use `EnumCase::value`. See: `docs/guides/arch/enum-pattern.md`.

---

## 7. Policy & Authorization Patterns

**Flat RBAC** — roles with no inheritance, functional roles derived at runtime. **Three-Layer
Authorization** — route middleware, Livewire `authorize()`, Policy gates. **Super Admin
Gate::before** — single bypass callback. **AuthorizesRoles Trait** — role-checking methods.
**AuthorizesOwnership Trait** — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()`. See:
`docs/guides/arch/policy-pattern.md`, `docs/guides/rbac.md`.

---

## 8. Livewire Component Patterns

**Thin Component Rule** — UI state and validation only; no `Model::create/update/delete`, `DB::`
queries, or business logic. **Action Injection** — Actions are method parameters. **Confirmation
Dialog** — `actionTarget`/`confirmingAction` state with `askAction()`/`confirmAction()`. **Form
Object** — complex forms extracted into `Livewire\Form` subclasses. **Component Alias** — domain:
`{mod}.{domain}.{name}`, cross-module: `{mod}.{name}`, shared: `{component-name}`. See:
`docs/guides/arch/livewire-pattern.md`.

---

## 9. Model Patterns

UUID primary keys via `HasUuids`. `#[Fillable]` PHP 8.4 attribute. Named entity bridge accessors.
Singular `BelongsTo`/`HasOne` and plural `HasMany`/`BelongsToMany` relationships. Common scopes:
`scopeActive()`, `scopeInactive()`, `scopeRecent()`, `scopeCreatedAfter()`, `scopeCreatedBefore()`,
`scopeOrdered()`. See: `docs/guides/arch/model-pattern.md`.

---

## 10. Logging & Error Handling Patterns

**SmartLogger** — fluent dual-channel (system + activity) logging with PII masking. **Dual Exception
Hierarchy** — `AppException` and `ModuleException` sibling trees. **HandlesActionErrors** — catches
unexpected `Throwable`, logs, rethrows. **HasExceptionContext** — `withHint()`, `withContext()`,
`toCliOutput()`, `getSanitizedContext()`. See: `docs/guides/arch/logging-pattern.md`,
`docs/guides/arch/exception-pattern.md`.

---

## 11. Testing Patterns

**Module-First** — `tests/{Module}/{SubModule}/{Name}Test.php`. **Scope Isolation** —
one test file per Action/component. **Layer Strategy** — enums/entities/DTOs/policies: unit (no DB);
Actions/Livewire: feature (with DB). **Action Testing** — test DTO construction, ActionResponse
handling, Entity rule enforcement, event dispatch. **Performance** — `LazilyRefreshDatabase`,
`assertModelExists()`. **TDD Order** — Enum → Entity → DTO → Command → Read → Process → Livewire →
Policy → Console. See: `docs/guides/arch/testing-pattern.md`, `docs/guides/infra/testing.md`.

---

## 12. Cache Patterns

**Centralized Key Registry** — all keys in `config/cache-keys.php`. **Event-Driven Invalidation** —
Command → event → listener → `Cache::forget()`. **TTL Categorization** — Short (<5m), Medium
(5m-1h), Long (1h-24h), Forever. See: `docs/guides/infra/cache.md`.

---

## 13. Route & Controller Patterns

Module-split route files under `routes/web/`. Module-level: `{module}.php`. Domain-level: `{domain}.php`
(no module prefix). Route names: flexible, describe the URL path. Middleware
groups: `auth`, `guest`, `role:{roles}`, `auth.throttle`. Controller suffix required, delegate to
Actions. See: `docs/guides/infra/routes.md`.

---

## 14. Notification Patterns

Multi-channel via `mail`, `broadcast`, `CustomDatabaseChannel`. Naming:
`{Entity}{Type}Notification`. All user-facing strings via `__()`. See:
`docs/guides/arch/event-pattern.md`.

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
`SecurityHeadersMiddleware` (CSP, X-Frame-Options, etc.), `LogContextMiddleware` (request tracing).

---

## 20. Cross-Role Proxy Protocol

Teachers can proxy as supervisor; admins can proxy both teacher and supervisor. Implemented at the
application layer — no multi-role assignment. See [4](../../adr/adr-cross-role-proxy.md) and
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

---

## 22. Accessibility (WCAG 2.1 AA)

All user-facing interfaces MUST meet WCAG 2.1 Level AA. Accessibility is not a post-launch
afterthought — it is a design constraint enforced at every layer.

### 22.1 Perceivable

- **Color contrast:** 4.5:1 minimum for normal text, 3:1 for large text (≥18pt or ≥14pt bold).
  Self-hosted palette (`--color-primary` etc. in `resources/css/app.css`) is pre-validated; never override with arbitrary Tailwind colors that fail
  contrast.
- **Color is not the sole indicator:** Status badges, error states, and capacity indicators must
  include text labels or icons alongside color. Use `badge-success` + "Verified" text, never color
  alone.
- **Text alternatives:** All `<img>` tags require `alt` attributes. Decorative images use `alt=""`.
  Icons paired with text must not duplicate the text in the `alt`.
- **Content reflow:** No horizontal scrolling at 320px viewport width (WCAG 1.4.10). Responsive
  breakpoints must prevent content clipping.

### 22.2 Operable

- **Keyboard navigation:** All interactive elements (buttons, links, form fields, modals, dropdowns)
  must be reachable and operable via keyboard alone. Tab order must follow logical reading order.
- **Focus indicators:** Every focusable element must have a visible focus ring. Tailwind/TallstackUI provides
  `focus:ring` by default — do not suppress it with `outline-none` without a replacement.
- **Skip links:** Pages with navigation must provide a "Skip to main content" link as the first
  focusable element.
- **Modal focus trap:** Modals (`x-ts-modal`) must trap focus within the modal when open. Focus
  must return to the trigger element on close.
- **No keyboard traps:** Users must be able to navigate away from any component using standard
  keyboard shortcuts.

### 22.3 Understandable

- **Language attribute:** `<html>` must include `lang="{{ locale }}"` attribute.
- **Form labels:** Every form input must have an associated `<label>` (via `for`/`id` or wrapping).
  Placeholder text is not a label substitute.
- **Error identification:** Validation errors must be announced to screen readers via `aria-live`
  regions. TallstackUI form components handle this automatically — do not bypass it.
- **Consistent navigation:** Navigation menus must appear in the same relative order across all
  pages.

### 22.4 Robust

- **ARIA landmarks:** Layout must use semantic HTML5 landmarks (`<nav>`, `<main>`, `<header>`,
  `<footer>`) or ARIA equivalents. The app layout provides `<nav>` (sidebar) and `<main>` (content).
- **aria-live for dynamic content:** Toast messages, real-time validation feedback, and Livewire
  partial updates must use `aria-live="polite"` or `aria-live="assertive"` to announce changes to
  screen readers.
- **Icon buttons:** Icon-only buttons must include `aria-label` (e.g.,
  `<button aria-label="Close modal">`).

### 22.5 Implementation Rules

- TallstackUI components (`x-ts-modal`, `x-ts-table`, `x-ts-input`, `x-ts-select.native` etc.) provide built-in ARIA
  attributes and keyboard support. Prefer these over custom HTML.
- TallstackUI `layout`, `modal`, `dropdown`, `tabs` components include keyboard and ARIA support.
  Use them rather than building custom equivalents.
- Livewire `wire:model` updates are not announced by default — wrap dynamic output regions in
  `aria-live` containers.
- Guide modals (`*-guide.blade.php`) must be keyboard-accessible and announce their content via
  ARIA.

See: `docs/guides/ui-ux.md` §6 (Accessibility), `docs/conventions.md` §13.

---

## 23. Localization Patterns

All user-facing strings MUST use the `__()` helper for translation. See `docs/conventions.md` §14
and `docs/guides/infra/localization.md` for file structure and key conventions.

### 23.1 Key Conventions by Layer

| Layer           | Pattern                                        | Example                                       |
| --------------- | ---------------------------------------------- | --------------------------------------------- |
| Module-level    | `{module}.key`                                 | `__('enrollment.register')`                   |
| Domain-level | `{domain}.key` (no module prefix)           | `__('internship.create_success')`             |
| Shared          | `common.key`                                   | `__('common.actions.save')`                   |
| Validation      | Laravel built-in `validation.*` keys            | `__('validation.required')`                   |
| Guide components | `{module}.guide.step{N}_desc`                | `__('setup.guide.step1_desc')`                |

### 23.2 Livewire Component Rules

- Toast messages: always `__('{module}.{entity}.{action}_success')` — never hardcoded strings (via `$this->toast()->success()->send()`).
- Status labels: use `LabelEnum::label()` which internally calls `__()` — never translate status
  in the view layer.
- Modal titles, button labels, table headers: all via `__()`.
- Form Object `messages()`: return translated validation messages via `__()`.
- Confirmation dialogs: pass translated `title`, `message`, `confirmText`, `cancelText` props.

### 23.3 Blade View Rules

- All visible text uses `{{ __('key') }}` — no hardcoded English strings in templates.
- HTML `lang` attribute: `<html lang="{{ app()->getLocale() }}">` (set in `base.blade.php`).
- Date/time formatting: use `Carbon::locale(app()->getLocale())->isoFormat(...)` for
  locale-aware dates.
- Number formatting: use `Number::locale(app()->getLocale())` for locale-aware numbers.

### 23.4 Dual Locale Requirement

Every translation key must exist in both `lang/en/{file}.php` and `lang/id/{file}.php`. Adding a
key to one locale without the other is a bug. The `LangChecker` static utility validates locale
completeness at runtime.

---

## Quick References

- `docs/architecture.md` — 4-Layer Architecture, data flow, dependency rules
- `docs/conventions.md` — coding conventions, invariants (C1-C8, D1-D6)
- Dedicated pattern deep-dives (each integrates a global standard):
  - [Action Pattern](action-pattern.md) — CQRS, Command Pattern, SOLID SRP, Unit of Work
  - [Entity Pattern](entity-pattern.md) — DDD Entity, Rich Domain Model, Aggregates
  - [Model Pattern](model-pattern.md) — Active Record, UUID v7 (RFC 9562), Bridge Pattern
  - [Data Pattern](data-pattern.md) — DTO (PoEAA), Value Object (DDD), Immutability
  - [Enum Pattern](enum-pattern.md) — State Machine, Type State, GoF State Pattern
  - [Event Pattern](event-pattern.md) — Domain Events, Observer Pattern, Event-Driven Architecture
  - [Exception Pattern](exception-pattern.md) — Exception Hierarchy, Defence in Depth (NIST)
  - [Cache Pattern](cache-pattern.md) — Cache-Aside, PSR-16, Cache Stampede / Thundering Herd
  - [Logging Pattern](logging-pattern.md) — PSR-3, Structured Logging, PII/GDPR compliance
  - [Service Pattern](service-pattern.md) — Service Layer (PoEAA), SRP, God Object anti-pattern
  - [Support Pattern](support-pattern.md) — Utility Pattern, Pure Functions, Immutability
  - [Repository Pattern](repository-pattern.md) — Repository (PoEAA), Active Record vs Data Mapper
  - [Policy Pattern](policy-pattern.md) — RBAC (NIST), Least Privilege, Defence in Depth
  - [Livewire Pattern](livewire-pattern.md) — Thin Controller, Presentation Model, MVVM
  - [UI Pattern](ui-pattern.md) — Visual Hierarchy, Tailwind v4 CSS-first, responsive
  - [UX Pattern](ux-pattern.md) — WCAG 2.2 AA, NN/g Heuristics, W3C i18n
  - [Testing Pattern](testing-pattern.md) — TDD, Testing Pyramid, AAA, FIRST
