# Memory — Autonomous Agent Learnings (Evolving)

## Description

This directory is the **autonomous agent memory**: evolving, agent-owned learnings captured across sessions — decisions, corrections, failures, patterns, constraints, and gaps that are not yet promoted to curated rules. It complements `.agents/context/` (mandatory known context). Each file is self-contained so an agent can read a single memory without loading the rest.

**How to use:** during orientation, check if any memory row overlaps your task and load it for background. At Summarize, write new learnings here. When a signal is seen ≥2 times, promote it to `.agents/rules/` or a skill step; durable decisions get an ADR in `docs/adr/`.

---

## Memory Index

| Memory file | Captures |
|-------------|----------|
| [learning-log.md](learning-log.md) | Chronological one-liners for every captured learning (the Learning Loop store) |
| *(add new)* | Self-contained topic file `.agents/memory/{topic}.md` — decisions, corrections, failures, patterns |

---

## Agent Rules (Memory Maintenance)

1. **Memory is evolving — append freely.** One topic = one file (flat, kebab-case). Register it in this index. Keep each file self-contained (paths, commands, rationale); link back to spec/context when relevant.
2. **Promote repeats.** A signal seen ≥2 times → promote to `.agents/rules/` (prefer `architecture-rules`, `coding-rules`, `testing-rules`) or a skill step. Durable decisions → ADR in `docs/adr/`. One-offs stay here — no rule-bloat.
3. **Context stays curated.** Do not move a fact from `.agents/context/` here unless it is no longer mandatory. Context is human-approved and must-read; memory is agent-owned and opportunistic.
4. **House style:** `## Description`, plain language, an `## AI Agent Guides` decision table where helpful. No inline `Last updated` metadata — history lives in `git log`.
5. **Link, don't duplicate.** If a topic extends a context file, link to it instead of copying.

---

## Quick References

- `.agents/context/index.md` — mandatory known context (must-read before tasks)
- `.agents/rules/self-improvement.md` — CAPTURE → CONSOLIDATE → APPLY procedure
- `docs/adr/index.md` — durable decisions
