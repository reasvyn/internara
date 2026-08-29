# Cache Pattern — Key Management, TTL, Invalidation & Anti-Patterns

## Description

This pattern governs how Internara uses **caching** for performance optimization. It synthesizes global industry standards — **Cache-Aside Pattern** (most common web caching strategy), **PSR-16 SimpleCache** (PHP-FIG), **Cache Stampede / Thundering Herd** (concurrent recomputation), **Write-Through / Write-Behind** — into enforceable rules tied to Internara's stack: centralized key registry (`config/cache-keys.php`), event-driven invalidation, TTL categories, and `Cache::remember()` / `Cache::forget()`.

Without it, cache keys collide, stale data persists, stampedes overwhelm the database, and cache logic scatters across Livewire components. With it, every cache entry is registered, invalidated via events, and degrades gracefully.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Centralized key registry — no inline keys.** Every cache key MUST be declared in `config/cache-keys.php`. Reference via `config('cache-keys.your_key')`. Never write raw strings like `Cache::remember('dashboard_stats', ...)`. This is **C4** (invariant from `docs/conventions.md` §9).

2. **Cache is a performance optimization, not persistence.** The application MUST function correctly on every cache miss. Data cached today may not be cached tomorrow. Every cache read has a fallback that recomputes the value.

3. **Targeted invalidation, never blanket flush.** Prefer `Cache::forget(config('cache-keys.affected_key'))` over `Cache::flush()`. Full cache flushes are for maintenance only, not normal operations.

4. **Event-driven invalidation for cross-module keys.** Command Actions dispatch events; `CacheInvalidationListener` classes flush affected keys. This decouples mutation from cache layer.

5. **Stale is better than wrong.** Never serve data that contradicts the database. If in doubt, miss the cache and recompute.

6. **Cache logic in Read Actions, not in Livewire.** Cache-and-fallback logic belongs in Read Actions. Livewire components call the Action — they do not manage cache directly.

7. **User-scoped data requires a qualifier.** Storing user-scoped data in a global key causes cross-user contamination. Scope with qualifier: `key:{userId}`.

---

## How to Apply

### 1. Cache-Aside Pattern — The Standard Strategy

The most common web caching strategy: the application checks the cache first; on miss, it reads from the database, then populates the cache. This is what `Cache::remember()` implements in Laravel.

### 2. Key Registry & Naming

```
{module}.{purpose}[.{qualifier}]
```

Register in `config/cache-keys.php`. Dynamic keys concatenate prefix with qualifier at call site.

### 3. TTL Categories

| TTL | Range | Rationale |
|-----|-------|-----------|
| **short** | < 5 min | Dashboard changes with every mutation |
| **medium** | 5 min – 1 h | Changes infrequently, tolerable lag |
| **long** | 1 h – 24 h | Branding changes only via admin UI |
| **forever** | Never expires | Cleared explicitly on write |

### 4. What Gets Cached vs What Doesn't

**Cache:** Aggregated statistics (expensive COUNT/SUM), settings (read on every request), brand/theme values, rate-limit state, module discovery, health-check status.

**Don't cache:** User-specific content (session/DB), write-heavy counters (atomic DB), real-time consistency data (DB directly).

### 5. Driver Strategy

| Environment | Driver |
|------------|--------|
| Tier 1 | `file` |
| Tier 2+ | `redis` |

Same binary at every tier — only `.env` values change.

### 6. Cache Warming — Post-Deployment

Artisan command pre-populates caches after deployment so first user request does not bear cold-cache cost.

### 7. Cache Stampede Mitigation

When a frequently-accessed key expires and concurrent requests all trigger recomputation simultaneously. Mitigate with: locking (`Cache::lock()`), short TTLs, pre-warming, or `Cache::remember()` with atomic fallback.

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `Cache::remember('dashboard_stats', ...)` inline key | `Cache::remember(config('cache-keys.dashboard_stats'), ...)` | C4 — inline cache key |
| `Cache::flush()` in normal operations | `Cache::forget(config('cache-keys.affected_key'))` | Blanket flush — clears rate limits, sessions |
| Cache write without corresponding invalidation event | `CacheInvalidationListener` on Command event | Stale reads — missing invalidation |
| Cache logic in Livewire `mount()` | Read Action encapsulates cache-and-fallback | Cache coupled to UI layer |
| User data in global cache key `stats` | `stats:{userId}` qualifier | Cross-user contamination |
| Same TTL for all keys (24h for dashboard) | Short TTL for mutable data, long for stable | Stale TTL — ignores access patterns |
| `Cache::remember()` without fallback logic | Fallback closure that recomputes value | No graceful degradation on miss |
| Cache-as-persistence (no DB backing) | Cache for performance, DB for critical data | Data loss on cache clear |

---

## Quick References

- `docs/conventions.md` §9 Caching Conventions — C4, key registry, TTL
- `docs/guides/infra/cache.md` — complete cache strategy reference
- `action-pattern.md` — Read Actions encapsulate cache logic
- [PSR-16 SimpleCache](https://www.php-fig.org/psr/psr-16/) — PHP cache interface standard
- [Laravel — Cache](https://laravel.com/docs/cache) — `Cache::remember()`, `Cache::forget()`, `Cache::lock()`
- [Cache-Aside Pattern](https://learn.microsoft.com/en-us/azure/architecture/patterns/cache-aside) — most common caching strategy
- [Cache Stampede / Thundering Herd](https://en.wikipedia.org/wiki/Thundering_herd_problem) — concurrent recomputation
- [Wikipedia — Cache (computing)](https://en.wikipedia.org/wiki/Cache_%28computing%29) — caching strategies
