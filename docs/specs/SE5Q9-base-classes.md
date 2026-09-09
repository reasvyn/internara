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

A developer assigned to a new SMK reporting module once scaffolded it under `app/{Module}/` and found the architecture assembling itself beneath her: the Model extending `BaseModel` brought UUID keys without a thought, the Entity extending `BaseEntity` arrived `final readonly` and bridged through `fromModel()`, mutations became `BaseCommandAction`s with transaction and logging already wired, the table screen extending `BaseRecordManager` inherited search, filter, sort, pagination, selection, and bulk actions, and the Policy extending `BasePolicy` carried the super-admin allow for free. Because every convention arrives by construction, `scan_class_contracts.py` simply confirms what the bases already guaranteed, under the governing guidance of the [base-class-mandate ADR](../adr/adr-base-class-mandate.md) and §6.1.

#### UC-BASE-002 — Business-Rule Violation Surfaces Cleanly

When a student submits an invalid operation through a Livewire form, the request travels a fixed pipeline: the component calls the Command Action, the Action delegates the rule check to the Entity, the rule fails, the Action throws `RejectedException`, and the Livewire base catches it into a user-friendly toast rendered through `__()` — no stack trace, no 500 page. Behind the scenes the attempt is recorded through `BaseAction::log()` for audit. A feature test driving an invalid submission through the full path demonstrates the C8 invariant end to end, with the business rejection traveling as `ModuleException` and never as a bare `RuntimeException`.

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

Consider a team that once wrapped transactions in one Action, logged in another, and forgot both in a third — three mutations with three reliability stories. `BaseAction` ends that variance by gathering the shared concerns — the `transaction()` wrapper, `dispatchEvent()`, SmartLogger-backed `log()`, and the `HandlesActionErrors` safety net — into the abstract root that Command and Process Actions extend. Read Actions deliberately stand apart under FR-BASE-003 rather than inheriting ceremony they must never use. The `scan_class_contracts.py` extends-check at the arch layer confirms the lineage holds.

#### FR-BASE-002 — BaseCommandAction

The mutation workhorse grew out of the god-service era, when a single `RegistrationService` accumulated `register()`, `approve()`, and `bulkApprove()` until nobody could say which path validated or logged. `BaseCommandAction` replaces that sprawl with a fixed toolkit — response factories `respond()`, `respondDeleted()`, and `respondError()`, plus `validate()`, the `authorize()` gate, and `flash()` messaging — and every concrete mutation takes the `{Verb}{Entity}Action` name the [action-pattern ADR](../adr/adr-action-pattern-over-services.md) mandates. Contract scanning at the arch layer shows each command carrying exactly that shape.

#### FR-BASE-003 — BaseReadAction

Give a dashboard widget transaction and logging powers and, during the morning attendance rush of a thousand concurrent students, it will eventually lock rows it only meant to count. `BaseReadAction` is deliberately crippled to prevent exactly that: a standalone base that never extends `BaseAction`, offering cache-backed reads with auto key generation alongside `remember()`, `rememberForever()`, `cacheKey()`, PII `mask()`, consistent `paginate()`, and a standard `format()` envelope, while never mutating state, opening transactions, or writing audit logs. A trivial same-module `Model::find()` may stay inline in Livewire; a Read Action earns its keep for complex aggregation, filtering, or cross-module assembly. Contract scanning plus a no-mutation feature test confirms reads stay lean.

#### FR-BASE-004 — BaseProcessAction

An SMK program-closure week once ran as copy-pasted orchestration inside three Livewire components, each sequencing finalize-assessments then issue-certificates slightly differently until one school issued certificates without finalized grades. `BaseProcessAction` exists so that orchestration has exactly one home: multi-step coordination composing other Actions through constructor injection per the [cross-module-communication ADR](../adr/adr-cross-module-communication.md) delegation guidance, with `step()` success and failure tracking, `trackProgress()`, `notify()`, `logProgress()`, and a single module event for the completed process under the `{Verb}{Entity}Process` name. Contract scanning at the arch layer verifies the shape.

#### FR-BASE-005 — One public execute()

