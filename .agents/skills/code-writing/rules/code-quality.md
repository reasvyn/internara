# Code Quality — Class Contract & Style Checklist

Checklist to ensure PHP code follows all project conventions and industry best practices.

## File-Level Checks

- [ ] `declare(strict_types=1)` present (except migrations/config)
- [ ] Namespace matches directory location
- [ ] Use statements sorted alphabetically
- [ ] No unused imports
- [ ] No debug calls (`dd`, `dump`, `ray`, `var_dump`, `print_r`, `die`)

## Class-Level Checks

- [ ] Extends correct base class
- [ ] Constructor uses `protected readonly` promotion for injected dependencies
- [ ] No empty zero-parameter constructors (unless private factory method)
- [ ] Single public `execute()` method (Actions only)

## Method-Level Checks

- [ ] Explicit return types on every method
- [ ] Type hints on all parameters
- [ ] Curly braces on all control structures (even single-line)
- [ ] `match()` over `switch()` when returning from expression
- [ ] Null-safe operator `?->` and null coalescing `??` over explicit null checks
- [ ] Trailing commas on multiline arrays, function calls, constructor params
- [ ] `str_contains()` / `str_starts_with()` / `str_ends_with()` over `strpos() === 0`

## Security Checks

- [ ] No `{!! $var !!}` for unsanitized user content
- [ ] No `DB::raw()` without parameterized binding
- [ ] No `$request->all()` — use `->only()` or `->toArray()`
- [ ] `#[Fillable]` attribute on all Models
- [ ] `@csrf` or Livewire on all forms

## Performance Checks

- [ ] No N+1 queries — `->with()` for eager loading
- [ ] `exists()` over `count() > 0`
- [ ] `pluck()` over `get()->pluck()`
- [ ] `chunk()` / `lazy()` for 1000+ row queries
- [ ] Cache keys registered in `config/cache-keys.php`

## Architecture Checks

- [ ] No `Model::create/update/delete` in Livewire
- [ ] No `app()->make()` / `resolve()` — constructor injection
- [ ] Business rules via Entity methods, not inline in Actions
- [ ] `RejectedException` for business rules, not `RuntimeException`
- [ ] Events via `$this->dispatchEvent()`, not `$event::dispatch()`
- [ ] DTO for 3+ params, ActionResponse for structured returns

## Destructive Patterns

- [red X] `dd()` / `dump()` / `ray()` left in committed code
- [red X] `$fillable` / `$guarded` property instead of `#[Fillable]` attribute
- [red X] `Model::create()` called directly from Livewire component
- [red X] `app()->make()` or `resolve()` for dependency resolution
- [red X] `RuntimeException` thrown for business rule violations
- [red X] Hardcoded English strings (not using `__()`)
- [red X] Missing `declare(strict_types=1)`
- [red X] Inline cache key strings (not in `config/cache-keys.php`)
