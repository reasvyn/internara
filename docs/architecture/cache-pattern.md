# Cache Pattern

> **Last updated:** 2026-06-14
>
> Cache philosophy, key management conventions, driver strategy, invalidation strategy, warming,
> and anti-pattern concepts in Internara.

---

## 1. Cache Philosophy

The cache layer is a **performance optimization, not a persistence mechanism**. The application
must function correctly on every cache miss — data cached today may not be cached tomorrow.

### Core Principles

1. **Graceful degradation** — every cache read has a fallback that recomputes the value.
2. **Targeted over blanket** — invalidate only the keys that changed, not the entire store.
3. **Event-driven invalidation** — Command Actions dispatch events; listeners flush affected keys.
4. **Stale is better than wrong** — never serve data that contradicts the database. If in doubt,
   miss the cache and recompute.

### What Gets Cached

| Category               | Rationale                                        |
| ---------------------- | ------------------------------------------------ |
| Aggregated statistics  | Expensive COUNT/SUM queries across multiple tables|
| Settings               | Read on every request, change rarely             |
| Brand/theme values     | Read on every page load, change via admin UI     |
| Rate-limit state       | Must be shared across requests (not session)     |
| Module discovery       | Livewire/policy/view registration metadata       |
| Health-check status    | Lightweight liveness probe                       |

### What Does NOT Get Cached

- User-specific content that varies per request (handled by session or database query).
- Write-heavy counters that would cause cache stampedes (use atomic DB operations).
- Data with real-time consistency requirements (use the database directly).

---

## 2. Centralized Key Registry

Every cache key **MUST** be declared in `config/cache-keys.php`. This prevents collisions, makes
dependencies discoverable, and enables systematic flushing across the entire application.

Always reference the registry via `config('cache-keys.your_key')` — never write raw strings.

### Registering a New Key

1. Add the entry to `config/cache-keys.php`.
2. Use `config('cache-keys.your_key')` everywhere the key is referenced.
3. Wire invalidation into the appropriate event listener (see §5).

---

## 3. Naming Convention

```
{module}.{purpose}[.{qualifier}]
```

The qualifier segment is optional. Use it to namespace dynamic or user-scoped keys. The trailing
colon or underscore in the prefix is intentional — it produces readable composite keys.

### Dynamic Keys at Runtime

When a qualifier is dynamic, concatenate the registered prefix with the qualifier at the call site.

---

## 4. TTL Categories

| TTL        | Range          | Rationale                              |
| ---------- | -------------- | -------------------------------------- |
| **short**  | < 5 min        | Dashboard changes with every mutation  |
| **medium** | 5 min – 1 h    | Changes infrequently, tolerable lag    |
| **long**   | 1 h – 24 h     | Branding changes only via admin UI     |
| **forever**| Never expires  | Cleared explicitly on write            |

**Short/Medium/Long** — use `Cache::remember()` with explicit seconds.

**Forever** — use `Cache::rememberForever()` or `Cache::forever()`.

---

## 5. Driver Strategy

| Environment | Driver             |
| ----------- | ------------------ |
| Tier 1      | `file`             |
| Tier 2+     | `redis`            |

The same binary runs at every tier — only `.env` values change. Redis can serve cache, session,
and queue simultaneously by using separate dedicated Laravel drivers.

---

## 6. Invalidation Strategy

### Rule Hierarchy

1. **If you update a value, invalidate its cache entry.** This is the fundamental rule.
2. Prefer **targeted invalidation** — clear only the keys that changed, not everything.
3. Use **event-driven invalidation** for cross-module cache keys.
4. Full cache flushes (`php artisan cache:clear`) are for maintenance only, not normal operations.

### Pattern: Event-Driven Invalidation (Preferred)

Command Actions dispatch events; `CacheInvalidationListener` classes listen and flush affected keys
via `Cache::forget(config('cache-keys.affected_key'))`. This decouples the mutation from the
cache layer and allows multiple listeners to react to a single event.

### Pattern: Direct Invalidation (Simple Cases)

When the cache consumer is in the same module and the invalidation is straightforward, call
`Cache::forget()` inline at the point of mutation.

### Pattern: Bulk Invalidation

When a single mutation affects multiple cache keys (e.g., a setting change that also invalidates
theme and brand caches), group all `Cache::forget()` calls in a single listener to ensure
consistency.

---

## 7. Cache Warming

An Artisan command pre-populates caches after deployment so the first user request does not bear
the cost of cold caches.

### When to Run

- **After every deployment** (as part of the deploy script).
- **After a full cache clear** (`php artisan optimize:clear`).

---

## 8. Anti-Pattern Concepts

### Raw String Keys

Using raw key strings instead of the centralized registry makes keys undiscoverable and
collision-prone.

### Cache-as-Persistence

Storing data exclusively in the cache that should live in the database. Cache is for performance
— critical data belongs in the database.

### Indiscriminate Full Flush

Calling `Cache::flush()` in normal operations clears every key including rate-limit state and
other active sessions. Use targeted `Cache::forget()` for individual keys.

### Missing Invalidation

Writing to the database without clearing the affected cache keys causes stale reads. Every
mutation that changes data consumed via a cached read must trigger invalidation.

### Caching User-Specific Data Without a Qualifier

Storing user-scoped data in a global key causes cross-user contamination. Scope with a qualifier
(e.g., `key:{userId}`).

### Stale TTLs

Using the same TTL for all keys ignores access patterns. Frequently-mutated data should have
short TTLs; rarely-changed data can have long TTLs or be stored forever.

### Cache Stampede

When a frequently-accessed key expires and multiple concurrent requests all trigger recomputation
simultaneously, the database may be overwhelmed. Mitigate with locking, short TTLs, or pre-warming.

### Inline Cache Logic in Presentation Layers

Cache logic mixed into Livewire components or controllers couples caching to the UI. Delegate to
Read Actions that encapsulate the cache-and-fallback logic.
