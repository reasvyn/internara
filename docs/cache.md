# Cache

## Purpose

The cache layer reduces database load and response latency by storing computed or frequently
accessed data in a fast retrieval store. It is a **performance optimization**, not a
persistence mechanism. Data cached today may not be cached tomorrow, and the application must
function correctly on cache miss.

---

## Driver Strategy by Tier

| Aspect | Tier 1 (Entry) | Tier 2 (Standard) | Tier 3 (HA) |
|---|---|---|---|
| **Driver** | `file` or `database` | `redis` | `redis` (cluster) |
| **Setup** | Zero config | Redis server required | Redis cluster |
| **Performance** | Moderate | High (in-memory) | High (distributed) |
| **Persistence** | File-based | Memory + persistence | Memory + replication |

Default for new installations: `file` driver. Zero external services required.

```env
# Tier 1 (default)
CACHE_STORE=file

# Tier 2+
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## What Gets Cached

### Data Layer Caching

| Data | Key Pattern | TTL | Driver | Invalidation |
|---|---|---|---|---|
| Settings | `settings.{key}` | forever | Primary cache | Explicit on write |
| Brand colors | `theme.css_variables` | forever | Primary cache | Explicit on color change |
| Permissions | Spatie internal | 24 hours | Primary cache | Auto on role/permission change |
| Dashboard stats | `admin.dashboard.stats` | 5 min | Primary cache | Periodic refresh |
| Unread notifications | `notification.unread:{userId}` | 5 min | Primary cache | On read / new notification |

### Application Layer Caching

| Data | Command | When |
|---|---|---|
| Config files | `php artisan config:cache` | Every deployment |
| Routes | `php artisan route:cache` | Every deployment (no Closure routes) |
| Blade views | `php artisan view:cache` | Every deployment |
| Events | `php artisan event:cache` | Every deployment (high-traffic only) |

### OpCache (Bytecode Cache)

PHP OpCache caches compiled PHP bytecode in shared memory — essential for production
performance. Without it, PHP re-parses and compiles every PHP file on every request.

```ini
; /etc/php/8.4/cli/conf.d/opcache.ini
opcache.enable=1
opcache.memory_consumption=256          ; 256 MB for bytecode
opcache.max_accelerated_files=20000     ; Enough for 2000+ PHP files
opcache.validate_timestamps=0           ; Production: never recheck
opcache.revalidate_freq=2               ; Not used when validate=0
opcache.max_wasted_percentage=10        ; Auto-restart when wasted too much
```

For **development**, disable OpCache or set `validate_timestamps=1` to avoid stale
bytecode:

```ini
opcache.enable=1
opcache.validate_timestamps=1
opcache.revalidate_freq=0               ; Check every request
```

---

## Invalidation Strategy

### Rules

1. **If you update a value, invalidate its cache entry.** This is the fundamental rule.
2. Prefer **targeted invalidation** — clear only the keys that changed, not everything.
3. Use **event-driven invalidation** for cross-domain cache keys — the Command Action
   dispatches an event, a listener flushes the affected keys.
4. Full cache flushes (`php artisan cache:clear`) are for maintenance only, not normal
   operations.

### Invalidation Flow

```
Command Action → event({Entity}Updated)
                     ↓
           CacheInvalidationListener
                     ↓
           Cache::forget('affected.key')
```

Example:

```php
class InvalidateDashboardCache
{
    public function handle(InternshipCreated $event): void
    {
        Cache::forget(CacheKeys::ADMIN_DASHBOARD_STATS);
    }
}
```

### Direct Invalidation (Inline, for simple cases)

```php
// Inside a Command Action
Cache::forget(CacheKeys::THEME_CSS_VARIABLES);
```

---

## Key Management

### Centralized Registry

Every cache key MUST be declared as a constant in `App\Domain\Core\Support\CacheKeys`.
This prevents collisions, makes dependencies discoverable, and enables systematic flushing.

```php
final readonly class CacheKeys
{
    /** TTL: forever. Invalidation: SetupFinalized event */
    public const string SETUP_INSTALLED = 'setup.is_installed';

    /** TTL: 5 min. Invalidation: manual flush */
    public const string ADMIN_DASHBOARD_STATS = 'admin.dashboard.stats';

    /** TTL: forever. Invalidation: Settings update */
    public const string THEME_CSS_VARIABLES = 'theme.css_variables';

    /** Key pattern: notification.unread:{userId} */
    public const string NOTIFICATION_UNREAD = 'notification.unread:';
}
```

### Naming Convention

```
{domain}.{purpose}[.{qualifier}]

