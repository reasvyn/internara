---
name: internara-review
description: Verify Internara quality gates — arch-guard, qa-protocol, security-audit, spec-audit. Runs C1-C8/D1-D6, OWASP/CWE, spec↔code sync; never writes code, only reports. Use when review, verify, audit, scan, quality gate, or check is mentioned.
---

# Review Command — Quality Gates & Audits (Internara)

Delegates to the `internara-reviewer` agent (verification specialist, `edit: deny`). Runs the
deterministic scanners and reports findings — never fixes code (that is `internara-builder`'s job).

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask which gate to run: post-implementation arch-guard, blind QA audit, security
audit, or spec↔code sync audit.

## Steps

1. **Pick the skill.** `arch-guard` for C1-C8/D1-D6 + class contracts + naming + security scanners;
   `qa-protocol` for blind external-benchmark audit; `security-audit` for OWASP/CWE + secrets + deps;
   `spec-audit` for spec↔code↔skills consistency.
2. **Batch all scans once.** `python3 tools/scan_*.py` + `vendor/bin/pint --dirty --test` + targeted
   tests (full suite only on-demand). Never per-edit.
3. **Verify before flagging.** Check paths/class names against actual `app/` and `docs/specs/` — no
   hallucinated findings.
4. **Report only.** Structured JSON in `tools/outputs/*.json`, GitHub issues for high-severity findings,
   one-paragraph checkpoint. Do not edit code.

## Validation

- [ ] Correct scanner set for the requested gate; outputs in `tools/outputs/`
- [ ] Findings verified against real code; report delivered (no code edits)