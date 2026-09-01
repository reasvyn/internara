# Self-Improvement (Continuous Learning) — Project Rules

Inherits the global Learning Loop (the user's homespace config under `~/`, contents vary per user — config that lives outside this repo): **CAPTURE → CONSOLIDATE →
APPLY**. This file adds Internara-specific capture targets and promotion gates. Severity: `mandatory`,
enforcement: `self-check`.

## Project Capture Targets (write to `context/` or `memory/`)

| Signal | Project-specific trigger | Where |
|--------|--------------------------|-------|
| Architectural decision (durable) | Resolved spec↔code conflict, new module boundary, Action/Entity split choice | `context/{topic}.md` + `docs/adr/` when durable |
| Architectural learning (evolving) | Session-specific approach that may generalize | `memory/{topic}.md` → promote to `rules/` after ≥2 |
| Invariant violation found | A C1-C8 / D1-D6 breach caught in review or `scan_violations.py` | `context/codebase-intentional-states.md` or new `context/` file if mandatory |
| Spec gap | A requirement with no test, or test with no requirement | `memory/learning-log.md` + raise via `issue-writing` skill |
| Recurring bug pattern | Same class of bug fixed ≥2 times (e.g., N+1, missing `#[Fillable]`) | promote to `rules/` (e.g. `rules/architecture-rules.md`) |
| Tooling/deploy quirk (mandatory) | `prettier-plugin-blade` via Pint, tag-driven `release.yml` pipeline (`deploy.sh` + `$HOME`), dependency pins | `context/dependency-pins-tooling-quirks.md` / `context/deploy-topology.md` |
| UI framework constraint | TallstackUI v4 only, self-hosted palette, no mary/daisyui/flasher | `context/ui-framework-coexistence.md` |
| Module health change | A module moved tier (production-ready ↔ skeleton) | `context/module-health.md` |
| User correction | Any redirected instruction ("don't do X", "batch verify") | `memory/learning-log.md` + promote if repeated |

## Consolidation Gates (project)

- **Repetition ≥ 2** in this codebase → promote to `rules/` (prefer existing `architecture-rules.md`,
  `coding-rules.md`, `testing-rules.md`) or add a step to the matching skill.
- **Durable cross-cutting decision** → write an ADR in `docs/adr/` and link from `context/index.md`.
- **One-off / rare** → keep in `context/` (never over-promote to a mandatory rule).
- **Stale fact** → update the `context/` file in place and write a descriptive commit message (do not create a second file).

## Capture Trigger (judgment, not per-summarize)

Memory capture is **on-need, by judgment** — not an automatic step in every session's Summarize. Record to
`memory/` only when the information is worth saving for a future agent: a durable decision, a non-obvious
trap/correction, a recurring pattern, or a constraint that a future session would otherwise re-learn. If a
session produced nothing novel, skip memory write entirely. Reserve `/internara-learn --deep` (`git diff`
mining) for sessions with substantive changes, not routine commits.

> **Memory is local-only.** `.agents/memory/` is **gitignored** (never committed/shared). Committed files
> outside it (AGENTS.md, `.agents/context/`, `.agents/rules/`, docs, agents, commands) may *mention* the
> `memory/` path conceptually, but must **never cross-reference its contents** (no link/pointer to a specific
> `memory/` file as the destination of real detail) — readers of committed files do not have your local copy,
> so such pointers are broken for them. Cross-references between files are allowed **only inside** `memory/`.

## Apply (session start)

In **Understand**, load `context/index.md` (mandatory) + `memory/index.md` (learnings); open the rows matching the task. Honor recorded corrections and intentional states (e.g. deprecated `laravel-model-status`, dummy-guard) — do not "fix" intentional states.

## Integration with Existing Memory

- `context/index.md` is the registry for mandatory known context; `memory/index.md` for autonomous learnings — every new file gets a row in its own index.
- `memory/learning-log.md` is the chronological capture store (what was learned, when, pointer to detail). It is **local only** (gitignored) — do not cross-reference its contents from committed files outside `memory/`.
- The global Learning Loop procedure lives in the user's homespace config under `~/` (contents vary per user, not referenced by path from committed files); this file is the project overlay.

## Validation

- [ ] Memory captured **only when the learning is worth saving** (judgment), not auto-per-summarize.
- [ ] If captured: `memory/learning-log.md` got an entry pointing to a `memory/`/`context/`/`rules/` detail.
- [ ] Repeated signal promoted to `rules/` or `docs/adr/` (not stranded in chat).
- [ ] `context/index.md` or `memory/index.md` row added/updated; no duplicate topic file.
- [ ] Next session would skip a known mistake without re-learning it.
