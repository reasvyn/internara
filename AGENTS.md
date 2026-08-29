# AGENTS.md — Navigation Hub for AI Agents

Mental model and navigation map for AI agents.
**Does NOT duplicate `docs/`** — points there for rules, patterns, and depth.
**Rule bodies live in `.agents/rules/{rule}.md`** — this file indexes them; load a rule file when
a task reaches its concern.

> **Terminology (homespace vs workspace):** when the user says **agent homespace** they mean the
> local configuration at **`~/.agents/`** (user-level — contents vary per user); when they say
> **agent workspace** they mean the project-level overlay at **`./.agents/`** (this repo:
> `internara/.agents/`). Homespace is whatever the user keeps at `~/.agents/`; workspace is the
> project-specific instantiation. Paths below are workspace-relative (`.agents/...`) unless prefixed
> with `~/`.

## Agent Workflow — Canonical

**Every instruction MUST run the full cycle** (`UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE`)
— any instruction, in any form: a one-line question, a bug report, a feature request, a docs tweak,
or an audit.

The pipeline runs **silently**: the 5 steps are internal reasoning — never narrate them. Surface to
the user **only**:

1. Ambiguity that needs their input.
2. A decision that changes scope, structure, or behavior.
3. An L-size session plan (one short paragraph).
4. One checkpoint before commit (M-size) or per-session (L-size).
5. The final report (what changed, what was verified, caveats).

Every sentence must carry new information or a decision; if it does neither, drop it.

```
UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE
```

| Step | Purpose | Core questions answered | Key outputs |
|------|---------|-------------------------|-------------|
| **1. Understand** | Intent, scope, and constraints before any exploration | *What is asked? What is the governing spec? What is affected? How big is it?* | Governing spec + FR/NFR/UC IDs, phase & size (S/M/L), affected modules/layers/files, blockers, reordered instruction list |
| **2. Plan** | Context gathering, approach selection, and design | *What exists today? Which approach? What contracts?* | Read file/docs inventory, 2+ considered approaches, chosen design (Action triad, Entity/DTO/Model contracts, error & cache strategy), test & doc plan |
| **3. Implement** | Surgical execution + documentation | *What changes, minimally and cleanly?* | Code edits (preserving unrelated code), doc/PHPDoc updates, automation/scripts |
| **4. Verify** | Quality gates, batched once | *Is anything broken or lost?* | `git status`/`diff` review, style checks, targeted & arch-guard scans, full suite only on-demand |
| **5. Summarize** | Commit and report | *What was delivered and what remains?* | Staged commit `type(scope): desc`, final report (changes, verification, caveats, next steps) |

> **Mapping from legacy 9-step (for reference):** Understand absorbs `Understand + Define & Scope`; Plan absorbs `Explore + Plan + Design`; Implement absorbs `Develop + Document`; Verify = `Test & Verify`; Summarize = `Commit & Report`. All invariants and checks remain — only the grouping is simplified.

### Step 1 — Understand

Internalize intent, not literal words. Do all scoping before reading files.

- **Intent & constraints** — what the user actually wants, hidden requirements, non-goals, hard constraints (deadlines, scope limits, compatibility).
- **Spec-First Doctrine (non-negotiable)** — locate the governing spec in `docs/specs/` via `docs/specs/index.md` (foundation, module, or feature); read its FR/NFR/UC IDs. No behavior without a requirement ID; if none exists, write the spec first — spec-first, never fix-first. Spec outranks literal wording and existing code. If spec and code disagree, fix code to spec; if spec is demonstrably wrong, amend spec with a recorded decision first, then align code and tests.
- **Define & scope** — list affected modules, layers (Action/Entity/DTO/Model/Livewire), and files; identify blockers (pending migrations, config, service registration, permission/policy gaps).
- **Classify** — SDLC phase (see §Phase Classification) and size S/M/L (see §Size Triage). If **L** (>10 files / multi-module / cross-cutting), inform the user in one short paragraph and split into sessions before proceeding (L-size protocol).
- **Instruction ordering** — if the message batches 2+ instructions, decompose → score by impact-to-effort ratio → sort → honor dependencies → group same-area work → surface resulting order only when it differs from the user's sequence (see `.agents/rules/instruction-ordering.md`).
- **Task type** — bug fix / feature / refactor / docs / audit / review / tooling; determines which downstream skills to load next (load only what the task actually uses; an unneeded skill bloats context).

**Exit criteria:** governing spec identified (or explicit recorded decision to proceed without), scope bounded, phase & size classified, instruction order decided.

### Step 2 — Plan

Gather context, decide approach, design contracts — before touching code.

