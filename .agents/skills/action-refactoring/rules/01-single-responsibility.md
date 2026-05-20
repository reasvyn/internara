# Single Responsibility

## What It Enforces

Every Action class represents exactly one business operation. The class is named `{Verb}{Noun}Action` (e.g., `CreateAcademicYearAction`) and exposes exactly one public method called `execute()`. There must never be multiple public methods on an Action class.

## Why It Matters

Single-responsibility Actions create a predictable, discoverable codebase. When every operation lives in its own class, you can find any business operation by name, test it in isolation, and change it without affecting other operations. An Action with multiple methods inevitably accumulates branching logic and conditional paths that make testing harder and refactoring riskier.

The `execute()` convention means every Action has the same entry point. Callers (Livewire components, Controllers, Artisan commands) always call `$action->execute(...)` regardless of what the Action does. This consistency reduces cognitive load.

## When It Applies

Always. This is the foundational rule of the Action pattern.

Signals that an Action needs splitting:
- More than 3 constructor-injected dependencies (it's doing too much)
- An `if` or `switch` on operation type inside execute
- Multiple distinct return paths with different meanings
- A method name that isn't `execute()` (the class name already describes the operation)

Return type conventions reinforce single responsibility: Create Actions return the created model. Update Actions return the updated model. Delete Actions return void. Complex operations return an array or DTO.

Exceptions: None. If you need multiple operations, create multiple Action classes.
