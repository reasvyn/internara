# AGENTS.md — Navigation Hub for AI Agents

Mental model, workflow, and navigation map for AI agents.
**Does NOT duplicate `docs/`** — points there for rules, patterns, and depth.

## Agent Workflow — Mandatory Steps

Every task MUST follow these 9 steps in order. **No step may be skipped.** If a step is
not applicable, explicitly note why and move on. Steps may be lightweight for simple tasks, but
they must never be omitted.

```
UNDERSTAND → DEFINE & SCOPE → EXPLORE & CONSTRUCT → PLAN → DESIGN → DEVELOP → TEST & VERIFY → DOCUMENT → COMMIT & REPORT
```

### 1. Understand Instruction

Internalize the user's **intent**, not just literal words. Clarify ambiguities. Identify constraints.
Output: clear restatement of the task, confirmed with user if ambiguous.

### 2. Define & Scope

Identify affected module(s), layer(s), files. Check dependencies. Output: scope statement.

### 3. Explore & Construct

Read the relevant docs (see Where to Find What below). Read existing code. Verify against source.
Output: complete understanding of existing patterns and code.

### 4. Plan

Consider 2+ approaches. Choose Action type, Entity boundaries, DTO structure, test strategy.
Output: implementation plan.

### 5. Design

Define class contracts before coding. Review against invariants (see Where to Find What).
Output: class signatures, data flow, error handling plan.

### 6. Develop

Write code matching the design. Follow conventions. Output: working code.

### 7. Test & Verify

Choose verification strategy (see `test-writing` skill). Write tests. Run linter + static analysis.
Output: all tests pass, linter clean.

### 8. Document

Update docs before/after code changes (documentation-first). Output: docs match code.

### 9. Commit & Report

Deliver report. Commit with conventional format. Output: clean commit, user informed.

---

## Project Identity

Self-hosted, single-tenant PKL management for Indonesian SMA/SMK (MIT).

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Frontend | Livewire 4, Alpine.js, maryUI 2, DaisyUI 5, Tailwind CSS v4 |
| Database | SQLite (default), MySQL 8+, MariaDB 10.6+, PostgreSQL 15+ |
| Testing | Pest 4, PHPStan (level 8), Laravel Pint |

---

## Skill Map — Which Skill to Load

| Task | Skill | Notes |
|------|-------|-------|
| First session, any task | `context-awareness` | Load first — project orientation |
| Writing PHP code | `code-writing` | Action Triad, Entity/DTO/Model contracts |
| Refactoring existing code | `code-refactoring` | Extract Actions, thin Livewire |
| Building a feature end-to-end | `feature-building` | Orchestrator — coordinates sub-skills |
| Livewire component | `livewire-development` | Component structure, reactivity |
| Writing/fixing tests | `pest-testing` | Test structure, mocking, coverage |
| Deciding verification strategy | `test-writing` | What to run, when, how much |
| Writing documentation | `doc-writing` | Two-tier model, metadata, PHPDoc |
| Syncing docs with code | `sync-docs` | Automated verification |
| Writing GitHub issues | `writing-issues` | Structured issue format |
| Security review | `security-audit` | OWASP, PII, auth patterns |
| Architecture audit | `audit-protocol` | Multi-layer codebase audit |
| Enforcing architecture rules | `arch-guard` | C1-C8, D1-D6, contracts, naming |
| Writing scripts | `script-automation` | Standards for `scripts/` devtools |
| CSS/styling | `tailwindcss-development` | Tailwind, DaisyUI, maryUI |
| File uploads/media | `medialibrary-development` | Spatie MediaLibrary |
| Laravel Pulse dashboard | `pulse-development` | Dashboard, recorders, cards |

---

## Module Quick Reference

| Module | Focus | Docs |
|--------|-------|------|
| **Core** | Base classes, contracts, exceptions, helpers | `docs/modules/core.md` |
| **Auth** | Login, password, activation, recovery, RBAC | `docs/modules/auth.md` |
| **User** | Profiles, notifications, account status, dashboards | `docs/modules/user.md` |
| **SysAdmin** | User admin, announcements, audit, health | `docs/modules/sysadmin.md` |
| **Setup** | One-time installation, environment, provisioning | `docs/modules/setup.md` |
| **Settings** | System config, branding, localization, feature toggles | `docs/modules/settings.md` |
| **Academics** | Departments, academic calendar | `docs/modules/academics.md` |
| **Program** | Internship programs, timelines, groups | `docs/modules/program.md` |
| **Enrollment** | Registration, placement, change requests | `docs/modules/enrollment.md` |
| **Assessment** | Rubrics, assessments, scoring frameworks | `docs/modules/assessment.md` |
| **Evaluation** | Feedback forms, sections, weighted questions | `docs/modules/evaluation.md` |
| **Assignment** | Course work, submissions, grading | `docs/modules/assignment.md` |
| **Journals** | Logbooks, attendance, absence requests | `docs/modules/journals.md` |
| **Guidance** | Mentoring, supervision logs | `docs/modules/guidance.md` |
| **Incident** | Incident reports, workplace concerns | `docs/modules/incident.md` |
| **Partners** | Companies, partnerships | `docs/modules/partners.md` |
| **Certification** | Certificate generation, credentials | `docs/modules/certification.md` |
| **Document** | Official document rendering, templates | `docs/modules/document.md` |
| **Reports** | Grade cards, archived snapshots | `docs/modules/reports.md` |

Full dependency graph: `docs/modules/index.md`

---

## Where to Find What

### Architecture & Patterns

