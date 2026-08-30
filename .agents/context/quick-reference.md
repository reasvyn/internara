# Quick Reference

> **Curated mandatory known context** — dev commands, commit format, branch naming, and navigation map. Read at start of every session.

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
**English only** — code, comments, commits, docs. Indonesian only in `lang/id/`. **AI Agent:** English-only for all artifacts and internal work, but direct communication with the user must follow the user's language.

---

## Where to Find What

### Architecture & Patterns

| I need to know about... | Look at |
|-------------------------|---------|
| Project contexts (intentional states, deploy caveats, dependency pins, known issues) | `.agents/context/index.md` |
| Global agent rules (doctrines, policies, checklists) | `.agents/rules/` — see Rules Index above |
| 4-Layer model | `docs/architecture.md` §4-Layer Model |
| Action Triad (Command/Read/Process) | `docs/guides/arch/action-pattern.md` |
| SRP & modularity rules | `docs/guides/arch/modular-pattern.md` §1.6 |
| Entity contracts (`final readonly`) | `docs/guides/arch/entity-pattern.md` |
| DTO/Data contracts (`BaseData`) | `docs/guides/arch/data-pattern.md` |
| Model contracts (`#[Fillable]`, entity bridge) | `docs/guides/arch/model-pattern.md` |
| Enum contracts (LabelEnum, StatusEnum) | `docs/guides/arch/enum-pattern.md` |
| Event dispatch & listeners | `docs/guides/arch/event-pattern.md` |
| Exception hierarchy | `docs/guides/arch/exception-pattern.md` |
| Cache patterns | `docs/guides/arch/cache-pattern.md` |
| Logging patterns | `docs/guides/arch/logging-pattern.md` |
| Policy authorization | `docs/guides/arch/policy-pattern.md` |
| Livewire patterns | `docs/guides/arch/livewire-pattern.md` |
| Service registration | `docs/guides/arch/service-pattern.md` |
| Testing patterns | `docs/guides/arch/testing-pattern.md` |
| Modular architecture | `docs/guides/arch/modular-pattern.md` |
| Repository (why none) | `docs/guides/arch/repository-pattern.md` |
| Support utilities | `docs/guides/arch/support-pattern.md` |
| UI pattern (visual design) | `docs/guides/arch/ui-pattern.md` |
| UX pattern (a11y, i18n, flow) | `docs/guides/arch/ux-pattern.md` |

### Feature Specs

| I need to know about... | Look at |
|-------------------------|---------|
| Feature spec index | `docs/specs/index.md` |
| Spec template & conventions | `.agents/skills/spec-writing/SKILL.md` |
| Writing a new spec | Load `spec-writing` skill |

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
| C1 — No Model mutations in Livewire | `docs/guides/arch/action-pattern.md` §Non-Negotiable |
| C2 — No service locator (`app()->make`) | `docs/conventions.md` §Dependency Injection |
| C3 — No raw SQL without bindings | `docs/conventions.md` §SQL Injection Prevention |
| C4 — No inline cache keys | `docs/guides/arch/cache-pattern.md` §Registration |
| C5 — Entity forbidden imports | `docs/guides/arch/entity-pattern.md` §Non-Negotiable |
| C6 — DTO forbidden imports | `docs/guides/arch/data-pattern.md` §Non-Negotiable |
| C7 — DTO for 3+ params | `docs/guides/arch/action-pattern.md` §Command Action |
| C8 — RejectedException not RuntimeException | `docs/guides/arch/exception-pattern.md` §Usage |
| D1 — `declare(strict_types=1)` | `docs/conventions.md` §Strict Types |
| D2 — No debug calls | `docs/conventions.md` §Debug Calls |
| D3 — `__()` for user strings | `docs/conventions.md` §Localization |
| D4 — `#[Fillable]` attribute | `docs/guides/arch/model-pattern.md` §Non-Negotiable |
| D5 — No raw request to create/update | `docs/conventions.md` §Input Sanitization |
| D6 — FK with onDelete/onUpdate | `docs/conventions.md` §Database Conventions |

### Super Admin Rules

| Rule | Where to find |
|------|--------------|
| Name always `Super Admin` | `docs/refs/modules/setup.md` §Super Admin |
| Username always `superadmin` | `docs/refs/modules/setup.md` §Super Admin |
| SetupSuperAdminAction signature | `docs/refs/modules/setup.md` §Super Admin |
| InitializeSuperAdminAction uses config | `docs/refs/modules/setup.md` §Super Admin |

### Reports Module Rules

| Rule | Where to find |
|------|--------------|
| Grade card only — no thesis content | `docs/refs/modules/reports.md` §Boundary |
| Thesis belongs in Assignment module | `docs/refs/modules/assignment.md` |

---
*Source: AGENTS.md §Quick Reference & §Where to Find What.*