# Script Structure — Template, Scanner Functions & Finding Construction

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Scripts follow one canonical template so they share helper functions, report wiring, and CLI behavior.
Deviation from the template means reinventing `read_file`, `build_report`, or `parse_args` per script —
duplicated logic that drifts. This rule documents the template, the scanner-function contract, and how
to construct findings.

---

## Script Template

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
SCAN_VERSION = "2.0.0"

# ─── Data ───────────────────────────────────────────────────────────────────

# Finding & ScanResult dataclasses — see output-format.md

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

def build_report(findings, scan_type, module, start_time) -> ScanResult:
    """Build standardized scan report (schema in output-format.md)."""


def write_report(result: ScanResult, output_path: Path | None = None) -> Path:
    """Write report to file and return path."""

# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    """Standard CLI (flags in script-interface.md)."""

def print_summary(result: ScanResult) -> None:
    """Print human-readable summary."""

# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = __import__("time").time()
    scan_type = "module" if args.module else "full"

    findings: list[Finding] = []
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

**Why the template matters:** every section has a job — constants scope the scan, helpers protect the
scanner functions, the report section standardizes output, and the CLI section keeps interaction
uniform. Copying the template per script means bug fixes to helpers land in one place conceptually but
must be re-applied everywhere; instead, keep the shared logic identical and add only the
`scan_{something}` functions plus constants.

---

## Scanner Function Signature

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

**Contract and why:**

- **Accepts a files list and optional module** — the caller (`main`) discovers files once and passes
  them; a scanner that re-discovers files cannot be reused and double-scans.
- **Returns `list[Finding]`** — pure input → findings; no side effects, no printing, no writing. This
  keeps scanning composable (multiple scanners -> one report).
- **Named `scan_{something}`** — readable, greppable, and matches the "one scanner per concern" model.

**Anti-patterns to avoid:** a scanner that writes to stdout itself; one that builds a report of its
own; one that mutates the shared `files` list; one that re-derives file lists instead of accepting
them.

---

## Pattern Detection

Use regex for pattern detection. **Pre-compile patterns for performance** — recompiling per file is a
measurable slowdown across a ~650-file codebase.

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

**Why it matters:** compiled regex patterns (module constants or module-level `RE_*`) are compiled once
and reused; inline `re.compile(...)` inside the file loop recompiles per file. Real-world scans hit
hundreds of files — the difference is seconds.

**How to apply:** keep regexes as module-level constants named `RE_*`; be explicit about what variant
they target (module-wide vs Livewire-only) so future maintainers know the intent.

---

## Finding Construction

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
    reference="docs/guides/arch/action-pattern.md",
))
```

**Contract and why:**

- `id` is `{rule}-{seq:03d}` — unique per run, deterministic within the run, references the rule.
- `file` uses `relative_path(filepath)` — a project-relative path is portable across clones; an
  absolute path breaks comparison and CI.
- `line` from the scanned content; `message` human-readable; `suggestion` concrete; `reference` the
  authoritative doc.
- `severity`/`category` from the standard enums (see `output-format.md`).

**Anti-patterns to avoid:** absolute paths in `file`; `suggestion` that restates the problem instead of
solving it; empty `reference`; zeroed `line` when the line is actually known.

---

## Verification / Detection

```bash
python3 -m py_compile scripts/{name}.py                # syntax gate
python3 scripts/{name}.py --module {Module} --strict   # scoped run + strict exit
python3 scripts/{name}.py --json | jq '.findings[0]'   # finding field contract
```

Confirm: one scanner per concern, each named `scan_{name}`; no stdout writes inside scanners; all file
reads go through `read_file`; all paths through `relative_path`.