Inside any concrete Action, the call graph stays trivially auditable: helpers such as `validate()`, `authorize()`, `step()`, and `remember()` live on the bases, and the subclass contributes exactly one public method, `execute()`. A reviewer tracing a mutation therefore starts at one doorway every time, and `scan_class_contracts.py` at the arch layer enforces the count mechanically — any second public method is a violation, not a style choice.

#### FR-BASE-006 — Transaction wrapping

A placement write that decrements a company slot but fails before creating the registration row leaves a phantom vacancy no report can explain — and enrollment week at a 1,000-student SMK produces exactly the contention that makes such halves likely. Every write path therefore runs atomic inside `$this->transaction()`, so a failed path rolls back whole and partial writes count as defects. Transient deadlocks retry per NFR-BASE-001, a behavior review plus failure-path feature tests confirm.

#### FR-BASE-007 — Logging after mutation

Silent mutations were once the norm: records changed, nobody knew who changed them, and BAN-PDM accreditation evidence had gaps no coordinator could fill. Success paths now record through SmartLogger — dual-channel into the system log and the activity log — following the [smartlogger ADR](../adr/adr-smartlogger-dual-channel.md), so every significant business event is auditable by default. Mutation feature tests asserting the activity rows turn the default into a verified habit.

#### FR-BASE-008 — Return contracts

Let each Action invent its own return shape and every Livewire component becomes a branching mess of `if (isset($result['ok']))` guesses. The contract removes the guessing: Command and Process Actions return `ActionResponse` with its `success` and `data` keys, while Reads return value data — a value object, collection, or DTO — and never mutate state. A Read smuggling a raw cross-module Eloquent Model across the boundary breaks the rule and must map to Entity or DTO first. Contract scanning together with a DB-snapshot no-mutation test for Reads proves both halves.

### 4.2 Data Layer

#### FR-BASE-009 — BaseModel

A school that once mixed auto-increment integers with UUIDs learned the cost during a join: every `foreignUuid` against an integer PK failed, and the fix touched a dozen migrations. `BaseModel` makes that mixture structurally impossible by remaining a pure persistence adapter — UUID v7 keys through `HasUuids`, common scopes through `HasCommonScopes`, `$incrementing = false` with `$keyType = 'string'`, an Entity bridge accessor, and the `#[Fillable]` attribute per D4 — while carrying no business rules at all, those belonging to the Entity under the [entity-model-separation ADR](../adr/adr-entity-model-separation.md). The extends-check with the `scan_conventions.py` D4 pass at the arch layer confirms the adapter stays lean.

#### FR-BASE-010 — BaseAuthenticatable + User exception

`User` cannot extend `BaseModel` no matter how tidy that would be, because Laravel's auth system demands an `Authenticatable` root. `BaseAuthenticatable` bridges the gap: it carries the UUID contract — `HasUuids` with a non-incrementing string key — onto the auth hierarchy so `User` keeps UUID consistency without forking authentication. This is the sole documented exception to the mandate, tracked explicitly under DD-BASE-003, and the `User extends BaseAuthenticatable` assertion with its UUID-override review at the arch layer keeps the bridge from drifting away from `BaseModel`.

#### FR-BASE-011 — BaseEntity

An approval invariant buried inside an Eloquent model once required a full database seed just to unit-test a single state transition — a millisecond predicate paying a seconds-long fixture tax. `BaseEntity` inverts that economy: a `final readonly` snapshot of state whose only persistence bridge is `fromModel(Model): static`, with models exposing named accessors like `asRegistrationState()` for the crossing, and value semantics through `equals()` and `with()` with `toArray()` and `JsonSerializable` for transport. Framework types stay welcome where they earn their keep, favoring testability over purity, and the whole arrangement answers to the [entity-model-separation ADR](../adr/adr-entity-model-separation.md). The extends-plus-`final` scan with entity unit tests constructing via `fromModel()` sans database shows the isolation holding.

#### FR-BASE-012 — BaseData DTOs

Before the DTO boundary, Livewire components passed raw arrays — and occasionally whole Models — straight into Actions, so a renamed column broke the UI and a lazy import dragged the query builder into validation logic. `BaseData` fixes the UI-to-business boundary as an `abstract readonly` object carrying only validated scalars, enums, and `Carbon`, never Models or Actions per C6, with `fromArray()` absorbing legacy keys (camelCase and snake_case alike) during the §4.7 migration alongside `toArray()`, `only()`, `except()`, and `merge()`. The C6 scan with the contract scan at the arch layer confirms the boundary stays clean.

