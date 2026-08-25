---
name: agent-workflow
description: "SDLC Phase: ORCHESTRATION. Single source of truth for the agent workflow — the 5-step pipeline Understand → Plan → Implement → Verify → Summarize, narration discipline, size triage, phase classification, and verification strategy. Load on EVERY instruction before any other skill. All other skills reference this instead of restating the workflow."
downstream:
  - context-awareness
  - arch-guard
  - code-writing
  - code-refactoring
  - doc-writing
  - feature-building
  - issue-writing
  - laravel-best-practices
  - livewire-development
  - medialibrary-development
  - pest-testing
  - pulse-development
  - qa-protocol
  - script-automation
  - security-audit
  - spec-audit
  - spec-writing
  - sync-docs
  - tailwindcss-development
  - test-writing
---

# Agent Workflow — Canonical

> **Prerequisite:** None. This skill defines the workflow every other skill follows; load it first
> on every instruction, then load the skill matching the task (Skill Map in AGENTS.md).

## When to Activate

Load this skill on **every instruction**, regardless of task type or size. It defines the single
workflow all agents must follow. Other skills do NOT restate this workflow — they reference it and
add only their task-specific steps. If a loaded skill repeats a generic workflow step already
defined here, ignore the duplication and follow this skill (it is the source of truth).

## Workflow — 5 Steps

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
| **3. Implement** | Surgical execution + documentation | *What changes, minimally and cleanly?* | Code edits (preserving unrelated code), doc/PHPDoc updates, metadata `Last updated`, automation/scripts |
| **4. Verify** | Quality gates, batched once | *Is anything broken or lost?* | `git status`/`diff` review, style checks, targeted & arch-guard scans, full suite only on-demand |
| **5. Summarize** | Commit and report | *What was delivered and what remains?* | Staged commit `type(scope): desc`, final report (changes, verification, caveats, next steps) |

> **Mapping from legacy 9-step (for reference):** Understand absorbs `Understand + Define & Scope`; Plan absorbs `Explore + Plan + Design`; Implement absorbs `Develop + Document`; Verify = `Test & Verify`; Summarize = `Commit & Report`. All invariants and checks remain — only the grouping is simplified.

### Step 1 — Understand

Internalize intent, not literal words. Do all scoping before reading files.

- **Intent & constraints** — what the user actually wants, hidden requirements, non-goals, hard constraints (deadlines, scope limits, compatibility).
- **Spec-First Doctrine (non-negotiable)** — locate the governing spec in `docs/specs/` via `docs/specs/index.md` (foundation, module, or feature); read its FR/NFR/UC IDs. No behavior without a requirement ID; if none exists, write the spec first — spec-first, never fix-first. Spec outranks literal wording and existing code. If spec and code disagree, fix code to spec; if spec is demonstrably wrong, amend spec with a recorded decision first, then align code and tests.
- **Define & scope** — list affected modules, layers (Action/Entity/DTO/Model/Livewire), and files; identify blockers (pending migrations, config, service registration, permission/policy gaps).
- **Classify** — SDLC phase (see §Phase Classification) and size S/M/L (see §Size Triage). If **L** (>10 files / multi-module / cross-cutting), inform the user in one short paragraph and split into sessions before proceeding (L-size protocol).
- **Instruction ordering** — if the message batches 2+ instructions, decompose → score by impact-to-effort ratio → sort → honor dependencies → group same-area work → surface resulting order only when it differs from the user's sequence (see `rules/instruction-ordering.md`).
- **Task type** — bug fix / feature / refactor / docs / audit / review / tooling; determines which downstream skills to load next (load only what the task actually uses; an unneeded skill bloats context).

**Exit criteria:** governing spec identified (or explicit recorded decision to proceed without), scope bounded, phase & size classified, instruction order decided.

### Step 2 — Plan

Gather context, decide approach, design contracts — before touching code.

- **Explore (context gathering)** — read module docs, architecture docs (`docs/guides/arch/*.md`), conventions (`docs/conventions.md`), and the **full current content of every file you may touch**; survey `scripts/` for existing devtools before manual work (Automation-First — reuse `scan_*.py` scanners instead of manual greps; if 3+ items would be touched repetitively, script it).
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
- **Automation-First in execution** — for repetitive / batch / pattern work (bulk renames, 3+ similar edits, seed data, mass scans), script it or reuse a devtool in `scripts/`; batch your own edits into few passes instead of many round-trips.
- **Documentation-first** — update module docs, architecture docs, conventions, and PHPDoc on public methods **before/after** code as part of the same step; update metadata lines (`> **Last updated:**` + `**Changes:**`) in every touched doc; keep `docs/specs/*.md` as SSOT and align docs ↔ code ↔ tests (Clean Code & Dedup-Align Doctrine — deduplicate on sight, reuse or extract instead of copy-pasting).
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
  - `python3 scripts/scan_violations.py` (C1-C8, D1-D6)
  - `python3 scripts/scan_class_contracts.py` (Action/Entity/DTO/Model/Enum)
  - `python3 scripts/scan_security.py` (XSS, SQLi, CSRF, auth)
  - `python3 scripts/scan_naming.py` · `scan_conventions.py` · `scan_doc_links.py`
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

**Exit criteria:** clean commit(s), report delivered, repo left cleaner than found.

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

The full rule (the impact-to-effort rule, scoring scale, worked examples, and commit grouping) lives in `rules/instruction-ordering.md` — apply that rule, not the summary below.

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

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Key rules (non-negotiable) | `rules/key-rules.md` | Every instruction |
| Instruction ordering (impact-to-effort) | `rules/instruction-ordering.md` | Batched/multi-instruction messages |

## References

| Topic | Doc |
|-------|-----|
| Full workflow & module map | `AGENTS.md` |
| Verification matrix | `AGENTS.md` §Verification Strategy |
| Pre-commit checklist | `AGENTS.md` §Pre-commit Checklist |
| Skill map | `AGENTS.md` §Skill Map |
| Conventions & invariants | `docs/conventions.md` |
| Instruction ordering rule | `rules/instruction-ordering.md` (this skill) |
