---
name: internara-automate
description: Automate repetitive or batch work into Internara devtools — scripts, scanners, and generators in tools/. Use when automate, script, batch, tool, devtool, scan, dry-run, or repetitive multi-item work in this project is mentioned.
---

# Automate Command — Turn Internal Repetition into Tools

Delegates to the `internara-automator` agent (tooling specialist) and the `script-automation` skill.
Automation-First: if the same operation runs on 3+ items (files, lines, records, translations) or is
scan/verify/batch-shaped in this repo, script it or reuse an existing scanner in `tools/` — never redo
by hand what a script does.

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask what repeats: how many items, how often, and the target outcome. A one-off
job goes to `/tmp` (run then discard); durable value goes to `tools/`.

## Steps

1. **Understand — Find the repetition.** Identify the items/operations that repeat. If fewer than ~3, do
   it directly. Confirm durable vs one-off.
2. **Survey `tools/` first** — `scan_violations.py`, `scan_class_contracts.py`, `scan_security.py`,
   `scan_naming.py`, `scan_conventions.py`, `scan_doc_links.py` are faster and deterministic than manual
   greps. Reuse before create.
3. **Build — Write & place it.** Follow `.agents/skills/script-automation/SKILL.md` (interface, output
   format `tools/outputs/{timestamp}-*.json`, error handling, idempotency, dry-run for destructive ops).
   Batch edits into few passes.
4. **Verify — Prove it works.** Run, confirm output shape, check idempotency. For renames/batch:
   `git status` + `git diff` show only intended changes.
5. **Summarize — Hand off.** Report script path, invocation, outcome. Add integration note to the
   Automation Scripts section or skill table so future agents reuse it.

## Validation

- [ ] Script in `tools/` (durable) or `/tmp` + discarded (one-off)
- [ ] Idempotent, dry-run guard for destructive ops, `tools/outputs/` JSON output follows standards
- [ ] Reuse-before-create honored — no duplicate of an existing scanner