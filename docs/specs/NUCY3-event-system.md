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

**Actor:** Developer
**Preconditions:** New cross-module side effect identified.
**Flow:**
1. Create a `final` class extending `BaseEvent` with typed constructor-promoted properties
2. Implement `eventName()` returning a dot-notation string
3. Register the listener in `config/event.php`
4. Implement the listener, adding `ShouldQueue` when it is I/O-bound
**Postconditions:** Event fires after commit; listener executes.
**Governing guidance:** FR-EVENT-001–005, FR-EVENT-011; [event-pattern](../guides/arch/event-pattern.md).

#### UC-EVENT-002 — Developer Debugs Event Flow

**Actor:** Developer
**Preconditions:** Event not firing or listener not executing.
**Flow:**
1. Check `config/event.php` for registration
2. Verify the event class extends `BaseEvent`
3. Check the listener implements `ShouldQueue` and the queue worker is running
4. Inspect the `failed_jobs` table for failed listener executions
**Postconditions:** Root cause identified.
**Governing guidance:** Manual troubleshooting runbook — no code-testable consequence at this spec's level, hence `—`.

#### UC-EVENT-003 — Cross-Module Cache Invalidation

**Actor:** System (automated)
**Preconditions:** Entity created/updated/deleted in Module A; Module B caches data derived from Module A.
**Flow:**
1. Command Action in Module A dispatches the event (e.g., `CompanyCreated`)
2. Event fires after the transaction commits
3. Listener in Module B invalidates the relevant cache keys
**Postconditions:** Module B cache is fresh, with no direct coupling between modules.
**Governing guidance:** FR-EVENT-007, FR-EVENT-010; cache keys per `config/cache-keys.php`.

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

- `BaseEvent` provides `Dispatchable`, `InteractsWithSockets`, `SerializesModels`, plus the `eventName()` / `toPayload()` contract — see §6.1; implementation lives in [base-classes](SE5Q9-base-classes.md).
- **Verification:** `scan_class_contracts.py` event contract (layer `A`).

#### FR-EVENT-002 — Final events with promoted properties

- Payload is declared in the constructor signature — no mutable public fields set after construction, no untyped bags.
- **Verification:** contract scan + review (layer `A`).

#### FR-EVENT-003 — Dot-notation event names

- `company.created`, `placement.approved`, `assessment.finalized` — entity noun plus past-tense verb; SmartLogger and audit trails key off this name.
- **Edge case:** a name that does not parse as `{entity}.{past_tense_action}` is a naming violation, caught in review.
- **Verification:** unit test asserting `eventName()` per event (layer `U`, grouped with FR-EVENT-004).

#### FR-EVENT-004 — Payload extraction rules

- Models → `{property_name}_id`; objects with `toArray()` → array; scalars kept as-is; nulls skipped. Queue-safe by construction — no live Model graphs cross the queue boundary.
- **Verification:** unit test over a representative event's `toPayload()` (layer `U`).

### 4.2 Registration & Topology

#### FR-EVENT-005 — Centralized registration

- One file is the whole graph (currently 33 mappings in 154 lines); per-module registration files are forbidden.
- **Edge case:** at ~50+ events the file may split per-module — that split requires a spec amendment, not a silent fork.
- **Verification:** boot-time registration validation (NFR-EVENT-005) + `scan_doc_links.py` on the registry (layer `A`).

#### FR-EVENT-006 — No orphan events

- Dispatching an event nobody handles is either dead code or a dropped side effect; both are defects.
- **Verification:** registry audit — every event key has a non-empty listener list (layer `A`).

### 4.3 Dispatch Lifecycle

#### FR-EVENT-007 — Deferred dispatch in transactions

- Events queue in memory during the Action and fire only on successful commit; rollback silently discards them.
- **Verification:** feature test asserts the listener runs on commit and never runs on rollback (layer `F`).

#### FR-EVENT-008 — Immediate dispatch outside transactions

- Standalone events (no surrounding mutation) dispatch immediately; the deferred path would add latency for no safety gain.
- **Verification:** feature test (layer `F`).

#### FR-EVENT-009 — SmartLogger dispatch+log combinator

- Most mutations need both an audit entry AND event dispatch; combining them keeps log+dispatch atomic. Mutations needing dispatch without logging use `$this->dispatchEvent()` directly.
- **Verification:** feature test asserts both the log row and the listener run (layer `F`).

#### FR-EVENT-010 — Exactly-once dispatch per execution

- Double dispatch (e.g., dispatch in both Action and listener) is a defect; the unit test asserts a single listener invocation per Action execution.
- **Verification:** Action unit test with a fake listener counter (layer `F`).

### 4.4 Listeners & Queuing

