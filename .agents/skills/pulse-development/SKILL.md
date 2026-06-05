# Laravel Pulse Development Skill

## When to Activate

Apply this skill when setting up Laravel Pulse, configuring the dashboard or authorization gate, defining recorders and filters, building custom Pulse cards, or optimizing with Redis ingest. Activate when the task involves `/pulse`, `pulse:check`, `pulse:work`, `Pulse::record()`, or application monitoring.

## Core Principles

### Dashboard Authorization

The `/pulse` dashboard is accessible by default only in local environments. For production access, a `viewPulse` Gate must be defined — typically restricted to `super_admin` role. Without this gate, the dashboard remains invisible in non-local environments.

### Recorders

Pulse ships with recorders for cache interactions, exceptions, queues, slow jobs, slow outgoing requests, slow queries, slow requests, servers, user jobs, and user requests. Each recorder has configurable sample rates, thresholds, and ignore patterns. Per-route thresholds allow different latency tolerances for different endpoints.

### Data Filtering

The `Pulse::filter()` callback can exclude entries from recording. This is useful for filtering out internal traffic, health checks, or bot requests. The filter runs before storage, so it reduces database volume as well.

## Custom Card Development

Custom Pulse cards are Livewire components extending `Laravel\Pulse\Livewire\Card`. They live in `app/{Module}/Livewire/Pulse/`. Data is recorded using `Pulse::record()` with key-value aggregation (sum, count, max, min, avg). Card views are at `resources/views/{domain}/pulse/{card-name}.blade.php`.

Custom recorders are plain classes with a `$listen` array of events. They call `Pulse::record()` in their `record()` method and are registered in `config/pulse.php`.

### Dashboard Blade

The Pulse dashboard view can be overridden at `resources/views/vendor/pulse/dashboard.blade.php` to arrange cards in a grid layout using `cols` and `rows` attributes.

## Operational Requirements

`pulse:check` must run as a persistent daemon (via Supervisor) for the Servers card to work. If using Redis ingest (`PULSE_INGEST_DRIVER=redis`), `pulse:work` must also run to drain the Redis stream. `pulse:restart` triggers graceful restarts on deploy. All require a working cache driver.

## Verification Before Finalizing

- Has the Pulse migration run?
- Is a `viewPulse` Gate defined for production?
- Is `pulse:check` configured as a Supervisor daemon?
- Is `pulse:work` running if Redis ingest is configured?
- Are custom cards registered and visible on the dashboard?
- Are recorders configured with appropriate thresholds and sample rates?
