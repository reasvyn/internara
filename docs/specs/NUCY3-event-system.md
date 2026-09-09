# Event System — Decoupled Communication Infrastructure

> **Spec ID:** NUCY3

## Description

Event-driven communication infrastructure that decouples Internara modules: events fire after
the originating transaction commits, I/O-bound listeners react asynchronously, and the entire
event-to-listener map is centralized in `config/event.php`. This spec contracts the `BaseEvent`
shape, the dispatch lifecycle, payload rules, naming conventions, the observer-vs-event gate,
and the complete registry discipline.

The mutation path that dispatches these events is defined in
[architecture-design](D2FT3-architecture.md) (FR-ARC-027). The `BaseEvent` class and
`dispatchEvent()` live in [base-classes](SE5Q9-base-classes.md); dispatch-time logging lives in
[logging-and-error-handling](89SRA-logging-and-error-handling.md). The observer alternative is
decided in the [eloquent-observers ADR](../adr/adr-eloquent-observers.md).

---

## 1. Problem Statements

### PS-1 — Tightly Coupled Module Communication

Without events, Module A must directly import Module B's Actions to trigger side effects (e.g.,
cache invalidation, notifications). This creates circular dependencies and makes modules
impossible to test or deploy independently.
**→ Requirement:** FR-EVENT-005/006/007 (central registry, deferred dispatch), FR-EVENT-010 (Action tests verify state without side effects).

### PS-2 — Invisible Side Effects

When side effects are inline in Actions, developers cannot see the full impact of a mutation
without reading every downstream Action. This leads to missed invalidations and inconsistent state.
**→ Requirement:** FR-EVENT-005 (single registry), FR-EVENT-012 (3-gate rule keeps the mechanism choice explicit).

### PS-3 — Unrecoverable Failures

Synchronous side effects inside transactions mean a failed notification can roll back the entire
business operation. I/O-bound work (mail, external APIs) should not block or endanger the
primary mutation.
**→ Requirement:** FR-EVENT-007/008 (after-commit dispatch), FR-EVENT-011 (ShouldQueue for I/O-bound listeners).

### PS-4 — Ambiguous Mechanism Choice

With both Eloquent Observers and Events + Listeners available, developers guess which to use —
and guess wrong in both directions (deferred events for must-be-synchronous guards; observers
for cross-module fan-out).
**→ Requirement:** FR-EVENT-012/017/018 (observer-vs-event 3-gate framework), DD-EVENT-004.

---

## 2. Goals & Non-Goals

### Goals

- **Route all cross-module side effects through events** — no direct Action imports for fire-and-forget work. *Why:* direct imports create the circular dependencies the module system exists to prevent.
- **Fire events only after the originating transaction commits** — rollbacks discard their events. *Why:* listeners must never react to uncommitted data or undo a mutation that never happened.
- **Centralize the event-to-listener map in one config file** — `config/event.php` is the whole graph. *Why:* a single auditable source of truth beats per-module registration that no one can see at once.
- **Keep every event listened** — no orphan events. *Why:* an event with no listener is either dead code or a silently dropped side effect.
- **Queue I/O-bound listeners** — mail, external APIs, and heavy work implement `ShouldQueue`. *Why:* the HTTP response must not wait on, or be endangered by, I/O.
- **Make the observer-vs-event choice mechanical** — the ADR 3-gate rule, not judgment. *Why:* synchronous guards and snapshots need observers; everything else needs the decoupling of events.

### Non-Goals

- **Event sourcing or event replay**. *Why:* an immutable event log with replay is operational weight the PKL domain does not need at MVP.
- **Real-time WebSocket event streaming**. *Why:* broadcasting stays on the log driver by default; no realtime surface exists.
- **Event versioning or schema evolution**. *Why:* single-deployable app with no cross-version consumers; payloads evolve with the code.
- **Cross-instance event distribution (multi-server pub/sub)**. *Why:* single-tenant, single-server product definition — no fan-out target exists.
- **Ordering guarantees beyond transaction boundaries**. *Why:* after-commit firing is the only ordering contract; cross-event sequencing is out of scope.
- **Preemptive events without registered listeners**. *Why:* speculative events rot; FR-EVENT-006 forbids orphans.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-EVENT-001 | Developer registers a new event with its listener in the central config | P0 | A | Full |
| UC-EVENT-002 | Developer debugs a silent event flow using the registry and failed-jobs table | P1 | — | — |
| UC-EVENT-003 | System invalidates a cross-module cache when an entity changes, with no direct coupling | P0 | F | Full |

