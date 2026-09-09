# Base Classes — Action Triad, Data Layer, UI Layer, Policies & Contracts

> **Spec ID:** SE5Q9
> **Status:** Full
> **Owner:** Core
> **Depends on:** FB792, ZT6VS

## Description

Defines the architectural base classes and contracts every module extends — the Action Triad, Entity/DTO/Model data layer, Livewire UI bases, Policy authorization, enum contracts, and the exception hierarchy. Tech-stack versions live in [tech-stack.md](FB792-tech-stack.md); runtime service behavior in [core-infra-services.md](ZT6VS-core-infra-services.md); cross-cutting utilities in [shared-utilities.md](C8F0D-shared-utilities.md).

---

## 1. Problem Statements

### PS-1 — Base Class Consistency

Eighteen modules share one vocabulary: how to write Actions, Models, Entities, DTOs, Livewire components, and Policies. Without enforced base classes, each module reinvents the pattern — transaction handling here, UUID setup there — creating maintenance nightmares and subtle behavioral drift across 150+ Actions.
**→ Requirement:** FR-BASE-001–008 (Action Triad), FR-BASE-009–014 (data layer), FR-BASE-015–023 (UI layer).

### PS-2 — Architectural Invariant Enforcement

Critical invariants (C1: no Model mutations in Livewire, C5: Entity forbidden imports, C6: DTO forbidden imports, C8: `RejectedException` over `RuntimeException`) must be enforceable at the class level, not just by convention. Abstract base classes with restricted APIs turn violations into structural failures rather than runtime surprises.
**→ Requirement:** FR-BASE-030 (C8), FR-BASE-036–038 (policy enforcement), NFR-BASE-003 (C6), NFR-BASE-005 (mandate scans).

---

## 2. Goals & Non-Goals

### Goals

- **Enforce the Action Triad via abstract base classes** — Command for mutations, Read for queries, Process for orchestration, each with one `execute()`. *Why:* one traceable mutation path with transactions, logging, and events; reads stay free of write ceremony.
- **Provide Livewire base classes covering all UI patterns** — table CRUD, modal CRUD, read-only list, full-page form, multi-step wizard. *Why:* every screen reuses search, sort, pagination, selection, and error handling instead of reimplementing them per module.
- **Define Entity/DTO/Model contracts with purity boundaries** — `final readonly` Entities bridged via `fromModel()`, `BaseData` DTOs as the UI→Business boundary, UUID Models with no business rules. *Why:* rules become unit-testable without a database; each Action gets a single validation surface.
- **Provide Policy infrastructure with super-admin auto-allow plus role and ownership traits** — `BasePolicy.before()` plus `AuthorizesRoles`/`AuthorizesOwnership`. *Why:* authorization is uniform across 18 modules; no policy forgets the super-admin escape hatch.
- **Maintain the dual exception hierarchy** — `AppException` for framework failures, `ModuleException` for business-rule violations, sharing `HasExceptionContext`. *Why:* catch blocks target either tree independently; the class name communicates intent (`RejectedException` = "rule rejected this").

### Non-Goals

- **Cache/session infrastructure**. *Why:* owned by [core-infra-services.md](ZT6VS-core-infra-services.md); base classes only consume those services.
- **SmartLogger, PiiMasker, PasswordRules**. *Why:* owned by [shared-utilities.md](C8F0D-shared-utilities.md).
- **Middleware ordering and execution**. *Why:* owned by [middleware-pipeline.md](2CF4Y-middleware-pipeline.md).
- **Exception rendering and error-page handling**. *Why:* owned by [logging-and-error-handling.md](89SRA-logging-and-error-handling.md), the canonical source for §4.5.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled only where the UC has a verifiable, code-testable consequence at this spec's level — both developer workflows below are verified by the mandate scans, so both are filled.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-BASE-001 | Developer creates a new module using the base classes and contracts | P0 | A | Full |
| UC-BASE-002 | System handles a business-rule violation via RejectedException semantics | P0 | F | Full |

### 3.1 Developer Workflows

#### UC-BASE-001 — Create a New Module From the Bases

A developer scaffolds a new module under `app/{Module}/`: the Model extends `BaseModel` (UUID keys automatic), the Entity extends `BaseEntity` (`final readonly`, bridged via `fromModel()`), mutations become `BaseCommandAction`s (transaction + logging automatic), the table screen extends `BaseRecordManager` (search, filter, sort, pagination, selection, bulk actions free), and the Policy extends `BasePolicy` (super-admin allow free). The result follows every architectural convention by construction, and `scan_class_contracts.py` confirms each class extends the mandated base. **Governing guidance:** [base-class-mandate ADR](../adr/adr-base-class-mandate.md), §6.1.

#### UC-BASE-002 — Business-Rule Violation Surfaces Cleanly

A student submits an invalid operation through a Livewire form. The component calls the Command Action, which delegates the rule check to the Entity; the rule fails, the Action throws `RejectedException`, and the Livewire base catches it into a user-friendly localized (`__()`) toast — no stack trace, no 500. The attempt is recorded through `BaseAction::log()` for audit. This is the C8 invariant end to end: business rejection travels as `ModuleException`, never as a bare `RuntimeException`. **Verification:** feature test on an invalid submission (layer `F`).

