# Cache

## Purpose

The cache layer exists to reduce database load and response latency by storing
computed or frequently accessed data in a fast retrieval store. It is a
performance optimization, not a persistence mechanism. Data cached today may
not be cached tomorrow, and the application must function correctly whether a
cache hit or miss occurs.

## What Gets Cached and Why

Settings are cached forever and invalidated explicitly. Brand colors, site
names, and operational thresholds change infrequently but are read on every
page load. Storing them indefinitely with targeted invalidation on update is
the most efficient strategy.

Permissions are cached automatically by the spatie/laravel-permission package.
The permission cache is flushed whenever roles or permissions are modified,
so no manual invalidation is needed.

Dashboard statistics — counts of active internships, registrations by status,
recent activity — are cached with a short time-to-live (5 to 15 minutes).
These numbers do not need to be perfectly current; staleness of a few minutes
is acceptable in exchange for avoiding expensive aggregation queries on every
page load.

User-specific transient data, such as multi-step form wizard progress, is
stored only for the duration of the user's session. It is cleared when the
operation completes.

## Invalidation Strategy

The fundamental rule is: if you update a value, you must invalidate its cache
entry. This is done explicitly by the action that performs the write. For
example, when a setting is updated, the action forgets that specific cache key.
When branding is changed, all branding-related keys are flushed.

The permission package handles its own cache invalidation internally. When
roles or permissions are assigned or removed, the cache is flushed
automatically.

Full cache flushes are available via an Artisan command but should be used
sparingly in production. The preferred approach is targeted invalidation:
clear only the keys that changed, not everything.

## Key Naming Convention

Cache keys follow a domain-prefixed dot-notation pattern. Keys that belong to
settings start with `settings.`, user-specific keys start with `user.{id}.`,
dashboard keys start with `dashboard.`, and system keys start with `system.`.
This convention makes it possible to flush all keys in a domain by prefix,
and it makes the origin of each cached value obvious from the key name alone.

## Cache vs Session

Cache and session serve fundamentally different purposes and should not be
confused. The cache stores computed data for performance — it is a
general-purpose optimization layer. The session stores authentication state
and user-specific ephemeral data — it is a security-critical persistence
layer.

The cache has no security restrictions; any code can read any cached key. The
session is encrypted, HTTP-only, and tied to a specific user's browser. Cache
entries have per-key time-to-live; session entries have a single global
lifetime. Cache backends can be swapped independently from session backends —
production may use Redis for cache (performance) and database for sessions
(durability, no single point of failure).

## Redis Configuration

When using Redis as the cache backend, configure these `.env` variables:

```env
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CLIENT=phpredis
```

Redis offers better performance than the database driver, especially under
concurrent load, and is the recommended backend for production. The same
Redis instance can serve cache, session, and queue simultaneously by using
separate databases (`REDIS_DB=0`, `REDIS_CACHE_DB=1`, `REDIS_QUEUE_DB=2`).

In the Docker Compose setup, a Redis 7 container is included by default.
See `docker-compose.yml` for the service definition.

## Where to Find It

Cache configuration is in `config/cache.php` with environment overrides in
`.env`. The cache migration creates the `cache` and `cache_locks` tables.
Settings caching is managed in `app/Domain/Settings/Support/Settings.php`.
Cache warming commands are in `app/Domain/Core/Console/Commands/`.
Redis connection settings are in `config/database.php` under the `redis` key.
