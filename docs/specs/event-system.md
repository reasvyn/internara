# Event System — Decoupled Communication Infrastructure

> **Last updated:** 2026-07-23 **Changes:** feat — initial event system specification

## Description

Defines the event-driven communication infrastructure that decouples modules within Internara.
Events fire after transaction commit, listeners react asynchronously when I/O-bound, and the
entire map is centralized in `config/event.php`. This spec covers the `BaseEvent` contract,
dispatch lifecycle, payload serialization, naming conventions, and the complete event-to-listener
registry.

---

## 1. Problem Statements

### PS-1 — Tightly Coupled Module Communication

Without events, Module A must directly import Module B's Actions to trigger side effects (e.g.,
cache invalidation, notifications). This creates circular dependencies and makes modules
impossible to test or deploy independently.

### PS-2 — Invisible Side Effects

When side effects are inline in Actions, developers cannot see the full impact of a mutation
without reading every downstream Action. This leads to missed invalidations and inconsistent state.

### PS-3 — Unrecoverable Failures

Synchronous side effects inside transactions mean a failed notification can roll back the entire
business operation. I/O-bound work (mail, external APIs) should not block or endanger the
primary mutation.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | All cross-module side effects communicate via events |
| G2  | Events fire only after the originating transaction commits |
| G3  | Event-to-listener mapping is centralized in a single config file |
| G4  | Every event has at least one registered listener |
| G5  | I/O-bound listeners execute asynchronously via queue |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Event sourcing or event replay |
| NG2  | Real-time WebSocket event streaming |
| NG3  | Event versioning or schema evolution |
| NG4  | Cross-instance event distribution (multi-server pub/sub) |
| NG5  | Event ordering guarantees beyond transaction boundaries |
| NG6  | Preemptive events without registered listeners |

---

## 3. User Stories / Use Cases

### UC-1 — Developer Registers New Event

**Actor:** Developer
**Preconditions:** New cross-module side effect identified
**Flow:**
1. Create `final` class extending `BaseEvent` with typed constructor properties
2. Implement `eventName()` returning dot-notation string
3. Register listener in `config/event.php`
4. Implement listener class implementing `ShouldQueue` if I/O-bound
**Postconditions:** Event fires after commit, listener executes

### UC-2 — Developer Debugs Event Flow

**Actor:** Developer
**Preconditions:** Event not firing or listener not executing
**Flow:**
1. Check `config/event.php` for registration
2. Verify event class extends `BaseEvent`
3. Check listener implements `ShouldQueue` and queue is running
4. Inspect `failed_jobs` table for failed listener executions
**Postconditions:** Root cause identified

### UC-3 — Cross-Module Cache Invalidation

**Actor:** System (automated)
**Preconditions:** Entity created/updated/deleted in Module A, Module B caches data from Module A
**Flow:**
1. Command Action in Module A dispatches event (e.g., `CompanyCreated`)
2. Event fires after transaction commits
3. Listener in Module B (`ClearDashboardOnCompanyChange`) invalidates relevant cache keys
**Postconditions:** Module B cache is fresh, no direct coupling between modules

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-EV1 | All events MUST extend `BaseEvent` abstract class |
| FR-EV2 | Event classes MUST be `final` with `public` typed constructor promotion properties |
| FR-EV3 | `eventName()` MUST return a dot-notation string matching `{entity}.{past_tense_action}` |
| FR-EV4 | `toPayload()` MUST convert Model properties to `{name}_id` strings |
| FR-EV5 | All event-to-listener mappings MUST be registered in `config/event.php` |
| FR-EV6 | No event MAY exist without at least one registered listener |
| FR-EV7 | Events dispatched inside transactions MUST use `$this->dispatchEvent()` (deferred) |
| FR-EV8 | Events dispatched outside transactions MAY use `Event::dispatch()` (immediate) |
| FR-EV9 | SmartLogger integration: `->event($baseEvent)->save()` auto-dispatches + logs |
| FR-EV10 | I/O-bound listeners MUST implement `ShouldQueue` |
| FR-EV11 | Payload extraction MUST preserve scalar values, convert objects via `toArray()`, skip nulls |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-EV1 | Event dispatch MUST NOT block the HTTP response |
| NFR-EV2 | Deferred events MUST fire within the same process after commit (not via queue) |
| NFR-EV3 | Queued listeners MUST complete within 60 seconds per attempt |
| NFR-EV4 | Failed queued listeners MUST retry up to 3 times with exponential backoff |
| NFR-EV5 | Event registration in `config/event.php` MUST be validatable at boot time |