---

## 4. Functional Requirements

The full base-class mandate — every architectural role extends or implements exactly one Core base; there is no alternative (per [base-class-mandate ADR](../adr/adr-base-class-mandate.md)).

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-BASE-001 | `BaseAction` is the abstract root: `transaction()` wrapper, `dispatchEvent()`, `log()`, error handling via `HandlesActionErrors` | P0 | A | Full |
| FR-BASE-002 | `BaseCommandAction` covers all mutations: `respond()`, `respondDeleted()`, `respondError()`, `validate()`, `authorize()`, `flash()` | P0 | A | Full |
| FR-BASE-003 | `BaseReadAction` covers queries only: `remember()`, `rememberForever()`, `cacheKey()`, `mask()` (PII), `paginate()`, `format()` — never `transaction()` or `log()` | P0 | A | Full |
| FR-BASE-004 | `BaseProcessAction` covers orchestration: `step()` with success/failure tracking, `trackProgress()`, `notify()`, `logProgress()` | P0 | A | Full |
| FR-BASE-005 | Every Action exposes exactly one public entry point: `execute()` | P0 | A | Full |
| FR-BASE-006 | Command/Process Actions wrap DB operations in `$this->transaction()` | P0 | A | Full |
| FR-BASE-007 | Command/Process Actions call `$this->log()` after successful mutation | P0 | A | Full |
| FR-BASE-008 | `BaseCommandAction::execute()` returns `ActionResponse`; `BaseReadAction::execute()` returns value data and never mutates state | P0 | A | Full |
| FR-BASE-009 | `BaseModel` is abstract Eloquent with `HasUuids` + `HasCommonScopes`: UUID v7 keys, `$incrementing = false`, `$keyType = 'string'` | P0 | A | Full |
| FR-BASE-010 | `BaseAuthenticatable` bridges Laravel `Authenticatable` with UUID support; `User` extends it (sole documented exception to BaseModel) | P0 | A | Full |
| FR-BASE-011 | `BaseEntity` is `abstract readonly`, implements `JsonSerializable`, requires `fromModel()`; exposes `toArray()`, `equals()`, `with()` | P0 | A | Full |
| FR-BASE-012 | `BaseData` is `abstract readonly`, implements `JsonSerializable`; `fromArray()` with camelCase/snake_case fallback, `toArray()`, `only()`, `except()`, `merge()` | P0 | A | Full |
| FR-BASE-013 | `ActionResponse` is a final readonly DTO: `ok()`, `created()`, `updated()`, `deleted()`, `error()`, `withRedirect()`, `failed()` | P0 | A | Full |
| FR-BASE-014 | `HasCommonScopes` provides `active()`, `inactive()`, `recent()`, `createdAfter()`, `createdBefore()`, `ordered()` | P1 | A | Full |
| FR-BASE-015 | `BaseRecordManager` — table CRUD: search, filter, sort, pagination, selection, bulk actions, Extra Menu (template download, CSV/Excel import-export, PDF export via `CsvHandler`; mechanics in [csv-import-export](O2KCR-csv-import-export.md)) | P0 | A | Full |
| FR-BASE-016 | `BaseRecordEntry` — modal create/edit with form binding and `handleError()` for `RejectedException` | P0 | A | Full |
| FR-BASE-017 | `BaseRecordList` — read-only list: search + pagination, no create/edit | P1 | A | Full |
| FR-BASE-018 | `BaseFormView` — full-page form: dirty tracking, `handleSave()` | P0 | A | Full |
| FR-BASE-019 | `BaseWizard` — multi-step wizard: abstract `steps()`, `nextStep()` (validates + advances), `prevStep()`, `goToStep()` (access-checked), `isStepAccessible()`, `progressPercent()`, `currentStepKey()`, `handleStepError()`, state persistence hooks | P0 | A | Full |
| FR-BASE-020 | `BaseController` — JSON helpers: `jsonSuccess()`, `jsonCreated()`, `jsonError()`, `jsonPaginated()` | P1 | A | Full |
| FR-BASE-021 | `BaseFormRequest` — failed validation throws `ValidationFailedException` consistently | P0 | A | Full |
| FR-BASE-022 | `WithSorting` trait — `$sortBy` (column + direction), `$sortableColumns` whitelist, `applySorting(Builder)` with validation | P1 | A | Full |
| FR-BASE-023 | `WithRecordSelection` trait — `$selectedIds`, `selectAll(ids)`, `clearSelection()`, computed `selected_count` | P1 | A | Full |
| FR-BASE-024 | `LabelEnum` — interface requiring `label(): string` on all enums | P0 | A | Full |
| FR-BASE-025 | `StatusEnum` — extends `LabelEnum`, adds `isTerminal()`, `canTransitionTo()`, `validTransitions()` | P0 | A | Full |
| FR-BASE-026 | `ColorableEnum` — interface requiring `color(): string` for badge styling | P2 | A | Full |
| FR-BASE-027 | `SendsNotifications` — Core contract for notification dispatch: `execute(NotificationData $data)` | P0 | A | Full |
| FR-BASE-028 | `AppException` (abstract) — framework-level errors, abstract `statusCode()`, `HasExceptionContext` trait | P0 | A | Full |
| FR-BASE-029 | `ModuleException` (abstract) — business-level errors, sibling of `AppException` (NOT a child), `HasExceptionContext` trait | P0 | A | Full |
| FR-BASE-030 | `RejectedException` extends `ModuleException` — HTTP 400, all business-rule violations: invalid transitions, duplicates, not-found-as-rule, rate limits (C8 invariant) | P0 | A | Full |
| FR-BASE-031 | `ValidationFailedException` extends `ActionException` — HTTP 422, form validation failures | P0 | A | Full |
| FR-BASE-032 | `UnauthorizedException` extends `PresentationException` — HTTP 403, authorization failures | P0 | A | Full |
| FR-BASE-033 | `InfrastructureException` extends `AppException` — HTTP 500, never user-facing | P0 | A | Full |
| FR-BASE-034 | `HasExceptionContext` trait — `withHint()`, `withContext()`, `getHint()`/`getContext()`, `toCliOutput()` | P0 | A | Full |
| FR-BASE-035 | `ActionFailedException` extends `InfrastructureException` — terminal wrapper for unhandled errors escaping `HandlesActionErrors` | P0 | A | Full |
| FR-BASE-036 | `BasePolicy` is abstract with `before()` auto-allowing `super_admin` | P0 | A | Full |
| FR-BASE-037 | `AuthorizesRoles` trait — `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` | P0 | A | Full |
| FR-BASE-038 | `AuthorizesOwnership` trait — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` | P0 | A | Full |
| FR-BASE-039 | DTO Start phase: `execute(array $data)` while the input shape is still changing | P1 | A | Full |
| FR-BASE-040 | DTO Stabilize phase: `execute(Data\|array $data)` union; `BaseData::fromArray()` keeps legacy callers working | P1 | A | Full |
| FR-BASE-041 | DTO Final phase: `execute(Data $data)` once the shape settles — the DTO is the only contract | P0 | A | Full |
| FR-BASE-042 | Entities may expose `static rules()` returning validation arrays shared by Form Objects and Form Requests (validation-centralization path) | P1 | A | Full |
| FR-BASE-043 | Read Actions resolve cache keys through the `config/cache-keys.php` registry via `cacheKey()` — no inline key strings | P0 | A | Full |

### 4.1 Actions — Action Triad

#### FR-BASE-001 — BaseAction root

- Marker plus shared concerns: transaction, event dispatch, SmartLogger-backed logging, `HandlesActionErrors` safety net. Command and Process Actions extend it; Read Actions stand alone (FR-BASE-003).
- **Verification:** `scan_class_contracts.py` extends-check (layer `A`).

#### FR-BASE-002 — BaseCommandAction

- The mutation workhorse: response factories, input validation, authorization gate, flash messaging. Named `{Verb}{Entity}Action` per [action-pattern ADR](../adr/adr-action-pattern-over-services.md).
- **Verification:** contract scan (layer `A`).

#### FR-BASE-003 — BaseReadAction

- Standalone base (does NOT extend `BaseAction`): cache-backed reads with auto key generation, PII masking, consistent pagination, standard envelope. Must not mutate state, open transactions, or write audit logs — reads pay no write ceremony.
- **Edge case:** trivial same-module `Model::find()` stays inline in Livewire; a Read Action is for complex aggregation, filtering, or cross-module assembly.
- **Verification:** contract scan + no-mutation feature test (layer `A`/`F`).

#### FR-BASE-004 — BaseProcessAction

- Multi-step coordination that composes other Actions via constructor injection (see [cross-module-communication ADR](../adr/adr-cross-module-communication.md) delegation): per-step tracking, progress, notifications, one module event for the completed process. Named `{Verb}{Entity}Process`.
- **Verification:** contract scan (layer `A`).

#### FR-BASE-005 — One public execute()

- Helpers (`validate()`, `authorize()`, `step()`, `remember()`) live on the bases; the concrete class contributes exactly one public method. Convention plus scan-enforced.
- **Verification:** `scan_class_contracts.py` (layer `A`).

#### FR-BASE-006 — Transaction wrapping

- Every write path is atomic; partial writes on failure are a defect. Retry on deadlock per NFR-BASE-001.
- **Verification:** review + failure-path feature tests (layer `A`/`F`).

#### FR-BASE-007 — Logging after mutation

- Success paths record through SmartLogger (dual-channel: system log + activity log) so every significant business event is auditable by default per [smartlogger ADR](../adr/adr-smartlogger-dual-channel.md).
- **Verification:** mutation feature tests assert activity rows (layer `F`).

#### FR-BASE-008 — Return contracts

- Command/Process → `ActionResponse` (`success` + `data` keys); Read → value object, collection, or DTO. A Read returning a raw cross-module Eloquent Model is a boundary violation — map to Entity/DTO first.
- **Verification:** contract scan + DB-snapshot no-mutation test for Reads.

### 4.2 Data Layer

#### FR-BASE-009 — BaseModel

- Persistence adapter only: UUID keys, common scopes, Entity bridge accessor — no business rules (those live on the Entity per [entity-model-separation ADR](../adr/adr-entity-model-separation.md)). `#[Fillable]` attribute per D4.
- **Verification:** extends-check + `scan_conventions.py` D4 (layer `A`).

