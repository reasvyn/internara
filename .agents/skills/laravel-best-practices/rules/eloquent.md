# Eloquent

## What It Enforces

Relationships use typed return hints. Local scopes encapsulate reusable query logic. Global scopes are reserved for multi-tenancy or soft deletes only. Attribute casts use the `casts()` method with proper types. Queries use `whereBelongsTo()` over manual FK conditions.

## Why It Matters

Typed relationship return hints enable IDE autocompletion and static analysis. Local scopes make queries readable and reusable — `AcademicYear::active()->get()` is clearer than `AcademicYear::where('is_active', true)->get()`. Global scopes are dangerous because they silently apply to every query; reserve them for truly universal filters.

`whereBelongsTo()` prevents hardcoded FK column names and keeps relationship queries consistent. `withCount()` prevents N+1 by using a subquery instead of loading collections just to count them. The `#[Fillable]` attribute modernizes mass assignment protection.

## When It Applies

Every Eloquent query should consider:
- Use `with()` to eager load and prevent N+1
- Use `whereBelongsTo($model)` over `where('user_id', $model->id)`
- Use `withCount()` over loading relations just to count
- Use local scopes over inline `where` clauses
- Use `casts()` method (not property) with proper date/decimal/array types
- Use `#[Fillable]` attribute (not `$fillable` property)
- Prevent lazy loading in development via `Model::preventLazyLoading()`
- Do not hardcode table names — use `(new Model)->getTable()` or work through the Model

Exceptions: Migrations are frozen snapshots — hardcoded table names are acceptable there. Global scopes are acceptable for multi-tenancy (where ALL queries must be scoped) and soft deletes.
