# AGENTS.md — Navigation Hub for AI Agents

Mental model, workflow, and navigation map for AI agents.
**Does NOT duplicate `docs/`** — points there for rules, patterns, and depth.

## Agent Workflow — Mandatory Steps

**Every instruction MUST run the full cycle. No step may be skipped.** This applies to
**any instruction, in any form** — a one-line question, a bug report, a feature request, a docs
tweak, or an audit. Steps are **adaptive**: their depth scales with the instruction's SDLC phase
(see table below). Omission is never allowed; when a step is not applicable to the phase, note
it explicitly with the reason and move on.

```
UNDERSTAND → DEFINE & SCOPE → EXPLORE → PLAN → DESIGN → DEVELOP → TEST & VERIFY → DOCUMENT → COMMIT & REPORT
```

### Workflow Vocabulary — 9-Step Pipeline ↔ Skill 4-Phase

Skills use a compact 4-phase model (**Construct → Execute → Verify → Report & Commit**). They are the
same process at different granularity — a skill's phases are the 9 steps collapsed, and every skill
phase maps back to this pipeline:

| Skill 4-phase | AGENTS.md 9-step |
|---------------|------------------|
| **1. Construct** — spec, context, scope, approach | Steps 1-5 (Understand → Design) |
| **2. Execute** — do the work | Step 6 (Develop) |
| **3. Verify** — quality gates | Step 7 (Test & Verify) |
| **4. Report & Commit** | Steps 8-9 (Document → Commit & Report) |

Always map a skill's phases to these 9 steps — never treat them as separate processes.

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
- **Inform the user before every decision:** any decision the agent takes beyond the literal ask —
  extraction, dedup, refactor, doc merge, re-scoping — **must be stated to the user first** with the
  rationale, and confirmed when it changes scope, structure, or behavior. Dedup and alignment are
  expected; silent structural changes are not.
- **Record decisions:** if a dedup/alignment decision affects a spec or an invariant, record it
  (ADR or spec amendment) rather than leaving it implicit.

### Phase Classification — Adaptive Depth

Before acting, classify the instruction into an SDLC phase (Step 1). The phase sets the depth of
each step for that run: **Full** = mandatory, complete depth · **Light** = executed but minimal ·
**Note** = note the reason and skip. Anything not listed under Full/Light defaults to Note.

| SDLC Phase | Instruction examples | Full (mandatory) | Light | Note (skip w/ reason) |
|------------|----------------------|------------------|-------|----------------------|
| **Support** | questions, "what does X do", explanations | Understand, Explore | Document | Define, Plan, Design, Develop, Test, Commit |
| **Analysis** | spec/QA/security audits, reviews, PII checks | Understand, Define, Explore, Document | Plan | Develop, Test, Commit (findings only — unless a fix is requested) |
| **Planning** | specs, roadmap, GitHub issues | Understand, Define, Plan, Document | Explore | Develop, Test, Commit (unless implementation requested) |
| **Design** | architecture, refactor design, class contracts | Understand, Define, Plan, Design, Document | Explore | Develop, Test, Commit (unless implementation requested) |
| **Implementation** | new feature, bug fix, refactor | **All 9 steps** | — | — |
| **Testing** | writing/fixing tests, verification | Understand, Define, Develop, Test, Document | Explore, Plan | Commit (unless requested) |
| **Documentation** | docs updates, sync-docs | Understand, Explore, Document | Define, Plan | Develop, Test, Commit |
| **Tooling** | scripts, devtools, automation | Understand, Define, Plan, Develop, Test, Document | Explore | Commit (unless requested) |
| **Maintenance** | dependency updates, cleanup, migrations | Understand, Define, Test, Document | Explore, Plan, Develop | Commit (unless requested) |

Even for Support/Analysis, the full cycle is still traversed — the table only controls depth, not
attendance. A step skipped without a recorded reason is a workflow violation.

### Size Triage — Session Splitting (Mandatory)

Before acting, classify the instruction by **size**, not just phase. Size decides whether the work
runs in a single pass or **must be split into multiple sessions**. Both dimensions apply: phase sets
depth, size sets duration.

| Size | Criteria | Execution | User check-in |
|------|----------|-----------|---------------|
| **S** | ≤3 files, single concern, no cross-module | Single pass, full 9 steps at phase depth | None required |
| **M** | 4-10 files, 2-3 concerns, or cross-layer | Single session, staged internally, batch verification | One checkpoint before commit |
| **L** | >10 files, multi-module, cross-cutting, heavy effort, or long runtime | **MUST split into multiple sessions** | **MUST inform the user first** |

**L-size protocol (non-negotiable):**
1. After Step 1 (Understand), tell the user plainly: *"This instruction is too broad for a single
   pass — I will split it into N sessions."*
