#!/usr/bin/env python3
"""
tool_runner.py — Orchestrate multiple scan tools with shared cache and reporting.

Usage:
    python3 tools/tool_runner.py                          # Run all scanners
    python3 tools/tool_runner.py --scanner violations      # Run single scanner
    python3 tools/tool_runner.py --scanner violations,naming # Run multiple scanners
    python3 tools/tool_runner.py --list                    # List available scanners
    python3 tools/tool_runner.py --format html             # Generate HTML report
    python3 tools/tool_runner.py --compare old.json new.json # Diff two reports
"""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parent.parent
TOOLS_DIR = ROOT / "tools"
OUTPUT_DIR = TOOLS_DIR / "outputs"

# Available scanners mapped to their CLI entry points
SCANNERS = {
    "architecture": "tools/scan_architecture.py",
    "arch-patterns": "tools/scan_arch_patterns.py",
    "class-contracts": "tools/scan_class_contracts.py",
    "conventions": "tools/scan_conventions.py",
    "dead-code": "tools/scan_dead_code.py",
    "doc-links": "tools/scan_doc_links.py",
    "files": "tools/scan_files.py",
    "issues": "tools/scan_issues.py",
    "module-boundaries": "tools/scan_module_boundaries.py",
    "naming": "tools/scan_naming.py",
    "security": "tools/scan_security.py",
    "spec-tests": "tools/scan_spec_tests.py",
    "tests": "tools/scan_tests.py",
    "ui-consistency": "tools/scan_ui_consistency.py",
    "violations": "tools/scan_violations.py",
}


@dataclass
class ToolResult:
    name: str
    status: str  # "passed", "failed", "error"
    duration_ms: int
    findings: int = 0
    output_path: str | None = None
    error: str | None = None


@dataclass
class RunSummary:
    timestamp: str
    total_scanners: int
    passed: int = 0
    failed: int = 0
    error: int = 0
    total_duration_ms: int = 0
    total_findings: int = 0
    results: list[ToolResult] = field(default_factory=list)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Orchestrate multiple scan tools with shared cache and reporting",
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument(
        "--scanner", "-s",
        help="Specific scanner(s) to run, comma-separated (e.g., violations,naming)"
    )
    parser.add_argument(
        "--list", "-l",
        action="store_true",
        help="List available scanners"
    )
    parser.add_argument(
        "--module", "-m",
        help="Target specific module for all scanners"
    )
    parser.add_argument(
        "--format", "-f",
        choices=["json", "text", "summary", "html", "markdown"],
        default="summary",
        help="Output format for combined report"
    )
    parser.add_argument(
        "--output", "-o",
        help="Output file path for combined report"
    )
    parser.add_argument(
        "--no-cache",
        action="store_true",
        help="Disable file caching"
    )
    parser.add_argument(
        "--compare",
        nargs=2,
        metavar=("OLD", "NEW"),
        help="Compare two JSON report files"
    )
    parser.add_argument(
        "--quiet", "-q",
        action="store_true",
        help="Only show summary"
    )
    parser.add_argument(
        "--strict",
        action="store_true",
        help="Exit with code 1 if any scanner fails"
    )
    return parser.parse_args()


def list_scanners() -> None:
    print("Available scanners:")
    for name, path in SCANNERS.items():
        print(f"  {name:20s} → {path}")


def run_scanner(name: str, module: str | None, use_cache: bool, quiet: bool) -> ToolResult:
    """Run a single scanner and return the result."""
    script = SCANNERS.get(name)
    if not script:
        return ToolResult(
            name=name,
            status="error",
            duration_ms=0,
            error=f"Unknown scanner: {name}"
        )
    
    start = time.time()
    cmd = [sys.executable, str(ROOT / script)]
    if module:
        cmd.extend(["--module", module])
    if not use_cache:
        cmd.append("--no-cache")
    cmd.extend(["--format", "json", "--quiet"])
    
    try:
        result = subprocess.run(
            cmd,
            cwd=ROOT,
            capture_output=True,
            text=True,
            timeout=600,
        )
        duration_ms = int((time.time() - start) * 1000)
        
        # Parse JSON output
        output_path = None
        findings = 0
        try:
            data = json.loads(result.stdout)
            findings = data.get("summary", {}).get("failed", 0)
            # Find the output file path from stderr
            for line in result.stderr.split("\n"):
                if "Report saved:" in line:
                    output_path = line.split("Report saved:", 1)[1].strip()
                    break
        except (json.JSONDecodeError, ValueError):
            pass
        
        status = "passed" if result.returncode == 0 else "failed"
        error = result.stderr.strip() if status == "error" else None
        
        if not quiet:
            status_icon = "✓" if status == "passed" else "✗"
            print(f"  {status_icon} {name:20s} {duration_ms:6d}ms  findings={findings}")
        
        return ToolResult(
            name=name,
            status=status,
            duration_ms=duration_ms,
            findings=findings,
            output_path=output_path,
            error=error,
        )
    
    except subprocess.TimeoutExpired:
        duration_ms = int((time.time() - start) * 1000)
        return ToolResult(
            name=name,
            status="error",
            duration_ms=duration_ms,
            error="Timeout after 600s"
        )
    except Exception as e:
        duration_ms = int((time.time() - start) * 1000)
        return ToolResult(
            name=name,
            status="error",
            duration_ms=duration_ms,
            error=str(e)
        )


