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
| `scan_architecture.py` | Component counts per module, submodule structure | `{ts}-architecture.json` | audit-protocol, context-awareness |
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings | `{ts}-conventions.json` | audit-protocol, code-writing |
| `scan_tests.py` | Run test suite, parse per-module results | `{ts}-tests.json` | pest-testing, test-writing |
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `{ts}-dead-code.json` | audit-protocol, code-refactoring |
| `scan_doc_links.py` | Validate relative links in markdown files | `{ts}-doc-links.json` | sync-docs, doc-writing |
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | `{ts}-issues.json` | writing-issues, audit-protocol |
| `scan_files.py` | File counts and lines of code per module | `{ts}-files.json` | context-awareness |

## Dependencies

- Python 3.10+
- PHP 8.4 + Laravel (for `scan_tests.py`)
- `gh` CLI (for `scan_issues.py`)
