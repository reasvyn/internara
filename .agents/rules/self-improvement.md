# Self-Improvement (Continuous Learning) — Project Rules

Inherits the global Learning Loop (`~/.agents/rules/self-improvement.md`): **CAPTURE → CONSOLIDATE →
APPLY**. This file adds Internara-specific capture targets and promotion gates. Severity: `mandatory`,
enforcement: `self-check`.

## Project Capture Targets (write to `context/`)

| Signal | Project-specific trigger | Where |
|--------|--------------------------|-------|
| Architectural decision | Resolved spec↔code conflict, new module boundary, Action/Entity split choice | `context/{topic}.md` + `docs/adr/` when durable |
| Invariant violation found | A C1-C8 / D1-D6 breach caught in review or `scan_violations.py` | `context/codebase-intentional-states.md` or new file |
| Spec gap | A requirement with no test, or test with no requirement | `learning-log.md` + raise via `issue-writing` skill |
| Recurring bug pattern | Same class of bug fixed ≥2 times (e.g., N+1, missing `#[Fillable]`) | promote to `rules/` (e.g. `rules/architecture-rules.md`) |
| Tooling/deploy quirk | `prettier-plugin-blade` via Pint, `docker-deploy` branch, dependency pins | `context/dependency-pins-tooling-quirks.md` / `context/deploy-topology.md` |
| UI framework constraint | TallstackUI v4 only, self-hosted palette, no mary/daisyui/flasher | `context/ui-framework-coexistence.md` |
| Module health change | A module moved tier (production-ready ↔ skeleton) | `context/module-health.md` |
| User correction | Any redirected instruction ("don't do X", "batch verify") | `learning-log.md` + promote if repeated |

## Consolidation Gates (project)

- **Repetition ≥ 2** in this codebase → promote to `rules/` (prefer existing `architecture-rules.md`,
  `coding-rules.md`, `testing-rules.md`) or add a step to the matching skill.
- **Durable cross-cutting decision** → write an ADR in `docs/adr/` and link from `context/index.md`.
- **One-off / rare** → keep in `context/` (never over-promote to a mandatory rule).
- **Stale fact** → update the `context/` file in place and write a descriptive commit message (do not create a second file).

## Apply (session start)

In **Understand**, load `context/index.md`; open the row matching the task. Honor recorded corrections
and intentional states (e.g. deprecated `laravel-model-status`, dummy-guard) — do not "fix" intentional
states. Run `/self-improvement --deep` at session end to mine `git diff`.

## Integration with Existing Memory

- `context/index.md` is the registry — every new context file gets a row there.
- `learning-log.md` is the chronological capture store (what was learned, when, pointer to detail).
- The global `skills/self-improvement/SKILL.md` is the procedure; this file is the project overlay.

## Validation

- [ ] `learning-log.md` has a new entry for the session's learning.
- [ ] Repeated signal promoted to `rules/` or `docs/adr/` (not stranded in chat).
- [ ] `context/index.md` row added/updated; no duplicate topic file.
- [ ] Next session would skip a known mistake without re-learning it.
