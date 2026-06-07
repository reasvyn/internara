# Caching

## What It Enforces

`Cache::remember()` replaces manual get/put patterns. `Cache::flexible()` provides
stale-while-revalidate for high-traffic keys. `Cache::add()` handles atomic conditional writes.
Cache tags enable grouped invalidation. Read operations are cached; write operations invalidate
affected caches.

## Why It Matters

Manual get/put patterns have a race condition: two requests can both miss the cache and
simultaneously recompute the value. `Cache::remember()` atomically checks, computes, and stores.
`Cache::flexible()` goes further by serving stale data while a background process refreshes the
cache — preventing the thundering herd problem where every concurrent user hits a cold cache.

`Cache::add()` atomically checks and sets — preventing race conditions in mutex-like scenarios.
`Cache::memo()` deduplicates within a single request, reducing cache store round trips.

## When It Applies

- Cache expensive queries and computations: `Cache::remember('key', $seconds, $callback)`
- High-traffic keys: `Cache::flexible('key', [300, 600], $callback)` for stale-while-revalidate
- Atomic locks: `Cache::lock('key', $seconds)->block($seconds, $callback)`
- Grouped invalidation: `Cache::tags(['group'])->flush()` (Redis/Memcached/DynamoDB only)
- Per-request memoization: `once(fn () => ...)` without cache store
- Invalidate on write: `Cache::forget('key')` in Actions after mutations
- Paginated results: only cache when data is stale-tolerant

Exceptions: User-specific or highly dynamic data may not benefit from caching.
