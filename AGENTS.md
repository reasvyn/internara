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

```
UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE
```

| Step | Purpose | Core questions answered | Key outputs |
|------|---------|-------------------------|-------------|
| **1. Understand** | Intent, scope, constraints before any exploration | What is asked? What is the governing spec? What is affected? How big is it? | Governing spec + FR/NFR/UC IDs, phase & size (S/M/L), affected modules/layers/files, blockers, reordered instruction list |
| **2. Plan** | Context gathering, approach selection, and design | What exists today? Which approach? What contracts? | Read file/docs inventory, 2+ considered approaches, chosen design (Action triad, Entity/DTO/Model contracts, error & cache strategy), test & doc plan |
| **3. Implement** | Surgical execution + documentation | What changes, minimally and cleanly? | Code edits (preserving unrelated code), doc/PHPDoc updates, automation/scripts |
| **4. Verify** | Quality gates, batched once | Is anything broken or lost? | `git status`/`diff` review, style checks, targeted & arch-guard scans, full suite only on-demand |
| **5. Summarize** | Commit and report | What was delivered and what remains? | Staged commit `type(scope): desc`, final report (changes, verification, caveats, next steps) |

> **Mapping from legacy 9-step (for reference):** Understand absorbs `Understand + Define & Scope`; Plan absorbs `Explore + Plan + Design`; Implement absorbs `Develop + Document`; Verify = `Test & Verify`; Summarize = `Commit & Report`. All invariants and checks remain — only the grouping is simplified.

### Step 1 — Understand

Internalize intent, not literal words. Do all scoping before reading files.

- **Intent & constraints** — what the user actually wants, hidden requirements, non-goals, hard constraints (deadlines, scope limits, compatibility).
- **Spec-First Doctrine (non-negotiable)** — locate the governing spec in `docs/specs/` via `docs/specs/index.md` (foundation, module, or feature); read its FR/NFR/UC IDs. No behavior without a requirement ID; if none exists, write the spec first — spec-first, never fix-first. Spec outranks literal wording and existing code. If spec and code disagree, fix code to spec; if spec is demonstrably wrong, amend spec with a recorded decision first, then align code and tests.
- **SSoT Priority Framework (when sources conflict)** — `adr > specs > guides > code > refs` — higher wins when resolving contradictions. **Never trust blindly:** even the highest (ADR) must be verified via `git log --follow`, `git blame`, and intent before acting. If history is silent or contradictory, treat as a finding and justify the decision comprehensively.
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
- **Risk & verification plan** — how verification will run (change-type matrix in AGENTS.md §Verification Strategy), which arch-guard scanners apply, and whether full suite is justified (on-demand only).

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
  - `python3 tools/scan_naming.py` · `tools/scan_conventions.py` · `tools/scan_doc_links.py`
- **Full verification on-demand only** (merge-day or user explicitly asks):
  - `php artisan test --compact` (full suite, all modules)
  - Change-type matrix in `AGENTS.md` §Verification Strategy decides what is required for the current change type; default is targeted checks, not full suite.

**Exit criteria:** change-type-appropriate gates pass; arch-guard clean or deviations explicitly justified/recorded; no silent tolerance of pre-existing warnings — fix safe adjacent issues or file a GitHub issue (`issue-writing` skill) before ending the session.

### Step 5 — Summarize

Close the loop: version-control checkpoint, commit, and a concise final report.

- **Final git review** — `git status` + `git diff` one last time; stage **only intended files**, never secrets or unrelated changes; confirm nothing was lost.
- **Commit** — format `type(scope): description` — types `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`; scope = module name; one concern per commit (group quick wins / interdependent changes; split strategically-separate concerns).
- **Report (surface to user):** what changed (files/modules/specs), what was verified (which gates ran and their result), caveats / known limitations, and **recommended next steps** (pending work, follow-ups, or L-size session plans). Keep it short — narration discipline applies.
- **Session handling** — for M-size: one checkpoint before commit; for L-size: per-session report + `git status`/`diff` review at the end of each session, never attempting L-size in one pass.
- **Pre-commit checklist** (AGENTS.md) must pass: strict types, no debug calls, `__()` coverage, Action triad + DTO rule, Entity delegation, cache registry, N+1 check, escaped output, tests traceable to spec, pint/arch-guard as appropriate.
- **Capture learnings (Self-Improvement Loop, judgment-based)** — write to `memory/` (evolving learnings) or `context/` (mandatory facts) **only when the information is worth saving for a future agent**: a durable decision, non-obvious trap/correction, recurring pattern, or constraint a future session would re-learn. Not an automatic per-summarize step — skip when nothing novel emerged. When captured: update the topic file in place, write a descriptive commit message, add/update a row in `context/index.md` or `memory/index.md`, and append a one-liner to `memory/learning-log.md`. Promote a signal seen ≥2 times to `rules/` or a skill; durable decisions get an ADR in `docs/adr/`. Reserve `/internara-learn --deep` (`git diff` mining) for sessions with substantive changes.

