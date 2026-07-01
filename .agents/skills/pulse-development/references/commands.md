# Pulse Development — Commands & Configuration Reference

> **Last updated:** 2026-07-01
> **Changes:** extracted from SKILL.md during file split

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
| Not disabling Pulse in tests | Test pollution, slow test runs | Set `PULSE_ENABLED=false` in `phpunit.xml` |
| Custom recorders not registered in scheduler | Metrics never recorded | Add `pulse:record-snapshots` (or equivalent) to `schedule()` |
| Running `pulse:check` as one-off command | Servers card empty | Configure as Supervisor daemon with `autorestart=true` |
| Not using URI grouping for SlowRequests | Dashboard full of individual dynamic URIs | Add `groups` to normalize `/students/{id}` patterns |
| Recording user metrics without authentication | `$user` is null | Guard user recording with `auth()->check()` |
| Setting `PULSE_INGEST_BUFFER` too low | Excessive Redis calls | Keep at 5000+ for production |
| No `pulse:restart` in deployment | Stale state after deploy | Add `php artisan pulse:restart` to deploy script |
