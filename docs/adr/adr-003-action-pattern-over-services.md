# ADR-003: Action Pattern over Service Classes

## Status
Accepted

## Context
Business operations (e.g., "register a student", "approve a registration", "submit a logbook
entry") need a home. Two competing patterns exist in the Laravel ecosystem:

1. **Service classes**: A class with multiple public methods representing related operations
   (e.g., `RegistrationService` with `register()`, `approve()`, `reject()`, `withdraw()`).
2. **Action classes**: One class per operation, each with a single public `execute()` method
   (e.g., `RegisterStudentAction`, `ApproveRegistrationAction`, `RejectRegistrationAction`).

Service classes tend to grow over time — what starts as a 3-method service becomes a 20-method
god class with mixed responsibilities. They are difficult to test (one test file must cover all
methods), difficult to decorate (cross-cutting concerns like logging apply to the whole class),
and encourage shared mutable state between related operations.

Action classes keep each operation isolated. A 150-Action codebase (as this one has) is easier
to navigate, test, and refactor than a 20-Service-class codebase with 7.5 methods each.

## Decision
Every business operation is a single-action class with one public `execute()` method. Actions
extend `BaseAction` which provides:
- `transaction()` — wraps mutations in `DB::transaction()`
- `log()` — dual-channel audit logging via `SmartLogger`
- `HandlesActionErrors` trait — consistent try-catch-log-rethrow

Action naming follows `{Verb}{Entity}Action` convention: `RegisterStudentAction`,
`ApproveRegistrationAction`, `SubmitLogbookAction`.

## Consequences
- **Positive**: Each action is independently testable. Test files map 1:1 with action classes.
- **Positive**: Cross-cutting concerns (transactions, logging, error handling) are centralized
  in `BaseAction` — actions only write business logic.
- **Positive**: Actions are composable — one action can call another without the overhead of a
  service class.
- **Positive**: Git history per action is clean — a change to "approve registration" touches
  exactly one file.
- **Negative**: More files than service classes (150 Actions vs. ~20 Services). Developers must
  learn the pattern.
- **Negative**: Actions that share setup logic must extract it to a shared method, trait, or
  another action — no inherited shared state.

## References
- `app/Domain/Core/Actions/BaseAction.php`
- `docs/conventions.md` — Section 5 (Actions)
- ~~`tests/Arch/ActionLayerArchTest.php`~~ (removed)
- Total: 150 Action classes across 24 domains
