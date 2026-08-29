# Automation-First — Scripts & Batch Patterns

## Description

Turn mechanical effort into scripts before doing manual repetitive work — check existing devtools
first, detect batch patterns early, and keep throwaway scripts out of `tools/`.

---

Speed up work by turning mechanical effort into scripts. Apply this **before** doing manual
repetitive work, not after.

- **Check `tools/` before repeating anything** — scanning, bulk renames, mass edits, seed data,
  report generation. If a devtool already covers the task, use it (they are faster, deterministic,
  and arch-verified). Never redo by hand what a script does.
- **Detect the pattern** — if the same operation would run on 3+ items (files, lines, records,
  translations) or is scan/verify/batch-shaped, script it or reuse an existing tool
  (Computational Thinking: algorithm design).
- **Run the existing scanners** for quality gates instead of manual greps: `scan_violations.py`,
  `scan_class_contracts.py`, `scan_security.py`, `scan_naming.py`, `scan_conventions.py`,
  `scan_doc_links.py` (see [verification-strategy.md](verification-strategy.md)).
- **When writing a new script**, load the `script-automation` skill first and follow its standards
  (interface, output format, error handling). Keep scripts in `tools/`.
- **One-off / few-off scripts NEVER go in `tools/`** — scripts used only a handful of times
  (single migration batch, temporary data fix, one-time conversion) must be written to `/tmp`
  (e.g. `/tmp/migrate_x.py`), run, then discarded. `tools/` is exclusively for durable,
  reusable devtools with long-term value; committing throwaway scripts pollutes the toolchain.
- **Batch your own operations too** — group edits, tests, and verification into few passes instead
  of many small round-trips (full suite is ~2GB+, 10+ min; never run it per-edit).
