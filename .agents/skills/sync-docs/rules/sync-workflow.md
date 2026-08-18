# Sync Workflow — Discover Drift Before Editing

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

## Intent

Before making any doc change, systematically discover what actually changed in the codebase and
specs in the **last 7 days**, identify which files were added, deleted, or modified, and map those
changes to the docs that must be updated. Drift is found by inspection, not by memory or guesswork.

## Rationale

Docs go stale because agents update them "when they remember" — which is never reliably. The
reliable signal is git history: it records exactly which code changed, so it tells you exactly which
docs are now wrong. Two failure modes this prevents:

1. **Updating the wrong docs.** Guessing which docs drifted wastes effort and leaves the real drift
   in place. A module doc updated "just in case" while the reference doc for a renamed Action stays
   stale is the classic outcome.
2. **Re-verifying unchanged docs.** Re-reading and "sync"-touching docs that have not drifted burns
   time, churns the git history, and — because each touch bumps `Last updated` — actively
   *hides* future drift (a doc looks fresh when nothing real changed).

The 7-day window is the right default: recent changes are the ones that matter, and commits that
already updated their docs (visible in `--stat`) are skipped, focusing effort on commits that
introduced code without doc updates.

## How to Apply

### Step 0 — Review Recent Git History

```bash
git log --since="7 days ago" --stat              # summary per commit
git log --since="7 days ago" --name-status       # consolidated file changes
git log --since="7 days ago" --format="%h %s"    # commit messages for context
```

- Note which modules, layers, and files were affected.
- Identify commits that already updated docs (skip those).
- Identify commits that introduced new code without doc updates (focus here).

### Step 1 — Identify What Changed

- Check `git diff` for new files, deleted files, and modified files.
- Identify which modules, submodules, and layers were affected.
- Note new Models, Actions, Entities, Enums, DTOs, Events, Policies, Livewire components.

### Step 2 — Determine Which Docs Need Updates

| If you changed...    | Update these docs                                                    |
| -------------------- | -------------------------------------------------------------------- |
| Module structure     | `docs/modules/{module}-reference.md` (file listing, actions, models) |
| Business rules       | `docs/modules/{module}.md` (business context)                        |
| Feature requirements | `docs/specs/{ID}-{feature}.md` (FR, NFR, user stories, data contracts)       |
| Architecture pattern | `docs/architecture.md` or `docs/architecture/{pattern}-pattern.md`   |
| Conventions          | `docs/conventions.md`                                                |
| Module dependencies  | `docs/modules/index.md`                                              |
| Database schema      | `docs/infrastructure/database.md`, `docs/specs/J68GZ-system-requirements.md` (§4.4, §7.3) |
| ADR                  | `docs/adr/` (if decision is notable)                                 |
| Feature specs        | `docs/specs/index.md`                                                |
| Config               | `docs/infrastructure/configuration.md`                               |
| Agent guides         | `AGENTS.md` (module map, invariants, rule pointers)                  |
| Agent skills         | `.agents/skills/{skill}/SKILL.md` (skill scope, rules, references)   |
| Agent contexts       | `.agents/contexts/*.md` (intentional states, deploy caveats, pins)   |
| Agent plans          | `.agents/plans/` (session plans, decisions)                          |

This mapping includes **agent guides & skills** — a spec amendment (renamed default, new invariant,
changed path) must be mirrored in any guide or skill that documents it, not just `docs/`.

## Examples

```bash
# A commit added a new Command Action:
git log --since="7 days ago" --stat
#   app/Enrollment/Placement/Actions/WithdrawPlacementAction.php  (new)

# Doc action required:
#   docs/modules/enrollment-reference.md  → add the Action row + execute() signature
#   docs/modules/enrollment.md            → add the business rule ("placement may be withdrawn
#                                            only before the internship starts") if new
#   docs/specs/{ID}-enrollment.md         → add/verify the FR that motivates the Action
```

## Anti-Patterns & Pitfalls

- **Skipping git history** and "auditing" by re-reading every doc — expensive, noisy, and misses the
  point (drift is introduced by changes; find the changes).
- **Widening the window blindly:** reviewing 90 days of history for a routine sync dilutes focus.
  The 7-day window is the default; widen it only when an explicit audit demands it.
- **Updating only `docs/` and ignoring agent guides & skills** — `AGENTS.md`, `.agents/skills/*`,
  `.agents/contexts/`, `.agents/plans/` document the same code and specs; a spec amendment not
  mirrored there leaves the agent layer stale even when `docs/` is clean.
- **Touching docs that didn't change** — re-verifying and re-dating docs that match the code hides
  real future drift.
- **Trusting docs over code** during discovery — verify paths, class names, and signatures against
  actual code; a doc can be stale and so can your memory of the code.

## Verification / Detection

- `git log --since="7 days ago" --stat` — confirm the audit surface matches actual commits.
- `git diff` — the set of changed files must match the docs queued for update (no orphan doc
  updates, no missed ones).
- `python3 scripts/scan_doc_links.py` — catches file-listing drift (renamed/deleted files still
  referenced).
