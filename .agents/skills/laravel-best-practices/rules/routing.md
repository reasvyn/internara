# Routing & Controllers

## What It Enforces

Routes are split by module into `routes/web/{domain}.php` files, required from `routes/web.php`. Livewire components use `Route::livewire()` for direct binding. Controllers, when used, delegate business logic to Actions. Routes use named dot notation and implicit model binding.

## Why It Matters

Domain-split route files keep routing organized and co-located with the module they serve. `Route::livewire()` eliminates the Controller layer for Livewire-backed pages. Named routes with dot notation provide a hierarchical, predictable naming scheme. Implicit model binding eliminates manual `findOrFail()` calls.

## When It Applies

Every route definition should:
- Live in the appropriate `routes/web/{domain}.php` file
- Use `Route::livewire()` for Livewire component pages
- Use named routes with `snake_case.dotted` convention
- Use implicit route model binding for model parameters
- Apply authorization middleware at the route group level

Controller pattern (when used):
- Keep Controllers thin — extract business logic to Actions
- Type-hint FormRequest classes (not `Request`) for validation
- Delegate to Actions via method injection

Route organization by role group: guest, auth, admin (prefix + role middleware), student, mentor.

Exceptions: API routes (if added later) would follow their own versioned structure.
