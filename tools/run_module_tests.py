#!/usr/bin/env python3
"""
run_module_tests.py — Modular Test Runner

Runs Pest/PHPUnit test suites one module at a time to minimize memory usage.
This avoids the ~2GB+ RAM and 10+ minute runtime of the full suite.

Usage:
    python3 tools/run_module_tests.py                    # Run all modules sequentially
    python3 tools/run_module_tests.py --module Core      # Run single module
    python3 tools/run_module_tests.py --list             # List available modules
    python3 tools/run_module_tests.py --failed-only      # Re-run only failed modules
    python3 tools/run_module_tests.py --parallel 2       # Run N modules in parallel (experimental)
"""

from __future__ import annotations

import argparse
import json
import os
import subprocess
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parent.parent
OUTPUT_DIR = ROOT / "tools" / "outputs"
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# Test suites from phpunit.xml (in build order per docs/specs/index.md)
MODULES = [
    "Core",
    "Setup",
    "Settings",
    "Academics",
    "Auth",
    "User",
    "SysAdmin",
    "Partners",
    "Program",
    "Enrollment",
    "Journals",
    "Incident",
    "Assessment",
    "Evaluation",
    "Assignment",
    "Document",
    "Certification",
    "Reports",
]


@dataclass
class ModuleResult:
    module: str
    status: str  # "passed", "failed", "skipped", "error"
    duration_ms: int
    tests: int = 0
    assertions: int = 0
    error: str | None = None
    output: str = ""


@dataclass
class RunSummary:
    timestamp: str
    total_modules: int
    passed: int
    failed: int
    skipped: int
    error: int
    total_duration_ms: int
    total_tests: int
    total_assertions: int
    results: list[ModuleResult]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Run Pest/PHPUnit test suites one module at a time"
    )
    parser.add_argument("--module", "-m", help="Run single module (e.g., Core)")
    parser.add_argument("--list", "-l", action="store_true", help="List available modules")
    parser.add_argument(
        "--failed-only",
        action="store_true",
        help="Re-run only modules that failed in last run",
    )
    parser.add_argument(
        "--parallel",
        "-p",
        type=int,
        default=1,
        help="Run N modules in parallel (experimental, default 1 = sequential)",
    )
    parser.add_argument(
        "--timeout",
        "-t",
        type=int,
        default=300,
        help="Timeout per module in seconds (default 300)",
    )
    parser.add_argument(
        "--no-output",
        action="store_true",
        help="Suppress module output (only show summary)",
    )
    parser.add_argument(
        "--output",
        "-o",
        type=Path,
        help="Output JSON report path",
    )
    parser.add_argument(
        "--format",
        "-f",
        choices=["json", "text", "summary"],
        default="text",
    )
    parser.add_argument(
        "--pest-args",
        default="",
        help="Additional arguments passed to pest (e.g., '--compact --filter=...')",
    )
    return parser.parse_args()


def run_module_test(
    module: str,
    pest_args: str = "",
    timeout: int = 300,
    no_output: bool = False,
) -> ModuleResult:
    """Run tests for a single module using Pest."""
    start = time.time()

    if not no_output:
        print(f"\n{'='*60}")
        print(f"  Running: {module}")
        print(f"{'='*60}")

    cmd = ["vendor/bin/pest", f"--testsuite={module}"]
    if pest_args:
        cmd.extend(pest_args.split())

    try:
        result = subprocess.run(
            cmd,
            cwd=ROOT,
            capture_output=True,
            text=True,
            timeout=timeout,
        )

        duration_ms = int((time.time() - start) * 1000)
        output = result.stdout + result.stderr

# Parse test results from output
        tests = 0
        assertions = 0
        status = "passed" if result.returncode == 0 else "failed"

        # Try to extract test count from output
        import re

        # Strip ANSI escape codes
        clean_output = re.sub(r"\x1b\[[0-9;]*m", "", output)

        for line in clean_output.splitlines():
            # Pest format: "Tests:  6 passed (20 assertions)"
            m = re.search(r"Tests:\s+(\d+)\s+\w+", line)
            if m:
                tests = int(m.group(1))
            m = re.search(r"\((\d+)\s+assertions?\)", line)
            if m:
                assertions = int(m.group(1))
            # PHPUnit format: "OK (5 tests, 12 assertions)"
            m = re.search(r"OK\s+\((\d+)\s+tests?,", line)
            if m:
                tests = int(m.group(1))
            m = re.search(r"(\d+)\s+assertions?\)", line)
            if m and assertions == 0:
                assertions = int(m.group(1))

        if not no_output:
            print(output.strip() or "(no output)")

        return ModuleResult(
            module=module,
            status=status,
            duration_ms=duration_ms,
            tests=tests,
            assertions=assertions,
            output=output if not no_output else "",
        )

    except subprocess.TimeoutExpired:
        duration_ms = int((time.time() - start) * 1000)
        return ModuleResult(
            module=module,
            status="error",
            duration_ms=duration_ms,
            error=f"Timeout after {timeout}s",
        )
    except Exception as e:
        duration_ms = int((time.time() - start) * 1000)
        return ModuleResult(
            module=module,
            status="error",
            duration_ms=duration_ms,
            error=str(e),
        )