#### FR-BASE-013 — ActionResponse

Without a shared envelope, a placement success returned `['ok' => true]` while a certificate success returned `['status' => 'done']`, and the toast layer needed per-module adapters to render either. `ActionResponse` collapses that dialect into one `final readonly` DTO — the uniform `{success, data, message, redirect, errors}` envelope built through `ok()`, `created()`, `updated()`, `deleted()`, and `error()` with `withRedirect()` and `failed()` for the edges — so Livewire maps every Command and Process result to toasts and redirects without branching on ad-hoc shapes. Contract scanning at the arch layer watches the envelope stay single.

#### FR-BASE-014 — HasCommonScopes

An SMK dashboard once filtered "recent" as seven days while its report filtered "recent" as thirty, and both numbers reached the principal's desk in the same meeting. `HasCommonScopes` ends that quiet divergence by fixing the shared query vocabulary — `active()`, `inactive()`, `recent()`, `createdAfter()`, `createdBefore()`, `ordered()` — so modules never redefine the same scopes with slightly different semantics. Trait presence with scope unit tests at the arch layer proves the vocabulary holds.

### 4.3 UI Layer — Livewire Base Classes

#### FR-BASE-015 — BaseRecordManager

Inside `BaseRecordManager` the default table screen for all 18 modules assembles itself: search, filter, sort, pagination, selection, and bulk actions arrive as inherited behavior, while the Extra Menu wires template download, CSV and Excel import-export, and PDF export through `CsvHandler` with row-outcome tracking specified in [csv-import-export](O2KCR-csv-import-export.md). A module's fortieth admin table therefore behaves like its first without reimplementation. The extends-check at the arch layer confirms every manager inherits rather than reinvents.

#### FR-BASE-016 — BaseRecordEntry

A coordinator entering a duplicate partnership at an SMK in Cirebon once received a raw 500 page; after the base took over, the same mistake highlights the offending field inline. `BaseRecordEntry` carries that modal create-and-edit behavior with form binding built in, mapping `RejectedException` through `handleError()` onto inline form errors — the UC-BASE-002 path rendered at component level. The extends-check with an invalid-submission feature test shows the mapping working.

#### FR-BASE-017 — BaseRecordList

Not every screen should offer a create button: public-facing lists and audit views must stay read-only even when the underlying table supports mutation elsewhere. `BaseRecordList` is the manager's deliberately narrowed sibling — search plus pagination, with no create or edit affordances to accidentally expose. The narrowing is historical as much as structural: it was extracted after a read-only report screen inherited full CRUD buttons by copy-paste. The extends-check at the arch layer confirms list screens inherit the restraint.

#### FR-BASE-018 — BaseFormView

Lose a half-completed school-profile form to an accidental navigation and the SMK operator retypes thirty fields from memory — the failure mode `BaseFormView` was built to absorb. Full-page forms for settings, profiles, and their kin inherit dirty tracking with `handleSave()`, so unsaved-changes awareness arrives without per-screen wiring. The extends-check at the arch layer verifies the inheritance.

#### FR-BASE-019 — BaseWizard

An SMK setup flow that lets operators jump to step four before step one validates produces half-configured schools and cryptic failures three screens later. `BaseWizard` sequences multi-step flows such as [installation](8NZAU-installation.md) through an abstract `steps()` definition with `nextStep()` validating before advancing, `prevStep()` retreating, `goToStep()` gated by `isStepAccessible()` so only completed ground is reachable, plus `progressPercent()`, `currentStepKey()`, localized `handleStepError()`, and state persistence hooks for resumability. The extends-check with a wizard-journey feature test walks the gates end to end.

#### FR-BASE-020 — BaseController

Inside the rare REST surface the application exposes, each endpoint still needs the same JSON manners: success, creation, error, and paginated envelopes shaped identically so API consumers parse once. `BaseController` contributes those cross-cutting helpers — `jsonSuccess()`, `jsonCreated()`, `jsonError()`, `jsonPaginated()` — without pulling in any domain logic. The extends-check at the arch layer confirms controllers inherit the manners rather than improvising them.