**Exit criteria:** clean commit(s), report delivered, **learning captured** (memory updated, repeats promoted), repo left cleaner than found.

---

## Project Snapshot — Quick Reference

> For the full detailed map, read `.agents/context/project-snapshot.md`. For tech stack, design principles, and boundaries, read `.agents/context/project-identity.md`.

### Identity

| Fact | Value |
|------|-------|
| **Version** | v0.15.9 — Stabilization |
| **Scope** | 19 modules = 18 business + UI + Core (696 PHP files, 45 migrations, 62 spec files, 16 route files) |
| **Single-tenant** | No `tenant_id` overhead — one instance per school |
| **DB** | SQLite default / MySQL 8 / MariaDB 10.6 / PG 15 |
| **Deploy** | Shared hosting ($5/mo) or VPS/Docker Compose |
| **License** | MIT |

### Architecture — 4-Layer + Action Triad

```
User → Livewire → Command Action::execute(DTO) → Entity::fromModel() → Model::create/update
      → $this->log() → $this->dispatchEvent() [queued, after commit] → ActionResponse
```

| Layer | Role |
|-------|------|
| **4 Presentation/UI** | Livewire, Blade, Policies, Routes, TallstackUI v4 + Alpine + Tailwind v4 |
| **3 Business/Domain Ops** | Command/Read/Process Actions, Events/Listeners |
| **2 Data/Persistent** | Models, Entities, DTOs, Enums |
| **1 Framework/Infra** | Base classes, Contracts, Exceptions, Services |

### Module Health (Summary)

- **Production-Ready:** Core, Auth, User, Settings, Setup, SysAdmin, Academics
- **Stable-Needs Attention:** Program, Partners, Enrollment, Journals, Incident, Assignment, Reports
- **Needs Work P0:** Assessment, Certification, Document
- **Skeleton:** Evaluation

Full details: `.agents/context/module-health.md`

---

## Context Awareness — Project Orientation

> **Prerequisite:** None — this is the orientation layer loaded after §Agent Workflow.

### When to Activate

Load this section at the start of every session. It provides the mental model all downstream skills depend on.

### Orientation Workflow

This is the **orientation layer** — it does NOT write code or run tests; it builds the mental model all downstream skills depend on. Follow the §Agent Workflow 5-step pipeline (Understand → Plan → Implement → Verify → Summarize) and **Size Triage** (S/M/L session splitting) for the overall instruction; this adds the orientation steps and memory-keeping duties below.

#### Construct — Orientation

- Read the user's instruction carefully; identify the **intent**, not just the literal request
- Determine scope: single file change, cross-module refactor, or new feature
- **Locate the governing spec** in `docs/specs/` (via `docs/specs/index.md`) — read the relevant FR/NFR/UC IDs; if no spec exists for the work, stop and raise it (write the spec first)
- Identify which module(s) are affected
- Read relevant docs: module docs, pattern docs, reference docs
- **Check mandatory known context** — read `.agents/context/index.md` and load any context file matching the task topic (intentional constraints, deploy caveats, dependency pins, known states). Context is **read-only curated knowledge** that every agent must know.
- **Check autonomous memory** — read `.agents/memory/index.md` (and `learning-log.md`) for prior session learnings relevant to the task. Memory is **agent-written evolving knowledge**.
- Verify paths, class names, signatures against actual code — never trust docs blindly; on code/doc mismatch, check git history before deciding which side is correct

#### Agent Memory — `.agents/context/` vs `.agents/memory/`

Two distinct stores with different lifecycles — do not conflate them:

