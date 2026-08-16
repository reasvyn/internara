# Contexts — Evolving Project Context for AI Agents

> **Last updated:** 2026-08-16 **Changes:** initial — moved from `docs/known-issues.md` into
> per-topic context files so agents can load only what they need

## Description

This directory holds **living project context** — intentional design decisions, operational
constraints, tooling quirks, and environmental facts that agents must know before touching the
affected area. It replaces `docs/known-issues.md`. Each file is self-contained so an agent can read
a single context without loading the rest.

**How to use:** before planning or editing, find the row whose topic matches your task and read that
context file. If no row matches, you do not need this directory.

---

## Context Index

| Context file | Read when working on... |
| ------------ | ----------------------- |
| [production-dummy-guard.md](production-dummy-guard.md) | Demo data, seeding, `DummySeeder`, `config/dummy.php`, `setup:install --with-dummy` |
| [deploy-topology.md](deploy-topology.md) | CI/CD, VPS, Docker deploy, `docker-deploy` branch, `build-and-deploy.yml`, GIT_URL |
| [dependency-pins-tooling-quirks.md](dependency-pins-tooling-quirks.md) | Composer/npm dependency changes, `symfony/console`, `prettier-plugin-blade`, tooling workarounds |
| [codebase-intentional-states.md](codebase-intentional-states.md) | Exception behavior, arch-guard scan baselines, adding a new spec, `ExceptionsTest` |

---

## Agent Rules

1. **These states are intentional — do not "fix" them.** A context file records a deliberate
   decision or a known caveat. Treat it as the source of truth unless a spec or a recorded decision
   overrides it.
2. **Context evolves.** When the underlying fact changes (dependency unpinned, deploy topology
   changed, a guard removed), update the context file's `**Changes:**` metadata and content in the
   same commit.
3. **Self-contained.** Each file is read in isolation — include the commands, paths, and rationale
   it needs. Do not assume the reader opened `index.md` first.
4. **No duplicate knowledge.** If a topic grows too large for one context file, split it into a new
   `{context}-{issue-name}.md` and add a row to this index.

---

## Quick References

- [Architecture Rules](../skills/context-awareness/rules/architecture-rules.md) — layer/contract checks
- [Deployment](../../docs/infrastructure/deployment.md) — full VPS/CI/CD topology
- [Documentation Map](../skills/context-awareness/SKILL.md#documentation-map) — where to find every doc