#### FR-BASE-021 — BaseFormRequest

One endpoint redirecting on validation failure while its neighbor returns JSON trained SMK frontend code to handle both — until a silent pass let bad data straight into the database. `BaseFormRequest` collapses the variance into a single behavior: failed validation throws `ValidationFailedException` for HTTP 422 everywhere, never an ad-hoc redirect and never a quiet pass. The extends-check with a validation feature test demonstrates the uniformity.

#### FR-BASE-022 — WithSorting

Sort parameters arrive from the browser, which makes `sortBy=requested_column` a quiet injection vector when the value flows near an order clause. `WithSorting` neutralizes it with a whitelist: the `$sortBy` column-plus-direction pair validates against `$sortableColumns`, and `applySorting(Builder)` applies only what survives validation. The convention grew from that exact probe — sorting is user input and must be treated as such. Concern usage with a unit test on `applySorting()` proves the gate.

#### FR-BASE-023 — WithRecordSelection

Bulk-approve 300 placements and the selection state has to survive pagination, filtering, and a distracted coordinator switching tabs mid-task. `WithRecordSelection` carries that shared state — `$selectedIds` with `selectAll(ids)`, `clearSelection()`, and the computed `selected_count` — identically across every manager screen, while `selectAll` stays scoped to the visible and authorized ID set the caller supplies. Concern-usage review at the arch layer confirms managers share rather than reimplement.

### 4.4 Contracts — Enum & Interface

#### FR-BASE-024 — LabelEnum

A status column rendering its raw `->value` leaks `pending_verification` into an SMK operator's screen where `Menunggu Verifikasi` belongs. `LabelEnum` makes that leak a contract violation by requiring `label(): string` on every enum, with bilingual text through `__()` at the implementation. The implements-check at the arch layer verifies no enum escapes human-renderability.

#### FR-BASE-025 — StatusEnum

Inside `StatusEnum` the state machine stops living in comments and starts living in code: extending `LabelEnum` and adding `isTerminal()`, `canTransitionTo()`, and `validTransitions()`, so illegal transitions are rejected through `RejectedException` instead of being silently applied. A certificate marked terminal can never drift back to draft because the transition table says no. The implements-check with transition unit tests exercises every legal edge and probes the illegal ones.

#### FR-BASE-026 — ColorableEnum

An SMK principal scanning eighteen modules' tables learns green-means-final in one module and then finds green-means-draft in the next — the kind of inconsistency that erodes trust in every badge on screen. `ColorableEnum` fixes badge colors to the enum itself through `color(): string`, so status pills stay consistent wherever the status travels. The implements-check at the arch layer confirms the coupling.

#### FR-BASE-027 — SendsNotifications

Direct imports of a sibling's notification class once wove the modules into a knot where renaming one notification broke three unrelated features. The `SendsNotifications` Core contract cuts that knot per the [cross-module-communication ADR](../adr/adr-cross-module-communication.md): modules consume the contract's `execute(NotificationData $data)` shape and never a concrete sibling class, while channel wiring and dispatch mechanics stay owned by [notification-infrastructure](TXR2H-notification-infrastructure.md). The implements-check at the arch layer shows the decoupling holding.

### 4.5 Exception Hierarchy

> **Canonical source:** [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) §4.5 — this section states the hierarchy for base-class purposes; rendering and handling detail lives there.

#### FR-BASE-028 — AppException root

Catch a bare `RuntimeException` to render a 500 page and a business rejection meant as a friendly toast disappears into the error log — the confusion the sibling-tree design was built to kill. `AppException` gives framework and infrastructure failures (action plumbing, external services, presentation) their own abstract root with structured status codes via `statusCode()` and the `HasExceptionContext` trait, so infrastructure catches never overlap business ones. The extends-check at the arch layer confirms the root.

#### FR-BASE-029 — ModuleException sibling root

