# Cache

| Setting | Production | Testing |
|---|---|---|
| Default store | `database` | `array` |
| Prefix | `{app}-cache-` | — |

Tables: `cache`, `cache_locks`. Config: `config/cache.php`.

## Usage

| Pattern | TTL | Method |
|---|---|---|
| Settings | Forever | `Cache::rememberForever()` via `Settings` class |
| Dashboard stats | 10 min | `Cache::remember('managerial_stats', now()->addMinutes(10), ...)` |
| User activity | Per request | `Cache::put("user.last_activity.{$user->id}", now())` |

## Invalidation

- Settings: `Settings::forget($key)` clears key, its group, and `all` cache
- Stats: `Cache::forget('managerial_stats')`
- Full: `Cache::flush()` or `php artisan cache:clear`