- **Explore (context gathering)** — read module docs, architecture docs (`docs/guides/arch/*.md`), conventions (`docs/conventions.md`), and the **full current content of every file you may touch**; survey `tools/` for existing devtools before manual work (Automation-First — reuse `scan_*.py` scanners instead of manual greps; if 3+ items would be touched repetitively, script it).
- **Plan (approach selection)** — consider **2+ approaches** and pick one; decide Action type (Command / Read / Process per `docs/guides/arch/action-pattern.md`), Entity boundaries (`final readonly` + `fromModel()`), DTO needs (C7: DTO for 3+ params), test strategy (spec-traceable, see Verification Strategy), doc changes, localization, and security implications.
- **Design (contracts)** — define class contracts up front:
  - Action signature and triad base class; `declare(strict_types=1)` (D1)
  - Entity `final readonly`, forbidden imports (C5), business rules delegated to Entity
  - DTO `BaseData`, forbidden imports (C6)
  - Model `#[Fillable]` (D4), FK with `onDelete`/`onUpdate` (D6)
  - Error handling: `RejectedException` not `RuntimeException` (C8)
  - Cache strategy: registered keys in `config/cache-keys.php`, no inline keys (C4)
  - Policy/authorization, validation (no raw request to create/update — D5), `__()` for user strings (D3)
- **Risk & verification plan** — how verification will run (change-type matrix in AGENTS.md §Verification Strategy), which arch-guard scanners apply, and whether full suite / PHPStan is justified (on-demand only).

**Exit criteria:** context inventoried, chosen approach documented (even if just internally), contracts sketched, test & doc plan clear. No code has been written yet.

### Step 3 — Implement

Execute surgically and keep code, specs, docs, and tests aligned.

- **Surgical edits** — smallest change that satisfies the requirement; never full-rewrite a large file by default; preserve unrelated code, comments, formatting, and context. Read before edit, edit → `git diff` sanity check, repeat.
- **Conventions (non-negotiable invariants):**
  - `declare(strict_types=1)` (D1), no debug calls `dd/dump/ray/var_dump` (D2), `__()` for every user-facing string (D3)
  - No Model mutations in Livewire — delegate to Actions (C1); constructor injection only, no `app()->make` (C2)
  - No raw SQL without bindings (C3); no raw `$request->all()` to create/update — use validated DTO/FormRequest (D5)
  - Cache keys via registry (C4), correct exception hierarchy (C8), `#[Fillable]` (D4), FK handling (D6)
  - No unescaped `{!! !!}` for user content; eager loading to avoid N+1; DRY extraction — prefer more, smaller, well-named modules over one dense blob
- **Automation-First in execution** — for repetitive / batch / pattern work (bulk renames, 3+ similar edits, seed data, mass scans), script it or reuse a devtool in `tools/`; batch your own edits into few passes instead of many round-trips.
- **Documentation-first** — update module docs, architecture docs, conventions, and PHPDoc on public methods **before/after** code as part of the same step; keep `docs/specs/*.md` as SSOT and align docs ↔ code ↔ tests (Clean Code & Dedup-Align Doctrine — deduplicate on sight, reuse or extract instead of copy-pasting). History is tracked via `git log --follow -- <file>` and `git diff`, not inline metadata.
- **Business rules in Entities** — Actions orchestrate; Entities own invariants; DTOs carry data; Models are persistence only.

**Exit criteria:** all planned changes applied, docs/PHPDoc in sync, no unrelated drift, `git status` shows only intended files.

### Step 4 — Verify

Batch all changes first, then verify **once**. Full suite is ~2GB+, 10+ min — never per-edit.

- **Version-control verification (Edit Policy, every change):** `git status` + `git diff` (and `git diff --stat`) before/after each edit — only intended files changed, nothing dropped; `git diff` is the lossless-edit proof.
- **Incremental style & build gates:**
  - `vendor/bin/pint --dirty --test --format agent` (PHP + Blade via `Pint/laravel_blade`)
  - `npx prettier --check <file>` for non-PHP (CSS/JS/JSON — `*.php`/`*.blade.php`/`*.md` ignored)
  - `npm run build` for Blade/CSS/JS changes; visual inspection for `*.md`/config
- **Targeted tests (spec-driven, not line-coverage):**
  - `vendor/bin/pest --testsuite={ModuleName}` or `php artisan test --compact --filter={ClassName}`
  - Every test traces to a spec FR/NFR/UC ID (`{SpecID}-{ReqID}: description`); no orphan tests, no spec gaps; coverage = requirements covered
- **Arch-guard scanners (run as a batch):**
  - `python3 tools/scan_violations.py` (C1-C8, D1-D6)
  - `python3 tools/scan_class_contracts.py` (Action/Entity/DTO/Model/Enum)
  - `python3 tools/scan_security.py` (XSS, SQLi, CSRF, auth)
  - `python3 tools/scan_naming.py` · `scan_conventions.py` · `scan_doc_links.py`
- **Full verification on-demand only** (merge-day or user explicitly asks):
  - `php artisan test --compact` (full suite, all modules)
  - `vendor/bin/phpstan analyse --no-progress` (level 8 / Larastan)
  - Change-type matrix in `AGENTS.md` §Verification Strategy decides what is required for the current change type; default is targeted checks, not full suite.