### 3.1 Event Workflows

#### UC-EVENT-001 — Developer Registers New Event

An SMK in Makassar asked that approving a placement immediately refresh the partner dashboard in another module, a textbook new cross-module side effect. The developer creates a `final` class extending `BaseEvent` with typed constructor-promoted properties, implements `eventName()` returning a dot-notation string, registers the listener in `config/event.php`, then implements the listener itself, adding `ShouldQueue` when it is I/O-bound. Once wired, the event fires after commit and the listener executes, following FR-EVENT-001 through 005 plus FR-EVENT-011 and the [event-pattern](../guides/arch/event-pattern.md).

#### UC-EVENT-002 — Developer Debugs Event Flow

When an event stops firing or its listener never runs, debugging walks the dispatch path in order. The developer checks `config/event.php` for registration, verifies the event class extends `BaseEvent`, confirms the listener implements `ShouldQueue` and that a queue worker is actually running, then inspects the `failed_jobs` table for failed listener executions. Each step narrows the root cause to registration, shape, queuing, or failure, and because this is a manual troubleshooting runbook with no code-testable consequence at this spec's level, the row carries `—`.

#### UC-EVENT-003 — Cross-Module Cache Invalidation

The tricky case is a rename: a company updates its name in Module A while Module B still serves the old name from cache, and a placement letter goes out wrong. The automated path avoids that without coupling the modules. A Command Action in Module A dispatches something like `CompanyCreated` when the entity is created, updated, or deleted, the event fires after the transaction commits, and a listener in Module B invalidates the relevant keys from `config/cache-keys.php`. Module B ends fresh with no direct import, under FR-EVENT-007 and the exactly-once guarantee of FR-EVENT-010.

---

## 4. Functional Requirements

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB) · `F` = Feature
(Action/Livewire/Console, real DB) · `B` = Browser (E2E journey) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-EVENT-001 | All events MUST extend the `BaseEvent` abstract class | P0 | A | Full |
| FR-EVENT-002 | Event classes MUST be `final` with `public` typed constructor-promoted properties | P0 | A | Full |
| FR-EVENT-003 | `eventName()` MUST return a dot-notation string matching `{entity}.{past_tense_action}` | P0 | A | Full |
| FR-EVENT-004 | `toPayload()` MUST convert Model properties to `{name}_id` strings, preserve scalars, convert objects via `toArray()`, and skip nulls | P0 | U | Full |
| FR-EVENT-005 | All event-to-listener mappings MUST be registered in `config/event.php` | P0 | A | Full |
| FR-EVENT-006 | No event MAY exist without at least one registered listener | P0 | A | Full |
| FR-EVENT-007 | Events dispatched inside transactions MUST use `$this->dispatchEvent()` (deferred until after commit) | P0 | F | Full |
| FR-EVENT-008 | Events dispatched outside transactions MAY use `Event::dispatch()` (immediate) | P1 | F | Full |
| FR-EVENT-009 | SmartLogger integration: `->event($baseEvent)->save()` auto-dispatches + logs in one call | P0 | F | Full |
| FR-EVENT-010 | Dispatch after commit MUST happen exactly once per Action execution | P0 | F | Full |
| FR-EVENT-011 | I/O-bound listeners MUST implement `ShouldQueue` | P0 | A | Full |
| FR-EVENT-012 | Eloquent Observers MUST be used only when ALL three criteria hold: same-module only, synchronous completion required before the HTTP response, and single-model scope | P0 | A | Full |
| FR-EVENT-013 | Observer side effects MUST run inside the same DB transaction (rolling back with the model) and MUST NOT be queueable — long-running logic is forbidden in observers | P0 | A | Full |
| FR-EVENT-014 | For all other side effects (cross-module, async, fire-and-forget) the Event + Listener pattern MUST be used, per the decision framework in the eloquent-observers ADR | P0 | A | Full |

