# Learning Log — Captured Experience (Internara Agent Memory)

> **Last updated:** 2026-08-29 **Changes:** seeded as the chronological capture store for the project Learning Loop

## Description

Chronological log of experience signals captured by agents across sessions, per the project
self-improvement rule (`.agents/rules/self-improvement.md`) and the global Learning Loop
(`~/.agents/rules/self-improvement.md`). Each entry points to the detailed `context/` file or `rules/`
entry so a future agent can drill in. This file is the **accumulation** store; `context/index.md` is
the **registry**. Keep entries short — detail lives in the linked file.

**Format:** `- YYYY-MM-DD · [type] · {one-line learning} → link`

## Log

- 2026-08-29 · [infra] · Rules consolidated to single source `.agents/rules/` (150 files); skill
  `rules/` dirs removed; agent-workflow + context-awareness folded into AGENTS.md →
  `AGENTS.md` §Context Awareness, `context/index.md`
- 2026-08-29 · [process] · Self-improvement loop established: CAPTURE→CONSOLIDATE→APPLY wired into
  both global (`~/.agents`) and project config → `.agents/rules/self-improvement.md`,
  `~/.agents/rules/self-improvement.md`

---

## How to Append

Add a row at the top of the Log (newest first) after each session's Summarize, or immediately on a user
correction / failure. Types: `decision`, `correction`, `failure`, `pattern`, `constraint`, `gap`.
Link to the detailed `context/{topic}.md` or `rules/{name}.md`. Fact-check paths before writing.

## Maintenance

- This file grows unbounded — archive quarterly into `learning-log-YYYY-Qn.md` if it exceeds ~200 lines.
- Never duplicate a `context/` file's content here; link only.
- Update `**Changes:**` on every edit.
