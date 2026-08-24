# AGENTS.md — Navigation Hub for AI Agents

> **Last updated:** 2026-08-23 **Changes:** architecture table — added SRP & modularity rules row (Modular Pattern §1.6)

Mental model, workflow, and navigation map for AI agents.
**Does NOT duplicate `docs/`** — points there for rules, patterns, and depth.

## Agent Workflow — Mandatory Steps

**Every instruction MUST run the full cycle. No step may be skipped.** This applies to
**any instruction, in any form** — a one-line question, a bug report, a feature request, a docs
tweak, or an audit. Steps are **adaptive**: their depth scales with the instruction's SDLC phase.

**ALWAYS load the `agent-workflow` skill first** — on every instruction, before any other skill. It
is the single source of truth for the workflow: the 5-step pipeline
(Understand → Plan → Implement → Verify → Summarize), narration discipline, phase classification
(adaptive depth), size triage (S/M/L session splitting), verification strategy, and the L-size
protocol. Do NOT restate the workflow in other skills — reference `agent-workflow` instead.

```
UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE
```

### Narration & Context Discipline — Non-Negotiable

The pipeline runs **silently**. The 5 steps are internal reasoning — do not narrate them, do not
restate the task, do not list the steps you took, do not explain reasoning already visible in your
tool use. Surface to the user **only**:

1. Ambiguity that needs their input.
2. A decision that changes scope, structure, or behavior.
3. An L-size session plan (one short paragraph).
4. One checkpoint before commit (M-size) or per-session (L-size).
5. The final report (what changed, what was verified, caveats).

Every sentence sent to the user must carry new information or a decision; if it does neither,
drop it. This keeps responses short and context usage low.

### Spec-First Doctrine — Non-Negotiable

**Every action, on every instruction, in any form, must be driven by the governing spec.**
`docs/specs/*.md` is the single authoritative source of truth — it defines intent, requirements
(FR/NFR/UC IDs), scope, and acceptance criteria. The spec outranks the user's literal words,
ad-hoc reasoning, and existing code. This applies to every task type: bug fixes, features,
refactors, tests, docs updates, audits, scripts, config tweaks, maintenance, and one-line
questions alike.

- **Consult the spec before any work:** locate the governing spec (foundation, module, or
  feature) in Step 1 and treat it as the source of truth for what "done" means.
- **No behavior without a requirement:** never change behavior, add features, or fix bugs
  without a corresponding requirement ID (FR/NFR/UC). If none exists, write it into the spec
  first — spec-first, never fix-first.
- **Code and spec disagree?** The spec is authoritative. Align code to the spec
  ("fix code, assert spec"). If the spec is demonstrably wrong, amend the spec with a recorded
  decision first, then align the code and tests.
- **Tests assert the spec:** every test traces to a requirement ID (spec-driven testing); no
  orphan tests, no spec gaps (see Verification Strategy).
- **Docs reflect the spec:** `docs/` and module docs stay in sync with specs and code.
- Failing to consult or follow the governing spec — for any instruction — is a workflow violation.

### Clean Code & Dedup-Align Doctrine — Non-Negotiable

**Every instruction must leave the touched content and code deduplicated, aligned, and clean.**

- **Deduplicate & align by default:** wherever the agent detects duplication or inconsistency —
  repeated code, copy-pasted docs, divergent patterns, stale references, duplicated requirements —
  deduplicate and align it as part of the work, even when the instruction did not ask for it
  explicitly. Never introduce a second copy of something that already exists; reuse or extract.
- **CLEAN CODE, DRY first:** apply clean-code principles with the **DRY** principle as the default
  bias — extract repeated logic into shared, named, modular units (helper, trait, Action, DTO,
  entity, doc cross-reference). Prefer **more, smaller, well-named modules** over one dense blob;
  modularization is not over-engineering, it is the prescribed direction.