2. Propose a session plan (each session = one deliverable unit with its own scope, verify, and report).
3. Execute sessions in order; each session starts from the verified state of the previous one.
4. Each session ends with: `git status` + `git diff` review, targeted verification, and a short
   user report. Commit per-session if requested.
5. Never attempt an L-size task in one pass — context overload degrades quality and risks lost work.

All 9 steps, skills, and verification rules below still apply **per session** at the appropriate depth.

### 1. Understand

Internalize the user's **intent**, not just literal words. Clarify ambiguities. Identify constraints.

- **ALWAYS load `context-awareness` first** — before any other action, on every instruction,
  whether or not the user asked for it. It is the universal orientation layer; all other skills
  assume it. There is no exception, not even for trivial questions.
- **Load related skill(s)** from the Skill Map that match the instruction (bug fix → `code-writing`,
  tests → `pest-testing`, scripts → `script-automation`, etc.). Load **every** skill that applies —
  loading a skill is cheap, a wrong assumption is not.
- **Classify the SDLC phase** using the Phase Classification table — it sets the depth of the
  9 steps for this run (Computational Thinking: pattern recognition).
- **Classify the size** (S/M/L) using Size Triage — if **L**, inform the user and propose a session
  plan before proceeding (session splitting is mandatory, never optional).
- **Identify task type:** bug fix, new feature, refactoring, docs update, audit, review.
- **Locate the governing spec** in `docs/specs/` (foundation, module, or feature) — it is the
  source of truth for intent, scope, and acceptance criteria (Spec-First Doctrine, above). No
  instruction may proceed without a governing spec or an explicit recorded decision.
- **Check `docs/roadmap.md`** for current development status and phase progress (for planning
  work, load the `roadmap-planning` skill).
- Output: clear restatement of the task + phase classification + loaded skills, confirmed with
  user if ambiguous.

### 2. Define & Scope

Identify affected module(s), layer(s), files. Check dependencies.

- **List affected modules** using Module Quick Reference below.
- **Identify affected layers:** Presentation (Livewire/Blade), Business (Action/Entity), Data (Model/DTO), Infrastructure.
- **Check for blockers:** migrations needed? config changes? service provider registration?
- **Select skills to load** based on task type (see Skill Map).
- **Scope the change surface** so work stays minimal — the smallest set of files that satisfies
  the instruction (Computational Thinking: decomposition).
- Output: scope statement with affected files and required skills.

### 3. Explore

Read the relevant docs and existing code. Build mental model before writing.

- **Load required skills** from Skill Map.
- **Survey existing tooling first** — check `scripts/` and devtools for an existing scanner or
  helper before doing manual or repeated work (Automation-First).
- **Read module docs** (`docs/modules/{module}.md`) for affected modules.
- **Read architecture docs** (`docs/architecture/`) for relevant patterns.
- **Read the full current content of every file you may touch** — before any edit, understand the
  existing code, patterns, naming, and structure so changes stay surgical (Edit Policy).
- **Check conventions** (`docs/conventions.md`) for invariants C1-C8, D1-D6.
- Output: complete understanding of existing patterns and code.

### 4. Plan

Consider 2+ approaches. Choose the best fit.

- **Action type:** Command (write), Read (query), Process (business logic).
- **Entity boundaries:** what goes in Entity vs Action vs DTO.
- **DTO needs:** required if Command/Process has 3+ parameters (C7).
- **Automation:** if the work is repetitive, batch, mechanical, or pattern-based (scanning,
  bulk renames, mass edits, seed data), plan to script it or reuse an existing devtool instead
  of doing it by hand (Automation-First, Computational Thinking: algorithm design).
- **Test strategy:** which test type, which verification commands (see Verification Strategy).
- **Document changes:** what docs need updating.
- Output: implementation plan with chosen approach.

### 5. Design

Define class contracts before coding.

- **Action signature:** constructor params, return type (ActionResponse).
- **Entity contract:** `final readonly`, `fromModel()`, forbidden imports (C5).
- **DTO contract:** extends `BaseData`, forbidden imports (C6).
- **Model contract:** `#[Fillable]`, entity bridge method.
- **Error handling:** which exceptions (C8: RejectedException, not RuntimeException).
- **Cache strategy:** key registration (C4), TTL, invalidation.
- Output: class signatures, data flow, error handling plan.

### 6. Develop

Write code matching the design. Follow conventions.

- **Edit Policy — never full-rewrite by default.** First check what already exists. Edit
  surgically (smallest possible change), preserve unrelated code, formatting, and context. A
  full rewrite is only justified for small files where the rewrite IS the intent — never as a
  shortcut on large files (risk of silently dropping important information).
