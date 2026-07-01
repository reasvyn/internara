---
name: pulse-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Laravel Pulse setup — dashboard, authorization, recorders, filters, custom cards, and Redis ingest.
upstream:
  - feature-building
downstream:
  - sync-docs
---

> **⚠️ Context Awareness Required:** Before following any instruction in this skill,
> read [context-awareness.md](context-awareness.md). Do NOT trust numbers, paths,
> class names, or method signatures without verifying them in the actual codebase.
> The codebase evolves independently of this document — verify, don't assume.
> **Rule:** If the skill says a number/path/name, verify it in the code first.


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
- **Architecture**: `docs/architecture.md#4-layer-architecture` (Layer 3 — Events/Listeners)
- **Observability**: `docs/infrastructure/observability.md` — Pulse overview, SmartLogger, health, cleanup
- **Logging**: `docs/architecture/logging-pattern.md` — SmartLogger, BaseAction::log(), event integration
- **Commands reference**: `references/commands.md` — recorder config, CLI commands, slow request profiling, snapshot commands, deployment, environment config, common mistakes
- **Patterns reference**: `references/patterns.md` — Pulse::record() patterns, card layout, alerting, filtering, Redis, user tracking, custom cards, performance, business metrics

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
