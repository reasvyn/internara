# Tools — Internal Devtools

Focused automation scripts for Internara's AI agent workflows. Each script performs one job
and outputs a timestamped JSON report to `tools/outputs/`.

## Standard CLI

Every scanner shares the same interface:

```bash
# Scan all modules
python3 tools/{script}.py

# Scan single module
python3 tools/{script}.py --module Academics

# Custom output path
python3 tools/{script}.py --output /tmp/custom-report.json

# Human-readable output instead of JSON file write
python3 tools/{script}.py --format summary    # table summary to stdout
python3 tools/{script}.py --format text       # plain text list of findings
python3 tools/{script}.py --json              # JSON dump to stdout

# Flags
python3 tools/{script}.py --strict            # exit 1 when findings exist
python3 tools/{script}.py --verbose           # extra detail
python3 tools/{script}.py --quiet             # suppress stdout noise
```

Default output: `tools/outputs/{YYYYMMDDHHMMSS}-{scan_name}.json`
(`tools/outputs/` is gitignored).

The mapping of each script to its output category (the `{scan_name}` suffix) is
registered in `tools/tools.json` — the source of truth for `clean_outputs.py --prune`.

### Pruning outputs

```bash
# Keep only the latest timestamped output per category, delete the rest
python3 tools/clean_outputs.py --prune

# See what would be deleted first
python3 tools/clean_outputs.py --prune --dry-run

# Keep latest plus show which file is retained per category
python3 tools/clean_outputs.py --prune -v
```

Categories come from `tools/tools.json`. Files that are not registered there, or whose
filename does not match `{YYYYMMDDHHMMSS}-{category}.json`, are never deleted.

## Output Schema

All reports share a common schema:

```json
{
  "scan_name": "violations",
  "scan_type": "full",
  "module": null,
  "timestamp": "2026-08-08T15:09:15+07:00",
  "execution_time_ms": 1234,
  "summary": {
    "total_checks": 14,
    "passed": 13,
    "failed": 59,
    "by_severity": { "critical": 0, "high": 21, "medium": 36, "low": 2 }
  },
  "findings": [
    {
      "id": "C2-001",
      "rule": "C2",
      "severity": "high",
      "category": "architecture",
      "file": "app/Core/Actions/BaseAction.php",
      "line": 42,
      "column": 0,
      "message": "...",
      "suggestion": "...",
      "reference": "docs/conventions.md §Dependency Injection",
      "context": {}
    }
  ],
  "metadata": { "total_php_files": 650, "model_classes": 42 }
}
```

Each finding is traceable to a rule ID and a `reference` into `docs/` — the authoritative
contract source.

## Scanner Inventory

| Script | Focus | Findings rules | Used by |
|--------|-------|----------------|---------|
| `scan_architecture.py` | Component counts per module, submodule structure | — (metadata only) | arch-guard, context-awareness |
| `scan_class_contracts.py` | Action/Entity/DTO/Model/Enum contract compliance | `ACTION_*`, `ENTITY_*`, `DTO_*`, `MODEL_*`, `ENUM_*`, `EVENT_*`, `POLICY_*`, `SERVICE_*`, `LISTENER_*` | arch-guard |
| `scan_conventions.py` | D1 strict_types, D4 Fillable, D2 debug calls, hardcoded strings | `D1_*`, `D2_*`, `D4_*`, `HARDCODED_STRING` | arch-guard, code-writing |
| `scan_dead_code.py` | Unregistered observers, orphan events, unused DTOs/Actions/Jobs | `UNREGISTERED_OBSERVER`, `EVENT_NO_LISTENER`, `UNUSED_DTO`, `UNUSED_ACTION`, `UNUSED_JOB` | arch-guard, code-refactoring |
| `scan_doc_links.py` | Validate relative links + in-page/other-file anchors in markdown | `BROKEN_FILE_LINK`, `BROKEN_ANCHOR` | sync-docs, doc-writing |
| `scan_files.py` | File counts and lines of code per module | — (metadata only) | context-awareness |
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | — (data fetch) | issue-writing, arch-guard |
| `scan_naming.py` | File and class naming conventions | `FILE_NAMING`, `CLASS_NAMING` | arch-guard |
| `scan_security.py` | XSS, CSP, SQL injection, mass assignment, auth, secrets, CSRF, uploads, rate limiting | `S1`–`S9` | arch-guard, security-audit |
| `scan_skills.py` | Agent SKILL.md meta-framework consistency (frontmatter, `agent-workflow` reference, no duplicated workflow boilerplate, spec-first, size, git verify, handoffs) | `SKILL_FRONTMATTER`, `SKILL_WORKFLOW_REF`, `SKILL_NO_DUP_WORKFLOW`, `SKILL_SPEC_FIRST`, `SKILL_SIZE_TRIAGE`, `SKILL_GIT_VERIFY`, `SKILL_HANDOFFS` | script-automation, all skills |
| `scan_spec_tests.py` | Spec↔tests coverage (FR/NFR/UC traceability, non-testable `*`/`~`/`!`/`-X`/`-NT` marker) | `SPEC_TEST_UNCOVERED`, `SPEC_TEST_ORPHAN`, `SPEC_TEST_MISSING_FILE`, `SPEC_TEST_NON_TESTABLE` | arch-guard, spec-audit, pest-testing |
| `scan_tests.py` | Run test suite, parse per-module results | — (data fetch) | pest-testing, test-writing |
| `scan_violations.py` | C1-C8, D1-D6 architecture invariant violations | `C1`–`C8`, `D1`–`D6`, `P2`, `P5` | arch-guard |
| `clean_outputs.py` | Remove old JSON output files by age/date range, or `--prune` (keep latest per category) | — | maintenance |
| `tools.json` | Registry mapping each scanner to its output category (used by `clean_outputs.py --prune`) | — | maintenance |

## Calibration Notes

Scanners were re-written to be calibrated against the actual codebase, eliminating the
old-baseline false positives:

- **scan_violations** — 59 findings: C2×19, C7×32, C5×2, P2×2, P5×2, C6×1, C8×1.
  D4/D6 false positives fixed (attribute-based fillable, FK with onDelete).
- **scan_class_contracts** — 8 genuine findings (4 `ACTION_MULTIPLE_PUBLIC`,
  1 `MODEL_BUSINESS_METHOD`, 3 `LISTENER_NOT_QUEUED`); baseline's 46 false positives removed.
- **scan_security** — 11 findings (S1×1, S5×10); baseline's S2 finding was a false positive.
- **scan_naming** — 4 genuine findings (`FILE_NAMING`×2, `CLASS_NAMING`×2);
  baseline's 221 snake_case/return-type false positives removed.
- **scan_conventions** — D1/D2/D4 clean; 233 `HARDCODED_STRING` (low) findings — genuine D3
  localization gaps (baseline found 0 only because it scanned the wrong directory).
- **scan_dead_code** — 20 findings (1 unregistered observer, 14 unused actions, 5 unused jobs).
  Events documented as fire-and-forget in `config/event.php` are correctly excluded.
- **scan_doc_links** — 0 broken links after fixing TOC anchors in `docs/conventions.md`
  (5.x → 6.x headings) and deployment anchors pointing at `#run-installer`.

## Dependencies

- Python 3.10+
- PHP 8.4 + Laravel (for `scan_tests.py`)
- `gh` CLI (for `scan_issues.py`)
