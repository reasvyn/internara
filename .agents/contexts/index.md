# Contexts — AI Agent Memory (Evolving Project Context)

> **Last updated:** 2026-08-16 **Changes:** repositioned as AI Agent memory — added memory-maintenance
> rules (update on inconsistency, create on critical knowledge)

## Description

This directory is the **AI Agent memory**: a living record of evolving project knowledge —
intentional design decisions, operational constraints, tooling quirks, and environmental facts that
agents must know before touching the affected area. It replaces `docs/known-issues.md`. Each file is
self-contained so an agent can read a single context without loading the rest.

**How to use:** before planning or editing, find the row whose topic matches your task and read that
context file. If no row matches, you do not need this directory. **When you discover something new
or conflicting, write it back here** — this memory is how context survives between sessions.

---

## Context Index

| Context file | Read when working on... |
| ------------ | ----------------------- |
| [production-dummy-guard.md](production-dummy-guard.md) | Demo data, seeding, `DummySeeder`, `config/dummy.php`, `setup:install --with-dummy` |
| [deploy-topology.md](deploy-topology.md) | CI/CD, VPS, Docker deploy, `docker-deploy` branch, `build-and-deploy.yml`, GIT_URL |
| [dependency-pins-tooling-quirks.md](dependency-pins-tooling-quirks.md) | Composer/npm dependency changes, `symfony/console`, `prettier-plugin-blade`, tooling workarounds |
| [codebase-intentional-states.md](codebase-intentional-states.md) | Exception behavior, arch-guard scan baselines, adding a new spec, `ExceptionsTest` |

---

## Agent Rules (Memory Maintenance)

1. **These states are intentional — do not "fix" them.** A context file records a deliberate
   decision or a known caveat. Treat it as the source of truth unless a spec or a recorded decision
   overrides it.
2. **Context evolves — update on inconsistency.** When the underlying fact changes (dependency
   unpinned, deploy topology changed, a guard removed) or a context file no longer matches reality,
   update it **directly in the same run**: fix the stale fact and bump its `**Changes:**` metadata.
   Never leave a discovered inconsistency to a later pass.
3. **Create when critical.** If you learn something highly important that is not yet recorded —
   non-obvious constraint, working workaround, environment quirk, deliberate decision — create a new
   `.agents/contexts/{context}-{issue-name}.md` (flat, kebab-case) and register it here. If a future
   agent would make a costly wrong assumption without it, record it.
4. **Self-contained.** Each file is read in isolation — include the commands, paths, and rationale
   it needs. Do not assume the reader opened `index.md` first.
5. **No duplicate knowledge.** If a topic grows too large for one context file, split it into a new
   `{context}-{issue-name}.md` and add a row to this index.

---

## Quick References

- [Architecture Rules](../skills/context-awareness/rules/architecture-rules.md) — layer/contract checks
- [Deployment](../../docs/infrastructure/deployment.md) — full VPS/CI/CD topology
- [Documentation Map](../skills/context-awareness/SKILL.md#documentation-map) — where to find every doc