| Store | Path | Nature | Who writes | When to read |
|-------|------|--------|------------|--------------|
| **Known Context (mandatory)** | `.agents/context/` | Curated, must-read before tasks; intentional constraints, deploy caveats, health tiers, deprecated states | Maintainers (human-approved) — agent updates only on proven inconsistency | **Every session start** — `index.md` + matching topic file |
| **Autonomous Memory** | `.agents/memory/` | Evolving, agent-owned learnings; decisions, corrections, failures, patterns, gaps discovered during sessions | Agents autonomously (every session) | **During orientation** if task overlaps prior learnings; **always write** at Summarize |

**Maintain Known Context (`.agents/context/`):**
- Context files are **normative**. Do not invent new facts — if a context file conflicts with reality (code/spec/docs/config changed), update it **directly in the same run** — fix the stale fact and commit with a descriptive message. Never defer.
- To add a new mandatory fact: create `.agents/context/{context}-{issue-name}.md` (flat, kebab-case) and register it in `.agents/context/index.md`. Record only facts where a future agent would make a costly wrong assumption without it; skip trivial/fluid facts.
- Keep each file self-contained (paths, commands, rationale); never duplicate a fact elsewhere — update the existing file instead.

**Maintain Autonomous Memory (`.agents/memory/`):**
- **Capture is judgment-based** — write into `.agents/memory/` (self-contained topic files, registered in `.agents/memory/index.md`, one-liner appended to `.agents/memory/learning-log.md`) only when the learning is worth saving for a future agent; skip when nothing novel emerged. Not automatic per-summarize.
- **Memory is local-only (gitignored).** Committed files outside it may *mention* the `memory/` path conceptually but must **never cross-reference its contents** — readers of committed docs don't have your local copy, so pointers to a specific `memory/` file break for them. Cross-refs between files are allowed only inside `memory/`.
- Promote a signal seen ≥2 times to `rules/` or a skill; durable decisions get an ADR in `docs/adr/`. One-offs stay in memory — no rule-bloat.
- Memory is append-evolving; context is curated-stable. Update `memory/` in place with a descriptive commit message; never duplicate a topic.

**House style (both stores):** `## Description`, plain language, an `## AI Agent Guides` decision table where helpful. No inline `Last updated` metadata — history lives in `git log`.

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

---

## Navigation Patterns

| Need to find... | Look here |
|-----------------|-----------|
| Business logic | `app/Modules/{Module}/Domain/{Domain}/Actions/` |
| Business rules | `app/Modules/{Module}/Domain/{Domain}/Entities/` |
| Data structure | `app/Modules/{Module}/Domain/{Domain}/Models/` |
| Data transfer | `app/Modules/{Module}/Domain/{Domain}/Data/` (DTOs) |
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

---

## Pattern Recognition

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

---

## Data Flow Tracing

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

---

## Module Boundary Awareness

- Each module owns its full stack: Models, Actions, Livewire, Events, Policies, Services
- Each Domain lives under `app/Modules/{Module}/Domain/{Domain}/` and owns its domain's full stack
- Cross-module imports are **allowed** but prefer events for side effects
- If Module A needs to react to Module B's mutation, use an Event — don't import B's Actions
- Shared code (base classes, contracts, exceptions) lives in `app/Modules/Core/`

---

## Testing Senses

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

---

## Documentation Senses

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

#### Mismatch Resolution — Git History First (with SSoT Priority)

When code and docs disagree — or a claim cannot be confirmed in either — the discrepancy may be an **unrecorded change**. Do NOT assume the code is the source of truth just because it runs, nor that the doc is authoritative just because it was written first. **Both can be stale.**

**SSoT Priority Framework:** `adr > specs > guides > code > refs` — higher wins when resolving contradictions, but **never trust blindly**. Even the highest (ADR) must be verified via `git log --follow`, `git blame`, and intent before acting. If history is silent or contradictory, treat as a finding and justify the decision comprehensively.

Before picking a side:

1. **Check git history** (`git log -p -- {file}`, `git blame {file}`) for the code and the doc to see when each last changed
2. **Look for the intent** — does a commit message explain the change (e.g., a refactor that moved a file, or an intentional behavior change that skipped the docs)?
3. **If a commit explains it**, update the other side to match the documented intent
4. **If neither side explains it**, treat as a finding: report it, don't silently decide

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

#### GitHub Version Senses

Track the version that will be deployed — local, tag, and VPS often drift.

