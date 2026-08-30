# Learning Log — Captured Experience (Autonomous Memory)

## Description

Chronological log of experience signals captured by agents across sessions, per the project self-improvement rule (`.agents/rules/self-improvement.md`) and the global Learning Loop (`~/.agents/rules/self-improvement.md`). Each entry points to the detailed `memory/` or `context/` file or `rules/` entry so a future agent can drill in. This file is the **accumulation** store; `memory/index.md` (and `context/index.md` for curated facts) is the **registry**. Keep entries short — detail lives in the linked file.

**Format:** `- YYYY-MM-DD · [type] · {one-line learning} → link`

## Log

- 2026-08-30 · [docs] · Split `.agents/context` (curated mandatory) vs `.agents/memory` (autonomous learnings); translated AGENTS.md Project Snapshot to English; moved `learning-log.md` to `memory/` per user request → `AGENTS.md` §Project Snapshot, `.agents/memory/index.md`, `.agents/context/index.md`
- 2026-08-30 · [docs] · Added `Project Snapshot — Comprehensive Map` to AGENTS.md (691 PHP files, 45 migrations, 64 specs, 19 modules, 4-layer Action Triad, health tiers, 12-phase build order, deploy topology) → `AGENTS.md` §Project Snapshot
- 2026-08-29 · [infra] · Rules consolidated to single source `.agents/rules/` (150 files); skill `rules/` dirs removed; agent-workflow + context-awareness folded into AGENTS.md → `AGENTS.md` §Context Awareness, `context/index.md`
- 2026-08-29 · [process] · Self-improvement loop established: CAPTURE→CONSOLIDATE→APPLY wired into both global (`~/.agents`) and project config → `.agents/rules/self-improvement.md`, `~/.agents/rules/self-improvement.md`

---

## How to Append

Add a row at the top of the Log (newest first) after each session's Summarize, or immediately on a user correction / failure. Types: `decision`, `correction`, `failure`, `pattern`, `constraint`, `gap`. Link to the detailed `memory/{topic}.md` or `rules/{name}.md` or `context/{topic}.md`. Fact-check paths before writing.

## Maintenance

- This file grows unbounded — archive quarterly into `learning-log-YYYY-Qn.md` if it exceeds ~200 lines.
- Never duplicate a `context/` or `memory/` file's content here; link only.
- History lives in `git log --follow -- .agents/memory/learning-log.md`.

---

## Where to Find It

- `.agents/memory/index.md` — memory registry (autonomous learnings)
- `.agents/context/index.md` — known context registry (mandatory)
- `.agents/rules/self-improvement.md` — learning loop procedure
