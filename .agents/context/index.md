# Context — Mandatory Known Context (Must-Read Before Tasks)

This directory is the **curated, mandatory known context**: the small set of facts every agent must know before touching the affected area — intentional design decisions, operational constraints, tooling quirks, and environmental truths. It replaces `docs/known-issues.md`. It is **not** the autonomous learning store (that is `.agents/memory/`). Each file is self-contained so an agent can read a single context without loading the rest.

**How to use:** before planning or editing, find the row whose topic matches your task and read that context file. If no row matches, you do not need this directory. **When a context file conflicts with reality, update it directly in the same run** — this context is how mandatory knowledge survives between sessions. For evolving session learnings, write to `.agents/memory/` instead.

**Required reading:** Every session start — `workflow-5step.md`, `project-snapshot.md`, `AGENTS.md` §Context Awareness. Before releasing new version — also read `project-snapshot.md`, `project-identity.md`, `quick-reference.md`, `version-bump-guide.md`.

---

## Context Index

| Context file | Read when working on... |
| ------------ | ----------------------- |
| [workflow-5step.md](workflow-5step.md) | **Every task** — new 5-step pipeline `Understand → Plan → Implement → Verify → Summarize` (replaces 9-step/4-phase); phase classification, size triage, instruction ordering |
| [module-health.md](module-health.md) | **Any module touch** — 18-module health tiers (production-ready → skeleton), P0 tech debt priority, which modules to scaffold vs. fix first |
| [testing-strategy.md](testing-strategy.md) | Tests, Pest — spec-driven minimalism, what to run when (batched once), layer patterns, module-specific guidance |
| [production-dummy-guard.md](production-dummy-guard.md) | Demo data, seeding, `DummySeeder`, `config/dummy.php`, `setup:install --with-dummy` |
| [deploy-topology.md](deploy-topology.md) | CI/CD, VPS, Docker deploy, tag-driven `release.yml` 4-stage pipeline, `deploy.sh`, GIT_URL |
| [dependency-pins-tooling-quirks.md](dependency-pins-tooling-quirks.md) | Composer/npm dependency changes, `symfony/console`, `prettier-plugin-blade`, tooling workarounds |
| [dep-model-status-deprecated.md](dep-model-status-deprecated.md) | **Any status/state persistence** — spatie/laravel-model-status deprecated, removal planned (#419); status columns are app-owned |
| [ui-framework-coexistence.md](ui-framework-coexistence.md) | **Any UI/component work** — TallstackUI v4 complete (mary/flasher/daisyui removed), self-hosted palette + shims, `x-ts-*` only |
| [codebase-intentional-states.md](codebase-intentional-states.md) | Exception behavior, arch-guard scan baselines, adding a new spec, `ExceptionsTest` |
| [project-snapshot.md](project-snapshot.md) | **Every session start + before release** — comprehensive project map: 19 modules, 12-phase spec build order, 4-layer architecture, health tiers, deploy topology, status |
| [project-identity.md](project-identity.md) | **Every session start + before release** — tech stack, project definition, target users, design principles, lifecycle scope |
| [phase-classification.md](phase-classification.md) | Session depth & size triage — SDLC phase classification, session splitting rules |
| [instruction-ordering.md](instruction-ordering.md) | **Batched/multi-instruction messages** — impact-to-effort scoring, computational thinking decision loop |
| [pre-existing-defects.md](pre-existing-defects.md) | **Warnings/errors encountered** — fix or file, self-improvement loop |
| [skill-rules.md](skill-rules.md) | Skill map index, references, and rules index (150+ rules) — load on demand |
| [documentation-senses.md](documentation-senses.md) | **Writing/updating docs** — doc drift detection, mismatch resolution, tier selection, history discipline, link integrity |
| [metacognitive-loop.md](metacognitive-loop.md) | **Every session** — construct→evaluate→verify→decide loop, automation scripts reference |
| [skill-map.md](skill-map.md) | **Every session** — which skill to load for each task type, module/spec reference |
| [quick-reference.md](quick-reference.md) | **Every session** — dev commands, commit format, branch naming, navigation map |
| [version-bump-guide.md](version-bump-guide.md) | **Before releasing** — version bump checklist, release flow, tag-driven pipeline |

---

## Agent Rules (Context vs Memory)

1. **These states are intentional — do not "fix" them.** A context file records a deliberate decision or a known caveat. Treat it as the source of truth unless a spec or a recorded decision overrides it.
2. **Context is curated and mandatory.** When the underlying fact changes (dependency unpinned, deploy topology changed, a guard removed) or a context file no longer matches reality, update it **directly in the same run**: fix the stale fact and commit with a descriptive message. Never leave a discovered inconsistency to a later pass.
3. **Create when critical.** If you learn a **mandatory** fact not yet recorded — non-obvious constraint, environment quirk, deliberate decision that every future agent must know — create `.agents/context/{context}-{issue-name}.md` (flat, kebab-case) and register it here. If a future agent would make a costly wrong assumption without it, record it here.
4. **Evolving learnings → memory/**. Session decisions, corrections, failures, and patterns that are not yet proven mandatory belong in `.agents/memory/` (local-only; see the `memory/` store itself, not referenced from here). Promote to `.agents/rules/` or `.agents/context/` only after ≥2 occurrences or maintainer approval.
5. **Self-contained.** Each file is read in isolation — include the commands, paths, and rationale it needs. Do not assume the reader opened `index.md` first.
6. **No duplicate knowledge.** If a topic grows too large for one context file, split it into a new `{context}-{issue-name}.md` and add a row to this index. Do not duplicate a memory file's content here; and do not cross-reference `memory/` contents from this directory (memory is local-only).

---

## Quick References

- `.agents/memory/` — autonomous learning store (agent-owned, **local-only**; read during orientation if available)
- [Architecture Rules](../rules/architecture-rules.md) — layer/contract checks
- [Deployment](../../docs/guides/infra/deployment.md) — full VPS/CI/CD topology
- [Documentation Map](../../docs/index.md) — where to find every doc
- [AGENTS.md](../../AGENTS.md) — navigation hub: workflow, project snapshot, context awareness