### 4.1 Event Contract

#### FR-EVENT-001 — BaseEvent extension

Before the base existed, each event hand-rolled its own serialization and half of them broke on queued listeners. `BaseEvent` ended that era by bundling `Dispatchable`, `InteractsWithSockets`, and `SerializesModels` with the `eventName()` and `toPayload()` contract in §6.1, implemented once in [base-classes](SE5Q9-base-classes.md). Every event extends it without exception, and `scan_class_contracts.py` proves the lineage at layer `A`.

#### FR-EVENT-002 — Final events with promoted properties

Let an event stay non-final with mutable public fields and two failures follow: a listener mutates the payload mid-fan-out so the second listener sees different data, and an untyped bag smuggles a live model across the queue boundary. Declaring the payload in the constructor signature as `final` with public typed promoted properties closes both holes — nothing is set after construction, no untyped bags exist. The contract scan plus review holds that shape at layer `A`.

#### FR-EVENT-003 — Dot-notation event names

An SMK helpdesk once chased a missing `placementApproved` log for an hour before realizing SmartLogger keys off dot notation and the event had been registered under camelCase. Names like `company.created`, `placement.approved`, and `assessment.finalized` prevent that — entity noun plus past-tense verb in `{entity}.{past_tense_action}` form, the exact key SmartLogger and audit trails index on. Anything that does not parse that way is a naming violation caught in review, and a unit test asserts `eventName()` per event at layer `U`, grouped with FR-EVENT-004.

#### FR-EVENT-004 — Payload extraction rules

When `toPayload()` runs, it walks each public property and converts by kind: a Model becomes its `{property_name}_id` string, an object exposing `toArray()` becomes an array, scalars pass through untouched, and nulls are skipped entirely. The result is queue-safe by construction because no live Model graph ever crosses the queue boundary — listeners rehydrate from identifiers instead. A unit test over a representative event's `toPayload()` locks those four conversions at layer `U`.

### 4.2 Registration & Topology

#### FR-EVENT-005 — Centralized registration

Today the whole graph fits in one file — 33 mappings in 154 lines — and per-module registration files are forbidden so that stays true. The edge everyone asks about is growth: past roughly fifty events the file gets unwieldy, and at that point it may split per module, but only through a spec amendment, never a silent fork that recreates the invisible topology this rule killed. Boot-time registration validation per NFR-EVENT-005 plus `scan_doc_links.py` over the registry keeps the single source honest at layer `A`.

#### FR-EVENT-006 — No orphan events

The orphan ban comes from a season of speculative events — developers fired `ReportViewed` and `SlotChecked` hoping someone would listen later, and nobody ever did. Dispatching an event nobody handles is either dead code or a silently dropped side effect, and both are defects. The registry audit treats them that way: every event key carries a non-empty listener list, checked at layer `A`.

### 4.3 Dispatch Lifecycle

#### FR-EVENT-007 — Deferred dispatch in transactions

Fire a notification from inside an uncommitted transaction and the failure writes itself: the listener emails a placement confirmation, the transaction then rolls back on a quota check, and the student arrives at a company that has no record of them. Deferred dispatch through `$this->dispatchEvent()` removes that window — events queue in memory during the Action and fire only on successful commit, while rollback silently discards them. The feature test proves both sides by asserting the listener runs on commit and never runs on rollback, at layer `F`.

#### FR-EVENT-008 — Immediate dispatch outside transactions

An SMK admin once pressed the manual cache-refresh button and waited seconds for feedback because the standalone signal had been routed through the deferred queue for no reason. Outside a transaction there is no commit to wait for and no rollback to protect against, so standalone events dispatch immediately via `Event::dispatch()` — the deferred path would add latency for zero safety gain. A feature test pins the immediate behavior at layer `F`.

#### FR-EVENT-009 — SmartLogger dispatch+log combinator

Inside a mutating Action the common path calls `->event($baseEvent)->save()` on SmartLogger, which writes the audit entry and queues the event dispatch as one atomic step — the log row and the after-commit fan-out can never diverge. Mutations that need dispatch without an audit trail skip the combinator and call `$this->dispatchEvent()` directly. The feature test asserts both halves together, the log row and the listener run, at layer `F`.

