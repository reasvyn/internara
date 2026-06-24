---
name: pulse-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Laravel Pulse setup — dashboard, authorization, recorders, filters, custom cards, and Redis ingest.
upstream:
  - feature-building
downstream:
  - sync-docs
---

# Laravel Pulse Development Skill

## When to Activate

Apply this skill when setting up Laravel Pulse, configuring the dashboard or authorization gate, defining recorders and filters, building custom Pulse cards, optimizing with Redis ingest, creating custom recorders, integrating business metrics, or deploying Pulse in production. Activates for `/pulse`, `pulse:check`, `pulse:work`, `pulse:restart`, `pulse:clear`, `Pulse::record()`, `Pulse::filter()`, `Pulse::alert()`, `Pulse::resister()`, and application monitoring.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — roadmap task requiring Pulse monitoring |
| **This skill** | **IMPLEMENTATION (Pulse)** — produces Pulse dashboards and recorders |
| **Downstream (output)** | `sync-docs` — documentation after Pulse changes |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

## Key References

- **Pulse config**: `config/pulse.php` — recorder configuration, storage, ingest, caching
- **Pulse dashboard**: `resources/views/vendor/pulse/dashboard.blade.php` — card layout
- **SystemCard**: `app/SysAdmin/Observability/Livewire/Pulse/SystemCard.php` — custom card (users, unread notifications)
- **RegistrationsCard**: `app/SysAdmin/Observability/Livewire/Pulse/RegistrationsCard.php` — custom card (registration stats)
- **SystemRecorder**: `app/SysAdmin/Observability/Recorders/SystemRecorder.php` — custom recorder (user/notification snapshots)
- **RegistrationRecorder**: `app/SysAdmin/Observability/Recorders/RegistrationRecorder.php` — custom recorder (registration lifecycle snapshots)
- **Snapshot Command**: `app/SysAdmin/Observability/Console/Commands/PulseRecordSnapshotsCommand.php` — cron-based recorder trigger
- **AppServiceProvider**: `app/Providers/AppServiceProvider.php` — `boot()` for Pulse filters/register/alert
- **Architecture**: `docs/architecture.md#layered-architecture` (Layer 9 — Communications)
- **Observability**: `docs/infrastructure/observability.md` — Pulse overview, SmartLogger, health, cleanup
- **Logging**: `docs/architecture/logging-pattern.md` — SmartLogger, BaseAction::log(), event integration

## Dashboard Authorization

The `/pulse` dashboard requires a `viewPulse` Gate for production access. Without this gate, the dashboard is only visible in local environments:

```php
Gate::define('viewPulse', function (User $user) {
    return $user->hasRole('super_admin');
});
```

For more granular authorization, check specific permissions or use the `BasePolicy` pattern:

```php
Gate::define('viewPulse', function (User $user) {
    if ($user->hasRole('super_admin')) return true;
    return $user->can('view monitoring');
});
```

The `Authorize` middleware class is registered in `config/pulse.php` as the last middleware. Custom middleware can be prepended for additional access logging or IP restrictions:

```php
'middleware' => ['web', 'auth', 'log.pulse.access', Authorize::class],
```

## Configured Recorders (10 active)

Pulse is configured via `config/pulse.php` with the following recorders:

| Recorder | Config Key | Threshold | Notes |
|----------|-----------|-----------|-------|
| `CacheInteractions` | `PULSE_CACHE_INTERACTIONS_ENABLED` | — | Ignores vendor cache keys; group patterns supported |
| `Exceptions` | `PULSE_EXCEPTIONS_ENABLED` | — | Location tracking enabled via `location` config |
| `Queues` | `PULSE_QUEUES_ENABLED` | — | Tracks queue throughput and wait times |
| `Servers` | — | — | Requires `pulse:check` daemon; reports CPU/memory/disk |
| `SlowJobs` | `PULSE_SLOW_JOBS_ENABLED` | 1000ms | Configurable threshold via env |
| `SlowOutgoingRequests` | `PULSE_SLOW_OUTGOING_REQUESTS_ENABLED` | 1000ms | URI grouping patterns for API normalization |
| `SlowQueries` | `PULSE_SLOW_QUERIES_ENABLED` | 1000ms | Max query length configurable; ignores Pulse/Telescope tables |
| `SlowRequests` | `PULSE_SLOW_REQUESTS_ENABLED` | 1000ms | Ignores Pulse dashboard path and Telescope |
| `UserJobs` | `PULSE_USER_JOBS_ENABLED` | — | Per-user job tracking via job authenticatable |
| `UserRequests` | `PULSE_USER_REQUESTS_ENABLED` | — | Per-user request tracking |

### Recorder Environment Variables

Every recorder has independently toggleable `enabled`, `sample_rate`, and `threshold` env vars:

