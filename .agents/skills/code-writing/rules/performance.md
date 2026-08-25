# Performance Rules — N+1, Queries, Caching

> **Last updated:** 2026-08-17 **Changes:** rewritten comprehensively — per-rule intent, application, and detection

Internara is a self-hosted single-tenant app serving schools; performance issues typically surface
as N+1 queries in list/dashboard views and unbounded cache keys. These rules keep hot paths lean
without over-engineering.

---

## N+1 Prevention

**Intent:** Never load a relationship lazily inside a Blade loop or a repeated per-row callback.

```php
// WRONG: N+1 query in Blade loop
@foreach ($users as $user)
    {{ $user->department->name }}
@endforeach

// CORRECT: eager loading
$users = User::with('department')->get();
```

**Why it matters:** Each loop iteration that touches `$user->department` issues one extra query (N+1
total). For a 100-row table that is 101 queries against the DB on every render — the single most
common performance defect in this kind of codebase.

**How to apply:**
- Always `->with(...)` relationships that Blade/Livewire render (or `->withCount()` when only a
  count is needed).
- Use `->when($condition, fn ($q) => $q->with(...))` for conditional eager loading so filters don't
  force a blanket eager load.
- In Livewire table components, eager-load inside the Read Action / query method, not the view.

**Detection:** `python3 scripts/scan_violations.py` · review list views for relationship access
without a corresponding `with()`.

---

## Query Optimization

| Instead of... | Use... | Why |
|---------------|--------|-----|
| `count() > 0` | `exists()` | Stops at first match; no full count scan |
| `get()->pluck()` | `pluck()` | Single query, less memory |
| Processing 1000+ rows | `chunk()` or `lazy()` | Streaming, memory efficient |
| Filtering in PHP | Filter at DB level | Faster, less memory |

**Intent:** Push work into the query engine and stop early wherever the data is only needed as a
truth test or a single column.

**Why it matters:** `count() > 0` fetches a full count where a LIMIT 1 truth test suffices;
`get()->pluck()` hydrates full models then discards every column but one; PHP-side filtering
transfers the whole dataset into memory. Each costs measurable time and RAM at school-scale row
counts and 3× in the test suite.

**How to apply:** Use `exists()`, `pluck()`, `chunk()`/`lazy()` per the table; translate filter
conditions into `->where()`/`->when()` clauses instead of `filter()` in PHP.

**Detection:** Manual code review plus `composer run analyse`/PHPStan-level array flow inspection.

---

## Caching

**Intent:** Reads reuse cached values via registered keys; mutations invalidate predictably.

- Every cache key MUST be registered in `config/cache-keys.php` (C4) — never inline literals.
- Use `Cache::remember()` for reads (with the key and TTL from `config/cache-keys.php`).
- Use event-driven invalidation for mutations (flush the affected registered keys when the source
  data changes).
- Never use inline cache key strings.

**Why it matters:** Caching is only as reliable as invalidation. An inline key written in two places
drifts and never flushes; an unregisterred key defies the audit surface. Event-driven invalidation
keeps caches consistent with the command flow (a mutation Action's event flushes dependent keys).

**How to apply:** Register in `config/cache-keys.php` (`docs/guides/arch/cache-pattern.md`
§Registration), read via `Cache::remember(config('cache-keys.{name}'), $ttl, fn () => ...)`; on
mutation, dispatch an event whose listener forgets the registered key.

**Anti-patterns to avoid:** `Cache::remember('user_'.$id, ...)` inline; storing whole collections
with no TTL; flushing every key on any write ("cache-miss cache").

**Detection:** `python3 scripts/scan_violations.py` · `scan_conventions.py` (inline cache key regex).