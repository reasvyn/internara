# Sync Workflow — Discover Drift Before Editing

## Intent

Before making any doc change, systematically discover what actually changed in the codebase and
specs in **at least the last 14 days** (minimal 14 hari terakhir), identify which files were added,
deleted, or modified, and map those changes to the docs that must be updated. Drift is found by
inspection, not by memory or guesswork. If the change surface is suspected to span longer (large
refactor, dormant module, cross-module rename), widen the window (full log) as needed.

## Rationale

Docs go stale because agents update them "when they remember" — which is never reliably. The
reliable signal is git history: it records exactly which code changed, so it tells you exactly which
docs are now wrong. Two failure modes this prevents:

1. **Updating the wrong docs.** Guessing which docs drifted wastes effort and leaves the real drift
   in place. A module doc updated "just in case" while the reference doc for a renamed Action stays
   stale is the classic outcome.
2. **Re-verifying unchanged docs.** Re-reading and "sync"-touching docs that have not drifted burns
   time, churns the git history, and churns the git history and
   *hides* future drift (a doc looks fresh when nothing real changed).

The 14-day window is the **minimum**, not a fixed cap: recent changes are the ones that matter most,
and commits that already updated their docs (visible in `--stat`) are skipped, focusing effort on
commits that introduced code without doc updates. Treat `14 days ago` as the floor — extend to
full log whenever drift may predate the window (e.g., infrequent module, large audit).

## How to Apply

### Step 0 — Check Uncommitted Changes and Commit Atomically First

```bash
git status --short
git diff --stat
git diff --cached --stat
```

- If `git status` shows modified (`M`), added (`A`), deleted (`D`), renamed (`R`), or untracked (`??`) files, **commit them first** before starting the sync.
- Uncommitted code is invisible to `git log` — syncing docs against `HEAD` while the working tree has uncommitted business logic leaves the new docs stale on arrival.
- Stage and commit **atomically** — e.g. `git add <file1> <file2> && git commit -m "type(scope): ..."` per concern (one commit per logical change, see `commit-as-checkpoint.md`). **Do not use `git add -A` nor `stash`** — broad staging hides unrelated changes and stashing defers the checkpoint; every pending change must be committed atomically with a proper message, or explicitly ignored with a recorded decision, then proceed to Step 1. Never run a doc sync on a dirty working tree without a checkpoint.

### Step 1 — Review Recent Git History (minimum 14 days, extend if needed)

```bash
git log --since="14 days ago" --stat              # summary per commit (minimum window)
git log --since="14 days ago" --name-status       # consolidated file changes
git log --since="14 days ago" --format="%h %s"    # commit messages for context
# Jika curiga drift lebih lama:
git log --since="14 days ago" --stat  # already minimum; for older drift use full log: git log --stat
```

- Note which modules, layers, and files were affected.
- Identify commits that already updated docs (skip those).
- Identify commits that introduced new code without doc updates (focus here).

### Step 2 — Identify What Changed

- Check `git diff` for new files, deleted files, and modified files.
- Identify which modules, submodules, and layers were affected.
- Note new Models, Actions, Entities, Enums, DTOs, Events, Policies, Livewire components.

### Step 3 — Determine Which Docs Need Updates

| If you changed...    | Update these docs                                                    |
| -------------------- | -------------------------------------------------------------------- |
| Module structure     | `docs/refs/modules/{module}-reference.md` (file listing, actions, models) |
| Business rules       | `docs/refs/modules/{module}.md` (business context)                        |
| Feature requirements | `docs/specs/{ID}-{feature}.md` (FR, NFR, user stories, data contracts)       |
| Architecture pattern | `docs/architecture.md` or `docs/guides/arch/{pattern}-pattern.md`   |
| Conventions          | `docs/conventions.md`                                                |
| Module dependencies  | `docs/refs/modules/index.md`                                              |
| Database schema      | `docs/guides/infra/database.md`, `docs/specs/J68GZ-system-requirements.md` (§4.4, §7.3) |
| ADR                  | `docs/adr/` (if decision is notable)                                 |
| Feature specs        | `docs/specs/index.md`                                                |
| Config               | `docs/guides/infra/configuration.md`                               |
| Agent guides         | `AGENTS.md` (module map, invariants, rule pointers)                  |
| Agent skills         | `.agents/skills/{skill}/SKILL.md` (skill scope, rules, references)   |
| Agent contexts       | `.agents/context/*.md` (intentional states, deploy caveats, pins)   |
| Agent plans          | `.agents/plans/` (session plans, decisions)                          |

This mapping includes **agent guides & skills** — a spec amendment (renamed default, new invariant,
changed path) must be mirrored in any guide or skill that documents it, not just `docs/`.

### Ordered Sync Execution (impact-to-effort)

Execute sync in phase order per `.agents/rules/impact-to-effort.md` and `audit-scope.md` expanded areas:
**Phase 1 Root → Phase 2 Core → Phase 3 Guides → Phase 4 Specs → Phase 5 ADR → Phase 6 Refs → Phase 7 Agent Layer**.
Dependency chains first (specs before refs that cite them), then business urgency (root/core before reference tier), then ratio (quick wins before strategic). Commit per phase as a checkpoint (`commit-as-checkpoint.md`).

## Examples

```bash
# A commit added a new Command Action:
git log --since="14 days ago" --stat
#   app/Enrollment/Placement/Actions/WithdrawPlacementAction.php  (new)

# Doc action required:
#   docs/refs/modules/enrollment-reference.md  → add the Action row + execute() signature
#   docs/refs/modules/enrollment.md            → add the business rule ("placement may be withdrawn
#                                            only before the internship starts") if new
#   docs/specs/{ID}-enrollment.md         → add/verify the FR that motivates the Action
```

## Anti-Patterns & Pitfalls

- **Skipping git history** and "auditing" by re-reading every doc — expensive, noisy, and misses the
  point (drift is introduced by changes; find the changes).
- **Capping the window at 14 days when drift is older:** treating 14 days as a hard limit hides stale
  docs that drifted 3–4 weeks ago. Minimal 14 days means floor, not ceiling — widen when audit signal
  (missing file refs, spec gap) suggests older changes.
- **Widening the window blindly:** reviewing 90 days of history for every routine sync dilutes focus.
  Extend only when needed — routine sync starts at 14 days, audit expands to full log.
- **Updating only `docs/` and ignoring agent guides & skills** — `AGENTS.md`, `.agents/skills/*`,
  `.agents/context/`, `.agents/plans/` document the same code and specs; a spec amendment not
  mirrored there leaves the agent layer stale even when `docs/` is clean.
- **Touching docs that didn't change** — re-verifying and re-dating docs that match the code hides
  real future drift.
- **Trusting docs over code** during discovery — verify paths, class names, and signatures against
  actual code; a doc can be stale and so can your memory of the code.

## Verification / Detection

- `git log --since="14 days ago" --stat` (minimum; extend to full log if drift suspected) — confirm the audit surface matches actual commits.
- `git diff` — the set of changed files must match the docs queued for update (no orphan doc
  updates, no missed ones).
- `python3 tools/scan_doc_links.py` — catches file-listing drift (renamed/deleted files still
  referenced).