A vocational school deployment once swallowed a payment-gateway timeout as a "quota full" message because one hierarchy forced both failures through the same catch. `ModuleException` exists so that story never repeats: deliberately not a child of `AppException` but its sibling, it lets `catch (ModuleException)` target business rules only and never accidentally swallow infrastructure failures, exactly as the [exception-hierarchy ADR](../adr/adr-exception-hierarchy.md) prescribes. A hierarchy assertion at the arch layer proves the two trees share no bloodline.

#### FR-BASE-030 — RejectedException (C8)

Inside the Action the failure path reads like a sentence: the rule check fails, the code calls `$this->fail()` or throws `RejectedException`, and Livewire renders the translatable message as a toast. That single business-rejection type — HTTP 400 for invalid transitions, duplicates, not-found-as-rule, and rate limits — supersedes the legacy `ConflictException`, `NotFoundException`, and `RateLimitException` trio precisely because three catch branches taught developers to catch `Exception` instead. Throwing a bare `RuntimeException` for a rule violation is the C8 defect, and the `scan_violations.py` C8 check at the arch layer catches it.

#### FR-BASE-031 — ValidationFailedException

A coordinator submitting a half-filled placement form with an invalid company ID should see field errors, not a vanished draft. `ValidationFailedException` carries that outcome: thrown by `BaseFormRequest` and by `BaseCommandAction::validate()`, it renders as HTTP 422 with the field errors attached. Validation feature tests at the feature layer drive bad payloads through both entry points and watch the same 422 shape emerge.

#### FR-BASE-032 — UnauthorizedException

Authorization failures once surfaced as generic 500s that sent SMK operators to the developer for what was really a permissions question. `UnauthorizedException` gives those denials their own voice: thrown by `BaseCommandAction::authorize()` and by policy denials, it renders as HTTP 403 with a message the operator can act on. Policy unit tests confirm the denial path without touching the database.

#### FR-BASE-033 — InfrastructureException

Show a student the raw certificate-PDF timeout payload and you leak internals while helping nobody; swallow it silently and the operator never learns the disk filled up. `InfrastructureException` threads the distinction: external-service and framework failures log with full context for operators yet never render verbatim to users, who see a generic retry instead. The extends-check at the arch layer confirms the type sits under `AppException` where infrastructure catches expect it.

#### FR-BASE-034 — HasExceptionContext

An exception without guidance — no hint for the user, no context for the log, no CLI rendering for the artisan run — forces every catch site to reinvent all three. `HasExceptionContext` supplies them once for both trees: `withHint()` and `withContext()` to attach resolution guidance and key-value debug detail, `getHint()` and `getContext()` to read them back, and `toCliOutput()` so commands render either tree identically. A trait-use scan at the arch layer shows both hierarchies sharing the capability.

#### FR-BASE-035 — ActionFailedException

Inside `HandlesActionErrors` the safety net has one job left for failures nobody anticipated: an unknown `Throwable` escaping an Action must never cross layer boundaries raw, where a Livewire component might render a stack trace to a student. It is wrapped into `ActionFailedException`, the terminal infrastructure type that marks the error as unhandled-but-contained. A failure-path test asserting the wrapper lands at the feature layer proves raw failures never leak unwrapped.

### 4.6 Policies

#### FR-BASE-036 — BasePolicy + super-admin gate

A policy author at an SMK once wrote a meticulous ownership check that locked out everyone — including the super admin called in to fix the outage it caused. The `before()` hook on `BasePolicy` makes that lockout structurally impossible by auto-allowing `super_admin` before any ability check runs, a guarantee consumed per [rbac-and-authorization](T4B26-rbac-and-authorization.md). The extends-check with a super-admin-allow unit test demonstrates the escape hatch surviving even the strictest policy.

#### FR-BASE-037 — AuthorizesRoles

When each module defined "admin" locally, a supervisor counted as admin in one policy and not in another, and cross-module authorization reviews became archaeology. `AuthorizesRoles` centralizes the role vocabulary — `isAdmin()`, `canManageAnyRole()`, `hasAnyOfRoles()` — so the word means the same thing in every module's gate. Trait use with unit tests at the arch and unit layers keeps the vocabulary single.

#### FR-BASE-038 — AuthorizesOwnership

