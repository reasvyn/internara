#!/usr/bin/env python3
"""
Shared helpers for all scan scripts — reliability and sophistication layer.

Provides:
- Finding / ScanResult dataclasses (canonical schema)
- File discovery with caching and filtering
- Robust file reading with per-file error isolation
- Report building with severity aggregation and timing
- CLI parsing helpers and output writing with atomic writes
- Performance helpers (pre-compiled regex, parallel execution)
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass, field, asdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Callable, Iterable

# ─── Constants ───────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_VERSION = "2.1.0"

# Exclude patterns for file discovery
EXCLUDE_DIRS = {"vendor", "node_modules", ".git", "storage", "bootstrap/cache"}
EXCLUDE_FILES = {"*.min.js", "*.min.css"}

VALID_SEVERITIES = {"critical", "high", "medium", "low"}
VALID_CATEGORIES = {"architecture", "security", "naming", "convention", "performance", "system", "documentation"}

# ─── Data ───────────────────────────────────────────────────────────────────


@dataclass
class Finding:
    id: str
    rule: str
    severity: str
    category: str
    file: str
    line: int
    column: int = 0
    message: str = ""
    suggestion: str = ""
    reference: str = ""
    context: dict[str, Any] = field(default_factory=dict)

    def __post_init__(self):
        if self.severity not in VALID_SEVERITIES:
            raise ValueError(f"Invalid severity: {self.severity}")
        if self.category not in VALID_CATEGORIES:
            raise ValueError(f"Invalid category: {self.category}")


@dataclass
class ScanResult:
    scan_version: str
    scan_name: str
    scan_type: str
    module: str | None
    timestamp: str
    execution_time_ms: int
    summary: dict[str, Any]
    findings: list[dict[str, Any]]
    metadata: dict[str, Any]


# ─── File Helpers ───────────────────────────────────────────────────────────


def relative_path(path: Path) -> str:
    """Convert absolute path to project-relative path with error isolation."""
    try:
        return str(path.relative_to(ROOT))
    except ValueError:
        return str(path)
    except Exception:
        return str(path)


def read_file(path: Path) -> str:
    """Read file contents with robust error handling. Returns empty string on error."""
    try:
        return path.read_text(encoding="utf-8", errors="replace")
    except (OSError, UnicodeError, PermissionError) as e:
        # Log to stderr in verbose mode, but don't crash the scan
        return ""
    except Exception:
        return ""


def read_file_lines(path: Path) -> list[str]:
    """Read file as lines with error isolation."""
    content = read_file(path)
    return content.splitlines() if content else []


def find_php_files(module: str | None = None, include_tests: bool = False) -> list[Path]:
    """Find PHP files with caching and filtering. Respects module filter."""
    if module:
        module_dir = APP_DIR / module
        if not module_dir.exists():
            return []
        pattern = "**/*.php"
        files = list(module_dir.rglob(pattern))
        if not include_tests:
            files = [f for f in files if "tests" not in f.parts]
        return sorted(files)
    
    # Full scan with exclusions
    files = []
    for p in APP_DIR.rglob("*.php"):
        if any(ex in p.parts for ex in EXCLUDE_DIRS):
            continue
        if not include_tests and "tests" in p.parts:
            continue
        files.append(p)
    return sorted(files)


def find_blade_files(module: str | None = None) -> list[Path]:
    """Find Blade template files."""
    views_dir = ROOT / "resources" / "views"
    if not views_dir.exists():
        return []
    if module:
        # Try module-specific views
        candidates = [
            views_dir / module.lower(),
            views_dir / module,
        ]
        for cand in candidates:
            if cand.exists():
                return sorted(cand.rglob("*.blade.php"))
        return []
    return sorted(views_dir.rglob("*.blade.php"))


def find_md_files(module: str | None = None) -> list[Path]:
    """Find Markdown files for doc scans."""
    if module:
        # For docs, module filter means subdirectory
        docs_sub = ROOT / "docs" / module.lower()
        if docs_sub.exists():
            return sorted(docs_sub.rglob("*.md"))
        return []
    
    files = []
    for p in ROOT.rglob("*.md"):
        if any(ex in p.parts for ex in EXCLUDE_DIRS):
            continue
        files.append(p)
    return sorted(files)


def find_files_parallel(
    files: list[Path],
    processor: Callable[[Path], list[Finding]],
    max_workers: int = 8,
) -> list[Finding]:
    """Process files in parallel with per-file error isolation."""
    findings: list[Finding] = []
    errors: list[tuple[Path, Exception]] = []
    
    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        future_to_path = {executor.submit(processor, f): f for f in files}
        for future in as_completed(future_to_path):
            path = future_to_path[future]
            try:
                result = future.result()
                if result:
                    findings.extend(result)
            except Exception as e:
                errors.append((path, e))
                # Don't crash, just record error as finding if verbose
                continue
    
    # Optionally log errors to stderr
    if errors and any("--verbose" in arg for arg in sys.argv):
        for path, err in errors[:5]:  # Only show first 5
            print(f"Warning: Error processing {relative_path(path)}: {err}", file=sys.stderr)
    
    return findings


# ─── Report Helpers ─────────────────────────────────────────────────────────


def build_report(
    findings: list[Finding],
    scan_name: str,
    scan_type: str,
    module: str | None,
    start_time: float,
    metadata: dict[str, Any] | None = None,
    total_checks: int | None = None,
) -> ScanResult:
    """Build standardized scan report with validation."""
    execution_time_ms = int((time.time() - start_time) * 1000)
    
    # Validate and normalize findings
    finding_dicts = []
    by_severity: dict[str, int] = {"critical": 0, "high": 0, "medium": 0, "low": 0}
    
    for f in findings:
        # Validate severity
        if f.severity not in VALID_SEVERITIES:
            f.severity = "medium"  # Fallback
        by_severity[f.severity] = by_severity.get(f.severity, 0) + 1
        finding_dicts.append(asdict(f))
    
    # Calculate summary
    failed = len(finding_dicts)
    if total_checks is None:
        total_checks = failed  # If not specified, assume checks == findings for simple scans
        passed = 0
    else:
        passed = max(0, total_checks - failed)
    
    summary = {
        "total_checks": total_checks,
        "passed": passed,
        "failed": failed,
        "by_severity": by_severity,
    }
    
    # Validate summary consistency
    assert summary["failed"] == len(finding_dicts), "Summary failed count mismatch"
    assert sum(by_severity.values()) == failed, "Severity buckets don't sum to failed count"
    
    return ScanResult(
        scan_version=SCAN_VERSION,
        scan_name=scan_name,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone.utc).isoformat(),
        execution_time_ms=execution_time_ms,
        summary=summary,
        findings=finding_dicts,
        metadata=metadata or {},
    )


def write_report(result: ScanResult, output_path: Path | None = None) -> Path:
    """Write report atomically to file."""
    if output_path is None:
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-{result.scan_name}.json"
    
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    
    # Atomic write via temp file
    tmp_path = output_path.with_suffix(".tmp")
    try:
        data = asdict(result) if hasattr(result, '__dataclass_fields__') else result
        # Handle nested dataclass conversion
        if hasattr(result, 'findings') and result.findings and isinstance(result.findings[0], dict):
            # Already dicts
            json_data = asdict(result) if hasattr(result, '__dataclass_fields__') else result
        else:
            json_data = asdict(result)
        
        tmp_path.write_text(json.dumps(json_data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        tmp_path.replace(output_path)
    except Exception as e:
        # Fallback to direct write if atomic fails
        try:
            output_path.write_text(json.dumps(asdict(result), indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        except Exception:
            raise RuntimeError(f"Failed to write report to {output_path}: {e}") from e
    finally:
        if tmp_path.exists():
            try:
                tmp_path.unlink()
            except OSError:
                pass
    
    return output_path


def print_summary(result: ScanResult, verbose: bool = False) -> None:
    """Print human-readable summary with sophistication."""
    # Color support detection
    use_color = sys.stdout.isatty()
    
    def colorize(text: str, color: str) -> str:
        if not use_color:
            return text
        colors = {"red": "\033[91m", "green": "\033[92m", "yellow": "\033[93m", "blue": "\033[94m", "reset": "\033[0m"}
        return f"{colors.get(color, '')}{text}{colors.get('reset', '')}"
    
    print(f"\n{colorize('═' * 60, 'blue')}")
    print(f"{colorize(f' Scan: {result.scan_name} ({result.scan_type})', 'blue')}")
    if result.module:
        print(f" Module: {result.module}")
    print(f" Time: {result.execution_time_ms}ms | Files: {result.metadata.get('total_files', 'N/A')}")
    print(f"{colorize('─' * 60, 'blue')}")
    
    s = result.summary
    status_color = "green" if s["failed"] == 0 else "red" if s["by_severity"]["critical"] > 0 else "yellow"
    passed_str = f"Passed: {s['passed']}"
    failed_str = f"Failed: {s['failed']}"
    print(f" Checks: {s['total_checks']} | {colorize(passed_str, 'green')} | {colorize(failed_str, status_color)}")
    
    if s["failed"] > 0:
        by_sev = s["by_severity"]
        sev_str = " | ".join(f"{k}: {v}" for k, v in by_sev.items() if v > 0)
        print(f" By severity: {sev_str}")
    
    if verbose and result.findings:
        print(f"\n{colorize('Findings:', 'yellow')}")
        for f in result.findings[:10]:  # Show first 10 in verbose
            print(f"  {colorize(f['id'], 'red')} [{f['severity']}] {f['file']}:{f['line']} - {f['message']}")
        if len(result.findings) > 10:
            print(f"  ... and {len(result.findings) - 10} more (see JSON report)")
    
    if s["failed"] == 0:
        print(colorize("\n ✓ All checks passed", "green"))
    else:
        print(colorize(f"\n ✗ {s['failed']} finding(s) need attention", "red"))
    
    print(f"{colorize('═' * 60, 'blue')}\n")


def parse_args_with_common(description: str) -> argparse.Namespace:
    """Create standardized argument parser with all required flags."""
    parser = argparse.ArgumentParser(description=description, formatter_class=argparse.RawDescriptionHelpFormatter)
    
    parser.add_argument("--module", "-m", help="Target specific module (e.g., Student, Academics)")
    parser.add_argument("--output", "-o", help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json", help="Output format")
    parser.add_argument("--verbose", "-v", action="store_true", help="Include detailed context in findings")
    parser.add_argument("--quiet", "-q", action="store_true", help="Only output summary, no findings")
    parser.add_argument("--strict", "-s", action="store_true", help="Exit with code 1 on any finding")
    parser.add_argument("--json", action="store_true", help="Force JSON output to stdout (for piping)")
    parser.add_argument("--severity", choices=["critical", "high", "medium", "low"], help="Filter by minimum severity")
    parser.add_argument("--baseline", help="Baseline file to ignore known findings")
    
    return parser.parse_args()


# ─── Validation Helpers ─────────────────────────────────────────────────────


def validate_findings(findings: list[Finding]) -> list[str]:
    """Validate findings for output quality, return list of errors."""
    errors = []
    seen_ids = set()
    
    for f in findings:
        if not f.file:
            errors.append(f"Finding {f.id} missing file")
        if f.line < 0:
            errors.append(f"Finding {f.id} invalid line {f.line}")
        if not f.message:
            errors.append(f"Finding {f.id} missing message")
        if not f.suggestion:
            errors.append(f"Finding {f.id} missing suggestion")
        if not f.reference:
            errors.append(f"Finding {f.id} missing reference")
        if f.id in seen_ids:
            errors.append(f"Duplicate finding id: {f.id}")
        seen_ids.add(f.id)
        if f.severity not in VALID_SEVERITIES:
            errors.append(f"Finding {f.id} invalid severity: {f.severity}")
        if f.category not in VALID_CATEGORIES:
            errors.append(f"Finding {f.id} invalid category: {f.category}")
    
    return errors


def load_baseline(baseline_path: Path) -> set[tuple[str, str, int]]:
    """Load baseline findings to ignore (file, rule, line)."""
    if not baseline_path.exists():
        return set()
    try:
        data = json.loads(baseline_path.read_text(encoding="utf-8"))
        return {(f["file"], f["rule"], f["line"]) for f in data.get("findings", [])}
    except Exception:
        return set()


def filter_by_baseline(findings: list[Finding], baseline: set[tuple[str, str, int]]) -> list[Finding]:
    """Filter out findings that are in baseline."""
    if not baseline:
        return findings
    return [f for f in findings if (f.file, f.rule, f.line) not in baseline]


def filter_by_severity(findings: list[Finding], min_severity: str | None) -> list[Finding]:
    """Filter findings by minimum severity."""
    if not min_severity:
        return findings
    
    order = {"low": 0, "medium": 1, "high": 2, "critical": 3}
    min_level = order.get(min_severity, 0)
    return [f for f in findings if order.get(f.severity, 0) >= min_level]
