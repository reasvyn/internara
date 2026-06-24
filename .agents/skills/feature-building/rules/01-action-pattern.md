# Action Pattern

## What It Enforces

All business logic is encapsulated in Action classes with a single `execute()` method. Actions live
in `app/{Module}/Actions/{Verb}{Noun}Action.php` and extend `BaseAction`. They are the sole
orchestrators of validation, persistence, and side effects. Actions enforce the 4-layer data flow:
receive a DTO (never raw array), delegate business rules to Entities, persist via Models, and return
ActionResponse (never Model directly).

## Why It Matters

The Action pattern creates a predictable, testable boundary around every business operation. Callers
(Livewire components, Controllers, Artisan commands) always interact the same way: inject the Action
via dependency injection, call `execute()` with a DTO, and handle the `ActionResponse`. This
eliminates scattered business logic, prevents circular dependencies, and makes every operation
independently verifiable.

## When It Applies

Every business operation that involves validation, persistence, or side effects must be an Action.
This includes CRUD operations, state transitions, imports/exports, batch operations, and any custom
business process.

The `execute()` method follows these conventions:

- Create: `execute(Data $data): Model|ActionResponse`
- Update: `execute(Model $model, Data|array $data): Model|ActionResponse`
- Delete: `execute(Model $model): void|ActionResponse`
- Toggle/state change: `execute(Model $model, mixed ...$params): Model|ActionResponse`
- Complex operations: `execute(Data $data): ActionResponse`

**Key rules:**
- Command/Process Actions SHOULD accept `BaseData` DTO for 3+ params — never raw `array`
- Command/Process Actions SHOULD return `ActionResponse` for structured feedback
- Actions MUST delegate business rules to Entity methods — throw `RejectedException` on violation
- Read Actions may accept explicit typed params; use DTO for complex filters

Exceptions: Read-only queries for display purposes (the query in a Livewire component's `render()`
method) do not need to be Actions.
