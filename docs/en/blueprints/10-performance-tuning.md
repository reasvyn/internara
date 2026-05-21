# Blueprint 10: Performance Tuning

## Caching

| Layer | Strategy | Production Recommendation |
|---|---|---|
| Config | `php artisan config:cache` | Always enabled |
| Routes | `php artisan route:cache` | Enable after removing Closure routes |
| Views | `php artisan view:cache` | Always enabled |
| Events | `php artisan event:cache` | Enable for high-traffic deployments |
| Data | `Cache::remember()` with TTL | Use Redis backend |
| Settings | `Cache::rememberForever()` | Invalidated on write |
| Permissions | Spatie auto-cache (24h) | Flushed on role/permission change |

Enable all caches for production:
```bash
php artisan optimize
```

## Database

| Optimization | SQLite | MySQL/PG |
|---|---|---|
| Connection pooling | Not needed | ProxySQL / PgBouncer |
| Read replicas | Not supported | Supported for reporting queries |
| Buffer pool | N/A | 70-80% of available RAM |
| Query logging | Disable in production | `DB::disableQueryLog()` |

### Index Strategy

Internara uses composite indexes for the most common query patterns:

| Pattern | Index Example |
|---|---|
| FK + status filter | `[registration_id, status]` on `attendances` |
| User + date lookup | `[user_id, date]` on `logbooks` and `attendances` |
| Polymorphic lookup | `[subject_type, subject_id]` on `activity_log` |

## Queue

| Setting | Dev | Production |
|---|---|---|
| Driver | `database` | `redis` |
| Worker count | 1 | 2-4 per server |
| Retry attempts | 3 | 3 |
| Max execution time | 3600s | 3600s |

Monitor queue health:
```bash
php artisan queue:monitor database:default --max=100
```

## PHP-FPM

```ini
pm = dynamic
pm.max_children = 50       # ~3GB RAM for 50 children
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 500      # Prevents memory leaks
```

## OpCache

Recommended `opcache.ini` settings:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=0       # Check file mtime on every request
opcache.validate_timestamps=1   # 0 in production for max speed
```

Set `validate_timestamps=0` and `revalidate_freq=2` in production.

## Opcache

For development, disable opcache to avoid stale bytecode.
For production, enable with:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

## Frontend Assets

| Strategy | Dev | Production |
|---|---|---|
| Build | `npm run dev` (HMR) | `npm run build` (minified) |
| CSS | Tailwind JIT (on-demand) | Purged + minified |
| JS | Vite dev server | Vite build + code splitting |

## References

- `config/cache.php` — cache store configuration
- `config/queue.php` — queue connection settings
- `config/database.php` — Redis connection settings
- `app/Domain/Core/Console/Commands/CacheWarmCommand.php` — cache warming
- `app/Domain/Core/Console/Commands/HealthCommand.php` — system verification
- `docs/en/cache.md` — caching strategy documentation
