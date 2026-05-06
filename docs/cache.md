# Cache

## Configuration

| Setting | Value |
|---|---|
| Store | `database` |
| Prefix | `internara_` |
| Serializable classes | `false` (blocked) |
| Test store | `array` (via `phpunit.xml`) |

Tables: `cache`, `cache_locks`. Config: `config/cache.php`.

## Usage Patterns

### Settings (Permanent Cache)

`App\Support\Settings` caches database settings forever using `Cache::rememberForever()`. Keys prefixed with `settings.`.

Resolution chain: runtime overrides → AppInfo → cached database → Laravel config → default.

```php
// Read a setting (auto-cached)
setting('app_name');

// Invalidate after update
Settings::forget('app_name');
Settings::forget('app_name', 'branding'); // also invalidates group cache
```

### Managerial Stats (TTL Cache)

Dashboard stats cached for 10 minutes:

```php
// app/Domain/Dashboard/Actions/GetManagerialStatsAction.php
return Cache::remember('managerial_stats', now()->addMinutes(10), function () {
    // compute stats
});
```

### User Session Expiry

`CheckUserSessionExpiryAction` tracks last activity per user:

```php
Cache::put("user.last_activity.{$user->id}", now());
$lastActivity = Cache::get("user.last_activity.{$user->id}");
```

## Cache Invalidation

- Settings: `Settings::forget($key)` clears key, group, and `all` cache
- Stats: `Cache::forget('managerial_stats')`
- Full clear: `Cache::flush()` or `php artisan cache:clear`

## Guidelines

| Data type | TTL | Reason |
|---|---|---|
| Settings | Forever | Changes rarely, auto-invalidated on update |
| Dashboard stats | 10 minutes | Expensive multi-table query |
| User session activity | Per request | Security-sensitive |