```env
PULSE_SLOW_QUERIES_ENABLED=true
PULSE_SLOW_QUERIES_SAMPLE_RATE=0.5
PULSE_SLOW_QUERIES_THRESHOLD=500
PULSE_SLOW_QUERIES_LOCATION=true
PULSE_SLOW_QUERIES_MAX_QUERY_LENGTH=10000
```

### Ignoring Recorder Noise

Nearly all recorders accept an `ignore` array for regex-based exclusion:

```php
Recorders\SlowQueries::class => [
    'ignore' => [
        '/(["`])pulse_[\w]+?\1/',    // Pulse tables
        '/(["`])telescope_[\w]+?\1/', // Telescope tables
        '/(["`])migrations\1/',       // Migration queries
    ],
],

Recorders\SlowRequests::class => [
    'ignore' => [
        '#^/'.env('PULSE_PATH', 'pulse').'$#',  // Pulse dashboard
        '#^/telescope#',                          // Telescope dashboard
        '#^/health-check#',                       // Health check endpoints
        '#\.(ico|css|js|png|jpg|svg|woff2?)$#',  // Static assets
    ],
],
```

## Custom Recorder Creation

### Pulse::record() Aggregation Types

`Pulse::record()` records a key-value pair and is always terminated by an aggregation method:

| Method | Behavior | Use Case |
|--------|----------|----------|
| `->count()` | Increments a counter for the key | Total counts, events per interval |
| `->avg()` | Records a value for averaging | Average response time, average load |
| `->max()` | Tracks the maximum value seen | Peak memory, max query time |
| `->min()` | Tracks the minimum value seen | Lowest queue wait time |
| `->sum()` | Accumulates a running total | Total bytes transferred, total cost |

Multiple aggregators can be chained on a single record call:

```php
Pulse::record('registrations_total', 'all', $total)->count()->avg()->max();
// Stores: count, rolling average, AND rolling max for the same metric
```

### Creating a Custom Recorder Class

Custom recorders live in `app/{Module}/Recorders/`. They do **not** need to implement any specific interface — any class can call `Pulse::record()`. Follow the existing `SystemRecorder` and `RegistrationRecorder` patterns:

```php
<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Recorders;

use App\Enrollment\Registration\Models\Registration;
use Laravel\Pulse\Facades\Pulse;

class RegistrationRecorder
{
    public array $listen = [];

    public static function recordSnapshot(): void
    {
        $total = Registration::count();
        $pending = Registration::whereStatus('pending')->count();
        $active = Registration::whereStatus('active')->count();
        $completed = Registration::whereStatus('completed')->count();

        Pulse::record('registrations_total', 'all', $total)->count()->avg()->max();
        Pulse::record('registrations_pending', 'all', $pending)->count()->avg()->max();
        Pulse::record('registrations_active', 'all', $active)->count()->avg()->max();
        Pulse::record('registrations_completed', 'all', $completed)->count()->avg()->max();
    }
}
```

### Recording with Tags

Attach tags for multi-dimensional filtering in the dashboard:

```php
Pulse::record('user_logins', auth()->user()->id, 1)
    ->withTags(['role:admin', 'source:web'])
    ->count();
```

### Recording with Timestamp

For historical or backfilled data, pass a custom timestamp as the last argument:

```php
Pulse::record('registrations_total', 'all', $total)
    ->withTimestamp(now()->subHour())
    ->count();
```

### Bulk Recording in Jobs

For expensive recordings, batch all `Pulse::record()` calls inside a single job:

```php
class RecordDailyMetricsJob implements ShouldQueue
{
    public function handle(): void
    {
        Pulse::record('daily_active_users', 'all', User::whereActive()->count())->count();
        Pulse::record('daily_new_internships', 'all', Internship::whereDateCreated(today())->count())->count();
        Pulse::record('daily_companies', 'all', Company::count())->count()->avg()->max();
        Pulse::record('daily_pending_approvals', 'all', Registration::pending()->count())->count();
    }
}
```

### Recorder Registration

Custom recorders do NOT go in `config/pulse.php` — that config is for built-in Pulse recorders only. Custom recorders are called explicitly from a scheduled command or event listener.

## Pulse::record() Patterns

### Basic Recording

```php
use Laravel\Pulse\Facades\Pulse;

Pulse::record('metric_name', 'key', $value)->count();
```

### Recording with All Aggregators

```php
Pulse::record('api_response_time', '/users', 342)->avg()->max()->min();
// Records 342ms as: rolling average, maximum ever, and minimum ever
```

### Recording Business Metrics

Record custom business KPIs from Command Actions (after the action log):

```php
public function execute(/* ... */): ActionResponse
{
    // ... business logic ...

    $this->log(...); // Standard action logging

    Pulse::record('internships_created', 'all', 1)->count();
    Pulse::record('students_placed', $studentId, 1)->count();

    return ActionResponse::success(...);
}
```

### Recording from Console Commands

```php
class PulseRecordSnapshotsCommand extends Command
{
    protected $signature = 'pulse:record-snapshots';

