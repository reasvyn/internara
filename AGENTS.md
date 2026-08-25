# AGENTS.md — Navigation Hub for AI Agents

> **Last updated:** 2026-08-25 **Changes:** refactored into a navigation hub — rule bodies moved to .agents/rules/{rule}.md, loaded on demand

Mental model and navigation map for AI agents.
**Does NOT duplicate `docs/`** — points there for rules, patterns, and depth.
**Rule bodies live in `.agents/rules/{rule}.md`** — this file indexes them; load a rule file when
a task reaches its concern.

## Mandatory Loading Order — Every Instruction, No Exceptions

**Every instruction MUST run the full cycle** (`UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE`)
— any instruction, in any form: a one-line question, a bug report, a feature request, a docs tweak,
or an audit.

1. **Load the `agent-workflow` skill first** — canonical SSOT for the 5-step pipeline, narration
   discipline, phase classification (adaptive depth), size triage (S/M/L), and the L-size protocol.
2. **Load `context-awareness` second** — universal orientation layer; all other skills assume it.
3. **Load only the skills the task actually uses** from the Skill Map below — every skill load
   consumes context; an unneeded skill is cheap to skip, a bloated context is not.

**Narration discipline:** the pipeline runs silently — surface only ambiguities needing input,
scope/structure/behavior decisions, L-size session plans, pre-commit checkpoints, and the final
report.

**Commit every session** — always end a session with a commit as its checkpoint; commit format
`type(scope): description`.

---

## Rules Index — Load on Demand

| Rule file | Governs | Load when |
|-----------|---------|-----------|
| [`spec-first-doctrine`](.agents/rules/spec-first-doctrine.md) | Governing spec is SSOT; no behavior without a requirement ID | Every task — consult before planning |
| [`clean-code-dedup-align`](.agents/rules/clean-code-dedup-align.md) | DRY default, spec↔code↔docs↔tests alignment, surfacing structural decisions | Every task — during implement & review |
| [`computational-thinking`](.agents/rules/computational-thinking.md) | Four decision pillars + predict→act→verify→adjust loop | Ambiguous or multi-step instructions |
| [`documentation-split`](.agents/rules/documentation-split.md) | Human docs in `docs/`, AI assets in `.agents/`; directional referencing | Any documentation change |
| [`automation-first`](.agents/rules/automation-first.md) | Script batch work; reuse scanners; `/tmp` for throwaway scripts | Repetitive/batch operations; writing scripts |
| [`edit-policy`](.agents/rules/edit-policy.md) | Read-before-edit, surgical diffs, git lossless proof | Every code/doc edit |
| [`pre-existing-defects`](.agents/rules/pre-existing-defects.md) | Fix or file noticed warnings/errors; never silent tolerance | Warnings/errors encountered mid-task |
| [`verification-strategy`](.agents/rules/verification-strategy.md) | Batched verification, change-type matrix, scanner commands | Before running tests or quality gates |
| [`pre-commit-checklist`](.agents/rules/pre-commit-checklist.md) | Final gate before every commit | Immediately before each commit |

---

## Project Identity

Self-hosted, single-tenant PKL management for Indonesian SMA/SMK (MIT).

