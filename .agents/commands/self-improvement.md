# /self-improvement Command (Internara)

Project-specific retrospective trigger for the Learning Loop (`.agents/rules/self-improvement.md`).
Mines the session for learning signals and writes them to project memory.

## When to Invoke

- End of any session (auto in Summarize, manual here for complex work).
- After a user correction or a failed verification gate.
- `/self-improvement --deep` to also scan `git diff HEAD~1` + `git log -5 --oneline`.

## Procedure

1. **Gather** signals from the transcript (and git history with `--deep`). Classify:
   decision / correction / failure / pattern / constraint / gap.
2. **Write** to `context/` (new file from `context/TEMPLATE.md` or update in place, bump `**Changes:**`),
   then add/refresh the row in `context/index.md`.
3. **Promote repeats**: signal seen ≥2 times in this codebase → add to `rules/architecture-rules.md`,
   `coding-rules.md`, `testing-rules.md`, or a new `rules/` file; for durable decisions write an ADR
   in `docs/adr/`.
4. **Log** a one-line entry in `context/learning-log.md` (newest first) linking the detail.
5. **Report** (one paragraph): files touched, rules promoted, the one correction that changes future behavior.

## Guardrails

- Fact-check every path/command (no hallucinated learning).
- One fact = one file; update in place, never duplicate.
- Don't over-promote one-offs; keep rare events in `context/`.
- Don't "fix" intentional states recorded in `context/` (e.g. deprecated model-status, dummy-guard).
