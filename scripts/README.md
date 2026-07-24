# Scripts

Focused automation scripts for Internara's AI agent workflows. Each script performs one job
and outputs a timestamped JSON report to `scripts/outputs/`.

## Usage

```bash
# Scan all modules
python3 scripts/{script}.py

# Scan single module
python3 scripts/{script}.py --module Academics

# Custom output path
python3 scripts/{script}.py --output /tmp/custom-report.json
```

Default output: `scripts/outputs/{YYYYMMDDHHMMSS}-{description}.json`

## Scripts

| Script | Focus | Output | Used by |
|--------|-------|--------|---------|
| `scan_architecture.py` | Component counts per module, submodule structure | `{ts}-architecture.json` | arch-guard, context-awareness |
| `scan_class_contracts.py` | Action, Entity, DTO, Model, Enum contract compliance | `{ts}-class-contracts.json` | arch-guard |
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings | `{ts}-conventions.json` | arch-guard, code-writing |
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `{ts}-dead-code.json` | arch-guard, code-refactoring |
| `scan_doc_links.py` | Validate relative links in markdown files | `{ts}-doc-links.json` | sync-docs, doc-writing |
| `scan_files.py` | File counts and lines of code per module | `{ts}-files.json` | context-awareness |
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | `{ts}-issues.json` | writing-issues, arch-guard |
| `scan_naming.py` | File, class, method, variable naming conventions | `{ts}-naming.json` | arch-guard |
| `scan_security.py` | XSS, SQL injection, auth gaps, hardcoded secrets | `{ts}-security.json` | arch-guard, security-audit |
| `scan_tests.py` | Run test suite, parse per-module results | `{ts}-tests.json` | pest-testing, test-writing |
| `scan_violations.py` | C1-C8, D1-D6 architecture invariant violations | `{ts}-violations.json` | arch-guard |
| `clean_outputs.py` | Remove old JSON output files by age or date range | — | maintenance |

## Dependencies

- Python 3.10+
- PHP 8.4 + Laravel (for `scan_tests.py`)
- `gh` CLI (for `scan_issues.py`)