    public function handle(): int
    {
        RegistrationRecorder::recordSnapshot();
        SystemRecorder::recordSnapshot();
        return Command::SUCCESS;
    }
}
```

### Recording on Events

Fire-and-forget recording from event listeners:

```php
class LogRegistrationMetrics implements ShouldHandleEvents
{
    public function handle(RegistrationCompleted $event): void
    {
        Pulse::record('registrations_completed', 'all', 1)->count();
        Pulse::record('registrations_completed_today', date('Y-m-d'), 1)->count();
        Pulse::record('registrations_by_department', $event->registration->department_id, 1)->count();
    }
}
```

### Recording with Dynamic Keys

Use dynamic keys to track per-entity or per-category metrics:

```php
// Per-program metrics
Pulse::record('internships_by_program', 'program:'.$programId, 1)->count();

// Per-status distribution
Pulse::record('registration_breakdown', 'status:'.$registration->status, 1)->count();

// Per-day historical tracking
Pulse::record('daily_signups', now()->format('Y-m-d'), 1)->count();
```

## Dashboard Card Layout

The Pulse dashboard at `resources/views/vendor/pulse/dashboard.blade.php` uses a grid layout. Cards are placed inside `<x-pulse>` with `cols` and optional `rows` attributes:

```blade
<x-pulse>
    <livewire:pulse.servers cols="full" />
    <livewire:sys-admin.observability.system-card cols="4" rows="2" />
    <livewire:sys-admin.observability.registrations-card cols="4" rows="2" />
    <livewire:pulse.slow-queries cols="8" />
    <livewire:pulse.exceptions cols="6" />
    <livewire:pulse.slow-requests cols="6" />
    <livewire:pulse.slow-jobs cols="6" />
    <livewire:pulse.slow-outgoing-requests cols="6" />
</x-pulse>
```

### Grid Column Options

| `cols` | Width | Use Case |
|--------|-------|----------|
| `"full"` | Full row | Servers, top-level overviews |
| `"8"` | 2/3 width | Main content cards (queries, requests) |
| `"6"` | 1/2 width | Side-by-side paired cards |
| `"4"` | 1/3 width | Compact stat cards (3 per row) |
| `"3"` | 1/4 width | Small metrics cards (4 per row) |
| `"2"` | 1/6 width | Inline companions (rarely used alone) |

### Row Sizing

Cards can also specify `rows` to extend vertically:

```blade
{{-- Takes 2 grid rows for tall content --}}
<livewire:sys-admin.observability.system-card cols="4" rows="2" />
```

### Card Placement Order

Cards appear left-to-right, top-to-bottom in the CSS grid. Place most-important cards first:

1. **System overview** (top row) — Servers, custom summary cards
2. **Performance** (middle) — Slow queries, slow requests, slow jobs
3. **Errors & throughput** (bottom) — Exceptions, queues, cache, outgoing requests
4. **Custom business metrics** — Registration stats, user activity

### Collapsible Cards

Built-in Pulse cards collapse via the `x-pulse::card` component. Custom cards should use the same collapse behavior automatically.

### Card Headers with Filters

Built-in card filters (time range, per-page) are inherited from `Laravel\Pulse\Livewire\Card`. Custom cards can add filter slots:

```blade
<x-pulse::card wire:poll.5s="">
    <x-pulse::card-header name="Registrations">
        <x-slot:icon>
            <x-pulse::icons.users />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select wire:model.live="filter" :options="['all' => 'All', 'pending' => 'Pending']" />
        </x-slot:actions>
    </x-pulse::card-header>
    ...
</x-pulse::card>
```

## Alerting Configuration

Pulse supports programmatic alerting via `Pulse::alert()` to notify when a metric crosses a threshold.

### Setup in AppServiceProvider

Register alerts in the `boot()` method:

```php
use Laravel\Pulse\Facades\Pulse;

