#!/usr/bin/env python3
"""
Run test suite and parse per-module results.

Enhanced v2.1: robust output parsing, timeout handling, retry logic,
comprehensive metadata, proper output schema, and performance telemetry.
"""

from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
import sys
import time
from datetime import datetime, timezone
from pathlib import Path

try:
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        SCAN_VERSION,
        relative_path,
        build_report,
        write_report,
        print_summary,
    )
except ImportError:
    sys.path.insert(0, str(Path(__file__).parent))
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        SCAN_VERSION,
        relative_path,
        build_report,
        write_report,
        print_summary,
    )

SCAN_NAME = "tests"

# Pre-compiled patterns for performance
RE_TESTS = re.compile(r"Tests:\s+(\d+)\s+passed(?:,\s+(\d+)\s+failed)?(?:,\s+(\d+)\s+skipped)?")
RE_ASSERTIONS = re.compile(r"Assertions:\s+(\d+)")
RE_DURATION = re.compile(r"Duration:\s+([\d.]+)s")
RE_MODULE_PASS = re.compile(r"(PASS|FAIL)\s+(\w+)[\\/]")
RE_ANSI = re.compile(r'\x1b\[[0-9;]*m')
RE_PEST_SUMMARY = re.compile(r"Tests:\s+(\d+)\s+passed.* (\d+)\s+failed" )


def run_tests_robust(filter_module: str | None = None, timeout: int = 300, retry: int = 1) -> tuple[dict, list[Finding]]:
    """Run tests with retry, timeout handling, and detailed error reporting."""
    findings: list[Finding] = []
    cmd = ["php", "artisan", "test", "--compact"]
    if filter_module:
        cmd.extend(["--filter", filter_module])

    last_error = ""
    for attempt in range(retry + 1):
        try:
            start = time.time()
            proc = subprocess.run(
                cmd, capture_output=True, text=True, cwd=str(ROOT),
                timeout=timeout, env={**os.environ, "FORCE_COLOR": "0", "APP_ENV": "testing"},
            )
            duration = time.time() - start
            
            # Strip ANSI codes robustly
            output = RE_ANSI.sub('', proc.stdout + proc.stderr)
            
            # Check for common failure modes
            if "No tests found" in output:
                findings.append(Finding(
                    id="TEST-NO-TESTS",
                    rule="TEST_COVERAGE",
                    severity="medium",
                    category="convention",
                    file=f"tests/{filter_module}" if filter_module else "tests/",
                    line=0,
                    message=f"No tests found for {filter_module or 'full suite'}",
                    suggestion="Add spec-traceable tests per pest-testing skill",
                    reference="docs/conventions.md",
                ))
                return {"passed": 0, "failed": 0, "assertions": 0, "duration_seconds": duration, "skipped": False, "output": output, "by_module": {}}, findings
            
            if proc.returncode != 0 and "Tests:" not in output:
                # PHP error, not test failure
                findings.append(Finding(
                    id="TEST-ERROR",
                    rule="TEST_EXECUTION",
                    severity="high",
                    category="system",
                    file="scripts/scan_tests.py",
                    line=0,
                    message=f"Test execution failed: {output[:200]}",
                    suggestion="Check PHP version, database, and artisan test setup",
                    reference="docs/conventions.md",
                ))
                last_error = output[:500]
                if attempt < retry:
                    time.sleep(1)
                    continue
            
            result = parse_test_output(output, filter_module)
            result["duration_seconds"] = duration
            result["raw_output"] = output[:2000]  # Truncate for metadata
            return result, findings
            
        except subprocess.TimeoutExpired:
            last_error = f"Timeout after {timeout}s"
            findings.append(Finding(
                id="TEST-TIMEOUT",
                rule="TEST_PERFORMANCE",
                severity="high",
                category="performance",
                file="tests/",
                line=0,
                message=f"Test suite timed out after {timeout}s",
                suggestion="Run with --module filter or increase timeout; check for hanging tests",
                reference="docs/conventions.md",
            ))
            if attempt < retry:
                continue
            return {"error": "timeout", "skipped": True, "duration_seconds": timeout, "by_module": {}}, findings
        except FileNotFoundError:
            findings.append(Finding(
                id="TEST-PHP-MISSING",
                rule="TEST_ENVIRONMENT",
                severity="critical",
                category="system",
                file="scripts/scan_tests.py",
                line=0,
                message="PHP not found: cannot run tests",
                suggestion="Install PHP 8.4 and ensure 'php' is in PATH",
                reference="docs/conventions.md",
            ))
            return {"error": "php not found", "skipped": True, "by_module": {}}, findings
        except Exception as e:
            last_error = str(e)
            if attempt < retry:
                continue
            findings.append(Finding(
                id="TEST-UNKNOWN",
                rule="TEST_EXECUTION",
                severity="high",
                category="system",
                file="scripts/scan_tests.py",
                line=0,
                message=f"Unexpected test error: {e}",
                suggestion="Check test setup and artisan availability",
                reference="docs/conventions.md",
            ))
            return {"error": str(e), "skipped": True, "by_module": {}}, findings
    
    return {"error": last_error, "skipped": True, "by_module": {}}, findings