#### FR-EVENT-011 — Queue I/O-bound listeners

- Mail, external APIs, media work, and anything touching the network queue; pure in-process work (cache forget, log write) stays synchronous. Queue lifecycle (retries, backoff, timeouts) is contracted in [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).
- **Edge case:** a listener that starts synchronous and grows I/O migrates to `ShouldQueue` at the second I/O call — per the Stabilize trigger in the [gradual-migration ADR](../adr/adr-gradual-migration.md).
- **Verification:** contract scan for `ShouldQueue` on flagged listeners + review (layer `A`).

### 4.5 Observer-vs-Event Gate

#### FR-EVENT-012 — The 3-gate rule

- Per the [eloquent-observers ADR](../adr/adr-eloquent-observers.md): (1) observer and model in the same module, (2) must complete before the response, (3) reacts only to this model's lifecycle. Fail any gate → Event + Listener.
- **Verification:** review gate against the three criteria; observer inventory in §6.5 (layer `A`).

#### FR-EVENT-013 — Same-transaction, non-queueable observers

- Cache invalidation that must complete before the next request can see stale data, snapshots at the exact status-change moment, and deletion guards that prevent the delete before it proceeds — all impossible with deferred events.
- **Edge case:** auto-registration via `booted()` means tests MUST explicitly disable observers when side effects are unwanted.
- **Verification:** feature tests for each current observer (guard throws, snapshot captured, cache fresh) (layer `A` + spot `F`).

#### FR-EVENT-014 — Events otherwise

- Notifications, cross-module cache invalidation, and activity logging are always Events + Listeners — never observers.
- **Verification:** `scan_violations.py` + review (layer `A`).

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

- **Verification:** response-time assertion with I/O-bound listeners attached; dispatch path never awaits listener completion.

#### NFR-EVENT-002 — Same-process after-commit firing

- The dispatch itself is in-memory, not a queue job; only the listener may queue.
- **Verification:** feature test asserts listener effects are visible without running a worker for the dispatch.

#### NFR-EVENT-003 — Listener time budget

- **Verification:** queue worker timeout config + `failed_jobs` monitoring; governed in detail by [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md).

#### NFR-EVENT-004 — Retry with backoff

- **Verification:** retry configuration review + recovery-rate metric in §8.

#### NFR-EVENT-005 — Boot-time registry validation

- Every event key resolves to an existing `BaseEvent` subclass; every listener resolves to an existing class.
- **Verification:** boot validation command/scan (layer `A`).

### 5.2 Observer Bounds

#### NFR-EVENT-006 — Fast synchronous observers

- Per the [eloquent-observers ADR](../adr/adr-eloquent-observers.md): long-running logic would block the request — observers stay fast, and anything slow becomes an Event + queued Listener.
- **Verification:** review gate on observer bodies (layer `A`).

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

**Decision:** Events dispatched via `$this->dispatchEvent()` are queued in memory and fire only
after the database transaction commits successfully.
**Rationale:** Prevents events from triggering side effects on uncommitted data. If the
transaction rolls back, events are silently discarded.
**Trade-off:** Listeners see a slight delay (milliseconds) between the mutation and the event.
This is acceptable because listeners handle non-critical side effects.

#### DD-EVENT-002 — Centralized Event Registration

**Decision:** All event-to-listener mappings live in `config/event.php`, not in individual
modules.
**Rationale:** Provides a single source of truth for the entire event graph. Makes it easy to
audit which events exist and what they trigger.
**Trade-off:** The config file grows linearly with events. If it becomes unwieldy, it splits into
per-module config files via a spec amendment — never a silent fork (FR-EVENT-005).

#### DD-EVENT-003 — SmartLogger as Dispatch+Log Combinator

**Decision:** `SmartLogger::event($event)->save()` combines event dispatch with audit logging
in a single call.
**Rationale:** Most mutations need both an audit log entry AND event dispatch. Combining them
reduces boilerplate and ensures log+dispatch are atomic.
**Trade-off:** Tight coupling between logging and event dispatch. Mutations that need dispatch
without logging must use `$this->dispatchEvent()` directly.

#### DD-EVENT-004 — Observer-vs-Event Framework

**Decision:** Eloquent Observers serve qualifying single-model synchronous side effects
(FR-EVENT-012/017); everything else uses Events + Listeners (FR-EVENT-014).
**Rationale:** Synchronous cache invalidation, exact-moment snapshots, and deletion prevention
are impossible with deferred events; decoupled cross-module work is impossible with observers.
The 3-gate rule makes the choice mechanical.
**Trade-off:** Tighter coupling where observers are used, plus `booted()` auto-registration that
tests must explicitly disable. Governed by the [eloquent-observers ADR](../adr/adr-eloquent-observers.md).

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