#### FR-BASE-010 — BaseAuthenticatable + User exception

- `User` must extend Laravel `Authenticatable` for auth, so it cannot extend `BaseModel`; `BaseAuthenticatable` carries the UUID contract instead (`HasUuids`, non-incrementing string key). This is the sole documented exception — kept in sync with `BaseModel` explicitly (DD-BASE-003).
- **Verification:** `User extends BaseAuthenticatable` + UUID overrides review (layer `A`).

#### FR-BASE-011 — BaseEntity

- `final readonly` snapshot of state at a point in time; `fromModel(Model): static` is the only persistence bridge (models expose named accessors like `asRegistrationState()`); value semantics via `equals()`/`with()`. Framework dependencies allowed where practical — testability over purity.
- **Verification:** extends + `final` scan; entity unit tests construct via `fromModel()` without a database.
- **Governance:** [entity-model-separation ADR](../adr/adr-entity-model-separation.md).

#### FR-BASE-012 — BaseData DTOs

- The UI→Business boundary object: validated scalars, enums, `Carbon` — never Models or Actions (C6). `fromArray()` keeps legacy callers compiling during DTO migration (§4.7).
- **Verification:** C6 scan + contract scan (layer `A`).

#### FR-BASE-013 — ActionResponse

- Uniform `{success, data, message, redirect, errors}` envelope from every Command/Process Action; Livewire maps it to toasts/redirects without branching on ad-hoc shapes.
- **Verification:** contract scan (layer `A`).