#### FR-EVENT-010 — Exactly-once dispatch per execution

The double-send edge is embarrassingly easy: dispatch in the Action and again in its listener, and parents receive two identical placement notifications. That shape is a defect, not redundancy. Dispatch after commit happens exactly once per Action execution, and the Action test proves it with a fake listener counter that must read one, at layer `F`.

### 4.4 Listeners & Queuing

#### FR-EVENT-011 — Queue I/O-bound listeners

The rule was written after a welcome-mail SMTP timeout held an enrollment response for thirty seconds and the operator retried, creating the student twice. Anything touching the network — mail, external APIs, media work — now implements `ShouldQueue`, while pure in-process work like a cache forget or a log write stays synchronous. A listener that starts synchronous and grows its first I/O call migrates to `ShouldQueue` at the second one, per the Stabilize trigger in the [gradual-migration ADR](../adr/adr-gradual-migration.md). Queue lifecycle details such as retries, backoff, and timeouts live in [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md), and the contract scan plus review enforces the marker at layer `A`.

### 4.5 Observer-vs-Event Gate

#### FR-EVENT-012 — The 3-gate rule

Guess the mechanism wrong and the failure is structural: an observer reaching across modules couples deploys, a deferred event guarding a delete runs after the row is already gone. The [eloquent-observers ADR](../adr/adr-eloquent-observers.md) makes the choice mechanical with three gates — observer and model in the same module, work that must complete before the response, and reaction only to this model's lifecycle — and failing any single gate means Event plus Listener. Review checks each observer against all three criteria with the §6.5 inventory as evidence, at layer `A`.

#### FR-EVENT-013 — Same-transaction, non-queueable observers

During enrollment week at an SMK in Surabaya, an admin renamed a quota and the very next student request still saw the old value because invalidation had been deferred past the read. Observers exist for exactly that synchronous edge: cache invalidation that must land before the next request, snapshots captured at the precise status-change moment, and deletion guards that stop the delete before it proceeds — all impossible with after-commit events. Their side effects run inside the same database transaction and roll back with the model, never queued, never long-running. Because auto-registration via `booted()` pulls them into every test, suites explicitly disable observers when side effects are unwanted. Feature tests cover each current observer — guard throws, snapshot captured, cache fresh — at layer `A` with spot `F`.

#### FR-EVENT-014 — Events otherwise

Everything outside the three gates travels the decoupled path: a mutation commits, the event fans out after commit, and each listener — notification sender, cross-module cache invalidator, activity logger — runs in its own context with its own retry policy. None of that work lives in an observer, where it would inherit the request's transaction and block the response. `scan_violations.py` plus review keeps the boundary clean at layer `A`.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-EVENT-001 | Event dispatch MUST NOT block the HTTP response | deferred + queued listeners off the request path | P0 | F | Full |
| NFR-EVENT-002 | Deferred events MUST fire within the same process after commit (not via queue) | 0 queue hops for the dispatch itself | P0 | F | Full |
| NFR-EVENT-003 | Queued listeners MUST complete within 60 seconds per attempt | 60s timeout | P1 | F | Full |
| NFR-EVENT-004 | Failed queued listeners MUST retry up to 3 times with exponential backoff | 3 attempts, exponential backoff | P1 | F | Full |
| NFR-EVENT-005 | Event registration in `config/event.php` MUST be validatable at boot time | boot validation green | P0 | A | Full |
| NFR-EVENT-006 | Observer handlers MUST stay fast and synchronous — no I/O, no queueable work inside observers | 0 I/O calls in observers | P0 | A | Full |

### 5.1 Dispatch & Delivery

#### NFR-EVENT-001 — Non-blocking dispatch

The edge is a slow mailbox: an SMTP server takes twenty seconds and enrollment must not wait for it. Dispatch stays off the request path by construction — deferred events plus queued listeners — so the HTTP response returns while mail still sits in the queue. A response-time assertion with I/O-bound listeners attached proves the dispatch path never awaits listener completion.

#### NFR-EVENT-002 — Same-process after-commit firing

