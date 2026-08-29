# Data & Cache Conventions — Models, Queries, and Caching

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

The persistence layer follows strict conventions so mutations are safe, queries are reusable, and
cache is consistent. These rules govern mass assignment, query scopes, relationships, and caching —
the places where default Laravel habits quietly produce security and consistency defects.

---

## Intent

Models use the `#[Fillable]` attribute (never `$fillable`/`$guarded`, never raw `$request->all()`),
queries are reusable via Model scopes or Read Actions, relationships are eager-loaded, and cache keys
are registered centrally with event-driven invalidation.

## Rationale — What Fails Without It

- **Raw mass-assignment defaults** — without an explicit allow-list, `Model::create($request->all())`
  lets a crafted request set fields it must not (role, status, foreign keys). `$fillable`/`$guarded`
  arrays are allowed by Laravel but are not the Internara contract — the `#[Fillable]` attribute
  keeps the allow-list declarative and scan-verifiable (D4).
- **Ad-hoc queries scattered in Livewire** duplicate the same WHERE logic in a dozen components;
  when the rule changes, half the copies are missed. A named scope on the Model (or a Read Action)
  centralizes the query once.
- **No eager loading** produces N+1 queries — one query per related row per render. On a list page
  with 50 rows that is 50+ uncached queries per request.
- **Inline cache keys** cannot be flushed consistently. If the key string is typed in one caller as
  `'intern_counts'` and another as `'intern_count'`, the two caches diverge and invalidation flushes
  the wrong one (C4).
- **Naive time-based invalidation** leaves stale data on screen after the underlying record changes.
  Event-driven invalidation ties the cache lifecycle to the mutation that alters it.

## How to Apply

### Mass assignment — `#[Fillable]` always

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[
    Fillable([
        'name',
        'email',
        'school_id',
        // …the explicit allow-list…
    ]),
]
final class Intern extends BaseModel
{
    // …
}
```

Rules:
- **Never `$fillable = [...]` as a plain property** — use the `#[Fillable([...])]` attribute (D4).
- **Never `$guarded`** — an allow-list is safer than a denylist because new columns are locked by
  default.
- **Never `$request->all()` for create/update** — pass a validated DTO's named array (`->toArray()`,
  `->only([...])`), not the raw request (D5).

### Query scopes — on the Model, or a Read Action

- Simple reusable filters → **Model scopes**, so any caller reuses the same WHERE.
- Complex queries (joins, aggregates, multi-condition dashboards) → **Read Action** that returns a
  typed, testable result.

```php
final class Intern extends BaseModel
{
    public function scopeActive($query): void
    {
        $query->where('status', 'active');
    }
}
```

### Relationships — define on the Model, eager load

- Define relationships on the Model, not in component queries.
- Use `->with([...])` (or `withCount`, `load`) wherever a collection or dashboard renders related
  data — never lazy-load inside a Blade loop.

```php
public function placements(): HasMany
{
    return $this->hasMany(Placement::class);
}

// in the Read Action / render query:
Intern::query()->with(['placements.company'])->active()->get();
```

### Caching — registered keys, event-driven invalidation

- **Every cache key lives in `config/cache-keys.php`** — never inline strings (C4).
- Invalidate on mutation via a **listener on the record's changed event**, so the invalidation travels
  with the write and can't be forgotten by another caller.

```php
// config/cache-keys.php
return [
    'interns' => [
        'dashboard_counts' => 'interns.dashboard_counts',
    ],
];

// In the Change listener
public function handle(InternUpdated $event): void
{
    Cache::forget(config('cache-keys.interns.dashboard_counts'));
}
```

## Anti-Patterns & Pitfalls

- `Intern::create($request->all())` in any layer — mass-assignment vulnerability + D5 violation.
- A component building the same `->where('status', ...)` filter in six places instead of one scope.
- Lazy relationship access (`$intern->placements`) inside a `@foreach` — N+1.
- `Cache::remember('interns.'.auth()->id(), ...)` inline — unregistered key that can't be flushed.
- `Cache::rememberForever()` for data that changes — stale forever, no invalidation hook.

## Verification

- Run `python3 tools/scan_conventions.py` — flags `$fillable`, missing `#[Fillable]`, debug calls;
  and `python3 tools/scan_violations.py` for C4/D4/D5 checks.
- Grep for raw `request()->all(` / `$request->all(` in create/update paths.
- Review list/dashboard queries for `->with()`; `config/cache-keys.php` contains every key used.
- `docs/guides/arch/model-pattern.md` §Mass Assignment and `docs/guides/arch/cache-pattern.md`
  §Registration are the authoritative sources.