Examples:
  setup.is_installed              → domain: setup, purpose: installation status
  admin.dashboard.stats           → domain: admin, purpose: dashboard statistics
  notification.unread:{userId}    → domain: notification, purpose: unread count
  theme.css_variables             → domain: theme, purpose: CSS custom properties
```

### TTL Legend

| TTL | Meaning | Examples |
|---|---|---|
| **short** | < 5 min | Dashboard stats, notification counts |
| **medium** | 5 min - 1 hour | Aggregated reports |
| **long** | 1 hour - 24h | Slowly changing reference data |
| **forever** | Until explicit invalidation | Settings, branding, permissions |

---

## Redis Configuration

### Single Instance for Multiple Services

A single Redis instance can serve cache, session, and queue by using separate databases:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_CLIENT=phpredis

REDIS_DB=0        # Cache
REDIS_CACHE_DB=1  # Cache fallback (not used when CACHE_STORE=redis)
```

### Connection Settings

```php
// config/database.php → 'redis' key
'options' => [
    'prefix' => env('REDIS_PREFIX', 'internara:'),
    'cluster' => env('REDIS_CLUSTER', false),
],
'default' => [
    'url' => null,
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'username' => env('REDIS_USERNAME'),
    'password' => env('REDIS_PASSWORD'),
    'port' => env('REDIS_PORT', 6379),
    'database' => env('REDIS_DB', 0),
],
```

### Cluster Configuration (Tier 3)

```env
REDIS_CLUSTER=true
REDIS_HOST=node1.host,node2.host,node3.host
REDIS_PASSWORD=cluster-password
```

---

## Cache vs Session

| | Cache | Session |
|---|---|---|
| **Purpose** | Performance optimization | Auth state + user ephemeral data |
| **Security** | No restrictions — any code reads any key | Encrypted, HTTP-only, SameSite |
| **Data loss** | Acceptable — recomputed on miss | Critical — user gets logged out |
| **TTL** | Per-key (independent lifetimes) | Single global lifetime (120 min) |
| **Backend** | `file` / `database` / `redis` | `file` / `database` / `redis` |

---

## Production Optimization Commands

```bash
# Enable all application caches at once (recommended after deployment)
php artisan optimize

# Individual caches (useful when only one changed)
php artisan config:cache      # Merge config files into cached version
php artisan route:cache       # Cache route registration (no Closure routes)
php artisan view:cache        # Compile all Blade templates
php artisan event:cache       # Cache event discovery

# Maintenence
php artisan cache:clear       # Flush only the cache store (not config/route/view)
php artisan optimize:clear    # Clear ALL caches (config, route, view, events, cache store)
```

### Cache Warming

```bash
php artisan system:cache-warm
```

This pre-warms settings, brand values, compiles config/views/events, and prepares
the cache for first requests after deployment.

---

## Where to Find It

- `config/cache.php` — cache store definitions, per-store configuration
- `config/database.php` — Redis connection settings under the `redis` key
- `app/Domain/Core/Support/CacheKeys.php` — centralized cache key registry
- `app/Domain/Settings/Support/Settings.php` — settings caching layer
- `app/Domain/Core/Console/Commands/CacheWarmCommand.php` — cache warming
- `database/migrations/` — cache and cache_locks table migrations
- `docs/infrastructure.md` — tier-based infrastructure design