| I need to know about... | Look at |
|-------------------------|---------|
| 4-Layer model | `docs/architecture.md` §4-Layer Model |
| Action Triad (Command/Read/Process) | `docs/architecture/action-pattern.md` |
| Entity contracts (`final readonly`) | `docs/architecture/entity-pattern.md` |
| DTO/Data contracts (`BaseData`) | `docs/architecture/data-pattern.md` |
| Model contracts (`#[Fillable]`, entity bridge) | `docs/architecture/model-pattern.md` |
| Enum contracts (LabelEnum, StatusEnum) | `docs/architecture/enum-pattern.md` |
| Event dispatch & listeners | `docs/architecture/event-pattern.md` |
| Exception hierarchy | `docs/architecture/exception-pattern.md` |
| Cache patterns | `docs/architecture/cache-pattern.md` |
| Logging patterns | `docs/architecture/logging-pattern.md` |
| Policy authorization | `docs/architecture/policy-pattern.md` |
| Livewire patterns | `docs/architecture/livewire-pattern.md` |
| Service registration | `docs/architecture/service-pattern.md` |
| Testing patterns | `docs/architecture/testing-pattern.md` |
| Modular architecture | `docs/architecture/modular-pattern.md` |

### Coding Conventions

| I need to know about... | Look at |
|-------------------------|---------|
| Critical invariants (C1-C8, D1-D6) | `docs/conventions.md` §Architecture Invariants |
| Naming conventions (files, classes, methods) | `docs/conventions.md` §Naming Conventions |
| Security (XSS, SQLi, CSRF, auth) | `docs/conventions.md` §Security Conventions |
| Database conventions (migrations, FKs) | `docs/conventions.md` §Database Conventions |
| Localization (`__()` usage) | `docs/conventions.md` §Localization |
| Testing conventions | `docs/conventions.md` §Testing Conventions |
| Doc conventions (metadata, PHPDoc) | `docs/conventions.md` §Documentation Conventions |
| Theming / form field icons | `docs/conventions.md` §Frontend Conventions |

### Specific Invariants

| Invariant | Where to find the full rule |
|-----------|----------------------------|
| C1 — No Model mutations in Livewire | `docs/architecture/action-pattern.md` §Non-Negotiable |
| C2 — No service locator (`app()->make`) | `docs/conventions.md` §Dependency Injection |
| C3 — No raw SQL without bindings | `docs/conventions.md` §SQL Injection Prevention |
| C4 — No inline cache keys | `docs/architecture/cache-pattern.md` §Registration |
| C5 — Entity forbidden imports | `docs/architecture/entity-pattern.md` §Non-Negotiable |
| C6 — DTO forbidden imports | `docs/architecture/data-pattern.md` §Non-Negotiable |
| C7 — DTO for 3+ params | `docs/architecture/action-pattern.md` §Command Action |
| C8 — RejectedException not RuntimeException | `docs/architecture/exception-pattern.md` §Usage |
| D1 — `declare(strict_types=1)` | `docs/conventions.md` §Strict Types |
| D2 — No debug calls | `docs/conventions.md` §Debug Calls |
| D3 — `__()` for user strings | `docs/conventions.md` §Localization |
| D4 — `#[Fillable]` attribute | `docs/architecture/model-pattern.md` §Non-Negotiable |
| D5 — No raw request to create/update | `docs/conventions.md` §Input Sanitization |
| D6 — FK with onDelete/onUpdate | `docs/conventions.md` §Database Conventions |

### Super Admin Rules

| Rule | Where to find |
|------|--------------|
| Name always `Administrator` | `docs/modules/setup.md` §Super Admin |
| Username always `superadmin` | `docs/modules/setup.md` §Super Admin |
| SetupSuperAdminAction signature | `docs/modules/setup.md` §Super Admin |
| InitializeSuperAdminAction uses config | `docs/modules/setup.md` §Super Admin |

### Reports Module Rules

| Rule | Where to find |
|------|--------------|
| Grade card only — no thesis content | `docs/modules/reports.md` §Boundary |
| Thesis belongs in Assignment module | `docs/modules/assignment.md` |

---

## Verification Strategy

**Batch ALL changes first, then verify ONCE.** Full suite is ~2GB+ memory, 10+ minutes.

| Change Type | Verification |
|-------------|-------------|
| Translation keys (`lang/*.php`) | `php -l` + `php artisan tinker --execute="echo __('key');"` |
| Config/docs/markdown | Visual inspection, no tests |
| Blade/CSS/JS | `npm run build` only |
| Refactoring (rename, extract) | Targeted test: `php artisan test --compact --filter={TestSuite}` |
| New feature / business logic | Full suite ONCE after all changes batched |
| Dependency updates | `vendor/bin/pest --testsuite={ModuleName}` (run affected module suites) |

```bash
# Targeted tests
vendor/bin/pest --testsuite={ModuleName}   # Run tests for a specific module (replace {ModuleName})
php artisan test --compact --filter={ClassName}
php -l path/to/file.php
php artisan system:health

# Full verification (after refactoring or before merge)
php artisan test --compact   # Run full test suite (all modules)
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress

# Architecture enforcement
python3 scripts/scan_violations.py         # C1-C8, D1-D6
python3 scripts/scan_class_contracts.py    # Action/Entity/DTO/Model/Enum
python3 scripts/scan_security.py           # XSS, SQLi, CSRF, auth
python3 scripts/scan_naming.py             # Naming conventions
python3 scripts/scan_conventions.py        # strict_types, Fillable, debug
python3 scripts/scan_doc_links.py          # Broken links in docs
```

---

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

---

## Quick Reference

### Dev Commands
```bash
composer run dev           # Serve + queue + logs + vite (concurrently)
composer run test          # Full suite (optimize:clear + test)
vendor/bin/pest --testsuite={ModuleName}  # Module-specific tests
composer run analyse       # PHPStan level 8
composer run quality       # Lint + analyse + module tests
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