public function boot(): void
{
    Pulse::alert(function ($alerts) {
        $alerts->when(
            fn ($metrics) => ($metrics['slow_queries_avg'] ?? 0) > 5000,
            fn () => $alerts->notify('Average query time exceeded 5 seconds'),
        );
    });
}
```

### Alert Thresholds

Alert thresholds can reference Pulse's aggregated metric values:

```php
Pulse::alert(function ($alerts) {
    // Alert when exception rate spikes
    $alerts->when(
        fn ($metrics) => ($metrics['exceptions_count'] ?? 0) > 50,
        fn () => $alerts
            ->message('Exception rate > 50 in the last interval')
            ->channels(['mail', 'slack'])
            ->cooldown(300), // 5 minutes
    );

    // Alert on high queue wait time
    $alerts->when(
        fn ($metrics) => ($metrics['queues_wait_max'] ?? 0) > 300000,
        fn () => $alerts
            ->message('Queue wait time exceeded 5 minutes')
            ->channels(['slack']),
    );
});
```

### Alert Channels

Configure notification channels via the `message()` builder:

```php
$alerts->notify(function ($event) {
    Notification::route('slack', config('pulse.alerts.slack_webhook'))
        ->notify(new PulseAlertNotification($event));
});
```

Alternative: use Pulse's built-in notification routing or custom notifications:

```php
$alerts
    ->message('Critical: Slow queries detected')
    ->channels(['mail', 'slack'])
    ->cooldown(600); // 10 minutes between duplicate alerts
```

### Cooldown Periods

The `cooldown()` method (in seconds) prevents alert fatigue by suppressing duplicate notifications within the window. Default is 300 seconds (5 minutes).

### Custom Alert Conditions

Build complex conditions from multiple Pulse metrics:

```php
Pulse::alert(function ($alerts) {
    $alerts->when(
        function ($metrics) {
            $errorRate = ($metrics['exceptions_count'] ?? 0) / max(($metrics['requests_count'] ?? 1), 1);
            return $errorRate > 0.05; // 5% error rate threshold
        },
        fn () => $alerts
            ->message('Error rate exceeded 5%')
            ->cooldown(600),
    );
});
```

## Filtering & Sampling

### Pulse::filter() for Excluding Noise

Register filters in `AppServiceProvider::boot()` to exclude unwanted entries from storage:

```php
use Laravel\Pulse\Facades\Pulse;