**Exit criteria:** change-type-appropriate gates pass; arch-guard clean or deviations explicitly justified/recorded; no silent tolerance of pre-existing warnings — fix safe adjacent issues or file a GitHub issue (`issue-writing` skill) before ending the session.

### Step 5 — Summarize

Close the loop: version-control checkpoint, commit, and a concise final report.

- **Final git review** — `git status` + `git diff` one last time; stage **only intended files**, never secrets or unrelated changes; confirm nothing was lost.
- **Commit** — format `type(scope): description` — types `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`; scope = module name; one concern per commit (group quick wins / interdependent changes; split strategically-separate concerns).
- **Report (surface to user):** what changed (files/modules/specs), what was verified (which gates ran and their result), caveats / known limitations, and **recommended next steps** (pending work, follow-ups, or L-size session plans). Keep it short — narration discipline applies.
- **Session handling** — for M-size: one checkpoint before commit; for L-size: per-session report + `git status`/`diff` review at the end of each session, never attempting L-size in one pass.
- **Pre-commit checklist** (AGENTS.md) must pass: strict types, no debug calls, `__()` coverage, Action triad + DTO rule, Entity delegation, cache registry, N+1 check, escaped output, tests traceable to spec, pint/phpstan/arch-guard as appropriate.
- **Capture learnings (Self-Improvement Loop)** — record decisions, corrections, failures, patterns, constraints, gaps into `context/` (update in place, write a descriptive commit message, add a row to `context/index.md`) and append a one-liner to `context/learning-log.md`. Promote a signal seen ≥2 times to `rules/` or a skill; durable decisions get an ADR in `docs/adr/`. Run `/self-improvement --deep` at session end to mine `git diff`.

**Exit criteria:** clean commit(s), report delivered, **learning captured** (memory updated, repeats promoted), repo left cleaner than found.

## Phase Classification — Adaptive Depth

Classify the instruction into an SDLC phase. Full = mandatory complete depth · Light = executed but minimal · Note = skip silently. Anything not listed defaults to Note. Depth is now expressed in the 5-step vocabulary.

| SDLC Phase | Full (mandatory) | Light | Note (skip silently) |
|------------|------------------|-------|----------------------|
| **Support** | Understand, Summarize | Plan (brief context check) | Implement, Verify (findings only) |
| **Analysis** | Understand, Plan, Summarize | Verify (sanity check) | Implement |
| **Planning** | Understand, Plan, Summarize | Verify (feasibility check) | Implement |
| **Design** | Understand, Plan, Summarize | Verify (design review) | Implement |
| **Implementation** | **All 5 steps** | — | — |
| **Testing** | Understand, Implement, Verify, Summarize | Plan (scope the test plan) | — |
| **Documentation** | Understand, Plan, Implement, Summarize | Verify (link & metadata check) | — |
| **Tooling** | Understand, Plan, Implement, Verify, Summarize | — | — |
| **Maintenance** | Understand, Verify, Summarize | Plan, Implement | — |

> **How to read:** e.g., a `Support` question runs Understand deeply (intent + spec lookup), skims Plan just enough to locate relevant docs, skips Implement/Verify except to validate the answer, and delivers a full Summarize. An `Implementation` task runs all 5 steps at full depth.

## Size Triage — Session Splitting

| Size | Criteria | Execution | User check-in |
|------|----------|-----------|---------------|
| **S** | ≤3 files, single concern, no cross-module | Single pass, full 5 steps at phase depth | None |
| **M** | 4-10 files, 2-3 concerns, or cross-layer | Single session, staged internally, batch verification (Verify once, then Summarize) | One checkpoint before commit (Step 5) |
| **L** | >10 files, multi-module, cross-cutting | **MUST split into multiple sessions** | **MUST inform the user first** |

**L-size protocol:** after **Plan** (Step 2), tell the user in one short paragraph: *"This instruction is too broad for a single pass — I will split it into N sessions"* + the session list. Execute sessions in order; each session runs Implement → Verify → Summarize with its own `git status` + `git diff` review, targeted verification, and short report. Never attempt L-size in one pass.

## Instruction Ordering — High-Impact, Low-Effort First

The user sometimes batches instructions in random order. Before executing any of them (during **Understand**), reorder the batch by **impact-to-effort ratio** — quick wins first, heavy lifts scheduled. Apply this to every multi-instruction message; run the scoring silently and surface only the resulting order.

The full rule (the impact-to-effort rule, scoring scale, worked examples, and commit grouping) lives in `.agents/rules/instruction-ordering.md` — apply that rule, not the summary below.

