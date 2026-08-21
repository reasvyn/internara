# Workflow 5-Step — Understand → Plan → Implement → Verify → Summarize

> **Last updated:** 2026-08-21 **Changes:** initial — documents the new 5-step pipeline that replaced the legacy 9-step/4-phase model (AGENTS.md + agent-workflow SKILL.md)

## Description

Since 2026-08-21, the canonical agent workflow is **5 steps** (SSOT: `.agents/skills/agent-workflow/SKILL.md` + `AGENTS.md#Agent Workflow`):

```
UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE
```

Legacy mapping: `Understand` absorbs `Understand + Define & Scope`; `Plan` absorbs `Explore + Plan + Design`; `Implement` absorbs `Develop + Document`; `Verify` = `Test & Verify`; `Summarize` = `Commit & Report`. This file is the agent's cheat-sheet for running the new pipeline without narrating it.

---

## The 5 Steps (what to do inside each)

| Step | Purpose | Do | Exit criteria |
|------|---------|----|---------------|
| **1. Understand** | Intent, scope, constraints **before** reading code | Locate governing spec in `docs/specs/` (`FR-*`/`NFR-*`/`UC-*`); list affected modules/layers/files; classify SDLC phase + size S/M/L; reorder batched instructions by impact-to-effort (`rules/instruction-ordering.md`) | Spec IDs + phase/size + scope bounded |
| **2. Plan** | Context, approach, design **before** touching code | Read module docs + `docs/architecture/*.md` + full file content of every file you may touch; survey `scripts/` (Automation-First); consider 2+ approaches; decide Action triad (`Command`/`Read`/`Process`), Entity (`final readonly` + `fromModel()`), DTO (`BaseData`), Model (`#[Fillable]`), error `RejectedException` (C8), cache registry (C4) | 2+ approaches + chosen design + test/doc plan |
| **3. Implement** | Surgical execution + docs | Smallest change, preserve unrelated code; `declare(strict_types=1)` (D1), no `dd()` (D2), `__()` (D3), no raw `$request->all()` (D5), FK `onDelete` (D6), no Model mutations in Livewire (C1), constructor injection (C2); update `> **Last updated:**` + PHPDoc in same step; DRY dedup on sight | Code + docs/PHPDoc in sync, `git status` only intended files |
| **4. Verify** | Quality gates, **batched once** | `git status`+`git diff` (Edit Policy), `vendor/bin/pint --dirty --test`, `npx prettier --check`, `php artisan test --compact --filter` / `vendor/bin/pest --testsuite`, `python3 scripts/scan_*.py` batch; full suite + PHPStan **on-demand only** (~2GB, 10+ min) | Change-type matrix (AGENTS.md#Verification Strategy) passes |
| **5. Summarize** | Commit + report | Final `git status`+`git diff`, stage only intended files, `type(scope): description` (`feat`/`fix`/`refactor`/`docs`/`chore`/`test`/`perf`/`security`), report (what changed, what verified, caveats, next steps) | Clean commit + short report, repo cleaner than found |

The pipeline runs **silently** — never narrate the steps. Surface only: (1) ambiguity, (2) scope/structure decision, (3) L-size plan (1 paragraph), (4) M/L checkpoint, (5) final report.

---

## Phase Classification (adaptive depth)

| SDLC Phase | Full | Light | Note |
|------------|------|-------|------|
| **Support** | Understand, Summarize | Plan (brief) | Implement, Verify |
| **Analysis** | Understand, Plan, Summarize | Verify | Implement |
| **Planning** | Understand, Plan, Summarize | Verify | Implement |
| **Design** | Understand, Plan, Summarize | Verify | Implement |
| **Implementation** | **All 5** | — | — |
| **Testing** | Understand, Implement, Verify, Summarize | Plan | — |
| **Documentation** | Understand, Plan, Implement, Summarize | Verify | — |
| **Tooling** | Understand, Plan, Implement, Verify, Summarize | — | — |
| **Maintenance** | Understand, Verify, Summarize | Plan, Implement | — |

---

## Size Triage

| Size | Criteria | Execution | Check-in |
|------|----------|-----------|----------|
| **S** | ≤3 files, single concern | Single pass, full 5 at phase depth | None |
| **M** | 4-10 files, 2-3 concerns | Single session, staged, Verify once → Summarize | 1 checkpoint before commit |
| **L** | >10 files, multi-module | **MUST split** into sessions | **MUST inform user** (1 paragraph: "too broad — N sessions" + list) |

L-size protocol: after **Plan**, inform user, then run each session `Implement → Verify → Summarize` with its own `git status`/`diff` + report.

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Run any instruction | `agent-workflow` is SSOT — load it first, then `context-awareness`, then task skill |
| Batch of instructions | Reorder by impact-to-effort ratio (quick wins first) per `rules/instruction-ordering.md` |
| Verify what to run | `AGENTS.md#Verification Strategy` change-type matrix + `agent-workflow` Step 4 |
| Commit | `type(scope): description`, scope = module name, one concern per commit |

---

## Quick References

- `.agents/skills/agent-workflow/SKILL.md` — canonical 5-step definition (SSOT)
- `AGENTS.md#Agent Workflow` + `#Phase Classification` + `#Size Triage` + `#Edit Policy` + `#Verification Strategy`
- `.agents/skills/agent-workflow/rules/instruction-ordering.md` — impact-to-effort scoring
- `.agents/skills/agent-workflow/rules/key-rules.md` — 6 non-negotiable rules
