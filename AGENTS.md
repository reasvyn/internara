# AGENTS.md — Project Guidelines for AI Agents

Essential mental model, non-negotiable rules, and quick-reference for AI agents.
Does NOT duplicate content in `docs/` — refer there for depth.

## Agent Onboarding

1. Load `context-awareness` skill first
2. Read `docs/architecture.md` (4-layer model, Action Triad, dependency rules)
3. Read `docs/conventions.md` (coding standards, naming, security)
4. Read Critical Invariants below — rules that MUST NOT be violated
5. Read relevant pattern docs before writing code in that area

## Project Identity

Self-hosted, single-tenant PKL management for Indonesian SMA/SMK (MIT).

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Frontend | Livewire 4, Alpine.js, maryUI 2, DaisyUI 5, Tailwind CSS v4 |
| Database | SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+ |
| Testing | Pest 4, PHPStan (level 8), Laravel Pint |

19 modules, each colocated under `app/{Module}/` owning its full stack.

## Architecture Compass

### 4-Layer Model (strict downward dependency)

| Layer | Content | Location Prefix |
|-------|---------|-----------------|
| 4 — Presentation/UI | Livewire, Blade, Policies, Routes | `{Module}/Livewire/`, `routes/web/` |
| 3 — Business/Domain Ops | Command/Read/Process Actions, Events | `{Module}/Actions/`, `{Module}/Events/` |
| 2 — Data/Persistent | Models, Entities (`final readonly`), DTOs (`BaseData`), Enums | `{Module}/Models/`, `{Module}/Entities/` |
| 1 — Framework/Infra | Core base classes, Contracts, Exceptions, Services | `app/Core/`, `{Module}/Services/` |

### Action Triad

| Type | Base | Transaction | Log | Events | Purpose |
|------|------|-------------|-----|--------|---------|
| **Command** | `BaseCommandAction` | ✅ | ✅ | Optional | All mutations (CUD, state transitions) |
| **Read** | `BaseReadAction` | ❌ | ❌ | ❌ | Complex queries, aggregation (does NOT extend BaseAction) |
| **Process** | `BaseProcessAction` | ✅ | ✅ | Optional | Multi-step orchestration |

**Key rules:**
- Exactly one public method: `execute()`
- No `Model::create/update/delete` in Livewire — always use Command Actions
- Accept `BaseData` DTO for 3+ params (typed scalars OK for 1-2). Never raw `array`
- Return `ActionResponse` for structured feedback
- Delegate business rules to Entities — throw `RejectedException` on violation
- Events queued via `$this->dispatchEvent()` (auto-flushed after transaction commits). Do NOT call `$event::dispatch()` directly in Actions

## Critical Invariants

### Architecture
| # | Rule |
|---|------|
| C1 | No `Model::create/update/delete` in Livewire — use Command Actions |
| C2 | No `app()->make()` / `resolve()` — use constructor injection |
| C3 | No `DB::raw()` / `whereRaw()` without parameterized binding |
| C4 | No inline cache keys — register in `config/cache-keys.php` |
| C5 | Entities must NOT import Actions, Services, Livewire, Controllers |
| C6 | DTOs must NOT import Models, Entities, Actions — Core BaseData, scalars, enums, Carbon only |
| C7 | Command/Process Actions: accept DTO for 3+ params, return ActionResponse |
| C8 | Business rules → `RejectedException`, not `RuntimeException` |

### Super Admin
| # | Rule |
|---|------|
| S1 | Name ALWAYS `Administrator` (config `setup.defaults.admin_name`) |
| S2 | Username ALWAYS `superadmin` (config `setup.defaults.admin_username`) |
| S3 | `SetupSuperAdminAction::execute()` accepts ONLY `(string $email, string $password)` |
| S4 | `InitializeSuperAdminAction` uses config defaults, NOT caller-provided values |

### Reports Module
| # | Rule |
|---|------|
| R1 | Grade card only — final scores, grade letter, archived snapshot |
| R2 | NEVER add thesis/final report content to `app/Reports/` |
| R3 | Student thesis belongs in `app/Assignment/` |

### Coding
| # | Rule |
|---|------|
| D1 | `declare(strict_types=1)` in ALL PHP files except migrations/config |
| D2 | No `dd/dump/ray/var_dump/print_r/die` in committed code |
| D3 | All user-facing strings use `__()` — both `lang/en/` and `lang/id/` |
| D4 | Models use `#[Fillable]` attribute (PHP 8.4), NOT `$fillable` / `$guarded` |
| D5 | Never pass raw request input to `create()`/`update()` — use `->only()` or `->toArray()` |
| D6 | Foreign keys use `foreignUuid()->constrained('{table}')` with explicit `onDelete()`/`onUpdate()` |

## Verification Strategy

**Batch ALL changes first, then verify ONCE.** Full suite is ~2GB+ memory, 10+ minutes.

| Change Type | Verification |
|-------------|-------------|
| Translation keys (`lang/*.php`) | `php -l` + `php artisan tinker --execute="echo __('key');"` |
| Config/docs/markdown | Visual inspection, no tests |
| Blade/CSS/JS | `npm run build` only |
| Refactoring (rename, extract) | Targeted test: `php artisan test --compact --filter={TestSuite}` |
| New feature / business logic | Full suite ONCE after all changes batched |
| Dependency updates | `php artisan test --compact --testsuite=Feature` |

```bash
# Targeted tests
php artisan test --compact --testsuite=Feature
php artisan test --compact --testsuite=Unit
php artisan test --compact --filter={ClassName}
php -l path/to/file.php
php artisan system:health

# Full verification (after refactoring or before merge)
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress
```

## Pre-commit Checklist
- [ ] `declare(strict_types=1)` present
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`)
- [ ] All user-facing strings use `__()`
- [ ] Action uses correct triad base class
- [ ] Command/Process: DTO for 3+ params, returns ActionResponse
- [ ] Business rules delegated to Entity (not inline in Action)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] No unescaped `{!! !!}` for user content
- [ ] `php artisan test --compact` passes
- [ ] `vendor/bin/pint --dirty --format agent` clean
- [ ] `vendor/bin/phpstan analyse --no-progress` passes
- [ ] Relevant docs updated (documentation-first approach)

## Quick Reference

### Dev Commands
```bash
composer run dev           # Serve + queue + logs + vite (concurrently)
composer run test          # Full suite (optimize:clear + test)
composer run test:feature  # Feature tests only
composer run test:unit     # Unit tests only
composer run analyse       # PHPStan level 8
composer run quality       # Lint + analyse + feature tests
php artisan system:health  # Health check
php artisan admin:recover  # Super admin CLI recovery
php artisan setup:install  # Audits env, runs migrations, seeds defaults
npm run build              # Vite build (check frontend)
```

### Commit Format
`type(scope): description` — `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`

### Branch Naming
`feat/{kebab}`, `fix/{desc}`, `refactor/{module}-{scope}`, `docs/{what}`, `chore/{task}`, `hotfix/{desc}`

### Language
**English only** — code, comments, commits, docs. Indonesian only in `lang/id/`.