| Quadrant | Impact | Effort | Handling |
|----------|--------|--------|----------|
| **Quick win** | High | Low | Execute first — highest impact-to-effort ratio |
| **Strategic** | High | High | Split into sessions (Size Triage L); schedule after quick wins |
| **Fill-in** | Low | Low | Batch opportunistically alongside larger work; do not skip |
| **Questionable** | Low | High | Challenge or defer; confirm with the user before investing |

**Ordering algorithm (summary — see the rule file for scoring):**
1. **Decompose** the batch into discrete, independently-executable instructions
2. **Score** each by impact (reach × importance) and effort (files × complexity × verification)
3. **Sort** by impact-to-effort ratio, quick wins first
4. **Honor dependencies** — if instruction B depends on A, execute A first even if B scores higher
5. **Group** same-area instructions into one pass (batch file touches, batch verification in Verify)
6. **Surface** the resulting order in one short paragraph when it differs from the user's sequence

## Computational Thinking — Decision Loop

Before each action: *predict outcome → act → verify → adjust*. Anticipate the next step. Resolve ambiguity yourself when the cost is low; escalate to the user only when the decision changes scope or architecture.

| Pillar | Application |
|--------|-------------|
| **Decomposition** | Break into sub-problems (files, layers, concerns); solve each, then integrate |
| **Pattern recognition** | Classify the instruction; reuse known patterns (skills, docs, conventions) |
| **Abstraction** | Filter irrelevant detail; focus on entities, flows, contracts, invariants |
| **Algorithm design** | Plan ordered steps with clear inputs/outputs |

## Pre-existing Defects — Fix or File

- **Fix by default, after the main work**: pre-existing warnings/errors noticed along the way (lint, PHPStan, arch-guard, broken doc links) get fixed before Summarize — leave the repo cleaner than found. Fix only what is safe and in-scope-adjacent; anything behavior-changing or spec-touching needs user sign-off first. This happens inside **Implement** (fix) and is confirmed in **Verify**.
- **Cannot fix? File a GitHub issue immediately** (`issue-writing` skill) — a defect noticed is a defect tracked.

## Self-Improvement Loop — Continuous Learning

The agent compounds in capability across sessions via a closed loop (inherits the global Learning Loop in
`~/.agents/rules/self-improvement.md`; project overlay: `.agents/rules/self-improvement.md`, procedure
`~/.agents/skills/self-improvement/SKILL.md`). Run `/self-improvement` (or `--deep`) for an explicit
retrospective.

```
CAPTURE  ──▶  CONSOLIDATE  ──▶  APPLY
   ▲                                 │
   └─────────────────────────────────┘
```

- **CAPTURE** (in **Summarize**, step 5): record decisions, corrections, failures, patterns, constraints,
  gaps into `context/` (self-contained topic files; register in `context/index.md`). Append a one-liner
  to `context/learning-log.md`.
- **CONSOLIDATE** (periodic): a signal seen ≥2 times in this codebase is promoted to `rules/`
  (prefer `architecture-rules`, `coding-rules`, `testing-rules`) or a skill step; durable decisions get
  an ADR in `docs/adr/`. One-offs stay in `context/` — no rule-bloat.
- **APPLY** (in **Understand**, step 1): load `context/index.md`, open the row matching the task, and
  honor recorded corrections + intentional states (deprecated `laravel-model-status`, dummy-guard,
  TallstackUI-only) so the same mistake is never repeated.

This is the agent's deep-learning mechanism: experience is extracted into patterns and pushed into its
own instructions (rules/skills), not just logged. Update `context/` files in place (write a descriptive commit message);
never duplicate a topic.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Key rules (non-negotiable) | `.agents/rules/key-rules.md` | Every instruction |
| Instruction ordering (impact-to-effort) | `.agents/rules/instruction-ordering.md` | Batched/multi-instruction messages |

## References

| Topic | Doc |
|-------|-----|
| Full workflow & module map | `AGENTS.md` |
| Verification matrix | `AGENTS.md` §Verification Strategy |
| Pre-commit checklist | `AGENTS.md` §Pre-commit Checklist |
| Skill map | `AGENTS.md` §Skill Map |
| Conventions & invariants | `docs/conventions.md` |
| Instruction ordering rule | `.agents/rules/instruction-ordering.md` (this skill) |

---

## Rules Index — Load on Demand

> All rule bodies live in `.agents/rules/` (150+ rules consolidated from skill `rules/` directories).
> The table below indexes the most-referenced rules; load any other rule file by name when a task
> reaches its concern.

