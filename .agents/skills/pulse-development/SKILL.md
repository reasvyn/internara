---
name: pulse-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Laravel Pulse setup — dashboard, authorization, recorders, filters, custom cards, and Redis ingest."
upstream:
  - feature-building
downstream:
  - sync-docs
---

# Pulse Development

> **Last updated:** 2026-08-17 **Changes:** extracted inline rules (§Key Configuration, §Authorization, §Recorders, §Adding Custom Cards, §Verification Checklist) into `rules/` rule assets with a `## Skill Rules` mapping section

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when configuring or extending Laravel Pulse — setting up the dashboard, adding
recorders, creating custom cards, or configuring Redis ingest.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting), verification
strategy, and commit format. This skill adds Laravel Pulse guidance — dashboard, authorization,
recorders, custom cards, and ingest — nothing else.

### Execute — Configure/Extend Pulse

- Configure Pulse recorders in `config/pulse.php`
- Set authorization: `Gate::define('viewPulse', ...)`
- Choose ingest driver according to deployment tier
- Create custom Pulse card if needed
- Test dashboard access for correct roles

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

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Dashboard & configuration (domain, middleware, recorders, ingest, retention) | `rules/dashboard-configuration.md` | Setting up or changing Pulse config |
| Authorization (viewPulse Gate, admin/superadmin-only) | `rules/authorization.md` | Granting or testing dashboard access |
| Recorders & ingest (recorder set, production Redis ingest) | `rules/recorders-and-ingest.md` | Choosing what Pulse captures and how |
| Custom cards (Card extension, registration, authorize) | `rules/custom-cards.md` | Building a custom dashboard card |

## References

| Topic                  | Doc                                    |
| ---------------------- | -------------------------------------- |
| Pulse configuration    | `config/pulse.php`                     |
| Observability overview | `docs/foundation/system-observability.md` |
| Deployment tiers       | `docs/infrastructure/deployment.md`    |
| Laravel Pulse docs     | `search-docs` with `laravel/pulse`     |
