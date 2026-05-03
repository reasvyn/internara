# Cache Documentation: Internara

## 1. Overview

Internara uses Laravel's cache system for performance optimization and data caching. The system
supports multiple cache drivers with **Database** as the default for production and **Array** for
testing.

### Configuration

- **Config File**: `config/cache.php`
- **Environment Variables**: `CACHE_STORE`, `CACHE_PREFIX`, `DB_CACHE_CONNECTION`
- **Default Store**: Database (`env('CACHE_STORE', 'database')`)
- **Serializable Classes**: Set to `false` by default for security hardening (Laravel 13+)

### Security: Serializable Classes (Laravel 13+)

Laravel 13 introduces the `serializable_classes` configuration option to prevent PHP deserialization
gadget chain attacks. By default, this is set to `false`, which blocks unserialization of arbitrary
PHP objects from cache.

If your application needs to store PHP objects in cache, you must explicitly whitelist the allowed
classes:

```php
// config/cache.php
'serializable_classes' => [
    App\Data\CachedDashboardStats::class,
    App\Support\CachedPricingSnapshot::class,
],
```

**Current Setting**: `false` (blocks all object unserialization)

If you attempt to retrieve a cached PHP object that isn't whitelisted, Laravel will throw an
exception. Migrate object-based cache payloads to arrays or add the classes to the allow-list.

## 2. Cache Drivers

### Available Drivers

| Driver      | Description                      | Use Case                    |
| ----------- | -------------------------------- | --------------------------- |
| `array`     | In-memory array (non-persistent) | Testing, single request     |
| `database`  | Database table storage           | Production default          |
| `file`      | File-based storage               | Simple deployments          |
| `redis`     | Redis key-value store            | High-performance production |
| `memcached` | Memcached distributed memory     | High-traffic applications   |
| `dynamodb`  | AWS DynamoDB                     | Cloud-native deployments    |
| `null`      | No caching (disabled)            | Development/debugging       |

### Default Configuration

```env
CACHE_STORE=database
DB_CACHE_CONNECTION=cache
DB_CACHE_TABLE=cache
DB_CACHE_LOCK_TABLE=cache_locks
```

## 3. Cache Table (`cache`)

### Migration: `2026_04_29_104704_create_cache_table.php`

```php
Schema::create('cache', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->mediumText('value');
    $table->integer('expiration')->index();
});
```

### Cache Locks Table (Optional)

```php
Schema::create('cache_locks', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->string('owner');
    $table->integer('expiration')->index();
});
```

## 4. Cache Usage Patterns

### Basic Cache Operations

#### Store & Retrieve

```php
use Illuminate\Support\Facades\Cache;

// Store with default expiration (60 minutes)
Cache::put('key', 'value');

// Store with specific TTL (10 minutes)
Cache::put('key', 'value', now()->addMinutes(10));

// Store forever (until manually removed)
Cache::forever('key', 'value');

// Retrieve (with default if not exists)
$value = Cache::get('key', 'default');

// Retrieve & delete (atomic)
$value = Cache::pull('key');
```

#### Check & Delete

```php
// Check if exists
if (Cache::has('key')) {
}

// Delete
Cache::forget('key');

// Delete multiple
Cache::deleteMultiple(['key1', 'key2']);

// Clear all cache
Cache::flush(); // ⚠️ Use with caution!
```

### Advanced Patterns

#### Remember (Get or Compute)

```php
$value = Cache::remember('users.active', now()->addHour(), function () {
    return User::where('status', 'active')->count();
});
```

#### Remember Forever

```php
$settings = Cache::rememberForever('app.settings', function () {
    return Setting::all();
});
```

#### Touch (Extend TTL - Laravel 13+)

```php
// Extend TTL without fetching or re-storing the value
Cache::touch('users.active', now()->addMinutes(30));

// Returns true if key exists and was touched, false otherwise
if (Cache::touch('session.' . $userId, now()->addHour())) {
    // Session extended successfully
}
```

#### Cache Locking (Atomic Operations)

```php
use Illuminate\Support\Facades\Cache;

$lock = Cache::lock('processing-report', 10); // 10 seconds

if ($lock->get()) {
    // Critical section - only one process can execute this
    // ... do work ...
    $lock->release();
}
```

#### Increment/Decrement

```php
Cache::increment('page.views');
Cache::decrement('inventory.count', 5);
```

## 5. Cache Prefix & Namespacing

### Prefix Configuration

```env
CACHE_PREFIX=internara_
```

All cache keys are automatically prefixed to avoid collisions in shared environments.

### Namespacing Convention

```php
// Use dot notation for grouping
Cache::put('managerial_stats', $stats);
Cache::put('user.' . $userId . '.profile', $profile);
Cache::put('internship.' . $id . '.registrations', $registrations);
```

## 6. Real-World Usage in Internara

### Example 1: Managerial Statistics (`GetManagerialStatsAction`)

```php
use Illuminate\Support\Facades\Cache;

class GetManagerialStatsAction
{
    public function execute(): array
    {
        return Cache::remember('managerial_stats', now()->addMinutes(10), function () {
            return [
                'total_mentees' => User::whereHas(
                    'roles',
                    fn($q) => $q->where('name', 'mentee'),
                )->count(),
                'active_internships' => Internship::where('status', 'active')->count(),
                // ... more stats
            ];
        });
    }
}
```

