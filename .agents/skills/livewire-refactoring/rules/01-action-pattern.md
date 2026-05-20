# Action Pattern (Refactoring)

## What It Enforces

Every business operation in a Livewire component must be extracted into an Action class. The Action extends `BaseAction`, has a single `execute()` method, validates input, delegates rule checks to Entities, wraps persistence in transactions, and emits side effects. The Livewire component receives the Action via method injection.

## Why It Matters

Inline business logic in Livewire components is the primary source of maintenance debt in Livewire applications. Components that call `Model::create()`, `DB::transaction()`, or `Validator::make()` directly cannot be tested independently, cannot be reused across callers, and accumulate complexity over time. Extracting to Actions solves all three problems.

## When It Applies

When refactoring any Livewire component, scan for these patterns and extract each to an Action:
- `Model::create()`, `Model::update()`, `Model::delete()` → Action
- `DB::transaction()` or `DB::raw()` → Action (wrapped in `$this->transaction()`)
- `Validator::make()` → Action (keep only `$this->validate()` in the component for UX)
- `Mail::send()` / `Notification::send()` → Action (as side effects inside the transaction)

The refactored component then injects the Action in the method signature: `public function save(CreateAction $action): void`. It calls `$action->execute(...)`, resets UI state, and flashes a message.

Exceptions: Read-only queries in `render()` for search/filter/pagination do not need to be Actions.