#### FR-BASE-014 — HasCommonScopes

- Shared query vocabulary (`active/inactive/recent/...`) so modules never redefine the same scopes with slightly different semantics.
- **Verification:** trait presence + scope unit tests (layer `A`).

### 4.3 UI Layer — Livewire Base Classes

#### FR-BASE-015 — BaseRecordManager

- The default table screen for all 18 modules; the Extra Menu wires import/export through `CsvHandler` so every module's bulk operations behave identically (row-outcome tracking is specified in [csv-import-export](O2KCR-csv-import-export.md)).
- **Verification:** extends-check (layer `A`).

#### FR-BASE-016 — BaseRecordEntry

- Modal CRUD with `RejectedException` mapped to inline form errors — the UC-BASE-002 path at the component level.
- **Verification:** extends-check + invalid-submission feature test.

#### FR-BASE-017 — BaseRecordList

- Read-only counterpart to the manager for screens that must not offer mutation affordances.
- **Verification:** extends-check (layer `A`).

#### FR-BASE-018 — BaseFormView

- Full-page forms (settings, profiles) with unsaved-changes awareness via dirty tracking.
- **Verification:** extends-check (layer `A`).

#### FR-BASE-019 — BaseWizard

- Setup and other multi-step flows (see [installation](8NZAU-installation.md)): step gating via `isStepAccessible()` (all prior steps completed), validated advancement, localized step errors, resumable state.
- **Verification:** extends-check + wizard-journey feature test.

#### FR-BASE-020 — BaseController

- Cross-cutting HTTP concerns for the rare REST surface: uniform JSON envelopes and paginated responses.
- **Verification:** extends-check (layer `A`).

#### FR-BASE-021 — BaseFormRequest

- One validation-failure behavior everywhere: throw `ValidationFailedException` (HTTP 422), never an ad-hoc redirect or silent pass.
- **Verification:** extends-check + validation feature test.

#### FR-BASE-022 — WithSorting

- Column whitelist prevents sort-by-arbitrary-input (including SQL-adjacent injection through order clauses); direction validated.
- **Verification:** concern usage + unit test on `applySorting()`.

#### FR-BASE-023 — WithRecordSelection

- Shared selection state for bulk actions across all manager screens; `selectAll` is scoped to the visible/authorized ID set by the caller.
- **Verification:** concern usage review (layer `A`).

### 4.4 Contracts — Enum & Interface

#### FR-BASE-024 — LabelEnum

- Every enum is human-renderable (bilingual labels via `__()`); no raw `->value` leaks into Blade.
- **Verification:** implements-check (layer `A`).

#### FR-BASE-025 — StatusEnum

- State machines declare terminal states and legal transitions in code; illegal transitions are rejected (via `RejectedException`), not silently applied.
- **Verification:** implements-check + transition unit tests.

#### FR-BASE-026 — ColorableEnum