- **Align spec ↔ code ↔ docs ↔ tests:** when one side drifts from the others, align the outlier to
  the spec (Spec-First Doctrine) instead of tolerating the drift. No documented behavior without
  code, no code without a requirement, no duplicated requirement across specs.
- **Surface structural decisions only:** decisions beyond the literal ask — extraction, dedup,
  refactor, doc merge, re-scoping — are stated to the user briefly when they change scope, structure,
  or behavior, and confirmed when they do. Routine dedup and alignment run silently (Narration
  Discipline); never narrate every decision, only the ones that affect the user.
- **Record decisions:** if a dedup/alignment decision affects a spec or an invariant, record it
  (ADR or spec amendment) rather than leaving it implicit.

### Phase Classification & Size Triage

Before acting, classify the instruction by **phase** (adaptive depth: Full/Light/Note) and **size**
(S/M/L). If **L**, inform the user and split into sessions. The classification tables, the L-size
protocol, and the per-step detail (Steps 1-5) live in the `agent-workflow` skill — follow them there.

### 1. Understand — Intent, Scope & Constraints

Internalize the user's **intent**, not just literal words. Clarify ambiguities. Identify constraints
and locate the governing spec. This step absorbs the legacy `Define & Scope`.

- **ALWAYS load `agent-workflow` first** — before any other action, on every instruction, whether or
  not the user asked for it. Then **ALWAYS load `context-awareness`** — the universal orientation
  layer; all other skills assume it. There is no exception, not even for trivial questions.
- **Load only the skills the task actually uses** from the Skill Map (bug fix → `code-writing`,
  tests → `pest-testing`, scripts → `script-automation`, etc.). Every skill load consumes context,
  so skip any skill the task will not exercise; an unneeded skill is cheap to skip, a bloated
  context is not. Skills are **rules-first**: each skill's rules live in
  `skills/{name}/rules/{rule}.md` (comprehensive prose — intent, rationale, how-to-apply, pitfalls,
  verification — never bare checklists) and are mapped by its `## Skill Rules` table in SKILL.md.
  Load a rule file only when the task reaches that rule; the mapping table is the index.
- **Classify phase + size** per `agent-workflow` (Phase Classification + Size Triage) — if **L**,
  inform the user and propose a session plan before proceeding.
- **Identify task type:** bug fix, new feature, refactoring, docs update, audit, review.
- **Locate the governing spec** in `docs/specs/` (foundation, module, or feature) — it is the
  source of truth for intent, scope, and acceptance criteria (Spec-First Doctrine, above). No
  instruction may proceed without a governing spec or an explicit recorded decision.
- **Define & scope** — list affected modules/layers/files and blockers (migrations, config,
  registration); reorder batched instructions by impact-to-effort ratio (see `agent-workflow`).

### 2. Plan — Context, Approach & Design

Gather context, decide approach, and design contracts before touching code. This step absorbs the
legacy `Explore + Plan + Design`.

- Read module docs, architecture docs, and the full current content of every file you may touch;
  survey `scripts/` for existing tooling (Automation-First).
- Consider 2+ approaches; decide Action triad, Entity/DTO/Model boundaries, DTO needs (C7), error
  handling (C8), cache strategy (C4), test & doc plan.
- Defined in full detail in the `agent-workflow` skill — follow it there.

### 3. Implement — Surgical Execution + Documentation

Execute the plan with minimal, clean edits and keep docs in sync. This step absorbs the legacy
`Develop + Document`.

- Edit surgically, preserve unrelated code, verify with `git status` + `git diff` (see §Edit Policy).
- Follow all invariants C1-C8, D1-D6; delegate business rules to Entities; batch repetitive work via
  scripts.
- Update module docs, PHPDoc, and `> **Last updated:**` metadata as part of the same step
  (documentation-first).

### 4. Verify — Quality Gates (Batched Once)

Batch all changes first, then verify once. See §Verification Strategy below. This is the legacy
`Test & Verify` step.

