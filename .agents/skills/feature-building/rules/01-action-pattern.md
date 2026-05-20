# Action Pattern

## What It Enforces

All business logic is encapsulated in Action classes with a single `execute()` method. Actions live in `app/Domain/{Domain}/Actions/{Verb}{Noun}Action.php` and extend `BaseAction`. They are the sole orchestrators of validation, persistence, and side effects.

## Why It Matters

The Action pattern creates a predictable, testable boundary around every business operation. Callers (Livewire components, Controllers, Artisan commands) always interact the same way: inject the Action via dependency injection, call `execute()` with the required parameters, and handle the result. This eliminates scattered business logic and makes every operation independently verifiable.

## When It Applies

Every business operation that involves validation, persistence, or side effects must be an Action. This includes CRUD operations, state transitions, imports/exports, batch operations, and any custom business process.

The `execute()` method follows these conventions:
- Create: `execute(array $data): Model`
- Update: `execute(Model $model, array $data): Model`
- Delete: `execute(Model $model): void`
- Toggle/state change: `execute(Model $model): Model`
- Complex operations: `execute(array $data): array`

Exceptions: Read-only queries for display purposes (the query in a Livewire component's `render()` method) do not need to be Actions.