- Badge colors travel with the enum so status pills stay consistent across all 18 modules' tables.
- **Verification:** implements-check (layer `A`).

#### FR-BASE-027 — SendsNotifications

- The preferred cross-module decoupling for notifications per [cross-module-communication ADR](../adr/adr-cross-module-communication.md): modules consume the Core contract, never a concrete sibling class. Channel wiring and dispatch mechanics live in [notification-infrastructure](TXR2H-notification-infrastructure.md).
- **Verification:** implements-check (layer `A`).

### 4.5 Exception Hierarchy

> **Canonical source:** [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) §4.5 — this section states the hierarchy for base-class purposes; rendering and handling detail lives there.

#### FR-BASE-028 — AppException root

- Framework and infrastructure failures (action plumbing, external services, presentation) share one catchable root with structured status codes.
- **Verification:** extends-check (layer `A`).

#### FR-BASE-029 — ModuleException sibling root

- Deliberately NOT a child of `AppException`: `catch (ModuleException)` targets business rules only and can never accidentally swallow infrastructure failures. Per [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md).
- **Verification:** hierarchy assertion (layer `A`).

#### FR-BASE-030 — RejectedException (C8)

- The single business-rejection type: legacy `ConflictException`/`NotFoundException`/`RateLimitException` are superseded — use `RejectedException` consistently. Throw it (or `$this->fail()`), never a bare `RuntimeException`, for rule violations.
- **Verification:** `scan_violations.py` C8 check (layer `A`).

#### FR-BASE-031 — ValidationFailedException

- Thrown by `BaseFormRequest` and `BaseCommandAction::validate()`; renders as 422 with field errors.
- **Verification:** validation feature tests (layer `F`).

#### FR-BASE-032 — UnauthorizedException

- Thrown by `BaseCommandAction::authorize()` and policy denials; renders as 403.
- **Verification:** policy unit tests (layer `U`).

#### FR-BASE-033 — InfrastructureException

- External-service and framework failures; logged with full context, never shown verbatim to users.
- **Verification:** extends-check (layer `A`).

#### FR-BASE-034 — HasExceptionContext

- Every exception carries a user-facing hint (resolution guidance) plus key-value debug context and CLI rendering — shared by both trees.
- **Verification:** trait-use scan (layer `A`).

#### FR-BASE-035 — ActionFailedException

- `HandlesActionErrors` wraps unknown `Throwable`s escaping an Action into this terminal type so raw failures never leak across layer boundaries unwrapped.
- **Verification:** failure-path test asserting the wrapper (layer `F`).

### 4.6 Policies

#### FR-BASE-036 — BasePolicy + super-admin gate

- No policy may lock out `super_admin`; the `before()` hook guarantees the escape hatch before any ability check runs. Consumed per [rbac-and-authorization](T4B26-rbac-and-authorization.md).
- **Verification:** extends-check + super-admin-allow unit test.

#### FR-BASE-037 — AuthorizesRoles

- Role vocabulary in one trait so "admin" means the same thing in every module's policy.
- **Verification:** trait-use + unit tests (layer `A`/`U`).

#### FR-BASE-038 — AuthorizesOwnership

- Owner-or-admin is the recurring authorization shape (students see their own records, admins see all); the trait prevents per-policy reimplementation drift.
- **Verification:** trait-use + unit tests (layer `A`/`U`).

### 4.7 Gradual DTO Migration (ADR-Demanded)

#### FR-BASE-039 — Array start

- No developer hesitates to write an Action for lack of a DTO — ship the array version first. Governing principle: good enough today beats perfect next week.
- **Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md).

#### FR-BASE-040 — Union stabilize

- Backward-compatible intermediate: the union type accepts both shapes while callers migrate; `fromArray()` (with camelCase/snake_case fallback) absorbs legacy keys.
- **Verification:** review — mixed phases during migration are expected and temporary.

#### FR-BASE-041 — DTO final

- Settled shapes collapse to `Data`-only signatures (C7 for 3+ params); the DTO carries the validation surface.
- **Verification:** `scan_violations.py` C7 check (layer `A`).

### 4.8 Shared Validation & Key Registry (ADR-Demanded)

#### FR-BASE-042 — Entity::rules() sharing

- When the same entity is created from two forms, rules move from the Form Object into `Entity::rules()` referenced by both (Stabilize); full DRY centralizes all rules in Entities (Final). Eliminates duplication across UI layers without forcing day-one ceremony.
- **Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md), [entity-model-separation ADR](../adr/adr-entity-model-separation.md).

#### FR-BASE-043 — Registry-backed read caching