| Question | How to check |
|----------|-------------|
| What version is the code at? | `cat composer.json \| grep version` + `git describe --tags` + `git tag --sort=-v:refname \| head` |
| What version is on VPS? | `ssh internara-vps "cat ~/apps/internara/composer.json \| grep version; git -C ~/apps/internara describe --tags; git log --oneline -1"` |
| Did the latest tag reach the VPS? | `ssh internara-vps "git -C ~/apps/internara describe --tags"` vs `git describe --tags` (local) — must match the pushed `vX.Y.Z` |
| Is the Docker image stale? | `GIT_URL` in `docker-compose.yml` (`#main` vs `#vX.Y.Z`), `docker images internara-app` `CREATED`, `docker exec ... cat /app/public/index.php \| head` |
| Why is VPS on older version? | `composer.json` version differs from the last pushed tag → bump `version`, create `git tag vX.Y.Z`, `git push origin vX.Y.Z` — `release.yml` deploys on tag push |

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

---

## Metacognitive Loop

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — Read relevant docs and existing code; verify paths and signatures; consider multiple approaches
2. **EVALUATE** — Does it match requirements (FR/NFR/UC from the governing spec)? Respect layer boundaries? Do ONE thing?
3. **VERIFY** — Lint + static analysis + tests pass; no debug calls; `__()` for strings
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer
   - **Split** when: task classified **L** (Size Triage) or scope grew beyond one session — inform the user, propose a session plan, never push through in one pass
   - **Escalate** when: the decision changes scope or architecture, or a governing spec is missing or ambiguous — surface it to the user rather than guessing

---

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `tools/scan_files.py` | File counts and lines of code per module | `python3 tools/scan_files.py` |
| `tools/scan_architecture.py` | Component counts per module, submodule structure | `python3 tools/scan_architecture.py` |
| `tools/scan_arch_patterns.py` | Architecture/pattern adherence (used by `composer arch`) | `python3 tools/scan_arch_patterns.py` |
| `tools/scan_module_boundaries.py` | Cross-module boundary checks (used by `composer arch`) | `python3 tools/scan_module_boundaries.py` |
| `tools/scan_ui_consistency.py` | UI/component consistency (used by `composer arch`) | `python3 tools/scan_ui_consistency.py` |
| `tools/scan_violations.py` | C1-C8, D1-D6 invariant violations | `python3 tools/scan_violations.py` |
| `tools/scan_class_contracts.py` | Action/Entity/DTO/Model/Enum class contracts | `python3 tools/scan_class_contracts.py` |
| `tools/scan_security.py` | XSS, SQLi, CSRF, auth patterns | `python3 tools/scan_security.py` |
| `tools/scan_naming.py` | Naming conventions | `python3 tools/scan_naming.py` |
| `tools/scan_conventions.py` | strict_types, Fillable, debug calls | `python3 tools/scan_conventions.py` |
| `tools/scan_doc_links.py` | Broken links in docs | `python3 tools/scan_doc_links.py` |
| `tools/scan_spec_tests.py` | Spec-to-test coverage mapping | `python3 tools/scan_spec_tests.py` |
| `tools/scan_tests.py` | Per-module test results | `python3 tools/scan_tests.py` |
| `tools/scan_issues.py` | GitHub issues by module/severity | `python3 tools/scan_issues.py` |
| `tools/scan_dead_code.py` | Dead code detection | `python3 tools/scan_dead_code.py` |
| `tools/run_module_tests.py` | Run tests for a single module | `python3 tools/run_module_tests.py --module {Module}` |
| `tools/tool_runner.py` | Orchestrate multiple scanners | `python3 tools/tool_runner.py --scanner violations,naming` |

All scanners accept `--module {Name}`, `--format summary|text|html|markdown`, `--output <path>`, `--json`, `--strict`, `--quiet`. Output (default JSON): `tools/outputs/{timestamp}-{description}.json`. Full interface in `tools/README.md`.

**Automation-First:** before doing manual or repeated work, check `tools/` and this table for an existing scanner or helper. Never redo by hand what a script does. If a recurring pattern has no script, load `script-automation` to add one.

---

> **AGENTS.md is now lean** — containing only the 5-step workflow, project snapshot, and context awareness.
> All supporting maps moved to `.agents/context/`: project-identity, phase-classification, instruction-ordering,
> pre-existing-defects, skill-rules, documentation-senses, metacognitive-loop, skill-map, quick-reference,
> version-bump-guide.
> See `.agents/context/index.md` for the full context index.
