# Queue
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Purpose

The queue layer enables asynchronous job processing. For the current deployment model (Tier 1,
shared hosting / single-server), all jobs run **synchronously** via `QUEUE_CONNECTION=sync` —
no worker process needed.

For future enterprise-scale deployments (Tier 2+), a queue worker can be introduced to move
heavy operations (email, media conversions, certificate generation) off the HTTP request cycle
and into background processing.

---

## Current Default: Sync (No Worker Required)

```env
QUEUE_CONNECTION=sync
```

In sync mode, every job executes immediately during the HTTP request. The user waits for the
result, but no background process is needed. This is the correct default for the target
deployment — schools typically have fewer than 50 concurrent users, and the added complexity
of a queue worker is unnecessary.

### What Still Works in Sync Mode

| Operation | Behavior | User Experience |
|---|---|---|
| Email delivery | Sent during request (~1-3s) | Slight delay on form submission |
| Media conversions | Processed during upload (~0.5-2s) | Slight delay on file upload |
| Certificate PDF generation | Generated during request (~2-5s) | Slight delay on download |
| Notification dispatch | Stored immediately | Instant (database write) |

---

## Future Enterprise: Worker-Based Queue (Tier 2+)

When the school grows beyond ~50 concurrent users, or when the synchronous delays become
noticeable, switch to a worker-based queue:

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### What a Worker Enables

| Before (sync) | After (async worker) |
|---|---|
| HTTP response waits for email to send | Email sent in background, instant response |
| Image conversion blocks page load | Conversion processed by worker, upload returns immediately |
| Certificate generation takes 2-5s | Generation queued, user notified when ready |
| No retry on failure | Automatic retry with exponential backoff (3 attempts) |

### Driver Strategy

| Aspect | Current (Tier 1) | Future Enterprise (Tier 2+) |
|---|---|---|
| **Driver** | `sync` | `redis` |
| **Worker** | None | Supervisor-managed |
| **Retries** | N/A (inline, no retry) | 3 attempts with backoff |
| **Failed jobs** | N/A | `failed_jobs` table for inspection |
| **Throughput** | N/A | ~1,000+ jobs/min |

### Supervisor Configuration (Future)

When deploying with a queue worker, Supervisor keeps the worker process alive:

```ini
[program:internara-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/app/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Start worker (development only — Supervisor for production)
php artisan queue:work --sleep=3 --tries=3
```

---

## Job Design

Jobs are written the same way regardless of driver — the same code works in sync mode today
and async mode tomorrow:

```php
class ProcessMediaConversion implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [2, 10, 30];

    // Accept IDs, not whole models — the entity may change before the job runs
    public function __construct(
        public string $mediaId,
    ) {}

    public function handle(): void
    {
        // Process media conversion
    }

    public function failed(\Throwable $e): void
    {
        SmartLogger::error('media_conversion_failed')
            ->withPayload(['media_id' => $this->mediaId])
            ->systemOnly()
            ->save();
    }
}
```

### Guidelines

| Rule | Rationale |
|---|---|
| Accept IDs, not models | The model may change between dispatch and execution |
| Make jobs idempotent | Running twice should not duplicate results |
| Handle missing entities gracefully | Entity may have been deleted before job runs |
| Set `$tries` and `$backoff` | Prevent infinite retries on permanent failures |
| Implement `failed()` for critical jobs | Surface permanent failures to operations |

---

## Failed Jobs (Future Enterprise)

When a worker is active, failed jobs are stored in the `failed_jobs` table for inspection:

```bash
php artisan queue:failed              # List failed jobs
php artisan queue:retry all           # Retry all failed
php artisan queue:prune-failed        # Prune old failed jobs (scheduled weekly)
```

Failed jobs older than 7 days are automatically pruned by the scheduler.

---

## Where to Find It

- `config/queue.php` — queue connection configuration
- `config/database.php` — Redis connection settings (future use)
- `database/migrations/` — jobs and failed_jobs table migrations (future use)
- `docs/deployment.md` — Supervisor configuration (future enterprise)
- `docs/infrastructure.md` — tier-based infrastructure design