- `BaseReadAction::remember()`/`cacheKey()` build module-scoped keys from the registry; an unregistered key is a C4 violation. Final phase of the cache-migration path (see ZT6VS FR-CORE-010/013).
- **Verification:** `scan_violations.py` C4 check (layer `A`).

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO; `N/A` means enforced structurally via scans/tests rather than measured at runtime.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-BASE-001 | Transaction wrapper retries up to 3 attempts on deadlock | 3 attempts | P0 | A | Full |
| NFR-BASE-002 | All base classes are abstract — never instantiated directly | 0 direct instantiations | P0 | A | Full |
| NFR-BASE-003 | Entities are `final readonly`; DTOs carry only scalars/enums/Carbon — never Models or Actions (C5/C6) | 0 forbidden imports | P0 | A | Full |
| NFR-BASE-004 | Module discovery at runtime — no manual registration of Livewire/Policies/Views (see [module-discovery](I1BCV-module-discovery.md)) | N/A | P1 | A | Full |
| NFR-BASE-005 | Base-class mandate covered by scans until arch tests are restored (naming, conventions, contracts) | Arch-guard batch green | P0 | A | Full |
| NFR-BASE-006 | All user-facing messages in base classes use the `__()` translation helper (dual `en` + `id`) | 0 hardcoded user strings | P0 | A | Full |
| NFR-BASE-007 | Error pages rendered by exception handlers meet WCAG 2.1 Level AA | AA | P2 | — | Full |

### 5.1 Structural Integrity

#### NFR-BASE-001 — Deadlock retries

- `BaseAction::transaction(callable $callback, int $attempts = 3)` absorbs transient deadlocks (concurrent attendance writes) without surfacing them. **Measurement:** signature review + concurrent-write test.

#### NFR-BASE-002 — Abstract-only bases

- A directly instantiated base is a contradiction — bases exist to be extended. **Measurement:** `scan_class_contracts.py` (layer `A`).

#### NFR-BASE-003 — Purity (C5/C6)

- Entity and DTO purity keep business rules database-free and Actions single-surfaced. **Measurement:** `scan_violations.py` C5/C6 (layer `A`).

#### NFR-BASE-004 — Runtime discovery

- With 18 modules, manual Livewire/policy registration would rot; `ModuleService` discovery plus cached results keeps boot fast and registration automatic. **Measurement:** discovery test in [module-discovery](I1BCV-module-discovery.md).

#### NFR-BASE-005 — Scan coverage of the mandate

- Architecture tests were removed over a `pest-plugin-arch` compatibility bug; until restored, blocking review plus the `tools/` scan batch enforces the mandate table (§6.1). **Measurement:** pre-commit arch-guard green (AGENTS.md §4–§5).

### 5.2 Localization & Accessibility

#### NFR-BASE-006 — Translated base messages

- Base-class toasts, validation messages, and wizard labels resolve through `__()` with both `en` and `id` strings — a hardcoded English string in a base reaches every module. **Measurement:** `LangChecker` + review.

#### NFR-BASE-007 — Accessible error pages

- Error rendering detail lives in [logging-and-error-handling](89SRA-logging-and-error-handling.md); the bar (WCAG 2.1 AA) is recorded here because the exception hierarchy in §4.5 is what routes users to those pages. Manual verification — hence `—`.

---

## 6. API / Data Contracts

### 6.1 Base-Class Mandate Table

| Layer | Base Class | Provides | Enforced By |
|-------|------------|----------|-------------|
| Model | `BaseModel` | UUID v7 (`HasUuids`), non-incrementing string PK | extends check |
| Model (auth) | `BaseAuthenticatable` | Same UUID contract for `User` — the sole exception (DD-BASE-003) | extends check |
| Action (Command/Process) | `BaseAction` | `transaction()`, `log()`, `HandlesActionErrors` | extends check |
| Action (Read) | `BaseReadAction` | `remember()`, `cacheKey()`, `mask()`, `paginate()` — standalone, no transaction/log | extends check |
| Entity | `BaseEntity` | `final readonly`, `fromModel()` bridge | extends + final |
| Policy | `BasePolicy` | `AuthorizesRoles` + `AuthorizesOwnership`, super-admin `before()` | extends check |
| Livewire CRUD | `BaseRecordManager` | Search, filter, sort, pagination, bulk actions | extends check |
| Controller | `BaseController` | Cross-cutting HTTP/JSON concerns | extends check |
| Form Request | `BaseFormRequest` | Consistent `ValidationFailedException` | extends check |
| Enum | implements `LabelEnum` | `label(): string` | implements |
| Status Enum | implements `StatusEnum` + `LabelEnum` | `canTransitionTo()`, `isTerminal()` | implements |
| Exception | `AppException` or `ModuleException` | `HasExceptionContext` | extends check |
| Cache key | `config/cache-keys.php` | Centralized registry consumed via `cacheKey()` | config array |

### 6.2 Action Triad Signatures

