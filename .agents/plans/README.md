# Plans — L-Size Session Splitting

## Description

`L` = >10 files, multi-module, or cross-cutting. **MUST split into multiple sessions** per `AGENTS.md#Size Triage` + `workflow-5step.md`. This directory holds the per-session plan files that survive between sessions so no context is lost.

**Scope:** This directory is for **non-audit L-size plans** — implementation roadmaps, refactor sessions, migration plans (TallstackUI, ADR rewrites, etc.). **Audit reports do not live here**; they live in **GitHub Issues** per `.agents/rules/audit-workflow.md` Phase 4 ("Audit record lives in GitHub Issues, not `.agents/plans/`").

## How to Use

1. After **Plan** (Step 2), the agent writes `plans/{YYYY-MM-DD}-{slug}.md` from the template below
2. Each session runs `Implement → Verify → Summarize` with its own `git status`+`git diff` + report
3. Plans are **living** — update the `Progress` checklist as sessions complete; never delete a plan until all sessions are merged and verified

## Template

Copy this for each L-size instruction:

```markdown
# Plan: {Title}

> **Created:** YYYY-MM-DD **Size:** L **Sessions:** N **Spec:** docs/specs/{ID}-{feature}.md

## Goal
One paragraph — what the batched instruction achieves and why.

## Instruction Ordering
| # | Instruction | Impact/Effort | Quadrant | Session |
|---|-------------|---------------|----------|---------|
| 1 | ... | 4/2 | Quick win | 1 |
| 2 | ... | 5/5 | Strategic | 2 |

## Sessions
### Session 1 — {Title} (files: X, concern: Y)
- [ ] Implement: {files/modules}
- [ ] Verify: `vendor/bin/pest --testsuite={Module}`, `python3 tools/scan_violations.py --module {Module}`
- [ ] Summarize: commit `type(scope): ...` + short report

### Session 2 — ...

## Dependencies
- Session 2 depends on Session 1 because ...

## Verification Strategy
Batch per AGENTS.md#Verification Strategy — e.g. `pint --dirty`, `npm run build`, targeted `pest --testsuite`.

## Risks
- ...

## Progress
- [ ] Session 1 — ...
- [ ] Session 2 — ...
```

## Quick References

- `AGENTS.md#Size Triage` — S/M/L criteria
- `.agents/context/workflow-5step.md#Size Triage` — L-size protocol
- `AGENTS.md §Agent Workflow` — canonical 5-step pipeline
