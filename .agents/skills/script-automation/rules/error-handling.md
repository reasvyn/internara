# Error Handling — Resilience & Exit Code Discipline

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

A scan against a ~650-file PHP codebase will hit corrupt, unreadable, or permission-denied files and
unexpected runtime errors somewhere in the corpus. Scripts MUST degrade gracefully, not crash halfway,
so a report reflecting the rest of the run is still produced. The exit code discipline (`--strict`)
turns those reports into CI gates.

---

## The Four Requirements

1. **Catch and report errors** — don't crash silently
2. **Continue scanning** after individual file errors
3. **Exit 0 on clean scan, exit 1 with `--strict` on findings**
4. **Always produce output** — even if empty findings list

### 1. Catch and report errors

**Why it matters:** an uncached exception in one file aborts the whole run and the agent/user gets *no*
report — worse than a partial one. Errors become findings (severity `low`, category `system`) so they
are visible in the report and can be triaged like any other finding.

### 2. Continue scanning after individual file errors

**Why it matters:** one unreadable file is not a reason to throw away findings from the other 649.
The scan proceeds per-file and records the failure as a finding rather than stopping the loop.

### 3. Exit code discipline

**Why it matters:** `--strict` combined with exit code 1 is the CI/CD gate — the pipeline fails when
any finding exists. Without it, scans are purely informational and drift goes unnoticed in automated
runs.

### 4. Always produce output

**Why it matters:** a scan with zero findings is still a valid, important result ("this module is
clean"). Writing an empty-findings report keeps the output contract stable for consumers.

---

## Safe-Scan Pattern

```python
def scan_files_safe(files: list[Path]) -> list[Finding]:
    """Scan with error handling."""
    findings = []
    for filepath in files:
        try:
            content = read_file(filepath)
            if not content:
                continue
            findings.extend(scan_single_file(filepath, content))
        except Exception as e:
            findings.append(Finding(
                id=f"ERR-{len(findings)+1:03d}",
                rule="SCAN_ERROR",
                severity="low",
                category="system",
                file=relative_path(filepath),
                line=0,
                message=f"Scan error: {e}",
                suggestion="Check file encoding and permissions",
            ))
    return findings
```

**Why this shape:** each file is an isolated try/except unit — the loop always advances; the exception
is converted into a `Finding` (id `ERR-...`, rule `SCAN_ERROR`, severity `low`, category `system`); the
message carries the error text; the suggestion tells the operator what likely went wrong
(encoding/permissions).

**How to apply:** wrap per-file scanning in `scan_files_safe` (or the equivalent guard) whenever a
script iterates over the corpus; keep the `read_file` helper itself non-throwing (it already returns
`""` on error), and reserve try/except for the *scanning logic* that can throw.

**Pitfalls to avoid:**

- Swallowing the exception with no finding — silently losing the file and hiding the failure.
- Letting a single bad file abort the run via an unguarded `raise`.
- Wrapping the whole corpus loop in one try/except — one failure ends the loop and you lose every
  subsequent finding.
- Emitting `SCAN_ERROR` at severity higher than `low` — infrastructure noise must never tilt a gate by
  itself.

---

## Exit Code Semantics

| Situation | Without `--strict` | With `--strict` |
|-----------|--------------------|-----------------|
| Zero findings | 0 | 0 |
| Any findings | 0 | 1 |
| Script crashes before producing a report | non-zero (Python traceback) | non-zero |

**Why it matters:** `--strict` is opt-in so interactive/exploratory runs report findings without
failing; CI always passes `--strict` so the pipeline only succeeds when a scan is clean.

---

## Verification / Detection

```bash
# Force an error path: run against an unreadable/corrupt fixture if one can be made, or review the
# per-file try/except by inspection.
python3 scripts/{name}.py --module MissingModule   # must NOT crash; empty/module findings, report still written
python3 scripts/{name}.py --json | jq '.summary.failed'   # verify SCAN_ERROR count flows into summary
```

Inspect: every corpus loop is per-file guarded; exceptions become `SCAN_ERROR` findings of severity
`low`; `--strict` handling is at the end of `main()` and uses `result.summary["failed"]`; a report file
exists even with zero findings.