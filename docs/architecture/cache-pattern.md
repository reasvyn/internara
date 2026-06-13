# Cache Pattern

> **Last updated:** 2026-06-10
>
> Comprehensive reference on caching architecture, key management, driver strategy, invalidation,
> warming, testing, and anti-patterns in Internara.

---

## Table of Contents

1. [Cache Philosophy](#1-cache-philosophy)
2. [Centralized Key Registry](#2-centralized-key-registry)
3. [Naming Convention](#3-naming-convention)
4. [TTL Categories](#4-ttl-categories)
5. [Driver Strategy by Tier](#5-driver-strategy-by-tier)
6. [Invalidation Strategy](#6-invalidation-strategy)
7. [Cache Warming](#7-cache-warming)
8. [Application Layer Caching](#8-application-layer-caching)
9. [OpCache Configuration](#9-opcache-configuration)
10. [Testing Cache](#10-testing-cache)
11. [Anti-Patterns](#11-anti-patterns)

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

```php
// config/cache-keys.php
declare(strict_types=1);

return [
    'setup_installed'        => 'setup.is_installed',
    'admin_dashboard_stats'  => 'sysadmin.dashboard.stats',
    'theme_css_variables'    => 'theme.css_variables',
    'brand_colors'           => 'brand.colors',
    'notification_unread'    => 'notification.unread:',
    'core_integrity'         => 'core.integrity_verified',
    'core_app_name'          => 'core.app_name',
    'appinfo_metadata'       => 'appinfo.metadata',
    'module_livewire'        => 'module.discovered_livewire',
    'module_policies'        => 'module.discovered_policies',
    'module_views'           => 'module.discovered_views',
    'auth_login_lockout'     => 'login:lockout:',
    'auth_login_attempts'    => 'login:attempts:',
    'auth_login_failures'    => 'auth.login-failures:',
    'health_check'           => 'health_check',
    'recover_admin_attempts' => 'recover_admin_attempts_',
    'settings_all'           => 'settings.all',
    'settings_group'         => 'settings.group.',
    'settings_keys'          => 'settings.keys',
    'settings_key'           => 'settings.',
];
```

### Accessing Keys via Config

Always reference the registry — never write raw strings:

```php
// ✅ Correct
Cache::remember(config('cache-keys.admin_dashboard_stats'), 300, fn () => [...]);

// ❌ Wrong — raw string, no discoverability, prone to collision
Cache::remember('sysadmin.dashboard.stats', 300, fn () => [...]);
```

### Registering a New Key

1. Add the entry to `config/cache-keys.php`.
2. Use `config('cache-keys.your_key')` everywhere the key is referenced.
3. Wire invalidation into the appropriate event listener (see §6).

---

## 3. Naming Convention

```
{module}.{purpose}[.{qualifier}]
```

The qualifier segment is optional. Use it to namespace dynamic or user-scoped keys.

| Key                        | Module        | Purpose                | Qualifier          |
| -------------------------- | ------------- | ---------------------- | ------------------ |
| `setup.is_installed`       | setup         | installation status    | —                  |
| `sysadmin.dashboard.stats` | sysadmin      | dashboard statistics   | —                  |
| `theme.css_variables`      | theme         | CSS custom properties  | —                  |
| `brand.colors`             | brand         | brand color palette    | —                  |
| `notification.unread:`     | notification  | unread count           | `{userId}`         |
| `login:lockout:`           | login         | brute-force lockout    | `{identifierHash}` |
| `login:attempts:`          | login         | failed attempt count   | `{identifierHash}` |
| `settings.group.`          | settings      | group-scoped cache     | `{groupName}`      |
| `settings.`                | settings      | individual key cache   | `{key}`            |

### Dynamic Keys at Runtime

When a qualifier is dynamic, concatenate the registered prefix with the qualifier at the call site:

```php
$attemptsKey = config('cache-keys.auth_login_attempts').$identifierHash;
Cache::put($attemptsKey, $attempts, now()->addHours(24));
```

The trailing colon or underscore in the prefix (`notification.unread:`, `settings.`) is intentional
— it produces readable composite keys like `notification.unread:a1b2c3d4`.

---

## 4. TTL Categories

| TTL       | Range          | Example Keys                              | Rationale                              |
| --------- | -------------- | ----------------------------------------- | -------------------------------------- |
| **short** | < 5 min        | `sysadmin.dashboard.stats` (300s)         | Dashboard changes with every mutation  |
| **medium**| 5 min – 1 h    | `setup.is_installed` (3600s)              | Changes infrequently, tolerable lag    |
| **long**  | 1 h – 24 h     | `brand.colors` (86400s)                   | Branding changes only via admin UI     |
| **forever**| Never expires | `settings.*` (via `rememberForever`)      | Cleared explicitly on write            |

### Implementation Patterns

**Short/Medium/Long** — use `Cache::remember()` with explicit seconds:

```php
Cache::remember(config('cache-keys.admin_dashboard_stats'), 300, function () {
    // ... expensive query ...
});
```

**Forever** — use `Cache::rememberForever()` or `Cache::forever()`:

```php
Cache::rememberForever(config('cache-keys.settings_all'), fn () => [...]);
```

---

## 5. Driver Strategy by Tier

| Aspect          | Tier 1 (Entry)       | Tier 2 (Standard)     | Tier 3 (HA)           |
| --------------- | -------------------- | --------------------- | --------------------- |
| **Driver**      | `file`               | `redis`               | `redis` (cluster)     |
| **Setup**       | Zero config          | Redis server required | Redis cluster         |
| **Performance** | Moderate             | High (in-memory)      | High (distributed)    |
| **Persistence** | File-based, durable  | Memory + persistence  | Memory + replication  |

### Configuration

```env
# Tier 1 (default)
CACHE_STORE=file

# Tier 2+
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

The same binary runs at every tier — only `.env` values change. See
[ADR-009 Performance & Optimization](../adr/adr-performance-optimization.md) for the full tier
decision matrix.

### Redis Prefix

```php
'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel')).'-cache-'),
```

The prefix prevents key collisions when multiple applications share a Redis instance. Default value
is `internara-cache-`.

### Single Redis Instance for Multiple Concerns

A single Redis instance can serve cache, session, and queue by using separate databases. Each
concern uses its own dedicated Laravel driver (not the cache store):

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 6. Invalidation Strategy

### Rule Hierarchy

1. **If you update a value, invalidate its cache entry.** This is the fundamental rule.
2. Prefer **targeted invalidation** — clear only the keys that changed, not everything.
3. Use **event-driven invalidation** for cross-module cache keys.
4. Full cache flushes (`php artisan cache:clear`) are for maintenance only, not normal operations.

### Pattern: Event-Driven Invalidation (Preferred)

```
Command Action → event({Entity}Updated)
                     ↓
           CacheInvalidationListener
                     ↓
           Cache::forget(config('cache-keys.affected_key'))
```

**Concrete example** — clearing the admin dashboard when a student registers:

```php
// app/Enrollment/Registration/Listeners/ClearDashboardOnRegistration.php
final class ClearDashboardOnRegistration
{
    public function handle(StudentRegistered $event): void
    {
        Cache::forget(config('cache-keys.admin_dashboard_stats'));
    }
}
```

The same listener pattern is used for company changes, academic year changes, and department
changes — all flush the same `admin_dashboard_stats` key:

```php
// app/Partners/Company/Listeners/ClearDashboardOnCompanyChange.php
Cache::forget(config('cache-keys.admin_dashboard_stats'));

// app/User/Dashboard/Listeners/ClearDashboardCacheOnYearChange.php
Cache::forget(config('cache-keys.admin_dashboard_stats'));

// app/User/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php
Cache::forget(config('cache-keys.admin_dashboard_stats'));
```

### Pattern: Direct Invalidation (Simple Cases)

When the cache consumer is in the same module and the invalidation is straightforward, call
`Cache::forget()` inline:

```php
// app/Settings/Support/Brand.php
public static function clearCache(): void
{
    Cache::forget(config('cache-keys.brand_colors'));
}
```

### Pattern: Bulk Invalidation (Settings Module)

The `InvalidateSettingsCache` listener demonstrates bulk invalidation of related keys when any
setting changes:

```php
class InvalidateSettingsCache
{
    public function handle(SettingUpdated $event): void
    {
        // 1. Invalidate the individual key
        Cache::forget(config('cache-keys.settings_key').$event->setting->key);

        // 2. Invalidate the "all settings" collection
        Cache::forget(config('cache-keys.settings_all'));

        // 3. Invalidate the group cache if a group is set
        if ($event->setting->group) {
            Cache::forget(config('cache-keys.settings_group').$event->setting->group);
        }

        // 4. Invalidate theme/brand caches if the changed key is a theme key
        if (in_array($event->setting->key, config('settings.theme_cache_keys', []))) {
            Cache::forget(config('cache-keys.theme_css_variables'));
            Cache::forget(config('cache-keys.brand_colors'));
        }
    }
}
```

### Invalidation Trigger Map

| Mutation                             | Cache Key(s) Invalidated                |
| ------------------------------------ | --------------------------------------- |
| Student registers                   | `sysadmin.dashboard.stats`             |
| Company created/updated/deleted      | `sysadmin.dashboard.stats`             |
| Academic year changed               | `sysadmin.dashboard.stats`             |
| Department changed                  | `sysadmin.dashboard.stats`             |
| Setting value updated               | `settings.{key}`, `settings.all`, `settings.group.{group}`, possibly `theme.css_variables`, `brand.colors` |
| Notification read / all read        | `notification.unread:{userId}`         |
| Successful login                    | `login:lockout:{hash}`, `login:attempts:{hash}` |
| Brand/theme color changed           | `theme.css_variables`, `brand.colors`  |
| Super admin recovered               | `recover_admin_attempts_{hash}`        |

---

## 7. Cache Warming

The `system:cache-warm` command pre-populates caches after deployment so the first user request
does not bear the cost of cold caches.

```bash
php artisan system:cache-warm
```

### Execution Steps

```php
// app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php
protected function warmSettings(): void
{
    setting('app_name', skipCache: false);
    setting('primary_color', skipCache: false);
}

protected function warmBrand(): void
{
    brand('name');
    brand('colors');
}

protected function warmConfig(): void
{
    Artisan::call('config:cache');
}

protected function warmViews(): void
{
    Artisan::call('view:cache');
}

protected function warmEvents(): void
{
    Artisan::call('event:cache');
}
```

### When to Run

- **After every deployment** (as part of the deploy script).
- **After a full cache clear** (`php artisan optimize:clear`).

The command logs success/failure via `SmartLogger` with the event name `cache.warm.completed` /
`cache.warm.failed`.

---

## 8. Application Layer Caching

These caches are managed by Artisan commands, not by the runtime cache store. They merge and
compile framework metadata into single files.

| Command                     | What It Caches                        | When to Run            |
| --------------------------- | ------------------------------------- | ---------------------- |
| `php artisan config:cache`  | Merges all `config/*.php` into one    | Every deployment       |
| `php artisan route:cache`   | Caches route registration             | Every deployment       |
| `php artisan view:cache`    | Pre-compiles all Blade templates      | Every deployment       |
| `php artisan event:cache`   | Caches event discovery map            | High-traffic deploys   |

### Deployment Shortcut

```bash
# Enable all application caches at once
php artisan optimize

# Clear all application caches
php artisan optimize:clear
```

`php artisan optimize` runs `config:cache`, `route:cache`, and `view:cache`. It does **not** run
`event:cache` — that must be called separately or via `system:cache-warm`.

### Route Cache Constraint

The route cache is incompatible with Closure-based routes. Internara uses dedicated controller
classes in `routes/web/{module}.php`, making the route cache safe to enable.

---

## 9. OpCache Configuration

OpCache caches compiled PHP bytecode in shared memory. Without it, PHP re-parses and re-compiles
every PHP file on every request.

### Production

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0       ; Never recheck — clear opcache on deploy
opcache.max_wasted_percentage=10    ; Auto-restart when wasted memory exceeds 10%
```

### Development

```ini
opcache.enable=1
opcache.validate_timestamps=1
opcache.revalidate_freq=0           ; Revalidate on every request
```

### OpCache Reset After Deployment

```bash
php -r 'opcache_reset();'
```

Or restart PHP-FPM:

```bash
sudo systemctl reload php8.4-fpm
```

---

## 11. Testing Cache

### Array Driver

All tests use the `array` driver, configured in `phpunit.xml`:

```xml
<env name="CACHE_STORE" value="array"/>
```

The array driver stores data in memory for the duration of the request. It is fast, requires no
filesystem or external service, and is automatically flushed between tests when using
`RefreshDatabase` or `LazilyRefreshDatabase` — though explicit flushes are common.

### Common Testing Patterns

**Flush before each test** to ensure clean state:

```php
beforeEach(function () {
    Cache::flush();
});
```

**Assert cache miss after invalidation:**

```php
Cache::rememberForever($cacheKey, fn () => collect(['key' => 'value']));

$listener = app(InvalidateSettingsCache::class);
$listener->handle(new SettingUpdated(/* ... */));

expect(Cache::get($cacheKey))->toBeNull();
```

**Seed the cache and assert it is used:**

```php
Cache::put(config('cache-keys.settings_key').$key, 'Original', 3600);
expect(Cache::get(config('cache-keys.settings_key').$key))->toBe('Original');

// Perform the action that should invalidate
$listener = app(InvalidateSettingsCache::class);
$listener->handle(new SettingUpdated(/* ... */));

expect(Cache::get(config('cache-keys.settings_key').$key))->toBeNull();
```

**Test that unrelated keys survive invalidation:**

```php
Cache::put(config('cache-keys.health_check'), 'healthy', 3600);

// Invalidate settings cache — health_check should survive
$listener = app(InvalidateSettingsCache::class);
$listener->handle(new SettingUpdated(/* ... */));

expect(Cache::get(config('cache-keys.health_check')))->toBe('healthy');
```

### What NOT to Do in Tests

- Do not assert `Cache::get()` returns the exact object from a `remember()` callback — the
  serialization round-trip may differ in structure. Assert the data's value instead.
- Do not rely on cache duration in tests — the array driver does not expire entries during a
  single test unless you explicitly `Cache::forget()` or `Cache::flush()`.

---

## 12. Anti-Patterns

### ❌ Raw String Keys

```php
// Avoid — not discoverable, collision-prone
Cache::forget('sysadmin.dashboard.stats');
```

Always use the registry:
```php
Cache::forget(config('cache-keys.admin_dashboard_stats'));
```

### ❌ Cache-as-Persistence

```php
// Avoid — data loss acceptable? Only if you wouldn't mind losing it.
Cache::put('critical_data', $data, 86400);
```

Cache is for performance. Critical data belongs in the database.

### ❌ Indiscriminate Full Flush

```php
// Avoid in production — clears every key including rate-limit state
Cache::flush();
```

Use targeted `Cache::forget()` for individual keys.

### ❌ Missing Invalidation

Writing to the database without clearing the affected cache keys causes stale reads. Every Command
Action that modifies data consumed via a cached read must trigger invalidation.

### ❌ Caching User-Specific Data in a Global Key

```php
// Avoid — appends to a global key, causing cross-user contamination
Cache::remember('dashboard_stats', 300, fn () => [...] );
```

Scope user-specific data with a qualifier:
```php
Cache::remember('dashboard_stats:'.$userId, 300, fn () => [...] );
```

### ❌ Stale TTLs

Using the same TTL for all keys ignores access patterns. Dashboard stats (short-lived, mutated
frequently) should not share a TTL with brand colors (long-lived, mutated rarely).

### ❌ Ignoring Cache Stampede

When a frequently-accessed key expires and multiple concurrent requests all trigger the
`remember()` callback simultaneously, the database may be overwhelmed. Mitigate with:

- `Cache::lock()` for expensive recomputation (see `GenerateSetupTokenAction`).
- Short TTLs with `Cache::remember()` (the stampede window is smaller).
- Pre-warming via `system:cache-warm`.

### ❌ Route Cache with Closure Routes

```php
// Avoid — breaks route:cache
Route::get('/health', function () { return response()->json(['ok' => true]); });
```

Always use invokable controllers or controller classes:
```php
Route::get('/health', HealthCheckController::class);
```

### ❌ Inline Cache Logic in Livewire Components

```php
// Avoid — cache logic mixed with presentation
public function mount()
{
    $this->stats = Cache::remember('sysadmin.dashboard.stats', 300, fn () => ...);
}
```

Delegate to a Read Action:
```php
public function mount(GetAdminDashboardStatsAction $statsAction)
{
    $this->stats = $statsAction->execute();
}
```

---

## References

- `config/cache-keys.php` — cache key registry
- `config/cache.php` — cache store definitions
- `config/database.php` — Redis connection settings
- `config/settings.php` — theme cache invalidation keys (`settings.theme_cache_keys`)
- `app/Settings/Support/Settings.php` — settings caching layer
- `app/Settings/Support/Brand.php` — brand value caching
- `app/Settings/Listeners/InvalidateSettingsCache.php` — bulk invalidation listener
- `app/SysAdmin/Observability/Console/Commands/SystemCacheWarmCommand.php` — cache warmer
- `app/SysAdmin/Actions/GetAdminDashboardStatsAction.php` — cached dashboard reader
- `app/Auth/Login/Actions/LoginAction.php` — rate-limit caching with TTLs
- `app/Settings/Theme/Support/Theme.php` — theme CSS variable caching
- `docs/infrastructure/cache.md` — operational cache guide
- `docs/adr/adr-performance-optimization.md` — tier decision rationale
