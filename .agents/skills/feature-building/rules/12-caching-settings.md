# Caching & Settings

## What It Enforces

Settings are read through `setting()` helper (which uses cached storage), written through `SetSettingAction` (which handles cache invalidation). Configuration values use `config()` for infrastructure defaults. Cache read operations and invalidate on writes.

## Why It Matters

Settings need to be fast (cached) and consistent (invalidated on write). The `SetSettingAction` ensures that updating a single setting clears all affected caches (`settings.{key}`, `settings.all`, and group caches). Direct table writes bypass this invalidation and cause stale data to be served.

## When It Applies

- Read settings: use `setting('key', 'default')` or `setting('key', 'default', skipCache: true)`
- Write settings: use `SetSettingAction::execute()` or `executeBatch()` — never write to the settings table directly
- Configuration values: use `config('app.name')` for Laravel config, never `env()` outside config files

Cache strategy:
- `Cache::remember()` for standard caching
- `Cache::flexible()` for stale-while-revalidate on high-traffic data
- Cache tags for grouped invalidation (Redis/Memcached/DynamoDB only)
- Invalidate on write in Actions
- `Cache::memo()` for same-request deduplication
- `once()` for per-request memoization without cache store

Exceptions: Bypassing cache with `skipCache: true` is acceptable for admin interfaces where freshness matters more than performance.
