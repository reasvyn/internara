---
name: pulse-development
description: "SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized Laravel Pulse setup — dashboard, authorization, recorders, filters, custom cards, and Redis ingest."
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

Using this skill follows 4 phases (mapped to AGENTS.md 9-step: Construct = Steps 1-5, Execute = 6,
Verify = 7, Report & Commit = 8-9):

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- **Locate the governing spec** (`docs/specs/`) — confirm the NFR/UC IDs the Pulse setup serves
  (Spec-First Doctrine: no behavior without a requirement; if the spec is missing, write it first
  via `spec-writing`)
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- **Classify the size (S/M/L)** per AGENTS.md Size Triage; if **L**, inform the user and propose a
  session plan
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

- Run change-type-appropriate verification (see AGENTS.md Verification Strategy — not a fixed
  command set)
- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Verify with git: `git status` + `git diff` — confirm only intended files changed, nothing lost
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

## Skill Handoffs (Actionable)

| Condition | Action |
|-----------|--------|
| Writing a custom Pulse card | Load `livewire-development` — Pulse cards are Livewire components |
| Spec missing or incomplete | Load `spec-writing`, write/amend the spec, get user approval, then continue |
| Dashboard authorization | See `docs/architecture/policy-pattern.md` |
| Feature is **L** size | Split into sessions; inform user first |

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
| Observability overview | `docs/foundation/system-observability.md` |
| Deployment tiers       | `docs/infrastructure/deployment.md`    |
| Laravel Pulse docs     | `search-docs` with `laravel/pulse`     |
