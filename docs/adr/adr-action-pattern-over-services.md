# Action Pattern over Service Classes

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Action Pattern](../guides/arch/action-pattern.md) and [Service Pattern](../guides/arch/service-pattern.md) |

## Context and Problem Statement

Business operations need a structural home. In the Laravel ecosystem two patterns dominate:
Service classes (one class, many public methods such as `register()`, `approve()`, `reject()`)
and Action classes (one class per operation, single `execute()`). Services drift into god
classes — a 3-method service becomes 20 methods with mixed responsibilities, difficult to test
(one file covers all methods), hard to decorate, and prone to shared mutable state. Yet
treating all operations as identical actions is also wrong: the system performs three
fundamentally different operation types — mutations that need transactions and logging, reads
that need neither, and multi-step orchestrations that need process-level coordination.

**Decision Drivers:**

* Single responsibility per operation — one class, one reason to change
* Operation-type-appropriate contracts (transactions/logging only where writes occur)
* Independent testability with 1:1 file mapping
* Traceability from spec requirement to implementing class without indirection

## Considered Options

* **Multi-method Service classes** — `RegistrationService` with `register()`, `approve()`,
  `reject()`. *Pros:* familiar, few files. *Cons:* god-class growth, mixed
  responsibilities, decoration applies to whole class, shared mutable state.*
* **Single Action type for all operations** — one base for every `execute()`.
  *Pros:* uniform. *Cons:* reads pay transaction/logging ceremony; orchestrations lack a
  distinct composition point.*
* **Action Triad — Command / Read / Process (chosen)** — three distinct types under
  `app/{Module}/Actions/`, each with a single `execute()`. *Pros:* contract matches need;
  CQRS shape without infrastructure cost; Process solves coordination without leaking into
  Livewire. *Cons:* three patterns to learn.*

## Decision Outcome

**Chosen option: Action Triad** — three distinct action types, all under
`app/{Module}/Actions/`, all with a single `execute()` method.

**1. Command Actions (Mutations)** — extend `BaseAction` (`transaction()`, `log()`,
`HandlesActionErrors`). Every write wraps DB operations in `$this->transaction()`, calls
`$this->log()` on success, and dispatches events for significant state changes.
Named `{Verb}{Entity}Action`.

**2. Read Actions (Queries)** — extend `BaseReadAction` (`remember()`, `rememberForever()`,
`forget()`, `withErrorHandling()`). Must NOT mutate state, call `transaction()`, or call
`log()`. For complex aggregations, filtering, or cross-module assembly; simple
`Model::find()` stays inline in Livewire. Named `Read{Entity}Action`.

**3. Process Actions (Orchestration)** — extend `BaseAction` and compose other Actions via
constructor injection. Coordinate multi-step workflows, handle partial failures, emit one
module event for the completed process. Named `{Verb}{Entity}Process`.

**Decision Table:**

| Scenario | Pattern | Base Class | Transaction | Logging | Event |
|----------|---------|------------|-------------|---------|-------|
| Create/update/delete | Command | BaseAction | Required | Required | Recommended |
| State transition | Command | BaseAction | Required | Required | Required |
| Simple list query | Inline | — | No | No | No |
| Complex query | Read Action | BaseReadAction | No | No | No |
| Multi-step workflow | Process | BaseAction | Required | Required | Required |

### Positive Consequences

* Each type carries the contract it needs — no ceremony for reads, safety for writes
* Mirrors CQRS without extra infrastructure — same models and database, different class contracts
* Process Actions contain coordination that previously leaked into Livewire
* Every action is independently testable; 1:1 file mapping

### Negative Consequences

* Three patterns to learn instead of one — requires distinguishing Command, Read, and Process

## Links

* [Action Pattern](../guides/arch/action-pattern.md) — triad contracts and decision table
* [Service Pattern](../guides/arch/service-pattern.md) — predecessor it replaces
* [Architecture Overview](../architecture.md) — where the triad sits in the 4-layer model