| Rule file | Governs | Load when |
|-----------|---------|-----------|
| [`spec-first-doctrine`](.agents/rules/spec-first-doctrine.md) | Governing spec is SSOT; no behavior without a requirement ID | Every task — consult before planning |
| [`clean-code-dedup-align`](.agents/rules/clean-code-dedup-align.md) | DRY default, spec↔code↔docs↔tests alignment, surfacing structural decisions | Every task — during implement & review |
| [`computational-thinking`](.agents/rules/computational-thinking.md) | Four decision pillars + predict→act→verify→adjust loop | Ambiguous or multi-step instructions |
| [`documentation-split`](.agents/rules/documentation-split.md) | Human docs in `docs/`, AI assets in `.agents/`; directional referencing | Any documentation change |
| [`automation-first`](.agents/rules/automation-first.md) | Script batch work; reuse scanners; `/tmp` for throwaway scripts | Repetitive/batch operations; writing scripts |
| [`impact-to-effort`](.agents/rules/impact-to-effort.md) | Order all work: dependency chains → business importance/urgency bands → impact-to-effort ratio | Multiple instructions, backlog triage, multi-stage planning |
| [`edit-policy`](.agents/rules/edit-policy.md) | Read-before-edit, surgical diffs, git lossless proof | Every code/doc edit |
| [`pre-existing-defects`](.agents/rules/pre-existing-defects.md) | Fix or file noticed warnings/errors; never silent tolerance | Warnings/errors encountered mid-task |
| [`commit-as-checkpoint`](.agents/rules/commit-as-checkpoint.md) | Commit at every session end AND every verified milestone; never leave verified work uncommitted | End of every session; each stage of multi-stage work |
| [`verification-strategy`](.agents/rules/verification-strategy.md) | Batched verification, change-type matrix, scanner commands | Before running tests or quality gates |
| [`pre-commit-checklist`](.agents/rules/pre-commit-checklist.md) | Final gate before every commit | Immediately before each commit |
| [`key-rules`](.agents/rules/key-rules.md) | Non-negotiable workflow rules (load order, no restate, spec-first, narration, batch verify, impact-to-effort) | Every instruction — governs workflow |
| [`instruction-ordering`](.agents/rules/instruction-ordering.md) | Impact-to-effort scoring for batched instructions | Multi-instruction messages |
| [`architecture-rules`](.agents/rules/architecture-rules.md) | Layer boundaries & Action Triad checks | Classifying/reviewing code against 4-layer model |
| [`domain-boundary`](.agents/rules/domain-boundary.md) | One business domain = one Domain; when a domain earns its own Domain | Decomposing/relocating a domain into its own Domain |
| [`coding-rules`](.agents/rules/coding-rules.md) | Practical coding application guide (before writing any class) | Creating/reviewing Actions, Entities, DTOs, Models, Enums |
| [`testing-rules`](.agents/rules/testing-rules.md) | What to verify when testing (spec-driven minimalism) | Writing/reviewing tests |
| [`invariants`](.agents/rules/invariants.md) | Non-negotiable invariants C1-C8, D1-D6 | Every class written or touched |
| [`class-contracts`](.agents/rules/class-contracts.md) | Action/Entity/DTO/Model/Enum/Livewire/Service contracts | Creating/modifying a component type |
| [`naming-conventions`](.agents/rules/naming-conventions.md) | File/class/method/variable naming | Naming files, classes, routes, tests |
| [`performance`](.agents/rules/performance.md) | N+1, queries, caching | Query-heavy or list/dashboard code |
| [`security`](.agents/rules/security.md) | XSS, SQLi, mass assignment, CSRF | Any user input, output, or form |

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

## Context Awareness — Project Orientation

> **Prerequisite:** None — this is the orientation layer loaded after `agent-workflow`.

### When to Activate

Load this section at the start of every session. It provides the mental model all downstream skills depend on.

### Orientation Workflow

This is the **orientation layer** — it does NOT write code or run tests; it builds the mental model all downstream skills depend on. Follow the `agent-workflow` 5-step pipeline (Understand → Plan → Implement → Verify → Summarize) and **Size Triage** (S/M/L session splitting) for the overall instruction; this adds the orientation steps and memory-keeping duties below.

#### Construct — Orientation

- Read the user's instruction carefully; identify the **intent**, not just the literal request
- Determine scope: single file change, cross-module refactor, or new feature
- **Locate the governing spec** in `docs/specs/` (via `docs/specs/index.md`) — read the relevant FR/NFR/UC IDs; if no spec exists for the work, stop and raise it (write the spec first)
- Identify which module(s) are affected
- Read relevant docs: module docs, pattern docs, reference docs
- **Check evolving project context** — read `.agents/context/index.md` and load any context file matching the task topic (intentional constraints, deploy caveats, dependency pins, known states)
- Verify paths, class names, signatures against actual code — never trust docs blindly; on code/doc mismatch, check git history before deciding which side is correct

#### Agent Memory — Maintain `.agents/context/`

`.agents/context/` is the **AI Agent memory**: a living record of evolving project knowledge that agents write to so no context is lost between sessions. It is both **read** (orientation) and **written** (maintenance). Treat it like a shared, append-only project memory — never let a discovery die in a conversation.