```php
abstract class BaseAction {
    protected function transaction(callable $callback, int $attempts = 3): mixed;
    protected function dispatchEvent(BaseEvent $event): void;
    protected function fail(string $message, array $context = []): never;
    protected function log(string $action, ?Model $subject = null, array $payload = []): void;
}

abstract class BaseCommandAction extends BaseAction {
    protected function respond(mixed $data, ?string $message = null, bool $created = false): ActionResponse;
    protected function respondDeleted(?string $message = null): ActionResponse;
    protected function respondError(string $message, array $errors = []): ActionResponse;
    protected function validate(array $data, array $rules): array;
    protected function authorize(string $ability, mixed $arguments = []): void;
    protected function flash(string $message, string $type = 'success'): void;
}

abstract class BaseReadAction {
    protected function remember(string $key, callable $callback, int $ttl = 300): mixed;
    protected function rememberForever(string $key, callable $callback): mixed;
    protected function forget(string $key): void;
    protected function cacheKey(string $purpose, string ...$qualifiers): string;
    protected function mask(array $data, array $fields = []): array;
    protected function paginate(Builder $query, int $perPage = 15): LengthAwarePaginator;
}

abstract class BaseProcessAction extends BaseAction {
    protected function step(string $name, callable $callback): mixed;
    protected function trackProgress(float $percent, ?string $message = null): void;
    protected function notify(mixed $notifiables, Notification $notification): void;
}
```

### 6.3 Data Layer Signatures

```php
abstract class BaseModel extends Eloquent\Model {
    // Traits: HasUuids, HasCommonScopes
    // UUID v7 primary keys, $incrementing = false, $keyType = 'string'
}

abstract class BaseAuthenticatable extends Authenticatable {
    // Traits: HasUuids — UUID contract for User (DD-BASE-003)
    // $incrementing = false, $keyType = 'string'
}

abstract readonly class BaseEntity implements JsonSerializable {
    abstract public static function fromModel(Model $model): static;
    public static function fromArray(array $data): static;
    public static function rules(?string $excludeId = null): array;
    public function toArray(): array;
    public function equals(self $other): bool;
    public function with(string $property, mixed $value): static;
}

abstract readonly class BaseData implements JsonSerializable {
    public static function fromArray(array $data): static;
    public static function from(mixed $source): static;
    public function toArray(): array;
    public function only(string ...$keys): array;
    public function except(string ...$keys): array;
    public function merge(array $overrides): static;
}

final readonly class ActionResponse implements JsonSerializable {
    public bool $success;
    public mixed $data;
    public ?string $message;
    public ?string $redirect;
    public array $errors;
    public static function ok(mixed $data = null, ?string $message = null): self;
    public static function created(mixed $data = null, ?string $message = null): self;
    public static function updated(mixed $data = null, ?string $message = null): self;
    public static function deleted(?string $message = null): self;
    public static function error(string $message, array $errors = []): self;
    public function withRedirect(string $url): self;
    public function failed(): bool;
}
```

### 6.4 Contracts

```php
interface LabelEnum {
    public function label(): string;
}

interface StatusEnum extends LabelEnum {
    public function isTerminal(): bool;
    public function canTransitionTo(self $target): bool;
    public function validTransitions(): array;
}

interface ColorableEnum {
    public function color(): string;
}

interface SendsNotifications {
    public function execute(NotificationData $data): mixed;
}
```

### 6.5 Gradual DTO Migration Phases

| Phase | Convention | When |
|-------|------------|------|
| Start | `execute(array $data)` | Input shape still changing (FR-BASE-039) |
| Stabilize | `execute(Data\|array $data)` | Union type; `fromArray()` keeps callers working (FR-BASE-040) |
| Final | `execute(Data $data)` | Shape settled; DTO is the only contract (FR-BASE-041) |

### 6.6 Exception Hierarchy

```
RuntimeException
├── AppException (abstract)
│   ├── ActionException (abstract, 400)
│   │   └── ValidationFailedException (422)
│   ├── InfrastructureException (abstract, 500)
│   │   └── ActionFailedException (terminal Action wrapper)
│   └── PresentationException (abstract, 400)
│       └── UnauthorizedException (403)
└── ModuleException (abstract)
    └── RejectedException (400)
```

### 6.7 Livewire Concerns