def parse_test_output(output: str, filter_module: str | None = None) -> dict:
    """Parse test output with multiple format support."""
    result: dict = {"passed": 0, "failed": 0, "skipped": 0, "assertions": 0, "duration_seconds": 0, "by_module": {}}

    # Try Pest format first
    m = RE_TESTS.search(output)
    if m:
        result["passed"] = int(m.group(1))
        result["failed"] = int(m.group(2) or 0)
        if m.group(3):
            result["skipped"] = int(m.group(3))

    m = RE_ASSERTIONS.search(output)
    if m:
        result["assertions"] = int(m.group(1))

    m = RE_DURATION.search(output)
    if m:
        try:
            result["duration_seconds"] = float(m.group(1))
        except ValueError:
            pass

    # Per-module parsing with multiple patterns
    module_tests: dict[str, dict] = {}
    
    # Pattern 1: PASS/FAIL per file
    for line in output.split("\n"):
        line = line.strip()
        m = RE_MODULE_PASS.match(line)
        if m:
            status, module = m.group(1), m.group(2)
            if module not in module_tests:
                module_tests[module] = {"passed": 0, "failed": 0, "skipped": 0}
            module_tests[module]["passed" if status == "PASS" else "failed"] += 1
        
        # Pattern 2: Pest's verbose output  ✓ Test name
        m2 = re.match(r'[✓✗]\s+(.+)', line)
        if m2 and filter_module and "FAIL" in line:
            if filter_module not in module_tests:
                module_tests[filter_module] = {"passed": 0, "failed": 0}
            module_tests[filter_module]["failed"] += 1

    # Fallback: attribute all to filter_module if no per-module data but we have totals
    if not module_tests and filter_module:
        passed = result.get("passed", 0)
        failed = result.get("failed", 0)
        skipped = result.get("skipped", 0)
        if passed or failed or skipped:
            module_tests[filter_module] = {"passed": passed, "failed": failed, "skipped": skipped}

    result["by_module"] = module_tests
    return result


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Run test suite and parse per-module results",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 scripts/scan_tests.py
  python3 scripts/scan_tests.py --module Assessment --verbose
  python3 scripts/scan_tests.py --json | jq '.metadata.by_module'
  python3 scripts/scan_tests.py --strict
  timeout 60 python3 scripts/scan_tests.py --module User
        """
    )
    parser.add_argument("--module", help="Run tests for a single module")
    parser.add_argument("--output", "-o", help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json", help="Output format")
    parser.add_argument("--verbose", "-v", action="store_true", help="Include detailed context")
    parser.add_argument("--quiet", "-q", action="store_true", help="Only output summary")
    parser.add_argument("--strict", "-s", action="store_true", help="Exit with code 1 on any failure")
    parser.add_argument("--json", action="store_true", help="Force JSON output to stdout")
    parser.add_argument("--timeout", type=int, default=300, help="Test timeout in seconds (default: 300)")
    parser.add_argument("--retry", type=int, default=1, help="Retry count on failure")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    start_time = time.time()
    
    if not args.quiet:
        print(f"Running tests{' for ' + args.module if args.module else ''}...")
    
    results, findings = run_tests_robust(args.module, timeout=args.timeout, retry=args.retry)
    
    # Add findings for failed tests
    if results.get("failed", 0) > 0:
        findings.append(Finding(
            id="TEST-FAILURES",
            rule="TEST_FAILURE",
            severity="high",
            category="convention",
            file=f"tests/{args.module}" if args.module else "tests/",
            line=0,
            message=f"{results['failed']} test(s) failed out of {results.get('passed', 0) + results['failed']} total",
            suggestion="Run php artisan test --compact for details, fix failing tests",
            reference="docs/conventions.md",
        ))
    
    total = results.get("passed", 0) + results.get("failed", 0)
    if not args.quiet:
        status = "✓" if results.get("failed", 0) == 0 and not results.get("skipped") else "✗"
        print(f"  {status} Passed: {results.get('passed', 0)}, Failed: {results.get('failed', 0)}, Skipped: {results.get('skipped', False)}, Duration: {results.get('duration_seconds', 0)}s")
        if results.get("by_module"):
            print(f"  Modules: {len(results['by_module'])}")

    metadata = {
        "summary": {
            "passed": results.get("passed", 0),
            "failed": results.get("failed", 0),
            "skipped": results.get("skipped", False),
            "assertions": results.get("assertions", 0),
            "duration_seconds": results.get("duration_seconds", 0),
            "total": total,
        },
        "by_module": results.get("by_module", {}),
        "timeout": args.timeout,
        "error": results.get("error"),
    }

    scan_type = "module" if args.module else "full"
    result = build_report(
        findings=findings,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=args.module,
        start_time=start_time,
        metadata=metadata,
        total_checks=total if total > 0 else 1,
    )
    # Ensure summary reflects test results
    result.summary["passed"] = results.get("passed", 0)
    result.summary["failed"] = results.get("failed", 0)

    # Output handling
    if args.json or args.format == "json":
        if not args.quiet:
            print(json.dumps(result.__dict__, indent=2, ensure_ascii=False))
    elif args.format == "summary":
        if not args.quiet:
            print_summary(result, verbose=args.verbose)
    elif args.format == "text":
        if not args.quiet:
            for f in result.findings:
                print(f"{f['file']}:{f['line']} [{f['severity']}] {f['message']}")

    output_path = Path(args.output) if args.output else None
    written_path = write_report(result, output_path)
    
    if not args.quiet and not args.json:
        print(f"\nReport saved: {relative_path(written_path)}")

    if args.strict and (result.summary["failed"] > 0 or results.get("skipped")):
        sys.exit(1)


if __name__ == "__main__":
    main()