An early design pushed the dispatch itself through the queue, which meant a stopped worker silently swallowed every side effect with zero queue hops to spare. The rule now keeps the dispatch in memory in the same process after commit — zero queue hops for the firing itself — and only the listener may queue. A feature test asserts listener effects are visible without running a worker for the dispatch, separating firing reliability from delivery scaling.

#### NFR-EVENT-003 — Listener time budget

A listener that hangs forever holds its worker slot and quietly starves every placement notification behind it. The sixty-second timeout per attempt bounds that damage, enforced through queue worker timeout config with `failed_jobs` monitoring as the backstop. Detailed retry and timeout mechanics live in [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).

#### NFR-EVENT-004 — Retry with backoff

An SMK in Medan lost a batch of industry notifications during a one-minute mail outage because the first failure was final. Retrying up to three times with exponential backoff turns that blip into a delay instead of a loss, giving the downstream service room to recover between attempts. Retry configuration review plus the recovery-rate metric in §8 shows whether the backoff actually heals transient faults.

#### NFR-EVENT-005 — Boot-time registry validation

At boot the registry is walked entry by entry: every event key must resolve to an existing `BaseEvent` subclass and every listener to an existing class, so a renamed or deleted class fails fast instead of dispatching into the void at enrollment time. The boot validation command and scan keep that gate green at layer `A`.

### 5.2 Observer Bounds

#### NFR-EVENT-006 — Fast synchronous observers

The slow-observer edge looks innocent: someone adds a PDF render to a `saved()` hook, and suddenly every student report save waits on a document build. Per the [eloquent-observers ADR](../adr/adr-eloquent-observers.md), observers hold zero I/O calls and no queueable work — long-running logic would block the request, so anything slow graduates to an Event plus queued Listener. Review gates each observer body against that bound at layer `A`.

---

## 6. API / Data Contracts

### 6.1 BaseEvent Contract

```php
abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function eventName(): string;

    public function toPayload(): array
    {
        // Extracts public properties
        // Models → {property_name}_id
        // Objects with toArray() → array
        // Scalars → kept as-is
        // Nulls → skipped
    }
}
```

### 6.2 Event Class Example

```php
final class CompanyCreated extends BaseEvent
{
    public function __construct(
        public readonly Company $company,
    ) {}

    public function eventName(): string
    {
        return 'company.created';
    }
}
```

### 6.3 Three Dispatch Mechanisms

| Mechanism | Context | Timing | Use Case |
|-----------|---------|--------|----------|
| `Event::dispatch($event)` | Outside transaction | Immediate | Standalone events |
| `$this->dispatchEvent($event)` | Inside Action transaction | After commit | Most mutations |
| `SmartLogger::event($event)->save()` | Inside Action | After commit + log | Mutations needing audit trail |

### 6.4 config/event.php Structure

```php
return [
    \App\Partners\Company\Events\CompanyCreated::class => [
        \App\User\Listeners\ClearDashboardOnCompanyChange::class,
    ],
    // 33 mappings: every event key has ≥1 listener (FR-EVENT-006)
];
```

### 6.5 Current Observers (FR-EVENT-012 inventory)

| Observer | Model | Hook | Purpose |
|----------|-------|------|---------|
| `SettingObserver` | `Setting` | `created/updated/deleted` | Invalidates per-key/group/global cache |
| `UserObserver` | `User` | `deleting()` | Prevents superadmin deletion via RejectedException |
| `StudentReportObserver` | `StudentReport` | `saved()` | Snapshot when status = FINALIZED |

Each row passes all three gates in FR-EVENT-012; anything that outgrows single-model scope refactors to an Event + Listener.

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). These are recorded decisions,
not test rows, so `Layer`/`Status` stay `—`.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-EVENT-001 | Deferred dispatch inside transactions | P0 | — | — |
| DD-EVENT-002 | Centralized event registration | P0 | — | — |
| DD-EVENT-003 | SmartLogger as dispatch+log combinator | P0 | — | — |
| DD-EVENT-004 | Observers for qualifying single-model side effects, Events otherwise | P0 | — | — |

### 7.1 Dispatch & Registration

#### DD-EVENT-001 — Deferred Dispatch Inside Transactions