def run_modules_sequential(
    modules: list[str],
    pest_args: str = "",
    timeout: int = 300,
    no_output: bool = False,
) -> list[ModuleResult]:
    """Run modules one at a time."""
    results = []
    for module in modules:
        result = run_module_test(module, pest_args, timeout, no_output)
        results.append(result)
        if result.status == "failed" and not no_output:
            print(f"  ⚠ {module} FAILED")
        elif result.status == "error" and not no_output:
            print(f"  ✗ {module} ERROR: {result.error}")
        elif not no_output:
            print(f"  ✓ {module} passed ({result.tests} tests, {result.assertions} assertions)")
    return results


def load_last_run() -> list[str] | None:
    """Load failed modules from last run report."""
    reports = sorted(OUTPUT_DIR.glob("*module-tests.json"))
    if not reports:
        return None
    try:
        data = json.loads(reports[-1].read_text())
        return [r["module"] for r in data["results"] if r["status"] in ("failed", "error")]
    except Exception:
        return None


def write_report(summary: RunSummary, output_path: Path | None = None) -> Path:
    if output_path is None:
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-module-tests.json"
    output_path.parent.mkdir(parents=True, exist_ok=True)
    # Convert dataclass to dict properly
    data = {
        "timestamp": summary.timestamp,
        "total_modules": summary.total_modules,
        "passed": summary.passed,
        "failed": summary.failed,
        "skipped": summary.skipped,
        "error": summary.error,
        "total_duration_ms": summary.total_duration_ms,
        "total_tests": summary.total_tests,
        "total_assertions": summary.total_assertions,
        "results": [
            {
                "module": r.module,
                "status": r.status,
                "duration_ms": r.duration_ms,
                "tests": r.tests,
                "assertions": r.assertions,
                "error": r.error,
                "output": r.output,
            }
            for r in summary.results
        ],
    }
    output_path.write_text(
        json.dumps(data, indent=2, ensure_ascii=False), encoding="utf-8"
    )
    return output_path


def print_summary(summary: RunSummary) -> None:
    print(f"\n{'='*60}")
    print(f"  MODULE TEST RUN SUMMARY")
    print(f"{'='*60}")
    print(f"  Timestamp:     {summary.timestamp}")
    print(f"  Modules:       {summary.total_modules}")
    print(f"  Passed:        {summary.passed}")
    print(f"  Failed:        {summary.failed}")
    print(f"  Skipped:       {summary.skipped}")
    print(f"  Errors:        {summary.error}")
    print(f"  Total tests:   {summary.total_tests}")
    print(f"  Total asserts: {summary.total_assertions}")
    print(f"  Duration:      {summary.total_duration_ms/1000:.1f}s")
    print(f"{'='*60}")

    if summary.failed > 0 or summary.error > 0:
        print("\n  Failed/Error modules:")
        for r in summary.results:
            if r.status in ("failed", "error"):
                print(f"    - {r.module}: {r.status} ({r.error or f'{r.tests} tests'})")


def main() -> int:
    args = parse_args()

    if args.list:
        print("Available modules (in build order):")
        for i, m in enumerate(MODULES, 1):
            print(f"  {i:2d}. {m}")
        return 0

    modules_to_run = MODULES

    if args.module:
        if args.module not in MODULES:
            print(f"Error: Unknown module '{args.module}'. Use --list to see available.")
            return 1
        modules_to_run = [args.module]
    elif args.failed_only:
        failed = load_last_run()
        if not failed:
            print("No previous failed modules found.")
            return 0
        modules_to_run = failed
        print(f"Re-running {len(failed)} failed module(s): {', '.join(failed)}")

    print(f"Running {len(modules_to_run)} module(s)...")

    start_time = time.time()
    if args.parallel > 1:
        # TODO: implement parallel execution
        print("Parallel execution not yet implemented, falling back to sequential")
        args.parallel = 1

    results = run_modules_sequential(
        modules_to_run,
        pest_args=args.pest_args,
        timeout=args.timeout,
        no_output=args.no_output,
    )

    total_duration_ms = int((time.time() - start_time) * 1000)

    summary = RunSummary(
        timestamp=datetime.now().isoformat(),
        total_modules=len(results),
        passed=sum(1 for r in results if r.status == "passed"),
        failed=sum(1 for r in results if r.status == "failed"),
        skipped=sum(1 for r in results if r.status == "skipped"),
        error=sum(1 for r in results if r.status == "error"),
        total_duration_ms=total_duration_ms,
        total_tests=sum(r.tests for r in results),
        total_assertions=sum(r.assertions for r in results),
        results=results,
    )

    output_path = write_report(summary, args.output)

    if args.format == "json":
        print(json.dumps(summary.__dict__, indent=2, default=str))
    elif not args.no_output:
        print_summary(summary)

    print(f"\nReport saved: {output_path.relative_to(ROOT)}")

    return 1 if summary.failed > 0 or summary.error > 0 else 0


if __name__ == "__main__":
    sys.exit(main())