### 5. Summarize — Commit & Report

Final git review, commit, and concise report. This is the legacy `Commit & Report` step.

- Commit format: `type(scope): description` — types `feat`, `fix`, `refactor`, `docs`, `chore`,
  `test`, `perf`, `security`; scope = module name.
- Report: what changed, what was verified, caveats, recommended next steps.
- One checkpoint before commit (M-size) or per-session (L-size); stage only intended files.
- **Commit every session** — always end a session with a commit as its checkpoint; never leave
  finished, verified work uncommitted across sessions.

---

## Computational Thinking — Agent Decision Framework

Apply these four pillars to every instruction to stay autonomous, anticipate the next step, and
avoid blind execution. They are referenced throughout the workflow above.

| Pillar | How the agent applies it |
|--------|--------------------------|
| **Decomposition** | Break the instruction into smaller sub-problems (files, layers, concerns). Solve each independently, then integrate. Never try to hold the whole problem at once. |
| **Pattern recognition** | Classify the instruction (bug? feature? refactor? docs? audit?) and reuse known patterns: skills from the Skill Map, existing code, docs, past conventions. A known pattern is a solved problem. |
| **Abstraction** | Filter out irrelevant detail (versions, formatting, noise) and focus on the essential structure — entities, flows, contracts, invariants. See the forest before the trees. |
| **Algorithm design** | Plan ordered steps with clear inputs/outputs. Before acting, ask: what is the expected outcome, what could go wrong, what does the next step depend on? |

**Decision loop** — before each action, run: *predict outcome → act → verify → adjust*. After
every step, anticipate the next: what must follow, what can break, what to verify. When the
instruction is ambiguous, resolve it yourself when the cost is low (look up the answer in code or
docs); escalate to the user only when the decision changes scope or architecture.

---

## Automation-First — Scripts & Batch Patterns

Speed up work by turning mechanical effort into scripts. Apply this **before** doing manual
repetitive work, not after.

- **Check `scripts/` before repeating anything** — scanning, bulk renames, mass edits, seed data,
  report generation. If a devtool already covers the task, use it (they are faster, deterministic,
  and arch-verified). Never redo by hand what a script does.
- **Detect the pattern** — if the same operation would run on 3+ items (files, lines, records,
  translations) or is scan/verify/batch-shaped, script it or reuse an existing tool
  (Computational Thinking: algorithm design).
- **Run the existing scanners** for quality gates instead of manual greps: `scan_violations.py`,
  `scan_class_contracts.py`, `scan_security.py`, `scan_naming.py`, `scan_conventions.py`,
  `scan_doc_links.py` (see Verification Strategy).
- **When writing a new script**, load the `script-automation` skill first and follow its standards
  (interface, output format, error handling). Keep scripts in `scripts/`.
- **One-off / few-off scripts NEVER go in `scripts/`** — scripts used only a handful of times
  (single migration batch, temporary data fix, one-time conversion) must be written to `/tmp`
  (e.g. `/tmp/migrate_x.py`), run, then discarded. `scripts/` is exclusively for durable,
  reusable devtools with long-term value; committing throwaway scripts pollutes the toolchain.
- **Batch your own operations too** — group edits, tests, and verification into few passes instead
  of many small round-trips (full suite is ~2GB+, 10+ min; never run it per-edit).

---

## Edit Policy — Surgical Edits Only

Guardrail against silent information loss.

- **Read before edit** — read the full current content of every file you may touch (Step 2 — Plan).
- **Edit, don't rewrite** — change only what the instruction requires; preserve unrelated code,
  comments, formatting, and context. A full rewrite is justified only for small files where the
  rewrite IS the intent.
- **Verify with git** — compare `git diff` before/after each change to prove nothing unintended
  was altered or dropped (Step 4 — Verify). This is the final check that an edit was lossless.
