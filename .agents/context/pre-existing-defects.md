# Pre-existing Defects & Self-Improvement Loop

> **Curated mandatory known context** — how to handle known issues and continuous learning. Read at start of every session.

## Pre-existing Defects — Fix or File

- **Fix by default, after the main work**: pre-existing warnings/errors noticed along the way (lint, PHPStan, arch-guard, broken doc links) get fixed before Summarize — leave the repo cleaner than found. Fix only what is safe and in-scope-adjacent; anything behavior-changing or spec-touching needs user sign-off first. This happens inside **Implement** (fix) and is confirmed in **Verify**.
- **Cannot fix? File a GitHub issue immediately** (`issue-writing` skill) — a defect noticed is a defect tracked.

## Self-Improvement Loop — Continuous Learning

The agent compounds in capability across sessions via a closed loop (inherits the global Learning Loop in `~/.agents/rules/self-improvement.md`; project overlay: `.agents/rules/self-improvement.md`, procedure `~/.agents/skills/self-improvement/SKILL.md`). Run `/self-improvement` (or `--deep`) for an explicit retrospective.

```
CAPTURE  ──▶  CONSOLIDATE  ──▶  APPLY
   ▲                                 │
   └─────────────────────────────────┘
```

- **CAPTURE** (in **Summarize**, step 5): record decisions, corrections, failures, patterns, constraints, gaps into `context/` (mandatory facts, register in `context/index.md`) or `memory/` (learnings, register in `memory/index.md`). Append a one-liner
  to `memory/learning-log.md`.
  → Split: mandatory facts → `.agents/context/`; evolving learnings → `.agents/memory/` (with `memory/index.md` + `memory/learning-log.md`). Promote a signal seen ≥2 times to `rules/` or a skill; durable decisions get an ADR in `docs/adr/`. One-offs stay in memory — no rule-bloat.
- **CONSOLIDATE** (periodic): a signal seen ≥2 times in this codebase is promoted to `rules/`
  (prefer `architecture-rules`, `coding-rules`, `testing-rules`) or a skill step; durable decisions get
  an ADR in `docs/adr/`. One-offs stay in `memory/` — no rule-bloat.
- **APPLY** (in **Understand**, step 1): load `context/index.md` + `memory/index.md`, open the rows matching the task, and
  honor recorded corrections + intentional states (deprecated `laravel-model-status`, dummy-guard,
  TallstackUI-only) so the same mistake is never repeated.

This is the agent's deep-learning mechanism: experience is extracted into patterns and pushed into its
own instructions (rules/skills), not just logged. Update `context/` and `memory/` files in place (write a descriptive commit message);
never duplicate a topic.

---
*Source: AGENTS.md §Pre-existing Defects & §Self-Improvement Loop. For the full procedure, see `.agents/rules/self-improvement.md`.*