# Context — AI Agent Memory (Evolving Project Context)

> **Last updated:** 2026-08-25 **Changes:** added dep-model-status-deprecated — spatie/laravel-model-status marked deprecated pending removal (#419)

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
| [workflow-5step.md](workflow-5step.md) | **Every task** — new 5-step pipeline `Understand → Plan → Implement → Verify → Summarize` (replaces 9-step/4-phase); phase classification, size triage, instruction ordering |
| [module-health.md](module-health.md) | **Any module touch** — 18-module health tiers (production-ready → skeleton), P0 tech debt priority, which modules to scaffold vs. fix first |
| [testing-strategy.md](testing-strategy.md) | Tests, Pest, PHPStan — spec-driven minimalism, what to run when (batched once), layer patterns, module-specific guidance |
| [production-dummy-guard.md](production-dummy-guard.md) | Demo data, seeding, `DummySeeder`, `config/dummy.php`, `setup:install --with-dummy` |
| [deploy-topology.md](deploy-topology.md) | CI/CD, VPS, Docker deploy, `docker-deploy` branch, `build-and-deploy.yml`, GIT_URL |
| [dependency-pins-tooling-quirks.md](dependency-pins-tooling-quirks.md) | Composer/npm dependency changes, `symfony/console`, `prettier-plugin-blade`, tooling workarounds |
| [dep-model-status-deprecated.md](dep-model-status-deprecated.md) | **Any status/state persistence** — spatie/laravel-model-status deprecated, removal planned (#419); status columns are app-owned |
| [ui-framework-coexistence.md](ui-framework-coexistence.md) | **Any UI/component work** — TallstackUI v4 complete (mary/flasher/daisyui removed), self-hosted palette + shims, `x-ts-*` only |
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
   `.agents/context/{context}-{issue-name}.md` (flat, kebab-case) and register it here. If a future
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
