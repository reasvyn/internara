# ADR-003: Action Pattern over Service Classes

> **Last updated:** 2026-06-18
> **Changes:** sync — initial metadata sync with new format


## Description

Business operations follow the Action Triad — Command, Read, and Process actions — each with a single execute() method, replacing traditional multi-method Service classes.

## Context

Business operations need a structural home. Two patterns dominate the Laravel ecosystem:

1. **Service classes** — a single class with multiple public methods representing related operations (e.g., `RegistrationService` with `register()`, `approve()`, `reject()`).
2. **Action classes** — one class per operation, each with a single `execute()` method.

Service classes grow into god classes over time — a 3-method service becomes 20 methods with mixed responsibilities. They are difficult to test (one file covers all methods), hard to decorate (cross-cutting concerns apply to the whole class), and encourage shared mutable state.

However, treating all operations as identical actions is also wrong. The system performs three fundamentally different operation types:

- **Mutations** — writes that create, update, or delete state. Need transactions and logging.
- **Reads** — queries that retrieve data without changing state. Need neither transactions nor logging.
- **Orchestrations** — multi-step workflows coordinating multiple mutations and reads. Need transaction management at the process level.

## Decision

Business operations use the **Action Triad**: three distinct action types, all under `app/{Module}/Actions/`, all with a single `execute()` method.

### 1. Command Actions (Mutations)

Extend `BaseAction` which provides `transaction()`, `log()`, and `HandlesActionErrors`. Every write operation wraps all database operations in `$this->transaction()`, calls `$this->log()` after success, and dispatches events for significant state changes. Named `{Verb}{Entity}Action`.

### 2. Read Actions (Queries)

*[2026-06-18: Pattern evolved — Read Actions now extend `BaseReadAction` and follow `Read{Entity}Action` naming. See `docs/architecture/action-pattern.md` for the current contract.]*

Extend `BaseReadAction` which provides `remember()`, `rememberForever()`, `forget()`, caching utilities, and `withErrorHandling()`. They must NOT mutate state, call `transaction()`, or call `log()`. Used for complex aggregations, filtering, or cross-module data assembly. Simple `Model::find()` stays inline in Livewire. Named `Read{Entity}Action`.

### 3. Process Actions (Orchestration)

Extend `BaseAction` and compose other Actions via constructor injection. They coordinate multi-step workflows, handle partial failure scenarios, and emit a single module event representing the completed process. Named `{Verb}{Entity}Process`.

### Decision Table

| Scenario | Pattern | Base Class | Transaction | Logging | Event |
|---|---|---|---|---|---|
| Create/update/delete | Command | BaseAction | Required | Required | Recommended |
| State transition | Command | BaseAction | Required | Required | Required |
| Simple list query | Inline | None | No | No | No |
| Complex query | Read Action | BaseReadAction | No | No | No |
| Multi-step workflow | Process | BaseAction | Required | Required | Required |

## Consequences

- **Positive**: Each action type has a contract matching its actual needs — mutations have transactions and logging, reads use a lightweight base with caching utilities.
- **Positive**: The triad mirrors CQRS without infrastructure cost. Same models, same database — different class contracts.
- **Positive**: Process Actions solve the coordination problem that previously forced orchestration logic into Livewire or single Actions.
- **Positive**: Every action is independently testable. Test files map 1:1 with action classes.
- **Negative**: Three patterns to learn instead of one. Developers must distinguish Command, Read, and Process.

## References

- `app/Core/Actions/BaseAction.php` — Base class for Command and Process Actions
- `app/Core/Actions/BaseReadAction.php` — Base class for Read Actions
- `app/Core/Actions/Concerns/HandlesActionErrors.php` — Error handling trait
- `docs/architecture.md` — Action Triad section
- `docs/conventions.md` — Actions section
