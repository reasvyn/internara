# Commit as Checkpoint

> **Last updated:** 2026-08-25 **Changes:** hardened after a missed checkpoint — multi-stage/migration work requires a commit at each verified milestone, not only at session end

## Description

Every agent session ends with a git commit that serves as its **checkpoint**: a durable, reviewable
marker of exactly what was delivered and verified in that session. For multi-stage work, checkpoints
land at **each verified milestone** — not only at session end.

---

**Always end a session with a commit as its checkpoint. Never leave finished, verified work
uncommitted across sessions.**

**Checkpoint per verified milestone, not per session only.** When work spans multiple stages
(migrations, bulk refactors, dependency upgrades), commit as soon as a stage reaches a verifiable
state — do not accumulate an entire risky operation into one terminal commit. A crash or context
loss mid-operation must never strand more than one stage of work.

## Why

- **Progress survives context loss.** A new session (or a different agent) resumes from git state,
  not from a stale conversation.
- **Uncommitted work is loss-prone.** Anything not committed can be overwritten, forgotten, or
  entangled with unrelated future changes.
- **History becomes the audit trail.** One checkpoint per stage/session maps cleanly onto review,
  rollback points, and the final report delivered to the user.
- **Crash blast radius stays small.** The worst uncommitted window is one stage, never the whole
  operation.

## How to Apply

| Situation | Action |
|-----------|--------|
| Work complete and verified | Commit normally — `type(scope): description`, stage only intended files |
| A multi-stage stage reaches a verifiable state | Commit immediately as that stage's checkpoint — before starting the next stage |
| Session ending mid-task | Still commit as checkpoint: use the nearest applicable type and state remaining work explicitly in the message body so the next session resumes from a clean tree |
| Verification cannot pass by session end | Do not silently commit a red state — file the defect per [pre-existing-defects.md](pre-existing-defects.md) and report the blocker in the final report |
| L-size multi-session work | One checkpoint commit per session plan item |

Rules:

- Run the [pre-commit checklist](pre-commit-checklist.md) before every checkpoint.
- Commit format follows the project convention `type(scope): description` (AGENTS.md §Quick
  Reference) — no empty commits, no dumping unrelated files into the checkpoint.
- The commit is the closing contract of its stage: after it lands, continue to the next stage or
  deliver the final report (what changed, what was verified, caveats).
- A checkpoint may carry known-broken intermediate state **only** when the message body states it
  explicitly (e.g., "mechanical move done; link repair follows") — silent red checkpoints are
  forbidden.