- **Scope smallest** — keep the change surface minimal (Step 1 — Understand). Fewer touched files = fewer
  places for errors to hide.

---

## Pre-existing Defects — Fix or File

**The agent must not leave pre-existing warnings and errors untouched.**

- **Fix by default, after the main work:** once the instruction's primary work is complete and
  verified, fix pre-existing warnings and errors the agent noticed along the way (lint, PHPStan,
  tests, arch-guard scans, deprecations, broken doc links). Do this before the final commit so the
  repository is left cleaner than found.
- **Fix only what is safe and in-scope-adjacent:** small, low-risk fixes (missing strict types,
  unused imports, dead doc references, obvious typos) are applied directly without asking. Anything
  that changes behavior, requires a design decision, or touches a spec needs the user informed
  first (Clean Code & Dedup-Align Doctrine).
- **Cannot fix? File an issue immediately:** if fixing requires design decisions, significant
  effort, or is out of the current change surface, **create a GitHub issue first** (using the
  `issue-writing` skill) before ending the session — never let a noticed defect go unrecorded.
- **A defect noticed is a defect tracked:** no silent tolerance. Every pre-existing warn/error
  either gets fixed, gets a GitHub issue, or is explicitly reported to the user as deferred with
  the reason.

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
| DaisyUI | UI Component | v5.7 |
| maryUI | UI Component | v2.9 |
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
| php-flasher/flasher-laravel | Flash Messages | v2.4 |
| spatie/laravel-activitylog | Audit Log | v5.0 |
| spatie/laravel-medialibrary | Media Upload | v11.23 |
| spatie/laravel-model-status | Model Status | v1.18 |
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

Full definition: `README.md` (System Boundary, Personas, 3S Doctrine, Deployment) — former `docs/foundation/product-definition.md` merged into root README

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
| Livewire component | `livewire-development` | Component structure, reactivity |
| Writing spec-driven tests | `pest-testing` | Each test traces to a spec FR/NFR; no orphan tests |
| Deciding verification strategy | `test-writing` | What to run, when, how much; spec-gap & orphan detection |
| Writing documentation | `doc-writing` | Two-tier model, metadata, PHPDoc |
| Syncing docs with code | `sync-docs` | Automated verification |
| Writing GitHub issues | `issue-writing` | Structured issue format |
| Security review | `security-audit` | OWASP, PII, auth patterns |
| Spec↔Code sync audit | `spec-audit` | Bidirectional spec-implementation verification |
| Independent QA audit | `qa-protocol` | Blind test against global standards (OWASP, ISO 25010, CWE, WCAG, PSR) |
| Enforcing architecture rules | `arch-guard` | C1-C8, D1-D6, contracts, naming |
| Writing scripts | `script-automation` | Standards for `scripts/` devtools |
| CSS/styling | `tailwindcss-development` | Tailwind, DaisyUI, maryUI |
| File uploads/media | `medialibrary-development` | Spatie MediaLibrary |
| Laravel Pulse dashboard | `pulse-development` | Dashboard, recorders, cards |

---

## Module & Spec Reference

Full module list with docs: `docs/modules/index.md`
Full spec list with build order: `docs/specs/index.md`

---

## Where to Find What

### Architecture & Patterns

| I need to know about... | Look at |
|-------------------------|---------|
| Project contexts (intentional states, deploy caveats, dependency pins, known issues) | `.agents/context/index.md` |
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
| Name always `Super Admin` | `docs/modules/setup.md` §Super Admin |
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

**Tests verify the spec — nothing more.** Every test traces to a requirement ID (`FR-*` / `NFR-*` /
`UC-*`) in `docs/specs/{ID}-{feature}.md`. Test descriptions use the `{SpecID}-{ReqID}: Test
description...` format, grouped under `describe("{SpecID}: Test description...")`. Coverage is
measured in spec requirements covered, not lines
of code. A requirement with no test is a spec gap (fill it); a test with no requirement is orphan
noise (remove it).