### Example 2: Settings Caching (`app/Support/Settings.php`)

```php
class Settings
{
    const CACHE_PREFIX = 'internara.settings.';

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key) {
            $setting = Setting::where('key', $key)->first();
            return $setting?->typed_value ?? $default;
        });
    }

    public static function forget(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public static function clearGroup(string $group): void
    {
        Cache::forget(self::CACHE_PREFIX . 'group.' . $group);
    }

    public static function clearAll(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'all');
    }
}
```

## 7. Cache Invalidation

### When to Clear Cache

- **Settings updated**: Clear settings cache
- **User profile changed**: Clear user-specific cache
- **Statistics affected**: Clear stats cache after batch operations

### Invalidation Methods

#### Manual Clear

```php
// Clear specific key
Cache::forget('managerial_stats');

// Clear by pattern (requires Redis or custom implementation)
// For database driver, use tagged cache or naming convention
```

#### Automatic Expiration

```php
// Set TTL when storing
Cache::put('temp_data', $data, now()->addMinutes(5)); // Expires in 5 minutes
```

## 8. Cache Stores Configuration

### Database Store (Default)

```php
// config/cache.php
'database' => [
    'driver' => 'database',
    'connection' => env('DB_CACHE_CONNECTION'),
    'table' => env('DB_CACHE_TABLE', 'cache'),
    'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
    'lock_table' => env('DB_CACHE_LOCK_TABLE', 'cache_locks'),
],
```

**Pros**:

- Persistent across server restarts
- No additional infrastructure needed
- Works with existing database

**Cons**:

- Slower than in-memory stores
- Adds database load

### Redis Store (High Performance)

```env
CACHE_STORE=redis
REDIS_CACHE_CONNECTION=cache
```

```php
// config/cache.php
'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
    'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
],
```

**Pros**:

- Very fast (in-memory)
- Supports tagging (if using PhpRedis)
- Atomic operations

**Cons**:

- Requires Redis server
- Additional infrastructure

### Array Store (Testing)

```env
CACHE_STORE=array
```

Used in `phpunit.xml` for testing:

```xml
<env name="CACHE_STORE" value="array"/>
```

**Pros**:

- Fastest (no I/O)
- Perfect for tests (non-persistent)

**Cons**:

- Not persistent
- Only lasts for single request

## 9. Cache Testing

### PHPUnit/Pest Testing

```php
use Illuminate\Support\Facades\Cache;

test('cache stores and retrieves data', function () {
    Cache::put('test_key', 'test_value', 10);

    expect(Cache::get('test_key'))->toBe('test_value');

    Cache::forget('test_key');
    expect(Cache::has('test_key'))->toBeFalse();
});
```

### Testing with Array Driver

Tests automatically use `array` driver (configured in `phpunit.xml`), so:

- No database needed for cache tests
- Cache is cleared between tests (using `RefreshDatabase` or manual `Cache::flush()`)

## 10. Performance Considerations

### When to Use Cache

✅ **Good candidates**:

- Expensive queries (statistics, reports)
- Configuration/settings
- User permissions (if not using Spatie)
- API responses (external data)

❌ **Bad candidates**:

- Real-time data (attendance logs, journal entries)
- Frequently changing data
- Small, fast queries

### Cache Duration Guidelines

| Data Type        | TTL        | Reason                        |
| ---------------- | ---------- | ----------------------------- |
| Managerial stats | 10 minutes | Computed from multiple tables |
| User settings    | Forever    | Changes rarely                |
| Permissions      | 1 hour     | Changes occasionally          |
| API tokens       | 5 minutes  | Security-sensitive            |

## 11. Cache Maintenance

### Monitor Cache Size

```sql
-- Database driver
SELECT COUNT(*) FROM cache;
SELECT key, expiration FROM cache ORDER BY expiration ASC LIMIT 10;
```

### Clear Expired Entries

Laravel automatically handles expiration, but you can manually clean:

```php
// Clear expired cache (database driver)
DB::table('cache')->where('expiration', '<', now()->timestamp)->delete();
```

### Clear All Cache

```bash
# Artisan command
php artisan cache:clear

# Or in code
Cache::flush();
```

## 12. Troubleshooting

### Cache Not Working

1. Check driver configuration: `php artisan about`
2. Verify database table exists: `php artisan migrate:status`
3. Check cache prefix: Ensure consistent naming

### Cache Key Collisions

- Always use prefix: `internara.settings.key`
- Use naming convention: `group.entity.action`

### Performance Issues

- Database cache too slow? → Switch to Redis
- Too many cache calls? → Batch operations, increase TTL
- Cache stampede? → Use `Cache::lock()` for expensive operations

---

**Last Updated**: May 2, 2026  
**Default Driver**: Database  
**Test Driver**: Array  
**Cache Table**: `cache` (with optional `cache_locks`)  
**Laravel Version**: 13+ (includes `Cache::touch()` and `serializable_classes`)