Immediate firing inside transactions once sent notifications for placements that never committed, and operators learned to distrust every email. The decision routes `$this->dispatchEvent()` through an in-memory queue that fires only after the database transaction commits successfully, so side effects never touch uncommitted data and rollback silently discards the queued events. Listeners observe a delay measured in milliseconds, which is acceptable because they handle non-critical side effects rather than the mutation itself.

#### DD-EVENT-002 — Centralized Event Registration

Scatter mappings across modules and the failure is audit blindness: nobody can answer what fires when a placement is approved without opening six directories, and a duplicated listener sends everything twice. Keeping all event-to-listener mappings in `config/event.php` gives the entire graph one auditable source of truth. The file grows linearly with the event count, and if it ever becomes unwieldy it splits into per-module files only through a spec amendment per FR-EVENT-005, never a silent fork.

#### DD-EVENT-003 — SmartLogger as Dispatch+Log Combinator

An SMK audit once found placement approvals with notification emails but no log rows, because the developer had added the dispatch and forgotten the audit call on a Friday deploy. `SmartLogger::event($event)->save()` makes that omission impossible by combining dispatch with audit logging in a single call, keeping log and dispatch atomic for the mutations that need both. The coupling is deliberate and narrow: mutations needing dispatch without logging simply use `$this->dispatchEvent()` directly.

#### DD-EVENT-004 — Observer-vs-Event Framework

At runtime the framework sorts every side effect at the gate: qualifying single-model synchronous work per FR-EVENT-012/017 runs in an Eloquent Observer inside the request's transaction, while everything else fans out through Events plus Listeners per FR-EVENT-014. That split is forced by physics — synchronous cache invalidation, exact-moment snapshots, and deletion prevention cannot wait for deferred events, and decoupled cross-module work cannot live inside an observer. The 3-gate rule keeps the choice mechanical. Observers accept tighter coupling plus `booted()` auto-registration that tests explicitly disable, all governed by the [eloquent-observers ADR](../adr/adr-eloquent-observers.md).

---

## 8. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Events without registered listeners | 0 | Registry audit (FR-EVENT-006) |
| Listener execution success rate | ≥ 99.5% | Queue monitoring |
| Failed listener retry recovery rate | ≥ 90% | `failed_jobs` review |
| Average listener execution time | < 5s (queued) | Queue monitoring |
| Cross-module coupling (direct Action imports for side effects) | 0 | `scan_module_boundaries.py` |
| Observer inventory drift (unlisted observers) | 0 | Observer search vs §6.5 |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes](SE5Q9-base-classes.md) | `BaseEvent` class, `dispatchEvent()` method on `BaseAction` |
| [logging-and-error-handling](89SRA-logging-and-error-handling.md) | `SmartLogger` for event dispatch logging |

### Build Guide

After implementing this spec, the system has event dispatch infrastructure with synchronous and
queued listeners, centralized registration, and `ShouldBroadcast` support. Events are used across
the system for cache invalidation, audit logging, and cross-module communication. The next step
is to build RBAC, which defines the authorization policies that middleware and Livewire
components enforce.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [rbac-and-authorization](T4B26-rbac-and-authorization.md) | Authorization events dispatched via `BaseEvent`, audit logged via `SmartLogger` |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume the queue worker runs wherever `ShouldQueue` listeners exist; on Tier-1 shared hosting the sync driver executes them inline per [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md) | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture design](D2FT3-architecture.md) — FR-ARC-027 cross-module side effects as Events
- [Base classes](SE5Q9-base-classes.md) — `BaseEvent` class and `dispatchEvent()` implementation
- [Logging & error handling](89SRA-logging-and-error-handling.md) — SmartLogger dispatch+log
- [Job & queue infrastructure](8FVZA-job-queue-infrastructure.md) — retry, backoff, timeout contracts
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — downstream consumer of this infra
- [Event pattern](../guides/arch/event-pattern.md) — conventions and alternatives
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — the 3-gate framework behind FR-EVENT-012–018
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — Start → Stabilize → Final event adoption
- [Spec-zero QLHDO](QLHDO-project-initialization.md) — global requirements this spec's rows serve