**Spec-driven minimalism — write only the tests the spec requires, then stop.** This is deliberate:
it speeds up development and verification (spec-scoped tests run in seconds vs. 10+ minutes for the
full suite), reduces resource usage (~2GB+ RAM for the full suite), and reduces cognitive overwhelm
— a suite mapping 1:1 to requirement IDs is self-explaining. When tempted to add a test "for
safety", ask which requirement it verifies; no requirement means don't write it.

**Full suite + PHPStan are on-demand only.** Do NOT run `php artisan test --compact` (full suite) or
`vendor/bin/phpstan analyse` as part of routine work — they are slow (~2GB+ RAM, 10+ minutes) and are
only run when the user explicitly asks for them. Default verification is the targeted per-change
checks in the table below (module suite, `--filter`, pint, prettier, arch-guard scanners). The full
suite / PHPStan stay reserved for merge-day or user-requested full verification.

| Change Type | Verification |
|-------------|-------------|
| Translation keys (`lang/*.php`) | `vendor/bin/pint --dirty --test --format agent` + `php artisan tinker --execute="echo __('key');"` |
| Blade templates | `vendor/bin/pint --dirty --test --format agent` (Blade via `Pint/laravel_blade` rule) + `npm run build` |
| Config/docs/markdown | Visual inspection (`*.md` is prettier-ignored — specs/docs use deliberate compact tables; see issue #384) |
| CSS/JS/JSON/non-PHP | `npx prettier --check` + `npm run build` |
| Refactoring (rename, extract) | Targeted test: `php artisan test --compact --filter={TestSuite}` |
| New feature / business logic | Full suite ONCE after all changes batched |
| Dependency updates | `vendor/bin/pest --testsuite={ModuleName}` (run affected module suites) |
| Test pruning / spec-gap filling | Manual per-module audit — map tests ↔ spec requirements, batch edits, then run targeted module tests once |

```bash
# Version-control verification (before/after every change — Edit Policy)
git status
git diff                  # review every change before/after editing
git diff --stat           # confirm only intended files were touched

# Targeted tests
vendor/bin/pest --testsuite={ModuleName}   # Run tests for a specific module (replace {ModuleName})
php artisan test --compact --filter={ClassName}
vendor/bin/pint --dirty --test --format agent   # PHP + Blade syntax & style (Pint/laravel_blade rule)
npx prettier --check <file>                     # Non-PHP only (CSS/JS/JSON — *.php, *.blade.php, *.md ignored)
php artisan system:health

# Full verification (after refactoring or before merge) — ONLY when the user explicitly asks
php artisan test --compact   # Run full test suite (all modules)
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --no-progress

# Architecture enforcement
python3 scripts/scan_violations.py         # C1-C8, D1-D6
python3 scripts/scan_class_contracts.py    # Action/Entity/DTO/Model/Enum
python3 scripts/scan_security.py           # XSS, SQLi, CSRF, auth
python3 scripts/scan_naming.py             # Naming conventions
python3 scripts/scan_conventions.py        # strict_types, Fillable, debug
python3 scripts/scan_doc_links.py          # Broken links in docs + .agents/context/ + outdated/missing metadata detection
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
- [ ] `php artisan test --compact` passes (only when the user requests full verification)
- [ ] Every test traces to a spec requirement — no orphan tests, no padding (spec-driven testing)
- [ ] `vendor/bin/pint --dirty --format agent` clean
- [ ] `vendor/bin/phpstan analyse --no-progress` passes (only when the user requests full verification)
- [ ] Arch-guard scripts clean — `scan_violations.py`, `scan_class_contracts.py`, `scan_security.py`, `scan_naming.py`, `scan_conventions.py`, `scan_doc_links.py`
- [ ] `git status` + `git diff` reviewed — only intended files changed, nothing dropped
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
