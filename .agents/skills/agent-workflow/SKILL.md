---
name: agent-workflow
description: "SDLC Phase: ORCHESTRATION. Single source of truth for the agent workflow — the 9-step pipeline mapped to the 4-phase skill model, narration discipline, size triage, phase classification, and verification strategy. Load on EVERY instruction before any other skill. All other skills reference this instead of restating the workflow."
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

## Workflow — 9 Steps ↔ 4 Phases

The pipeline runs **silently**: the 9 steps are internal reasoning — never narrate them. Surface to
the user **only**:

1. Ambiguity that needs their input.
2. A decision that changes scope, structure, or behavior.
3. An L-size session plan (one short paragraph).
4. One checkpoint before commit (M-size) or per-session (L-size).
5. The final report (what changed, what was verified, caveats).

Every sentence must carry new information or a decision; if it does neither, drop it.

| Skill 4-phase | 9-step pipeline |
|---------------|-----------------|
| **1. Construct** — spec, context, scope, approach | Steps 1-5 (Understand → Design) |
| **2. Execute** — do the work | Step 6 (Develop) |
| **3. Verify** — quality gates | Step 7 (Test & Verify) |
| **4. Report & Commit** | Steps 8-9 (Document → Commit & Report) |

### Phase 1 — Construct (Steps 1-5)

- **Understand** the user's intent, not just literal words; classify SDLC phase (table below) and
  size (S/M/L); locate the governing spec in `docs/specs/` (Spec-First Doctrine: no behavior
  without a requirement ID; if none exists, write the spec first); if the message batches multiple
  instructions, reorder them by impact-to-effort ratio (§Instruction Ordering below)
- **Define & Scope** — list affected modules/layers/files; check blockers (migrations, config,
  registration)
- **Explore** — read module docs, architecture docs, and the full current content of every file you
  may touch; survey `scripts/` for existing tooling before manual work (Automation-First)
- **Plan** — consider 2+ approaches; decide action type (Command/Read/Process), entity boundaries,
  DTO needs (C7: 3+ params), test strategy, doc changes
- **Design** — class contracts: Action signature, Entity `final readonly` + `fromModel()`, DTO
  `BaseData`, Model `#[Fillable]`, error handling (C8), cache strategy (C4)

### Phase 2 — Execute (Step 6)

- Edit surgically (smallest change, never full-rewrite large files); preserve unrelated code
- Follow conventions: `declare(strict_types=1)` (D1), no debug calls (D2), `__()` for user strings
  (D3), no raw request to create/update (D5), FKs with onDelete/onUpdate (D6), no Model mutations in
  Livewire (C1), constructor injection (C2)
- For repetitive/batch/pattern work, script it or reuse a devtool (Automation-First)

### Phase 3 — Verify (Step 7)

- Batch all changes first, then verify once (full suite ~2GB+, 10+ min)
- Version-control verification: `git status` + `git diff` before/after every change — only intended
  files changed, nothing lost
- Incremental checks: `vendor/bin/pint --dirty --test --format agent`, `npx prettier --check`
- Targeted tests: `vendor/bin/pest --testsuite={ModuleName}`, `php artisan test --compact --filter={ClassName}`
- Arch-guard scripts: `python3 scripts/scan_violations.py`, `scan_class_contracts.py`,
  `scan_security.py`, `scan_naming.py`, `scan_conventions.py`, `scan_doc_links.py`
- Full suite + PHPStan on-demand only: `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress`
- See AGENTS.md Verification Strategy for the change-type matrix (what to run for each change type)

### Phase 4 — Report & Commit (Steps 8-9)

- Update docs before/after code (documentation-first): module docs, architecture docs, conventions,
  PHPDoc on public methods; update metadata lines (`> **Last updated:**` + `**Changes:**`)
- Final git review (`git status` + `git diff`); stage only intended files, never secrets
- Commit format: `type(scope): description` — types: `feat`, `fix`, `refactor`, `docs`, `chore`,
  `test`, `perf`, `security`; scope = module name
