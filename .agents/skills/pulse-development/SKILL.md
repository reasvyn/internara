---
name: pulse-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Laravel Pulse setup — dashboard, authorization, recorders, filters, custom cards, and Redis ingest.
upstream:
  - feature-building
downstream:
  - sync-docs
---

# Pulse Development

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when configuring or extending Laravel Pulse — setting up the dashboard, adding
recorders, creating custom cards, or configuring Redis ingest.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Configure/Extend Pulse

- Configure Pulse recorders in config/pulse.php
- Set authorization: Gate::define('viewPulse', ...)
- Choose ingest driver according to deployment tier
- Create custom Pulse card if needed
- Test dashboard access for correct roles
- Output: Pulse configuration, authorization gate, recorder settings, and optional custom cards

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of Pulse configuration
    - Recorders enabled and thresholds
    - Authorization setup
- Feeds into: sync-docs (configuration documentation)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                           |
| -------------- | ----------------------------------------------- |
| **Upstream**   | `feature-building` (implementation flow)        |
| **This skill** | **IMPLEMENTATION (Sub-skill)** — Pulse-specific |
| **Downstream** | `sync-docs`                                     |

## Key Configuration

Pulse configuration lives in `config/pulse.php`. Key settings:

| Setting      | Purpose                                        |
| ------------ | ---------------------------------------------- |
| `domain`     | Pulse dashboard domain (restrict by subdomain) |
| `middleware` | Auth + authorization middleware group          |
| `recorders`  | Which recorders are enabled                    |
| `ingest`     | `redis` (production) or `file` (development)   |

## Authorization

Pulse access is controlled via `Gate::define('viewPulse', ...)` in
`app/Providers/AppServiceProvider.php`. Only users with `admin` or `superadmin` roles should have
access.

## Recorders

Enable recorders in `config/pulse.php`:

| Recorder               | What It Captures             |
| ---------------------- | ---------------------------- |
| `SlowRequests`         | Requests exceeding threshold |
| `SlowJobs`             | Slow queue jobs              |
| `SlowQueries`          | Slow database queries        |
| `SlowOutgoingRequests` | Slow HTTP calls              |
| `Exceptions`           | Exception frequency          |
| `Cache`                | Cache hit/miss ratio         |
| `UserSessions`         | Active user count            |

## Adding Custom Cards

Custom Pulse cards extend `Pulse\Livewire\Card`. Live in `app/Providers/PulseServiceProvider.php` or
as standalone Livewire components.

1. Create the card class extending `Card`
2. Register in `config/pulse.php` under `dashboard.cards`
3. Define authorization via `authorize()` method

## Verification Checklist

- [ ] Pulse dashboard accessible only by authorized roles
- [ ] Recorders configured for production ingest (Redis)
- [ ] Custom cards have proper authorization
- [ ] Ingest configured appropriately for the deployment tier
- [ ] Pulse data retention set in config

## References

| Topic                  | Doc                                    |
| ---------------------- | -------------------------------------- |
| Pulse configuration    | `config/pulse.php`                     |
| Observability overview | `docs/infrastructure/observability.md` |
| Deployment tiers       | `docs/infrastructure/deployment.md`    |
| Laravel Pulse docs     | `search-docs` with `laravel/pulse`     |