public function boot(): void
{
    Pulse::filter(function ($entry) {
        // Exclude health check requests
        if (str_contains($entry->request?->url() ?? '', '/health-check')) {
            return false;
        }

        // Exclude Pulse's own requests
        if (str_contains($entry->request?->url() ?? '', '/pulse')) {
            return false;
        }

        // Exclude static assets
        if (preg_match('/\.(ico|css|js|png|jpg|svg|woff2?)$/', $entry->request?->url() ?? '')) {
            return false;
        }

        return true;
    });
}
```

Filters apply **globally** to all recorders. For recorder-specific filtering, use the `ignore` key in the recorder config.

### Pulse::resister() for Rate-Based Sampling

`Pulse::resister()` (note: the actual API method name — controls sampling rate) dynamically adjusts sampling:

```php
Pulse::resister(function ($entry) {
    // Only sample 10% of cache interactions
    if ($entry->type === 'cache_interaction') {
        return random_int(1, 100) <= 10;
    }
    return true;
});
```

**Important:** The actual Pulse API uses `sample_rate` in recorder config for static sampling. For dynamic sampling logic, inspect the entry type and return `true` (record) or `false` (skip).

### Pulse::ingest->only() for Selective Recording

Restrict Pulse ingest to specific entry types:

```php
// In AppServiceProvider::boot()
Pulse::ingest->only([
    'slow_query',
    'slow_request',
    'exception',
]);
```

This prevents all other entry types from being ingested.

### Ignoring Vendor Paths

Configure vendor path exclusion at the recorder level:

```php
Recorders\CacheInteractions::class => [
    'ignore' => [
        ...Pulse::defaultVendorCacheKeys(),
        '/^laravel:\*/',
        '/^illuminate:\*/',
    ],
],
```

`Pulse::defaultVendorCacheKeys()` returns keys automatically excluded like `illuminate:*`, `framework:*`, and `nova:*`.

### Ignoring Known-Good Patterns

For SlowQueries, exclude migrations, sessions, and cache queries:

```php
'ignore' => [
    '/(["`])pulse_[\w]+?\1/',
    '/(["`])migrations\1/',
    '/(["`])sessions\1/',
    '/(["`])cache[\w_]*?\1/',
    '/^select.*from.*information_schema/',
    '/^select.*from.*performance_schema/',
],
```

## Redis Ingest Optimization

### Configuration

Set `PULSE_INGEST_DRIVER=redis` to offload data storage from request workers:

```env
PULSE_INGEST_DRIVER=redis
PULSE_REDIS_CONNECTION=pulse
PULSE_INGEST_BUFFER=5000
```

### pulse:work Daemon

When using Redis ingest, a `pulse:work` process must run continuously to drain Pulse entries from the Redis stream into the storage driver:

```bash
php artisan pulse:work
```

Supervisor config (`/etc/supervisor/conf.d/pulse-worker.conf`):

```ini
[program:pulse-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan pulse:work --with-sleep
autostart=true
autorestart=true
numprocs=1
user=forge
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/pulse-worker.log
stopwaitsecs=3600
```

### Batch vs Immediate Ingest

| Driver | Behavior | Latency | Best For |
|--------|----------|---------|----------|
| `storage` (default) | Synchronous write on every request | Real-time | Low-traffic, single-server |
| `redis` | Buffered to Redis stream, drained by `pulse:work` | ~1-5s | High-traffic, multi-server |

### Ingest Buffer Tuning

```php
'ingest' => [
    'buffer' => env('PULSE_INGEST_BUFFER', 5_000), // Entries before flush
    'trim' => [
        'lottery' => [1, 1_000], // 0.1% chance per flush to trim old entries
        'keep' => env('PULSE_INGEST_KEEP', '7 days'),
    ],
],
```

Higher buffer values batch more entries per flush (better throughput, more memory). Lower values reduce memory but increase Redis calls.

### Redis Memory Considerations

Monitor Redis memory usage to avoid OOM issues:

```bash
redis-cli INFO memory | grep used_memory_human
redis-cli XLEN pulse_entries  # Check stream length
```

Configure Redis maxmemory-policy appropriately — Pulse streams benefit from `noeviction` or `allkeys-lru`:

```ini
# redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

### Redis Stream Names

Default stream names used by Pulse (visible in Redis for debugging):

```bash
redis-cli KEYS "pulse:*"
# -> pulse_entries
# -> pulse_aggregations
```

## Slow Request Profiling

### Configuring Slow Threshold

Set per-recorder thresholds via env vars or config:

```php
Recorders\SlowRequests::class => [
    'threshold' => env('PULSE_SLOW_REQUESTS_THRESHOLD', 1000), // milliseconds
],
```

### Grouping Patterns for URI Normalization

Use `groups` to normalize dynamic URIs into readable patterns:

```php
Recorders\SlowRequests::class => [
    'groups' => [
        '#^/internships/\d+$#' => '/internships/{id}',
        '#^/students/\d+/profile$#' => '/students/{id}/profile',
        '#^/api/v1/\w+/\d+$#' => '/api/v1/{resource}/{id}',
        '#/\d{4}-\d{2}-\d{2}#' => '/{date}',
    ],
],
```

### Ignoring Static Assets

Prevent asset requests from polluting the slow request view:

```php
'ignore' => [
    '#^/'.env('PULSE_PATH', 'pulse').'$#',
    '#^/telescope#',
    '#\.(ico|css|js|png|jpg|gif|svg|woff|woff2|ttf|eot)$#',
    '#^/vendor/#',
    '#^/build/assets/#',
],
```

### Request URI Normalization

Grouping transforms `/students/38293/profile?page=2` into readable `/students/{id}/profile`. This makes the dashboard useful for identifying slow endpoint patterns rather than individual requests.

### Custom Slow Request Conditions

Combine filtering with Pulse's `SlowRequests` recorder to skip certain methods or response statuses:

```php
Pulse::filter(function ($entry) {
    // Skip GET requests for slow request tracking
    if ($entry->type === 'slow_request' && request()?->method() === 'GET') {
        return false;
    }
    return true;
});
```

## User-Focused Tracking

### Per-User Recorder Filtering

Enable `UserJobs` and `UserRequests` recorders to associate metrics with authenticated users:

```php
Recorders\UserJobs::class => [
    'enabled' => env('PULSE_USER_JOBS_ENABLED', true),
],

Recorders\UserRequests::class => [
    'enabled' => env('PULSE_USER_REQUESTS_ENABLED', true),
],
```

### Filtering by Specific Users

Use `->onlyUsers()` concept (Pulse captures users automatically) — restrict recording to specific user types:

```php
Pulse::filter(function ($entry) {
    if ($entry->type === 'user_request') {
        $user = User::find($entry->key);
        // Only track admin users
        return $user?->hasRole('super_admin') ?? false;
    }
    return true;
});
```

### Associating Custom Metrics with Users

When recording custom metrics with user context, embed the user ID:

```php
Pulse::record('actions_by_user', auth()->id(), 1)->count();
Pulse::record('logins_by_role', 'role:'.$user->roles->first()?->name, 1)->count();
```

### Custom User Tracking Metrics

Track department-level or program-level user activity:

```php
// Per-department action tracking
Pulse::record('department_actions', $department->slug, 1)->count();

// Per-program enrollment tracking
Pulse::record('program_enrollments', 'program:'.$internship->program_id, 1)->count();
```

## Snapshot Command Pattern

Custom recorders are driven by a scheduled Artisan command:

```php
class PulseRecordSnapshotsCommand extends Command
{
    protected $signature = 'pulse:record-snapshots';
    protected $description = 'Record Internara-specific Pulse snapshots for custom dashboard cards';

    public function handle(): int
    {
        RegistrationRecorder::recordSnapshot();
        SystemRecorder::recordSnapshot();
        return Command::SUCCESS;
    }
}
```

Register in the scheduler (typically in `App\Console\Kernel`):

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('pulse:record-snapshots')->everyFiveMinutes();
}
```

Or trigger via the cron controller at `app/SysAdmin/Http/Controllers/CronController.php`:

```php
$exitCode = Artisan::call('pulse:record-snapshots');
$output['pulse:record-snapshots'] = $exitCode;
```

## Deployment & Operations

### pulse:check Supervisor Configuration

The `pulse:check` command collects server metrics (CPU, memory, disk). It must run continuously:

```ini
[program:pulse-check]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan pulse:check
autostart=true
autorestart=true
numprocs=1
user=forge
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/pulse-check.log
stopwaitsecs=3600
```

### pulse:restart on Deploy

Gracefully restart Pulse after every deployment to clear stale state:

```bash
php artisan pulse:restart
```

Add to deployment script (e.g., Envoyer, Forge, or custom deploy.sh):

```bash
php artisan down --retry=5
# ... deploy steps ...
php artisan migrate --force
php artisan pulse:restart
php artisan queue:restart
php artisan up
```

### pulse:clear for Data Lifecycle

Manually clear all Pulse data (use sparingly):

```bash
php artisan pulse:clear
```

For selective pruning, Pulse automatically trims old data via the `trim` config:

```php
'storage' => [
    'trim' => [
        'keep' => env('PULSE_STORAGE_KEEP', '7 days'),
    ],
],
```

The `system:cleanup` command (in `app/SysAdmin/Observability/Console/Commands/SystemCleanupCommand.php`) runs nightly and prunes Pulse records alongside activity logs, failed jobs, and notifications.

### Database Pruning

The storage driver automatically prunes entries on a lottery basis (`[1, 1_000]` means ~0.1% chance per flush). For the database driver:

```sql
-- Manual prune of Pulse entries older than 7 days
DELETE FROM pulse_entries WHERE created_at < NOW() - INTERVAL 7 DAY;
DELETE FROM pulse_aggregations WHERE period_end < NOW() - INTERVAL 7 DAY;

