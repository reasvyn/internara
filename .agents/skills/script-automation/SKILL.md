---
name: script-automation
description: >
  SDLC Phase: TOOLING. Standards and conventions for writing, maintaining, and
  integrating Python devtool scripts in `scripts/`. Defines script interface,
  output format, error handling, testing, and how scripts integrate with agent skills.
  Reference this skill BEFORE creating or modifying any script in `scripts/`.
---

# Script Automation

Standards for writing, maintaining, and integrating Python devtool scripts in `scripts/`.

## Script Directory Structure

```
scripts/
├── scan_architecture.py      # Component counts, module stats
├── scan_class_contracts.py   # Action/Entity/DTO/Model/Enum contracts
├── scan_conventions.py       # strict_types, Fillable, debug, hardcoded strings
├── scan_dead_code.py         # Unused observers, DTOs, events
├── scan_doc_links.py         # Broken links in docs
├── scan_issues.py            # GitHub issue metrics
├── scan_naming.py            # Naming convention compliance
├── scan_security.py          # XSS, SQLi, mass assignment patterns
├── scan_tests.py             # Test pass/fail results
├── scan_violations.py        # C1-C8, D1-D6 violations
├── scan_files.py             # File inventory, LOC counts
├── outputs/                  # .gitignored
│   ├── .gitkeep
│   └── 20260711120000-violations.json
└── README.md                 # Human-readable script guide
```

## Script Interface

Every script MUST follow this interface:

### Command Line

```bash
python3 scripts/{script_name}.py [OPTIONS]
```

**Required flags:**

| Flag | Description | Default |
|------|-------------|---------|
| `--module`, `-m` | Target specific module (e.g., `Student`, `Academics`) | `null` (all) |
| `--output`, `-o` | Output file path | `scripts/outputs/{timestamp}-{scan_name}.json` |
| `--format`, `-f` | Output format: `json`, `text`, `summary` | `json` |
| `--verbose`, `-v` | Include detailed context in findings | `false` |
| `--quiet`, `-q` | Only output summary, no findings | `false` |
| `--strict`, `-s` | Exit with code 1 on any finding | `false` |
| `--json` | Force JSON output to stdout (for piping) | `false` |

**Examples:**

```bash
# Full scan, auto-named output
python3 scripts/scan_violations.py

# Module-specific, strict mode
python3 scripts/scan_violations.py --module Student --strict

# Quiet summary only
python3 scripts/scan_violations.py --quiet

# Pipe to jq
python3 scripts/scan_violations.py --json | jq '.summary'
```

### Output Format

Every script MUST produce JSON conforming to this schema:

```json
{
  "scan_version": "1.0.0",
  "scan_name": "violations",
  "scan_type": "full|module|targeted",
  "module": null,
  "timestamp": "2026-07-11T12:00:00+07:00",
  "execution_time_ms": 1234,
  "summary": {
    "total_checks": 100,
    "passed": 95,
    "failed": 5,
    "by_severity": {
      "critical": 0,
      "high": 2,
      "medium": 2,
      "low": 1
    }
  },
  "findings": [
    {
      "id": "RULE-001",
      "rule": "C1",
      "severity": "high|medium|low|critical",
      "category": "architecture|security|naming|convention|performance",
      "file": "app/Student/Livewire/StoreStudentForm.php",
      "line": 42,
      "column": 5,
      "message": "Human-readable description",
      "suggestion": "How to fix",
      "reference": "docs/architecture/action-pattern.md",
      "context": {}
    }
  ],
  "metadata": {
    "php_version": "8.4",
    "laravel_version": "13.0",
    "total_php_files": 650,
    "total_modules": 22
  }
}
```

### Output Path Convention

Default output path: `scripts/outputs/{YYYYMMDDHHMMSS}-{scan_name}.json`

```python
from datetime import datetime
from pathlib import Path

OUTPUT_DIR = Path(__file__).parent / "outputs"

def default_output_path(scan_name: str) -> Path:
    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
    return OUTPUT_DIR / f"{timestamp}-{scan_name}.json"
```

## Script Structure

Every script MUST follow this template:

```python
#!/usr/bin/env python3
"""
{Script Name} — {One-line description}
Scans {what} for {what it finds}.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import dataclass, field
from datetime import datetime, timezone, timedelta
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "{scan_name}"
SCAN_VERSION = "1.0.0"

# ─── Data ───────────────────────────────────────────────────────────────────

@dataclass
class Finding:
    id: str
    rule: str
    severity: str  # critical | high | medium | low
    category: str  # architecture | security | naming | convention | performance
    file: str
    line: int
    column: int = 0
    message: str = ""
    suggestion: str = ""
    reference: str = ""
    context: dict[str, Any] = field(default_factory=dict)


@dataclass
class ScanResult:
    scan_version: str
    scan_name: str
    scan_type: str  # full | module | targeted
    module: str | None
    timestamp: str
    execution_time_ms: int
    summary: dict[str, Any]
    findings: list[dict[str, Any]]
    metadata: dict[str, Any]


# ─── Helpers ────────────────────────────────────────────────────────────────

def find_php_files(module: str | None = None) -> list[Path]:
    """Find PHP files, optionally filtered by module."""
    if module:
        module_dir = APP_DIR / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.php"))
    return sorted(APP_DIR.rglob("*.php"))


def find_livewire_files(module: str | None = None) -> list[Path]:
    """Find Livewire component files."""
    files = find_php_files(module)
    return [f for f in files if "/Livewire/" in str(f)]


def find_blade_files(module: str | None = None) -> list[Path]:
    """Find Blade template files."""
    views_dir = ROOT / "resources" / "views"
    if not views_dir.exists():
        return []
    if module:
        module_dir = views_dir / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.blade.php"))
    return sorted(views_dir.rglob("*.blade.php"))


def read_file(path: Path) -> str:
    """Read file contents, return empty string on error."""
    try:
        return path.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def relative_path(path: Path) -> str:
    """Convert absolute path to project-relative path."""
    try:
        return str(path.relative_to(ROOT))
    except ValueError:
        return str(path)


# ─── Scanner Functions ──────────────────────────────────────────────────────

# Add scanner functions here...
# Each function should:
# 1. Accept module: str | None and files list
# 2. Return list[Finding]
# 3. Be named scan_{something}()


# ─── Report ─────────────────────────────────────────────────────────────────

def build_report(
    findings: list[Finding],
    scan_type: str,
    module: str | None,
    start_time: float,
) -> ScanResult:
    """Build standardized scan report."""
    elapsed_ms = int((__import__("time").time() - start_time) * 1000)

    by_severity = {"critical": 0, "high": 0, "medium": 0, "low": 0}
    for f in findings:
        by_severity[f.severity] = by_severity.get(f.severity, 0) + 1

    return ScanResult(
        scan_version=SCAN_VERSION,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(
            timezone(timedelta(hours=7))
        ).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 0,
            "passed": 0,
            "failed": len(findings),
            "by_severity": by_severity,
        },
        findings=[vars(f) for f in findings],
        metadata={},
    )


def write_report(result: ScanResult, output_path: Path | None = None) -> Path:
    """Write report to file and return path."""
    if output_path is None:
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-{SCAN_NAME}.json"

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(
        json.dumps(vars(result), indent=2, ensure_ascii=False),
        encoding="utf-8",
    )
    return output_path


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="{Script description}",
    )
    parser.add_argument(
        "--module", "-m",
        help="Target specific module",
    )
    parser.add_argument(
        "--output", "-o",
        type=Path,
        help="Output file path",
    )
    parser.add_argument(
        "--format", "-f",
        choices=["json", "text", "summary"],
        default="json",
        help="Output format",
    )
    parser.add_argument(
        "--verbose", "-v",
        action="store_true",
        help="Include detailed context",
    )
    parser.add_argument(
        "--quiet", "-q",
        action="store_true",
        help="Only output summary",
    )
    parser.add_argument(
        "--strict", "-s",
        action="store_true",
        help="Exit with code 1 on any finding",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Force JSON output to stdout",
    )
    return parser.parse_args()


def print_summary(result: ScanResult) -> None:
    """Print human-readable summary."""
    s = result.summary
    by_sev = s["by_severity"]
    print(f"\n{'='*60}")
    print(f"  {SCAN_NAME.upper()} SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Total findings: {s['failed']}")
    print(f"  Critical: {by_sev.get('critical', 0)}")
    print(f"  High:     {by_sev.get('high', 0)}")
    print(f"  Medium:   {by_sev.get('medium', 0)}")
    print(f"  Low:      {by_sev.get('low', 0)}")
    print(f"  Time:     {result.execution_time_ms}ms")
    print(f"{'='*60}\n")


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = __import__("time").time()

    scan_type = "module" if args.module else "full"

    findings: list[Finding] = []

    # Run scanners...
    # findings.extend(scan_something(args.module))

    result = build_report(findings, scan_type, args.module, start_time)

    if args.json or args.format == "json":
        print(json.dumps(vars(result), indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result)

    output_path = write_report(result, args.output)

    if not args.quiet:
        print(f"Report saved: {relative_path(output_path)}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
```