def run_scanners(
    scanner_names: list[str],
    module: str | None,
    use_cache: bool,
    quiet: bool,
) -> RunSummary:
    """Run multiple scanners and collect results."""
    results: list[ToolResult] = []
    
    for name in scanner_names:
        result = run_scanner(name, module, use_cache, quiet)
        results.append(result)
    
    return RunSummary(
        timestamp=datetime.now().isoformat(),
        total_scanners=len(results),
        passed=sum(1 for r in results if r.status == "passed"),
        failed=sum(1 for r in results if r.status == "failed"),
        error=sum(1 for r in results if r.status == "error"),
        total_duration_ms=sum(r.duration_ms for r in results),
        total_findings=sum(r.findings for r in results),
        results=results,
    )


def print_summary(summary: RunSummary) -> None:
    status_icon = "✓" if summary.failed == 0 and summary.error == 0 else "✗"
    print(f"\n{'='*60}")
    print(f"  {status_icon} TOOL RUNNER SUMMARY")
    print(f"{'='*60}")
    print(f"  Scanners:    {summary.total_scanners}")
    print(f"  Passed:      {summary.passed}")
    print(f"  Failed:      {summary.failed}")
    print(f"  Errors:      {summary.error}")
    print(f"  Findings:    {summary.total_findings}")
    print(f"  Duration:    {summary.total_duration_ms/1000:.1f}s")
    print(f"{'='*60}")
    
    if summary.failed > 0 or summary.error > 0:
        print("\n  Failed/Error scanners:")
        for r in summary.results:
            if r.status in ("failed", "error"):
                print(f"    - {r.name}: {r.status} ({r.error or f'{r.findings} findings'})")


def write_report(summary: RunSummary, output_path: Path | None = None) -> Path:
    """Write combined report to file."""
    if output_path is None:
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-tool-runner.json"
    
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    
    data = {
        "timestamp": summary.timestamp,
        "total_scanners": summary.total_scanners,
        "passed": summary.passed,
        "failed": summary.failed,
        "error": summary.error,
        "total_duration_ms": summary.total_duration_ms,
        "total_findings": summary.total_findings,
        "results": [
            {
                "name": r.name,
                "status": r.status,
                "duration_ms": r.duration_ms,
                "findings": r.findings,
                "output_path": r.output_path,
                "error": r.error,
            }
            for r in summary.results
        ],
    }
    
    output_path.write_text(
        json.dumps(data, indent=2, ensure_ascii=False) + "\n",
        encoding="utf-8",
    )
    return output_path


def compare_reports(old_path: Path, new_path: Path) -> None:
    """Compare two JSON report files and print diff."""
    try:
        old_data = json.loads(old_path.read_text(encoding="utf-8"))
        new_data = json.loads(new_path.read_text(encoding="utf-8"))
    except Exception as e:
        print(f"Error reading reports: {e}", file=sys.stderr)
        sys.exit(1)
    
    # Create minimal ScanResult-like objects for diff
    from _common import ScanResult
    
    old_result = ScanResult(
        scan_version="old",
        scan_name=old_data.get("scan_name", "old"),
        scan_type="full",
        module=old_data.get("module"),
        timestamp=old_data.get("timestamp", ""),
        execution_time_ms=0,
        summary=old_data.get("summary", {}),
        findings=old_data.get("findings", []),
        metadata=old_data.get("metadata", {}),
    )
    
    new_result = ScanResult(
        scan_version="new",
        scan_name=new_data.get("scan_name", "new"),
        scan_type="full",
        module=new_data.get("module"),
        timestamp=new_data.get("timestamp", ""),
        execution_time_ms=0,
        summary=new_data.get("summary", {}),
        findings=new_data.get("findings", []),
        metadata=new_data.get("metadata", {}),
    )
    
    from _output import format_diff
    print(format_diff(old_result, new_result))


def main() -> int:
    args = parse_args()
    
    if args.compare:
        compare_reports(Path(args.compare[0]), Path(args.compare[1]))
        return 0
    
    if args.list:
        list_scanners()
        return 0
    
    # Determine which scanners to run
    if args.scanner:
        scanner_names = [s.strip() for s in args.scanner.split(",")]
    else:
        scanner_names = list(SCANNERS.keys())
    
    print(f"Running {len(scanner_names)} scanner(s)...")
    start_time = time.time()
    
    summary = run_scanners(
        scanner_names=scanner_names,
        module=args.module,
        use_cache=not args.no_cache,
        quiet=args.quiet,
    )
    
    if not args.quiet:
        print_summary(summary)
    
    output_path = write_report(summary, args.output)
    print(f"\nReport saved: {output_path.relative_to(ROOT)}")
    
    if args.strict and (summary.failed > 0 or summary.error > 0):
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