Copy-pasted owner-or-admin checks drift: one policy compares `user_id`, another traverses a different relation, a third forgets the admin branch, and students intermittently see each other's logbooks. `AuthorizesOwnership` stops the drift by fixing the recurring shape once — `isOwner()`, `isRelatedThrough()`, `isOwnerOrAdmin()` — so students see their own records and admins see all, identically everywhere. Trait use with unit tests at the arch and unit layers confirms no policy reimplements the shape by hand.

### 4.7 Gradual DTO Migration (ADR-Demanded)

#### FR-BASE-039 — Array start

A developer facing a placement CSV whose columns changed weekly during pilot once stalled for days designing a twelve-field DTO that was obsolete before review. The Start phase refuses that trap: while the input shape is still changing, the Action ships as `execute(array $data)` and the feature moves. The governing principle is good enough today beats perfect next week, owned by the [gradual-migration ADR](../adr/adr-gradual-migration.md) — velocity now, with the migration path ahead keeping direction.

#### FR-BASE-040 — Union stabilize

Once callers multiply, changing the signature overnight would break every one of them at once. The Stabilize phase bridges the gap with a backward-compatible union: `execute(Data|array $data)` accepts both shapes while callers migrate at their own pace, and `BaseData::fromArray()` with its camelCase and snake_case fallback absorbs legacy keys so old call sites keep compiling. Mixed phases during migration are expected and temporary, a tolerance review confirms rather than punishes.

#### FR-BASE-041 — DTO final

An SMK enrollment Action that still accepted raw arrays after its shape settled kept sprouting ad-hoc keys — `company_id` here, `companyId` there — until validation lived nowhere. The Final phase collapses settled shapes to `Data`-only signatures, `execute(Data $data)`, so the DTO becomes the single validated contract and the C7 rule for three or more parameters holds by construction. The `scan_violations.py` C7 check at the arch layer marks the arrival.

### 4.8 Shared Validation & Key Registry (ADR-Demanded)

#### FR-BASE-042 — Entity::rules() sharing

Validation rules for the same entity once lived in two form objects that diverged field by field until identical forms rejected different input. The centralization path heals the split in stages: when the second form appears, rules move into `Entity::rules()` where both forms reference them, and full DRY later gathers every rule in the Entity. The journey spares day-one ceremony while converging on one truth, governed jointly by the [gradual-migration ADR](../adr/adr-gradual-migration.md) and the [entity-model-separation ADR](../adr/adr-entity-model-separation.md).

#### FR-BASE-043 — Registry-backed read caching

Inline cache-key strings rot: two Reads caching under `slots` with different TTLs, a third forgetting to invalidate, and enrollment numbers that disagree by screen. The final phase of the cache-migration path builds keys from the registry instead — `BaseReadAction::remember()` and `cacheKey()` deriving module-scoped keys from `config/cache-keys.php` — so an unregistered key fails the C4 check rather than silently forking. The migration's earlier legs live in ZT6VS FR-CORE-010 and FR-CORE-013, and the `scan_violations.py` C4 check at the arch layer enforces arrival.

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

During concurrent attendance writes at an SMK with 800 students clocking in within the same ten minutes, two transactions occasionally deadlock — a transient collision, not a bug, that should never surface as a student-facing error. `BaseAction::transaction(callable $callback, int $attempts = 3)` absorbs those moments by retrying up to three attempts before admitting defeat. Signature review with a concurrent-write test demonstrates the absorption holding under contention.

#### NFR-BASE-002 — Abstract-only bases

Inside the container, a directly instantiated base is a contradiction: bases exist to be extended, and an instance of one carries shared machinery with no domain to govern. The mandate therefore keeps every base abstract with zero direct instantiations permitted. The `scan_class_contracts.py` pass at the arch layer walks the mandate table in §6.1 and reports any instantiation as a structural break.

#### NFR-BASE-003 — Purity (C5/C6)

An Entity importing a Model drags the query builder into what should be a millisecond unit test; a DTO importing an Action turns the validation surface into a dependency knot. Purity forbids both: Entities stay `final readonly` and DTOs carry only scalars, enums, and Carbon, never Models or Actions. The `scan_violations.py` C5 and C6 checks at the arch layer keep business rules database-free and Actions single-surfaced.

#### NFR-BASE-004 — Runtime discovery

