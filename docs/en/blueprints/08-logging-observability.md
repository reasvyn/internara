# Blueprint 08: Logging & Observability

## Monitoring Categories

| Category | Tool | Data Source | Retention |
|---|---|---|---|
| Application errors | Log files | `storage/logs/laravel.log` | 30 days (pruned by `system:cleanup`) |
| Business audit | Activity log | `activity_log` table | 365 days (configurable) |
| Performance | Laravel Pulse | `pulse_*` tables | 7 days (configurable) |
| System health | Health command | Runtime checks | Point-in-time |

## Log Channels

The default log stack uses a single file:

```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

Available channels (configured in `config/logging.php`):
- `single` — one file, rotated manually
- `daily` — daily rotation, kept 14 days
- `slack` — critical errors to Slack webhook
- `papertrail` — remote syslog aggregation
- `stderr` — Docker/container friendly
- `syslog` — system syslog
- `null` — discard (testing)

## SmartLogger Dual-Channel

All business events log through `SmartLogger`, which writes to both the
system log file and the `activity_log` database table simultaneously.
PII (passwords, tokens, emails) is masked automatically before writing.

```php
SmartLogger::info('User registered')
    ->event('created')
    ->module('Auth')
    ->about($user)
    ->activityOnly()
    ->save();
```

## Laravel Pulse

Pulse records performance metrics in real time. Configured recorders:

| Recorder | What It Tracks | Threshold |
|---|---|---|
| Slow Queries | DB queries exceeding threshold | 1,000 ms |
| Slow Requests | HTTP requests exceeding threshold | 1,000 ms |
| Slow Jobs | Queued jobs exceeding threshold | 1,000 ms |
| Exceptions | All unhandled exceptions | Always |
| Cache | Cache hit/miss ratio | Always |
| Queues | Queue throughput | Always |

Enable Pulse recording:
```bash
php artisan pulse:check  # runs continuously, or add to schedule
```

The dashboard is available at `/pulse` and restricted to authorized users.

## Health Checks

```bash
php artisan system:health        # 14-point check (table output)
php artisan system:health --json # machine-readable
```

Checks: PHP version, extensions, memory, database, migrations, storage,
disk space, queue, cache, app key, storage link, maintenance mode,
rec. extensions.

## References

- `config/logging.php` — log channel configuration
- `config/activitylog.php` — activity log retention
- `config/pulse.php` — Pulse recorder configuration
- `app/Domain/Core/Support/SmartLogger.php` — dual-channel logger
- `app/Domain/Core/Console/Commands/HealthCommand.php` — health checks
- `app/Domain/Core/Http/Middleware/LogContext.php` — request context
- `docs/en/observability.md` — observability overview