| Technology | Layer | Version |
|------------|-------|---------|
| PHP | Language | v8.4 |
| Laravel | Framework | v13.24 |
| Livewire | Frontend | v4.3 |
| Alpine.js | Frontend JS | — |
| Tailwind CSS | CSS | v4.3 |
| TallstackUI | UI Component | v4.3 |
| Flatpickr | Date Picker | v4.6 |
| Marked | Markdown Parser | v18.0 |
| Vite | Build Tool | v8.1 |
| laravel-vite-plugin | Build Plugin | v3.1 |
| SQLite | Database | — |
| MySQL | Database | v8.0 |
| MariaDB | Database | v10.6 |
| PostgreSQL | Database | v15.0 |
| barryvdh/laravel-dompdf | PDF Generation | v3.1 |
| laravel-lang/lang | Localization | v15.34 |
| Laravel Pulse | Monitoring | v1.8 |
| spatie/laravel-activitylog | Audit Log | v5.0 |
| spatie/laravel-medialibrary | Media Upload | v11.23 |
| spatie/laravel-model-status | Model Status | v1.18 — **deprecated, removal planned (#419); do not use in new code** |
| spatie/laravel-permission | RBAC | v8.0 |
| Pest | Testing | v4.2 |
| PHPStan | Static Analysis | v2.1 |
| Larastan | Laravel PHPStan | v3.10 |
| Laravel Pint | Code Style | v1.24 |
| Mockery | Mocking | v1.6 |
| Faker | Test Data | v1.23 |
| Collision | Error Handler | v8.6 |
| Laravel Tinker | REPL | v3.0 |
| Laravel Pail | Log Viewer | v1.2 |
| Laravel Sail | Docker Dev | v1.65 |
| Prettier | Formatter (non-PHP only) | v3.9 |
| prettier-plugin-blade | Blade Formatter (via Pint) | v3.2 |
| prettier-plugin-tailwindcss | Tailwind Class Sorter (via Pint) | v0.8 |
| concurrently | Task Runner | v10.0 |

---

## Project Definition

**Internara** is a self-hosted, single-tenant web application for managing compulsory industrial
fieldwork programs (PKL — _Praktik Kerja Lapangan_) at Indonesian vocational schools (SMA/SMK).

### Target Users

| Persona | Role |
|---------|------|
| **Students (Interns)** | Register, daily logbook, attendance, assignments, certificates |
| **Schools (Admin/Teacher)** | System config, enrollment, grading, supervision, reporting |
| **Companies (Supervisors)** | Attendance verification, logbook review, competency evaluation |

### Design Principles (3S Doctrine)

| Principle | Definition |
|-----------|------------|
| **S1 — Secure** | Enforce authorization at every layer, protect data integrity and PII |
| **S2 — Sustain** | Module colocation, Action single-responsibility, clear boundaries |
| **S3 — Scalable** | Single-tenant (no tenant-ID overhead), CQRS-inspired Action triad |

### Lifecycle Scope

Foundation → Configuration → Identity & Auth → Institutional → Partnerships → Programs → Enrollment → Daily Ops → Assessment → Certification → Reporting → Maintenance

### Out-of-Scope

Multi-tenant SaaS, HR/payroll, real-time chat, government DB sync (CSV import/export only).

Full definition: `docs/project-vision.md` (personas, system boundary, horizon) and `docs/philosophy.md` (3S Doctrine); condensed overview in `README.md`

---

## Skill Map — Which Skill to Load

| Task | Skill | Notes |
|------|-------|-------|
| Every instruction, any task | `agent-workflow` | **ALWAYS load first** — canonical workflow SSOT (5-step Understand → Plan → Implement → Verify → Summarize, size triage, verification) |
| Every instruction, any task | `context-awareness` | **ALWAYS load second** — universal orientation layer, no exceptions |
| Writing feature specs | `spec-writing` | 11-section spec template, requirements IDs |
| Writing PHP code | `code-writing` | Action Triad, Entity/DTO/Model contracts |
| Refactoring existing code | `code-refactoring` | Extract Actions, thin Livewire |
| Building a feature end-to-end | `feature-building` | Orchestrator — coordinates sub-skills |
| Laravel/architecture best practices | `laravel-best-practices` | Cross-cutting overrides for the Module-first Action architecture |
| Data architecture (schema, flow, security, contracts, DTO, mapping, formatting) | `data-architect` | Single source for any data-related task — schema, flow, security, interface/struct/type/enum/DTO, mapping, formatting |
| Livewire component | `livewire-development` | Livewire mechanics only — thin component, delegation, tables |
| General UI (Blade, layout, a11y, i18n, TallstackUI) | `ui-development` | General UI — Blade presentation, view structure, layout/responsive/dark mode, component library, accessibility, localization (delegates Tailwind details to tailwindcss-development) |
| Writing spec-driven tests | `pest-testing` | Each test traces to a spec FR/NFR; no orphan tests |
| Deciding verification strategy | `test-writing` | What to run, when, how much; spec-gap & orphan detection |
| Writing documentation | `doc-writing` | Two-tier model, metadata, PHPDoc |
| Syncing docs with code | `sync-docs` | Automated verification |
| Writing GitHub issues | `issue-writing` | Structured issue format |
| Security review | `security-audit` | OWASP, PII, auth patterns |
| Spec↔Code sync audit | `spec-audit` | Bidirectional spec-implementation verification |
| Independent QA audit | `qa-protocol` | Blind test against global standards (OWASP, ISO 25010, CWE, WCAG, PSR) |
| Enforcing architecture rules + ADR | `arch-guard` | C1-C8, D1-D6, contracts, naming, ADR staleness/linkage (docs/adr/*.md) |
| Writing scripts | `script-automation` | Standards for `scripts/` devtools |
| Tailwind CSS utilities & palette | `tailwindcss-development` | Tailwind CSS v4 only — utilities, @theme, semantic palette, no general UI |
| TallStackUI components | `tallstackui-development` | TallStackUI v4 — x-ts-* components, interactions (1:1 tallstackui/tallstackui) |
| Laravel framework | `laravel-development` | Laravel core — routing, container, Eloquent, validation (1:1 laravel/framework) |
| File uploads/media | `medialibrary-development` | Spatie MediaLibrary (1:1 spatie/laravel-medialibrary) |
| RBAC / Permission | `permission-development` | Spatie Permission — roles, @hasrole, policies (1:1 spatie/laravel-permission) |
| Audit trail | `activitylog-development` | Spatie Activity Log — audit, SmartLogger (1:1 spatie/laravel-activitylog) |
| PDF generation | `dompdf-development` | DOMPDF — Blade-to-PDF, assets (1:1 barryvdh/laravel-dompdf) |
| Build pipeline | `vite-development` | Vite — entry, plugins, HMR, build (1:1 vite) |
| Laravel Pulse dashboard | `pulse-development` | Dashboard, recorders, cards (1:1 laravel/pulse) |

---

## Module & Spec Reference

Full module list with docs: `docs/refs/modules/index.md`
Full spec list with build order: `docs/specs/index.md`

---

## Where to Find What

### Architecture & Patterns

| I need to know about... | Look at |
|-------------------------|---------|
| Project contexts (intentional states, deploy caveats, dependency pins, known issues) | `.agents/context/index.md` |
| Global agent rules (doctrines, policies, checklists) | `.agents/rules/` — see Rules Index above |
| 4-Layer model | `docs/architecture.md` §4-Layer Model |
| Action Triad (Command/Read/Process) | `docs/architecture/action-pattern.md` |
| SRP & modularity rules | `docs/architecture/modular-pattern.md` §1.6 |
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
