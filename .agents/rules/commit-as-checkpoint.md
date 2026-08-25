# Commit as Checkpoint

> **Last updated:** 2026-08-25 **Changes:** initial — promoted from an inline AGENTS.md line into a formal agent rule

## Description

Every agent session ends with a git commit that serves as its **checkpoint**: a durable, reviewable
marker of exactly what was delivered and verified in that session.

---

**Always end a session with a commit as its checkpoint. Never leave finished, verified work
uncommitted across sessions.**

## Why

- **Progress survives context loss.** A new session (or a different agent) resumes from git state,
  not from a stale conversation.
- **Uncommitted work is loss-prone.** Anything not committed can be overwritten, forgotten, or
  entangled with unrelated future changes.
- **History becomes the audit trail.** One checkpoint per session maps cleanly onto review,
  rollback points, and the final report delivered to the user.

## How to Apply

| Situation | Action |
|-----------|--------|
| Work complete and verified | Commit normally — `type(scope): description`, stage only intended files |
| Session ending mid-task | Still commit as checkpoint: use the nearest applicable type and state remaining work explicitly in the message body so the next session resumes from a clean tree |
| Verification cannot pass by session end | Do not silently commit a red state — file the defect per [pre-existing-defects.md](pre-existing-defects.md) and report the blocker in the final report |
| L-size multi-session work | One checkpoint commit per session plan item |

Rules:

- Run the [pre-commit checklist](pre-commit-checklist.md) before every checkpoint.
- Commit format follows the project convention `type(scope): description` (AGENTS.md §Quick
  Reference) — no empty commits, no dumping unrelated files into the checkpoint.
- The commit is the session's closing contract: after it lands, deliver the final report
  (what changed, what was verified, caveats).
