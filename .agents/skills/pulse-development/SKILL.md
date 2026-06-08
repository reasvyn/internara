---
name: pulse-development
description: Apply this skill when setting up Laravel Pulse, configuring the dashboard or authorization gate, defining recorders and filters, building custom Pulse cards, or optimizing with Redis ingest. Activates when the task involves /pulse, pulse:check, pulse:work, Pulse::record(), or application monitoring.
---

# Laravel Pulse Development Skill

## When to Activate

Apply this skill when setting up Laravel Pulse, configuring the dashboard or authorization gate, defining recorders and filters, building custom Pulse cards, or optimizing with Redis ingest. Activates for `/pulse`, `pulse:check`, `pulse:work`, `Pulse::record()`, or application monitoring.

## Key References

- **Pulse config**: `config/pulse.php` — recorder configuration, storage, ingest, caching
- **SystemCard**: `app/SysAdmin/Observability/Livewire/Pulse/SystemCard.php` — custom card (users, unread notifications)
- **RegistrationsCard**: `app/SysAdmin/Observability/Livewire/Pulse/RegistrationsCard.php` — custom card (registration stats)
- **Architecture**: `docs/architecture.md#layered-architecture` (Layer 9 — Communications)

## Dashboard Authorization

The `/pulse` dashboard requires a `viewPulse` Gate for production access:

```php
Gate::define('viewPulse', function (User $user) {
    return $user->hasRole('super_admin');
});
```

Without this gate, the dashboard is only visible in local environments.

## Configured Recorders (6 active)

Pulse is configured via `config/pulse.php` with the following recorders:

| Recorder | Config Key | Threshold | Notes |
|----------|-----------|-----------|-------|
| `CacheInteractions` | `PULSE_CACHE_INTERACTIONS_ENABLED` | — | Ignores vendor cache keys |
| `Exceptions` | `PULSE_EXCEPTIONS_ENABLED` | — | Location tracking enabled |
| `Queues` | `PULSE_QUEUES_ENABLED` | — | — |
| `Servers` | — | — | Requires `pulse:check` daemon |
| `SlowJobs` | `PULSE_SLOW_JOBS_ENABLED` | 1000ms | Configurable threshold |
| `SlowOutgoingRequests` | `PULSE_SLOW_OUTGOING_REQUESTS_ENABLED` | 1000ms | Grouping patterns available |
| `SlowQueries` | `PULSE_SLOW_QUERIES_ENABLED` | 1000ms | Max query length configurable |
| `SlowRequests` | `PULSE_SLOW_REQUESTS_ENABLED` | 1000ms | Ignores Pulse dashboard path |
| `UserJobs` | `PULSE_USER_JOBS_ENABLED` | — | Per-user job tracking |
| `UserRequests` | `PULSE_USER_REQUESTS_ENABLED` | — | Per-user request tracking |

Recorders can be toggled independently via environment variables. Sample rates and thresholds are also configurable per environment.

## Custom Cards

Custom Pulse cards extend `Laravel\Pulse\Livewire\Card` and live in `app/{Module}/Livewire/Pulse/`:

```php
#[Lazy]
class SystemCard extends Card
{
    public function render(): View
    {
        return view('sysadmin.observability.pulse.system-card', [
            'users' => User::count(),
            'unreadNotifications' => Notification::where('is_read', false)->count(),
        ]);
    }
}
```

Card views live at `resources/views/{module}/pulse/{card-name}.blade.php`. Custom recorders use `Pulse::record()` with key-value aggregation and are registered in `config/pulse.php`.

## Operational Requirements

| Command | Purpose | Requires |
|---------|---------|----------|
| `pulse:check` | Daemon for Servers card | Supervisor config |
| `pulse:work` | Drain Redis stream | `PULSE_INGEST_DRIVER=redis` |
| `pulse:restart` | Graceful restart on deploy | — |
| `pulse:clear` | Clear stored data | — |

## Data Filtering

```php
Pulse::filter(function ($entry) {
    return !str_contains($entry->request?->url() ?? '', '/health-check');
});
```

Filters reduce both storage volume and dashboard noise. Apply in `AppServiceProvider::boot()`.

## Custom Dashboard View

Override `resources/views/vendor/pulse/dashboard.blade.php` to arrange cards:

```blade
<x-pulse>
    <livewire:pulse.servers cols="full" />
    <livewire:sysadmin.observability.system-card cols="2" />
    <livewire:sysadmin.observability.registrations-card cols="2" />
    <livewire:pulse.slow-queries cols="4" />
</x-pulse>
```

## Verification

- Pulse migration has run?
- `viewPulse` Gate defined for production?
- `pulse:check` configured as Supervisor daemon?
- `pulse:work` running if Redis ingest configured?
- Custom cards registered and visible on dashboard?
- Recorders configured with appropriate thresholds and sample rates?