- **`declare(strict_types=1)`** in every PHP file (D1).
- **No debug calls** — `dd/dump/ray/var_dump/print_r/die` (D2).
- **`__()` for all user-facing strings** (D3).
- **No raw request** for create/update — use validated DTOs (D5).
- **No Model mutations in Livewire** — use Actions (C1).
- **No service locator** — use constructor injection (C2).
- Output: working code matching design, produced by surgical edits.

### 7. Test & Verify

Choose verification level. Run targeted checks first, full suite once at end.

- **Batch changes** before running full suite (expensive: ~2GB+, 10+ min).
- **Verify with git before and after** — run `git status` + `git diff` to compare the changed
  files against their previous state: confirm only intended files changed, no unrelated edits,
  and no content was lost in any edit (version-control verification, Edit Policy).
- **Run incremental checks** during development:
  - `php -l path/to/file.php` — syntax check
  - `vendor/bin/pint --dirty --format agent` — code style
- **Run targeted tests** after completing a logical unit:
  - `vendor/bin/pest --testsuite={ModuleName}`
  - `php artisan test --compact --filter={ClassName}`
- **Run arch-guard scripts** before commit (Automation-First — never skip these in favor of
  manual greps; they are faster and deterministic):
  - `python3 scripts/scan_violations.py` — C1-C8, D1-D6
  - `python3 scripts/scan_class_contracts.py` — class contracts
  - `python3 scripts/scan_security.py` — security patterns
  - `python3 scripts/scan_naming.py` — naming conventions
  - `python3 scripts/scan_conventions.py` — strict_types, Fillable, debug
  - `python3 scripts/scan_doc_links.py` — broken links in docs
- **Run full suite** only when the user explicitly asks (on-demand only, never routine):
  - `php artisan test --compact`
  - `vendor/bin/phpstan analyse --no-progress`
- Output: all tests pass, linter clean, arch-guard clean, git diff reviewed.

### 8. Document

Update docs before/after code changes (documentation-first).

- **Module docs** (`docs/modules/{module}.md`) — update if features/API changed.
- **Architecture docs** (`docs/architecture/`) — update if patterns changed.
- **Conventions** (`docs/conventions.md`) — update if new rules added.
- **PHPDoc blocks** — required on all public methods.
- Output: docs match code.

### 9. Commit & Report

Deliver report. Commit with conventional format.

- **Final git review** — `git status` + `git diff` before committing: stage only intended files,
  never secrets, never unrelated changes. Confirm the final state matches the verified diff.
- **Conventional format:** `type(scope): description`
- **Scope = module name** (e.g., `feat(enrollment): add bulk placement`)
- **Types:** `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`
- **Report:** summarize what changed, what was verified, any caveats.
- Output: clean commit, user informed.

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
- **Batch your own operations too** — group edits, tests, and verification into few passes instead
  of many small round-trips (full suite is ~2GB+, 10+ min; never run it per-edit).

---

## Edit Policy — Surgical Edits Only

Guardrail against silent information loss.

- **Read before edit** — read the full current content of every file you may touch (Step 3).
- **Edit, don't rewrite** — change only what the instruction requires; preserve unrelated code,
  comments, formatting, and context. A full rewrite is justified only for small files where the
  rewrite IS the intent.
- **Verify with git** — compare `git diff` before/after each change to prove nothing unintended
  was altered or dropped (Step 7). This is the final check that an edit was lossless.
- **Scope smallest** — keep the change surface minimal (Step 2). Fewer touched files = fewer
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
| Prettier | Formatter | v3.9 |
| @prettier/plugin-php | PHP Formatter | v0.25 |
| prettier-plugin-blade | Blade Formatter | v3.2 |
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

Full definition: `docs/foundation/product-definition.md`

---

## Skill Map — Which Skill to Load

| Task | Skill | Notes |
|------|-------|-------|
| Every instruction, any task | `context-awareness` | **ALWAYS load first** — universal orientation layer, no exceptions |
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
| Planning the roadmap | `roadmap-planning` | Phased planning, priorities, dependencies |
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

**Tests verify the spec — nothing more.** Every test traces to a requirement ID (`FR-*` / `NFR-*` /
`UC-*`) in `docs/specs/{feature}.md`. Coverage is measured in spec requirements covered, not lines
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
checks in the table below (module suite, `--filter`, `php -l`, pint, arch-guard scanners). The full
suite / PHPStan stay reserved for merge-day or user-requested full verification.

| Change Type | Verification |
|-------------|-------------|
| Translation keys (`lang/*.php`) | `php -l` + `php artisan tinker --execute="echo __('key');"` |
| Config/docs/markdown | Visual inspection, no tests |
| Blade/CSS/JS | `npm run build` only |
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
php -l path/to/file.php
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