- **Update on inconsistency:** whenever you detect that a context file no longer matches reality (code, spec, docs, config, or environment changed), update that context file **directly in the same run** — fix the stale fact, write a descriptive commit message and update the file directly. Do not just note it to the user or leave it for a later pass.
- **Create when critical:** if you learn something **highly important** for future agents that is not yet recorded — a non-obvious constraint, a working workaround, an environment quirk, a deliberate decision — create a new context file `.agents/context/{context}-{issue-name}.md` (flat, kebab-case) and register it in `.agents/context/index.md`. Rules of thumb:
  - Would a future agent make a costly wrong assumption without this knowledge? → **record it**
  - Does the fact change often or is it trivial/obvious? → **do not record it**
- **Keep it self-contained and deduplicated:** each file stands alone (paths, commands, rationale included); never duplicate a fact already recorded elsewhere — update the existing file instead.
- **House style:** `## Description`, plain language, an `## AI Agent Guides` decision table where helpful. No inline `Last updated` metadata — history lives in `git log`.

#### Verify — Orientation Completeness

Before handing off to any downstream skill, confirm:
- [ ] Which module(s) and layer(s) are affected
- [ ] Which class types need to be created or modified
- [ ] What invariants (C1-C8, D1-D6) apply to this task
- [ ] What existing code can be followed as a pattern
- [ ] What docs need to be read before writing code

#### Report — Hand Off to Downstream Skill

- Deliver orientation summary to the user:
  - Affected modules and layers
  - Architecture constraints that apply
  - Existing patterns to follow
  - Risks or edge cases identified
- Recommend the appropriate downstream skill(s) for execution
- If the task was classified **L**, present the proposed session plan (each session = one deliverable unit with its own verify + report) and get user approval before execution
- If the work is repetitive, batch, or pattern-based, note the reusable tools/devtools to use (Automation-First — check `tools/` and the Automation Scripts section below first)

### Navigation Patterns

| Need to find... | Look here |
|-----------------|-----------|
| Business logic | `app/Modules/{Module}/Domain/{Domain}/Actions/` |
| Business rules | `app/Modules/{Module}/Domain/{Domain}/Entities/` |
| Data structure | `app/Modules/{Module}/Domain/{Domain}/Models/` |
| Data transfer | `app/Modules/{Module}/Domain/{Domain}/Entities/` (DTOs) |
| State machines | `app/Modules/{Module}/Domain/{Domain}/Enums/` |
| UI components | `app/Modules/{Module}/Domain/{Domain}/Livewire/` |
| Authorization | `app/Modules/{Module}/Domain/{Domain}/Policies/` |
| Side effects | `app/Modules/{Module}/Domain/{Domain}/Events/` and `Listeners/` |
| Infrastructure | `app/Modules/{Module}/Domain/{Domain}/Services/` or `Support/` |
| Base contracts | `app/Modules/Core/Actions/`, `app/Modules/Core/Entities/`, `app/Modules/Core/Enums/` |
| Tests | `tests/{Module}/{Domain}/` |
| Config | `config/{module}.php` |
| Routes | `routes/web/{module}.php` (domains: `{domain}.php` in same dir) |
| Translations | `lang/en/{module}.php`, `lang/id/{module}.php` (domains: `{domain}.php` in same dir) |

### Pattern Recognition

| You see... | It should be... | Violation? |
|------------|-----------------|------------|
| `Model::create()` in a Livewire component | Command Action | **C1 violation** |
| `app()->make(SomeAction::class)` | Constructor injection | **C2 violation** |
| `DB::raw("...")` without binding | Eloquent query builder | **C3 violation** |
| `'cache_key'` string inline | Key in `config/cache-keys.php` | **C4 violation** |
| Entity importing `Action` or `Service` | Entity should be pure | **C5 violation** |
| DTO importing `Model` or `Entity` | DTO should carry scalars only | **C6 violation** |
| Action accepting raw `array` for 3+ params | Should accept DTO | **C7 violation** |
| `throw new RuntimeException('business rule')` | Should be `RejectedException` | **C8 violation** |
| `$fillable = [...]` property | `#[Fillable([...])]` attribute | **D4 violation** |
| `$request->all()` in create/update | `->only()` or `->toArray()` | **D5 violation** |

### Data Flow Tracing

Every mutation in the system follows this path:

```
User interaction
  → Livewire component (validates input, catches RejectedException)
    → Command Action::execute(DTO)
      → Entity::fromModel(model) → business rules
      → Model::create/update(values from DTO)
      → $this->log()
      → $this->dispatchEvent() [queued, fires after commit]
    ← ActionResponse
  ← Flash message / redirect / re-render
```

When debugging or reviewing code, trace this path. If any step is missing or out of order, there's likely a bug or architecture violation.

### Module Boundary Awareness