## Writing Scanner Functions

### Function Signature

```python
def scan_something(
    files: list[Path],
    module: str | None = None,
) -> list[Finding]:
    """Scan files for something specific."""
    findings = []
    for filepath in files:
        content = read_file(filepath)
        lines = content.split("\n")
        # ... scanning logic ...
    return findings
```

### Pattern Detection

Use regex for pattern detection. Pre-compile patterns for performance:

```python
# Module-specific patterns
RE_MODEL_CREATE = re.compile(
    r"(?:Model::create|\\::create|::forceCreate)\s*\(",
)

# Livewire-specific patterns
RE_LIVEWIRE_CREATE = re.compile(
    r"\\::create\s*\(",
)
```

### Finding Construction

```python
findings.append(Finding(
    id=f"{rule_id}-{len(findings)+1:03d}",
    rule=rule_id,
    severity="high",
    category="architecture",
    file=relative_path(filepath),
    line=line_num,
    column=0,
    message=f"Description of what was found",
    suggestion=f"How to fix it",
    reference="docs/architecture/action-pattern.md",
))
```

## Error Handling

Scripts MUST:

1. **Catch and report errors** — don't crash silently
2. **Continue scanning** after individual file errors
3. **Exit 0** on clean scan, **exit 1** with `--strict` on findings
4. **Always produce output** — even if empty findings list

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

## Integration with Agent Skills

### How Skills Reference Scripts

Skills reference scripts in their `## Automation Scripts` section:

```markdown
## Automation Scripts

| Script | Purpose | Skill Integration |
|--------|---------|-------------------|
| `scripts/scan_violations.py` | C1-C8, D1-D6 checks | arch-guard |
| `scripts/scan_class_contracts.py` | Class contract compliance | arch-guard |
```

### How to Add a New Script

1. Create `scripts/scan_{name}.py` following the template
2. Add `## Automation Scripts` entry to relevant skills
3. Test: `python3 scripts/scan_{name}.py --module {Module}`
4. Verify output schema matches the standard
5. Add to `scripts/README.md` table
6. Commit with message: `chore(scripts): add {name} scan`

### Skill → Script → Skill Flow

```
Agent skill detects issue
    ↓
Runs relevant script
    ↓
Script produces JSON findings
    ↓
Skill reads findings
    ↓
Skill produces actionable recommendations
    ↓
User/Agent acts on recommendations
```

## Script Testing

### Manual Testing

```bash
# Test against specific module
python3 scripts/scan_{name}.py --module Student --strict

# Test output format
python3 scripts/scan_{name}.py --json | jq '.summary'

# Test quiet mode
python3 scripts/scan_{name}.py --quiet
```

### Automated Testing (Optional)

Place test scripts in `scripts/tests/`:

```python
# scripts/tests/test_scan_{name}.py
import subprocess
import json

def test_scan_produces_valid_json():
    result = subprocess.run(
        ["python3", "scripts/scan_{name}.py", "--json"],
        capture_output=True,
        text=True,
    )
    assert result.returncode == 0
    data = json.loads(result.stdout)
    assert "summary" in data
    assert "findings" in data
```

## Performance Guidelines

| Guideline | Rationale |
|-----------|-----------|
| Pre-compile regex patterns | Avoid recompilation per file |
| Use `pathlib` over `os.path` | Consistent, readable |
| Stream large files | Don't load entire codebase into memory |
| Cache file reads when possible | Single scan may read same file multiple times |
| Target < 30s for full scan | Keep developer feedback loop fast |

## Output Quality Rules

| Rule | Rationale |
|------|-----------|
| Every finding MUST have file + line | Actionable — developer knows where to look |
| Every finding MUST have message + suggestion | Actionable — developer knows what to do |
| Every finding MUST have reference | Traceable — links to authoritative doc |
| Severity MUST be one of: critical, high, medium, low | Consistent prioritization |
| Category MUST be one of: architecture, security, naming, convention, performance, system | Consistent categorization |
| IDs MUST be unique per scan run | Reference findings in conversations |