-- Check Pulse table sizes
SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = DATABASE() AND table_name LIKE 'pulse_%';
```

### Storage Cleanup

Monitor and clean Pulse storage directories:

```bash
# Database storage size (SQLite)
du -sh storage/*.sqlite

# Pulse entries count
php artisan tinker --execute="DB::table('pulse_entries')->count();"
php artisan tinker --execute="DB::table('pulse_aggregations')->count();"
```

## Performance Considerations

### Ingest Throughput Limits

| Driver | Approx Throughput | Bottleneck |
|--------|-------------------|------------|
| `storage` (DB) | ~500 entries/sec | DB write contention |
| `redis` | ~10,000 entries/sec | Redis memory + `pulse:work` speed |

### Recording High-Frequency Metrics

Avoid calling `Pulse::record()` inside hot loops or high-traffic request paths. Instead:

1. **Aggregate in memory first**, record once:

```php
// DON'T: Record per-item in a loop
foreach ($items as $item) {
    Pulse::record('items_processed', $item->id, 1)->count();
}

// DO: Aggregate then record once
Pulse::record('items_processed_count', 'batch', count($items))->count()->avg()->max();
```

2. **Use a debounce window** — write to cache, flush to Pulse periodically via a cron command.

3. **Record from queued jobs** rather than synchronously in request lifecycle.

### Database Storage vs Redis

| Storage Driver | Pro | Con |
|---------------|-----|-----|
| `database` | Simple, no extra infrastructure | Slower under high volume |
| `redis` | Fast ingest, offloads workers | Requires Redis + `pulse:work` process |

### Pulse Table Growth Estimation

| Metric | Per Entry Size | 100 req/s for 7 days | Total |
|--------|---------------|----------------------|-------|
| `pulse_entries` | ~200 bytes | ~1.2B entries | ~240 GB |
| `pulse_aggregations` | ~100 bytes | ~10K aggregations | ~1 MB |

**Reality:** Pulse uses aggregation, not raw entry storage. After aggregation, entries are trimmed. Typical production database growth is 10-100 MB per week depending on request volume and sample rates.

### Pruning Strategy

1. **Automatic pruning**: `trim.lottery` in config triggers periodic cleanup
2. **Scheduled pruning**: `system:cleanup` runs nightly (configured in `config/pulse.php` `storage.trim.keep`)
3. **Manual intervention**: `pulse:clear` for full reset, or direct DB queries for targeted cleanup

## Environment-Specific Configuration

### Dev vs Staging vs Production Recorder Sets

```env
# .env (development) — full visibility
PULSE_ENABLED=true
PULSE_SLOW_QUERIES_THRESHOLD=200
PULSE_SLOW_REQUESTS_THRESHOLD=500

# .env.staging — moderate visibility
PULSE_ENABLED=true
PULSE_SLOW_QUERIES_THRESHOLD=500
PULSE_SLOW_REQUESTS_THRESHOLD=1000
PULSE_SAMPLE_RATE=0.5

# .env.production — cautious, filtered
PULSE_ENABLED=true
PULSE_SLOW_QUERIES_THRESHOLD=1000
PULSE_SLOW_REQUESTS_THRESHOLD=2000
PULSE_CACHE_INTERACTIONS_ENABLED=false
PULSE_EXCEPTIONS_SAMPLE_RATE=0.1
```

### Feature Flags for Recorders

Toggle recorders conditionally based on environment or config:

```php
// config/pulse.php
Recorders\UserJobs::class => [
    'enabled' => env('PULSE_USER_JOBS_ENABLED', false),
],
Recorders\UserRequests::class => [
    'enabled' => env('PULSE_USER_REQUESTS_ENABLED', false),
],
```

### Environment-Specific Thresholds

```php
// In a service provider or Pulse config provider
$threshold = match (app()->environment()) {
    'production' => 2000,
    'staging' => 1000,
    default => 200,
};

config(['pulse.recorders.'.Recorders\SlowRequests::class.'.threshold' => $threshold]);
```

### Pulse Disabled in Testing

Pulse should be disabled during tests to avoid interfering with test assertions:

```env
# phpunit.xml
<env name="PULSE_ENABLED" value="false"/>
```

Alternatively, disable Pulse programmatically in service provider when testing:

```php
public function boot(): void
{
    if ($this->app->environment('testing')) {
        config(['pulse.enabled' => false]);
        return;
    }
    // ... Pulse configuration ...
}
```

## Integration with Custom Metrics

### Business Metrics via Pulse::record()

Record business KPIs alongside performance metrics:

```php
Pulse::record('registrations', 'all', 1)->count();
Pulse::record('internships_activated', 'all', 1)->count();
Pulse::record('certificates_issued', 'all', 1)->count();
```

### Recording from Command Actions

After the standard Action log call, add Pulse recording:

```php
class AssignStudentAction extends BaseCommandAction
{
    public function execute(Student $student, Internship $internship): ActionResponse
    {
        // Business logic...
        $placement = $this->createPlacement($student, $internship);

        $this->log()
            ->on($placement)
            ->withPayload(['student_id' => $student->id])
            ->save();

        // Pulse metric
        Pulse::record('students_placed', 'all', 1)->count();
        Pulse::record('placements_by_program', 'program:'.$internship->program_id, 1)->count();

        return ActionResponse::success($placement);
    }
}
```

### Recording from Console Commands (Cron-Based Reporting)

```php
class RecordWeeklyMetricsCommand extends Command
{
    protected $signature = 'pulse:record-weekly';

    public function handle(): int
    {
        Pulse::record('weekly_active_schools', 'count', School::whereActive()->count())->count()->avg();
        Pulse::record('weekly_new_companies', 'count', Company::whereMonthCreated(now()->month)->count())->count();
        Pulse::record('weekly_completed_internships', 'count', Internship::whereStatus('completed')->count())->count();
        return Command::SUCCESS;
    }
}
```

### Recording on Events

Leverage the event system for decoupled metric recording:

```php
class LogPlacementMetrics implements ShouldHandleEvents
{
    public function handle(StudentPlaced $event): void
    {
        Pulse::record('placements', 'all', 1)->count();
        Pulse::record('placements_by_department', $event->student->department_id, 1)->count();
        Pulse::record('placements_today', now()->format('Y-m-d'), 1)->count();
    }
}
```

## Custom Dashboard Card Implementation

### Card PHP Class

Custom Pulse cards extend `Laravel\Pulse\Livewire\Card` and should use the `#[Lazy]` attribute:

```php
<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Livewire\Pulse;

use App\Enrollment\Registration\Models\Registration;
use App\User\Models\User;
use Illuminate\View\View;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Url;

#[Lazy]
class RegistrationsCard extends Card
{
    #[Url(as: 'filter')]
    public string $filter = 'all';

    public function render(): View
    {
        $query = Registration::query();

        if ($this->filter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->filter === 'active') {
            $query->where('status', 'active');
        }

        return view('sysadmin.observability.pulse.registrations-card', [
            'total' => Registration::count(),
            'filtered' => $query->count(),
            'users' => User::count(),
        ]);
    }
}
```

### Card Blade View

```blade
<x-pulse::card wire:poll.5s="">
    <x-pulse::card-header name="Registrations">
        <x-slot:icon>
            <x-pulse::icons.users />
        </x-slot:icon>
        <x-slot:actions>
            <x-pulse::select wire:model.live="filter" :options="[
                'all' => 'All',
                'pending' => 'Pending',
                'active' => 'Active',
            ]" />
        </x-slot:actions>
    </x-pulse::card-header>

    <x-pulse::scroll :expand="$expand">
        <div class="grid grid-cols-2 gap-4 p-4">
            <div class="flex flex-col items-center rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Total</span>
            </div>
            <div class="flex flex-col items-center rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $filtered }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Filtered</span>
            </div>
        </div>
    </x-pulse::scroll>
</x-pulse::card>
```

### Card Registration

Custom cards are auto-discovered by `AppServiceProvider::discoverLivewireComponents()` when they live under `app/{Module}/Livewire/`. The Livewire component alias follows the `{kebab-module}.{kebab-submodule}.{kebab-class}` pattern. For example, `app/SysAdmin/Observability/Livewire/Pulse/SystemCard.php` becomes `sys-admin.observability.system-card`.

## Gateway & PulseGuard

PulseGuard lives at `app/SysAdmin/Observability/Services/PulseGuard.php` and provides an authorization guard layer for Pulse access. The dashboard lives under `/admin/pulse` and should never be publicly accessible. The `viewPulse` gate is the primary authorization mechanism.

## Common Mistakes

| Mistake | Symptom | Fix |
|---------|---------|-----|
| Forgetting `Pulse::alert()` cooldown | Alert fatigue, flooded notifications | Add `->cooldown(600)` to every alert |
| Recording metrics inside hot loops | Dashboard pollution, performance hit | Aggregate first, record once |
| Not filtering health-check routes | Dashboard polluted with health-check noise | Add health-check paths to `ignore` or `Pulse::filter()` |
| Missing `viewPulse` Gate in production | 403 on `/pulse` | Define `Gate::define('viewPulse', ...)` in `AppServiceProvider` |
| `pulse:work` not running with Redis ingest | Data never drains to storage | Add Supervisor config for `pulse:work` |
| Calling `Pulse::record()` without termination method | Data silently lost | Always chain `.count()`, `.avg()`, `.max()`, `.min()`, or `.sum()` |
| Using inline cache keys instead of `config/cache-keys.php` | Cache key collisions | Register all Pulse-related cache keys in `config/cache-keys.php` |
| Not disabling Pulse in tests | Test pollution, slow test runs | Set `PULGE_ENABLED=false` in `phpunit.xml` |
| Custom recorders not registered in scheduler | Metrics never recorded | Add `pulse:record-snapshots` (or equivalent) to `schedule()` |
| Running `pulse:check` as one-off command | Servers card empty | Configure as Supervisor daemon with `autorestart=true` |
| Not using URI grouping for SlowRequests | Dashboard full of individual dynamic URIs | Add `groups` to normalize `/students/{id}` patterns |
| Recording user metrics without authentication | `$user` is null | Guard user recording with `auth()->check()` |
| Setting `PULSE_INGEST_BUFFER` too low | Excessive Redis calls | Keep at 5000+ for production |
| No `pulse:restart` in deployment | Stale state after deploy | Add `php artisan pulse:restart` to deploy script |

## Verification

### Standard Verification

- [ ] Pulse migration has run? (`php artisan migrate --path=/vendor/laravel/pulse/database/migrations`)
- [ ] `viewPulse` Gate defined for production?
- [ ] `pulse:check` configured as Supervisor daemon?
- [ ] `pulse:work` running if Redis ingest configured?
- [ ] Custom cards registered and visible on dashboard?
- [ ] Recorders configured with appropriate thresholds and sample rates?
- [ ] All custom recorders have tests (`tests/Unit/SysAdmin/Observability/Recorders/`)
- [ ] Snapshot command registered in scheduler or cron controller?

### Recorder-Specific Verification

- [ ] Each custom recorder calls `Pulse::record()` with at least one aggregation method
- [ ] Recorder config excludes Pulse dashboard and health check paths from `ignore`
- [ ] URI grouping patterns normalize dynamic segments in SlowRequests/SlowOutgoingRequests
- [ ] Sample rates set below 1.0 for high-traffic environments
- [ ] `PULSE_ENABLED=false` set in `phpunit.xml` for testing

### Alert-Specific Verification

- [ ] `Pulse::alert()` registered in `AppServiceProvider::boot()`
- [ ] Each alert has a cooldown period to prevent notification fatigue
- [ ] Alert thresholds are reasonable for the environment
- [ ] Notification channels (email, Slack, Discord) are properly configured

### Deployment Verification

- [ ] `pulse:restart` added to deploy script
- [ ] Supervisor config for `pulse:check` and `pulse:work` (if Redis)
- [ ] Redis memory limits configured if using Redis ingest
- [ ] Storage pruning configured via `trim.keep` (default 7 days)
- [ ] Pulse data lifecycle integrated with `system:cleanup` command