---

## 6. API / Data Contracts

### BaseEvent Contract

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

### Event Class Example

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

### Three Dispatch Mechanisms

| Mechanism | Context | Timing | Use Case |
|-----------|---------|--------|----------|
| `Event::dispatch($event)` | Outside transaction | Immediate | Standalone events |
| `$this->dispatchEvent($event)` | Inside Action transaction | After commit | Most mutations |
| `SmartLogger::event($event)->save()` | Inside Action | After commit + log | Mutations needing audit trail |

### config/event.php Structure

```php
return [
    \App\Partners\Company\Events\CompanyCreated::class => [
        \App\User\Listeners\ClearDashboardOnCompanyChange::class,
    ],
    // 49 events, 20 listeners
];
```

---

## 7. Design Decisions

### DD-1 — Deferred Dispatch Inside Transactions

**Decision:** Events dispatched via `$this->dispatchEvent()` are queued in memory and fire only
after the database transaction commits successfully.

**Rationale:** Prevents events from triggering side effects on uncommitted data. If the
transaction rolls back, events are silently discarded.

**Trade-off:** Listeners see a slight delay (milliseconds) between the mutation and the event.
This is acceptable because listeners handle non-critical side effects.

### DD-2 — Centralized Event Registration

**Decision:** All event-to-listener mappings live in `config/event.php`, not in individual
modules.

**Rationale:** Provides a single source of truth for the entire event graph. Makes it easy to
audit which events exist and what they trigger.

**Trade-off:** The config file grows linearly with events. At 49 events this is manageable
(146 lines). If it becomes unwieldy, could split into per-module config files.

### DD-3 — SmartLogger as Dispatch+Log Combinator

**Decision:** `SmartLogger::event($event)->save()` combines event dispatch with audit logging
in a single call.

**Rationale:** Most mutations need both an audit log entry AND event dispatch. Combining them
reduces boilerplate and ensures log+dispatch are atomic.

**Trade-off:** Tight coupling between logging and event dispatch. Mutations that need dispatch
without logging must use `$this->dispatchEvent()` directly.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Events without registered listeners | 0 |
| Listener execution success rate | ≥ 99.5% |
| Failed listener retry recovery rate | ≥ 90% |
| Average listener execution time | < 5s (queued) |
| Cross-module coupling (direct Action imports for side effects) | 0 |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [core-foundation.md](core-foundation.md) | `BaseEvent` class, `dispatchEvent()` method on `BaseAction` |
| [logging-and-error-handling.md](logging-and-error-handling.md) | `SmartLogger` for event dispatch logging |

### Build Guide
After implementing this spec, the system has event dispatch infrastructure with synchronous and queued listeners, lazy event discovery, and `ShouldBroadcast` support. Events are used across the system for cache invalidation, audit logging, and cross-module communication. The next step is to build RBAC, which defines the authorization policies that middleware and Livewire components enforce.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [rbac-and-authorization.md](rbac-and-authorization.md) | Authorization events dispatched via `BaseEvent`, audit logged via `SmartLogger` |

## Quick References

- `app/Core/Events/BaseEvent.php` — Base event class
- `config/event.php` — Event-to-listener registry (49 events, 20 listeners)
- `app/Core/Services/SmartLogger.php` — SmartLogger with event integration
- `docs/architecture/event-pattern.md` — Architecture pattern documentation
- `docs/architecture/logging-pattern.md` — SmartLogger architecture
- `docs/specs/core-foundation.md` — Base classes and contracts
- `docs/specs/logging-and-error-handling.md` — SmartLogger full specification