Manual registration of Livewire components, policies, and views across 18 modules would rot within a semester — every new screen a chance to forget a line. Runtime discovery through `ModuleService` with cached results removes the forgetting by removing the manual step: boot finds what the filesystem declares. The arrangement exists because hand-maintained provider lists failed that way before, and the discovery tests in [module-discovery](I1BCV-module-discovery.md) demonstrate the automatic registration holding.

#### NFR-BASE-005 — Scan coverage of the mandate

Losing the architecture tests to the `pest-plugin-arch` compatibility bug could have left the mandate in §6.1 on honor-system enforcement — exactly how contract drift begins. Until the plugin returns, blocking review plus the `tools/` scan batch (naming, conventions, contracts) stands in as the enforcer, with the pre-commit arch-guard from AGENTS.md §4–§5 as the gate every change passes. A green batch is the standard met.

### 5.2 Localization & Accessibility

#### NFR-BASE-006 — Translated base messages

A hardcoded English string in a base class does not stay a single oversight — it replicates into every module's toasts, validation messages, and wizard labels, greeting SMK operators in a foreign language on dozens of screens. Base-class messages therefore resolve through `__()` with both `en` and `id` strings from the start. `LangChecker` with review proves zero hardcoded user strings survive.

#### NFR-BASE-007 — Accessible error pages

Inside the failure journey, the exception hierarchy in §4.5 decides which error page a user lands on, so the bar for those pages belongs beside the hierarchy even though the rendering detail lives elsewhere. The bar is WCAG 2.1 Level AA per [logging-and-error-handling](89SRA-logging-and-error-handling.md), verified manually — which is why no layer tag claims an automated check here.

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

A controller catching one `RuntimeException` root once rendered an infrastructure outage as a business toast and a business rejection as a 500 page — both wrong, both confusing to the SMK operator reading them. Two sibling trees under `RuntimeException` fix the targeting: `AppException` for framework failures, `ModuleException` for business violations, with `RejectedException` as the familiar HTTP 400 face of the business side. The hierarchy costs a little extra shape to learn, but it ends the catch-everything habit, with canonical detail in [logging-and-error-handling.md](89SRA-logging-and-error-handling.md) §4.5 and §7.1.

#### DD-BASE-002 — Module Discovery at Runtime

In the early flat-layout days every new Livewire component meant a hand-written registration line in a service provider, and the forgotten line always surfaced as a blank page during a school demo — never in development, where the component had been registered months earlier by someone who has since left. With nineteen modules that failure mode multiplies by every component, policy, and Blade namespace the system owns. Runtime discovery through `ModuleService` removes the manual registry step entirely: a new module is picked up simply for existing on disk in the right directory, and the slight boot cost is paid once and cached. Full mechanics in [module-discovery](I1BCV-module-discovery.md).

#### DD-BASE-003 — User/Authenticatable Exception

Laravel's auth system only recognizes `Authenticatable`, so `User` cannot extend `BaseModel` no matter how uniform the mandate wants to be. The bridge `BaseAuthenticatable` carries the missing half of the contract — `HasUuids` plus the `getIncrementing()` / `getKeyType()` overrides — which keeps every `foreignUuid` join against `users` honest. The day someone "simplifies" `User` back onto auto-increment, half the schema's foreign keys silently disagree in type, and the failure arrives as corrupt joins rather than a loud error. That is why this is the sole documented exception and why it is pinned by an arch assertion at layer `A`: any `BaseModel` evolution must be mirrored here by hand (FR-BASE-010).

#### DD-BASE-004 — Gradual DTO Adoption

Ask a developer to design the perfect `PlacementImportData` DTO before writing a line of import logic during a pilot where the CSV shape changes weekly, and the rational response is to not write the Action at all — velocity dies to ceremony. The three-phase path (§6.5) exists so the first version ships on `execute(array)`, the union signature keeps old callers green while the shape settles, and the DTO becomes the sole contract only when there is something stable to contract against. Mixed phases are the visible cost and they are temporary by design; the real risk is areas that stall at phase one forever, which is what the quarterly architecture review hunts. Governance lives in the [gradual-migration ADR](../adr/adr-gradual-migration.md).

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