- Each module owns its full stack: Models, Actions, Livewire, Events, Policies, Services
- Each Domain lives under `app/Modules/{Module}/Domain/{Domain}/` and owns its domain's full stack
- Cross-module imports are **allowed** but prefer events for side effects
- If Module A needs to react to Module B's mutation, use an Event — don't import B's Actions
- Shared code (base classes, contracts, exceptions) lives in `app/Modules/Core/`

### Testing Senses

#### Spec-Driven Minimalism

**Write only the tests the spec requires, then stop.** This is deliberate, not lazy — it speeds up development and verification (spec-scoped tests run in seconds vs. 10+ minutes for the full suite), reduces resource usage (~2GB+ RAM for the full suite), and reduces cognitive overwhelm: a suite that maps 1:1 to requirement IDs is self-explaining. Every test answers "which `FR-*` / `NFR-*` / `UC-*` does this verify?"; if the answer is "none", don't write it.

#### Verification Strategy Selection

**Core principle:** Always ask "can I verify this without running tests?" before reaching for the test suite. The full suite consumes ~2GB+ RAM and 10+ minutes.

| Change type | Lightest verification |
|-------------|----------------------|
| Translation keys | `vendor/bin/pint --dirty --test` + tinker echo |
| Config / docs | Visual inspection |
| Blade / CSS / JS | `npm run build` |
| Single method refactor | `php artisan test --compact --filter={ClassName}` |
| Cross-module refactor | `vendor/bin/pest --testsuite={Module}` |
| New feature / business logic | Full suite ONCE, after all changes batched |

#### Test Pattern Recognition

**Spec first, always.** Before choosing a pattern, map the requirement: which `FR-*` / `NFR-*` / `UC-*` ID in `docs/specs/{ID}-{feature}.md` does this test verify? Test descriptions carry that ID.

| What you're testing | Pattern to follow |
|---------------------|-------------------|
| Command Action (spec-defined mutation) | Arrange (factory + DTO) → Act (execute) → Assert (assertModelExists + ActionResponse) |
| Read Action (spec-defined query) | Arrange (seed data) → Act (execute) → Assert (typed return, collection shape) |
| Entity | Test only the business-rule methods a requirement names; no DB needed |
| DTO | Test the shape the spec's §6 contract defines; no DB needed |
| Enum | Test `label()` / transitions only for the cases and rules the spec lists |
| Livewire | Test render, mount, form submission, authorization; use `actingAs()` |
| Policy | Test `allow` / `deny` for each role; no DB needed beyond the model |

#### Test Health Indicators

| Symptom | Diagnosis |
|---------|-----------|
| Test passes in isolation, fails in suite | Shared state or ordering issue — check `LazilyRefreshDatabase` |
| `Class "X" not found` | Autoload stale — `composer dump-autoload` |
| `SQLSTATE[HY000]` | Migration missing — `php artisan migrate:fresh` |
| Test times out | Infinite loop or queue not drained — add `Queue::fake()` |
| Flaky test (sometimes passes) | Race condition or missing `RefreshDatabase` — isolate the test |
| Test was failing before your change | Pre-existing issue — flag it, don't fix it unless asked |

#### Coverage = Spec Coverage

**Coverage is measured in spec requirements covered — never lines of code.** A requirement with no test is a **spec gap** (fill it). A test with no requirement is **orphan noise** (remove it). The old per-layer percentages (Enum/Entity/DTO 100%, Actions ≥90%, Livewire ≥80%) were removed because they produced padding tests; they may be used only as an internal diagnostic, never as a mandate.

| Question | Answer |
|----------|--------|
| Which spec does this test verify? | Read `docs/specs/index.md` → `docs/specs/{ID}-{feature}.md` |
| Does this test trace to a requirement ID? | If no → orphan, candidate for deletion |
| Does every requirement have a test? | If no → spec gap, write the test |
| Is the scenario beyond what the spec names? | If yes → noise, don't write it |

### Documentation Senses

#### Doc Drift Detection

Doc drift happens when code changes but docs don't. Detect it by asking:

| Question | How to check |
|----------|-------------|
| Does the doc's file listing match the actual directory? | `ls app/Modules/{Module}/Domain/{Domain}/` vs doc |
| Does the Actions table list all current Actions? | `find app/Modules/{Module}/Domain/{Domain}/Actions -name '*Action.php'` |
| Does the Entity description match the actual methods? | Read the Entity class |
| Do the enum cases in the doc match the code? | Read the Enum class |
| Do the migration descriptions match the actual migrations? | Check `database/migrations/` |
| Are the cross-references still valid? | Verify every `[text](path)` resolves |

#### Mismatch Resolution — Git History First

When code and docs disagree — or a claim cannot be confirmed in either — the discrepancy may be an **unrecorded change**. Do NOT assume the code is the source of truth just because it runs, nor that the doc is authoritative just because it was written first. **Both can be stale.**

Before picking a side:

1. **Check git history** (`git log -p -- {file}`, `git blame {file}`) for the code and the doc to see when each last changed
2. **Look for the intent** — does a commit message explain the change (e.g., a refactor that moved a file, or an intentional behavior change that skipped the docs)?
3. **If a commit explains it**, update the other side to match the documented intent
4. **If neither side explains it**, treat it as a finding: report it, don't silently decide

Only trust a claim after confirming it against the codebase **and** git history.

#### Tier Selection

| Content type | Tier | Example |
|-------------|------|---------|
| "Why does this module exist?" | Conceptual | `docs/refs/modules/{module}.md` |
| "What business rules govern enrollment?" | Conceptual | `docs/refs/modules/enrollment.md` |
| "Which files implement the Action?" | Reference | `docs/refs/modules/enrollment-reference.md` |
| "What's the table schema?" | Reference | `docs/refs/modules/enrollment-reference.md` |
| "Why did we choose Actions over Services?" | Conceptual (architecture) | `docs/guides/arch/action-pattern.md` |
| "What's the Action contract?" | Reference (architecture) | `docs/guides/arch/action-pattern.md` |

**Rule of thumb:** If it explains *why*, it's conceptual. If it explains *what* or *how*, it's reference.

#### When to Update Docs

| Code change | Doc to update |
|-------------|--------------|
| New Action added | Module reference doc (Actions table) |
| Entity method changed | Module conceptual doc (business rules) |
| Enum case added/removed | Module reference doc (enum table) |
| New migration | Module reference doc (schema section) |
| New module created | `docs/refs/modules/index.md` + conceptual + reference |
| Config key added | Module reference doc (config section) |
| Route added/changed | Module reference doc (Routes table) |
| Base class method changed | `docs/guides/arch/{pattern}-pattern.md` |
| Invariant added/changed | `AGENTS.md` |

#### History Discipline (Git as Source of Truth)

No inline `Last updated` metadata in markdown files. Document freshness and change history are tracked via git:

```bash
git log --follow -- <file>      # history of a doc
git diff -- <file>              # what changed in this branch
git log --since="14 days ago" --oneline -- docs/
```

Write a descriptive commit message (`type(scope): desc`); do not duplicate it inside the file.

#### Link Integrity

Before committing any doc change:
1. Every `[text](path)` resolves to an existing file
2. Every `[text](path#anchor)` matches an existing heading
3. No content is duplicated — cross-reference instead
4. `## Where to Find It` is the standard footer (not `## References`)

### Metacognitive Loop

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — Read relevant docs and existing code; verify paths and signatures; consider multiple approaches
2. **EVALUATE** — Does it match requirements (FR/NFR/UC from the governing spec)? Respect layer boundaries? Do ONE thing?
3. **VERIFY** — Lint + static analysis + tests pass; no debug calls; `__()` for strings
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer
   - **Split** when: task classified **L** (Size Triage) or scope grew beyond one session — inform the user, propose a session plan, never push through in one pass
   - **Escalate** when: the decision changes scope or architecture, or a governing spec is missing or ambiguous — surface it to the user rather than guessing

### Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_files.py` | File counts and lines of code per module | `python3 tools/scan_files.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 tools/scan_architecture.py` |
| `scan_violations.py` | C1-C8, D1-D6 invariant violations | `python3 tools/scan_violations.py` |
| `scan_class_contracts.py` | Action/Entity/DTO/Model/Enum class contracts | `python3 tools/scan_class_contracts.py` |
| `scan_security.py` | XSS, SQLi, CSRF, auth patterns | `python3 tools/scan_security.py` |
| `scan_naming.py` | Naming conventions | `python3 tools/scan_naming.py` |
| `scan_conventions.py` | strict_types, Fillable, debug calls | `python3 tools/scan_conventions.py` |
| `scan_doc_links.py` | Broken links in docs | `python3 tools/scan_doc_links.py` |
| `scan_tests.py` | Per-module test results | `python3 tools/scan_tests.py` |
| `scan_skills.py` | SKILL.md meta-framework consistency | `python3 tools/scan_skills.py` |
| `scan_issues.py` | GitHub issues by module/severity | `python3 tools/scan_issues.py` |
| `scan_dead_code.py` | Dead code detection | `python3 tools/scan_dead_code.py` |

Output: `tools/outputs/{timestamp}-{description}.json`.

**Automation-First:** before doing manual or repeated work, check `tools/` and this table for an existing scanner or helper. Never redo by hand what a script does. If a recurring pattern has no script, load `script-automation` to add one.

---

## Skill Map — Which Skill to Load

| Task | Skill | Notes |
|------|-------|-------|

| Every instruction, any task | `agent-workflow` (see §Agent Workflow above) | **ALWAYS apply first** — universal workflow, no exceptions |
| Every instruction, any task | `context-awareness` (see §Context Awareness above) | **ALWAYS apply second** — universal orientation layer, no exceptions |
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
| Writing scripts | `script-automation` | Standards for `tools/` devtools |
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