- Report: what changed, what was verified, caveats, **recommended next steps** (if any pending work, follow-ups, or L-size session plans)

## Phase Classification — Adaptive Depth

Classify the instruction into an SDLC phase. Full = mandatory complete depth · Light = executed but
minimal · Note = skip silently. Anything not listed defaults to Note.

| SDLC Phase | Full (mandatory) | Light | Note (skip silently) |
|------------|------------------|-------|----------------------|
| **Support** | Understand, Explore, Document | Define | Plan, Design, Develop, Test, Commit |
| **Analysis** | Understand, Define, Explore, Document | Plan | Develop, Test, Commit (findings only) |
| **Planning** | Understand, Define, Plan, Document | Explore | Develop, Test, Commit (unless requested) |
| **Design** | Understand, Define, Plan, Design, Document | Explore | Develop, Test, Commit (unless requested) |
| **Implementation** | **All 9 steps** | — | — |
| **Testing** | Understand, Define, Develop, Test, Document | Explore, Plan | Commit (unless requested) |
| **Documentation** | Understand, Explore, Document | Define, Plan | Develop, Test, Commit |
| **Tooling** | Understand, Define, Plan, Develop, Test, Document | Explore | Commit (unless requested) |
| **Maintenance** | Understand, Define, Test, Document | Explore, Plan, Develop | Commit (unless requested) |

## Size Triage — Session Splitting

| Size | Criteria | Execution | User check-in |
|------|----------|-----------|---------------|
| **S** | ≤3 files, single concern, no cross-module | Single pass, full 9 steps at phase depth | None |
| **M** | 4-10 files, 2-3 concerns, or cross-layer | Single session, staged internally, batch verification | One checkpoint before commit |
| **L** | >10 files, multi-module, cross-cutting | **MUST split into multiple sessions** | **MUST inform the user first** |

**L-size protocol:** after Construct, tell the user in one short paragraph: *"This instruction is too
broad for a single pass — I will split it into N sessions"* + the session list. Execute sessions in
order; each session ends with `git status` + `git diff` review, targeted verification, and a short
report. Never attempt L-size in one pass.

## Instruction Ordering — High-Impact, Low-Effort First

The user sometimes batches instructions in random order. Before executing any of them, reorder the
batch by **impact-to-effort ratio** — quick wins first, heavy lifts scheduled. Apply this to every
multi-instruction message; run the scoring silently and surface only the resulting order.

The full rule (the impact-to-effort rule, scoring scale, worked examples, and commit grouping) lives
in `rules/instruction-ordering.md` — apply that rule, not the summary below.

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
5. **Group** same-area instructions into one pass (batch file touches, batch verification)
6. **Surface** the resulting order in one short paragraph when it differs from the user's sequence

## Computational Thinking — Decision Loop

Before each action: *predict outcome → act → verify → adjust*. Anticipate the next step. Resolve
ambiguity yourself when the cost is low; escalate to the user only when the decision changes scope
or architecture.

| Pillar | Application |
|--------|-------------|
| **Decomposition** | Break into sub-problems (files, layers, concerns); solve each, then integrate |
| **Pattern recognition** | Classify the instruction; reuse known patterns (skills, docs, conventions) |
| **Abstraction** | Filter irrelevant detail; focus on entities, flows, contracts, invariants |
| **Algorithm design** | Plan ordered steps with clear inputs/outputs |

## Pre-existing Defects — Fix or File

- **Fix by default, after the main work**: pre-existing warnings/errors noticed along the way (lint,
  PHPStan, arch-guard, broken doc links) get fixed before the final commit — leave the repo cleaner
  than found. Fix only what is safe and in-scope-adjacent; anything behavior-changing or spec-touching
  needs user sign-off first.
- **Cannot fix? File a GitHub issue immediately** (`issue-writing` skill) — a defect noticed is a
  defect tracked.

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