```php
trait WithSorting {
    public array $sortBy;
    protected array $sortableColumns;
    protected function applySorting(Builder $query): Builder;
}

trait WithRecordSelection {
    public array $selectedIds = [];
    public function selectAll(array $ids): void;
    public function clearSelection(): void;
    #[Computed] public function selected_count(): int;
}
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless a decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-BASE-001 | Dual exception hierarchy (AppException + ModuleException siblings) | P0 | — | — |
| DD-BASE-002 | Module discovery at runtime over manual registration | P1 | — | — |
| DD-BASE-003 | User extends BaseAuthenticatable — sole exception to the BaseModel mandate | P0 | A | Full |
| DD-BASE-004 | Gradual DTO adoption (array → union → DTO) over day-one DTO enforcement | P1 | — | — |

### 7.1 Structure

#### DD-BASE-001 — Dual Exception Hierarchy

**Decision:** Two sibling exception trees: `AppException` (framework) and `ModuleException` (business) under `RuntimeException`.
**Rationale:** Precise catch targeting — a controller catching module violations never accidentally catches infrastructure errors; `RejectedException` (the most common business exception) always means HTTP 400.
**Trade-off:** Slightly more complex hierarchy, but prevents the "catch everything as RuntimeException" anti-pattern.
**Canonical detail:** [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) §4.5, §7.1.

#### DD-BASE-002 — Module Discovery at Runtime

**Decision:** Livewire components, policies, and Blade namespaces are discovered dynamically via `ModuleService`, not manually registered.
**Rationale:** With 18 modules, manual registration in service providers would be error-prone and a maintenance burden. Runtime scanning adds negligible startup cost and picks up new modules automatically.
**Trade-off:** Slightly slower boot time, mitigated by caching discovery results. Detail in [module-discovery](I1BCV-module-discovery.md).

#### DD-BASE-003 — User/Authenticatable Exception

**Decision:** `User` extends `BaseAuthenticatable` (which bridges Laravel `Authenticatable` with `HasUuids`), not `BaseModel` — the sole documented exception to the mandate, kept in sync with `BaseModel` explicitly.
**Rationale:** Laravel auth requires `Authenticatable`; the bridge preserves UUID consistency (`getIncrementing()`/`getKeyType()` overrides) without forking the auth system.
**Trade-off:** The exception adds maintenance burden — it must track `BaseModel` evolution (FR-BASE-010).
**Verification:** `User extends BaseAuthenticatable` assertion (layer `A`).

#### DD-BASE-004 — Gradual DTO Adoption

**Decision:** Actions may start with `execute(array)`, stabilize through `execute(Data|array)`, and settle on `execute(Data)` — enforced migration triggers, not day-one DTO purity.
**Rationale:** DTOs demand a class before any business logic; enforcing them upfront blocks velocity and discourages Action creation. Each phase's trigger is explicit (§6.5).
**Trade-off:** Mixed phases during migration (some Actions on DTOs, some on arrays) — expected and temporary; stalls need periodic architecture review.
**Governance:** [gradual-migration ADR](../adr/adr-gradual-migration.md).

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Strict types | 100% of PHP files (except migrations/config) | `scan_conventions.py` |
| No debug calls | Zero in committed code | `scan_conventions.py` |
| Base class usage | 100% of Actions extend BaseAction variants | `scan_class_contracts.py` |
| Entity contracts | 100% of Entities `final readonly` with `fromModel()` | `scan_class_contracts.py` |
| DTO contracts | 100% of DTOs extend `BaseData` | `scan_class_contracts.py` |
| Mandate coverage | Full mandate table (§6.1) enforced | Arch-guard batch green |
| C8 compliance | 0 business-rule `RuntimeException`s | `scan_violations.py` C8 |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [tech-stack.md](FB792-tech-stack.md) | PHP 8.4, Laravel 13, Eloquent Model base, queue/mail packages |
| [core-infra-services.md](ZT6VS-core-infra-services.md) | Cache/session/queue/mail runtime behavior the base classes consume |

### Build Guide

This spec defines the architectural vocabulary: Action Triad for business logic, Entity/DTO for data, Livewire base classes for UI, Policies for authorization, and the exception hierarchy for error handling. Every module extends these classes. The next step is the cross-cutting utilities, then the event/RBAC infrastructure these bases depend on.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [shared-utilities.md](C8F0D-shared-utilities.md) | Uses `BaseData`, `BaseEntity` contracts for utility DTOs |
| 2 | [event-system.md](NUCY3-event-system.md) | Uses `BaseEvent` and `dispatchEvent()` from `BaseAction` |
| 3 | [rbac-and-authorization.md](T4B26-rbac-and-authorization.md) | Uses `BasePolicy`, `AuthorizesRoles`, `AuthorizesOwnership` |
| 4 | [middleware-pipeline.md](2CF4Y-middleware-pipeline.md) | Defines middleware that `bootstrap/app.php` registers |
| 5 | [installation.md](8NZAU-installation.md) | Uses `BaseWizard`, `BaseCommandAction`, exception hierarchy |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If `pest-plugin-arch` never stabilizes, review-plus-scans remain the permanent mandate enforcement — if the plugin returns, the scan/review split needs re-deciding | Open | Maintainer | — |
| A-1 | We assume the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md) tree plus `ActionFailedException` (FR-BASE-035, added 0.15.x) is the complete hierarchy until 89SRA says otherwise | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — the governing architecture these bases implement (FR-ARC-011–023)
- [Core infra services](ZT6VS-core-infra-services.md) — runtime services the bases consume
- [Base-class-mandate ADR](../adr/adr-base-class-mandate.md) — why one base per layer
- [Action-pattern ADR](../adr/adr-action-pattern-over-services.md) — why the Command/Read/Process triad
- [Entity-model-separation ADR](../adr/adr-entity-model-separation.md) — Entity contract and `rules()` sharing
- [Exception-hierarchy ADR](../adr/adr-exception-hierarchy.md) — sibling-tree rationale and selection guide
- [Gradual-migration ADR](../adr/adr-gradual-migration.md) — DTO/cache/validation migration phases
- [Cross-module-communication ADR](../adr/adr-cross-module-communication.md) — delegation and Core contracts
- [SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md) — what `BaseAction::log()` routes to
- [UUID ADR](../adr/adr-uuid-primary-keys.md) — User-model exception origin
