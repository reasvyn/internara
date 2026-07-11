#!/usr/bin/env python3
"""Run test suite and parse per-module results."""

from __future__ import annotations

import argparse
import json
import os
import re
import subprocess
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent


def run_tests(filter_module: str | None = None) -> dict:
    cmd = ["php", "artisan", "test", "--compact"]
    if filter_module:
        cmd.extend(["--filter", filter_module])

    try:
        proc = subprocess.run(
            cmd, capture_output=True, text=True, cwd=str(ROOT),
            timeout=300, env={**os.environ, "FORCE_COLOR": "0"},
        )
        # Strip ANSI escape codes
        output = re.sub(r'\x1b\[[0-9;]*m', '', proc.stdout + proc.stderr)
    except subprocess.TimeoutExpired:
        return {"error": "timeout", "skipped": True}
    except FileNotFoundError:
        return {"error": "php not found", "skipped": True}

    result = {"passed": 0, "failed": 0, "assertions": 0, "duration_seconds": 0, "skipped": False, "by_module": {}}

    m = re.search(r"Tests:\s+(\d+)\s+passed(?:,\s+(\d+)\s+failed)?", output)
    if m:
        result["passed"] = int(m.group(1))
        result["failed"] = int(m.group(2) or 0)

    m = re.search(r"Assertions:\s+(\d+)", output)
    if m:
        result["assertions"] = int(m.group(1))

    m = re.search(r"Duration:\s+([\d.]+)s", output)
    if m:
        result["duration_seconds"] = float(m.group(1))

    # Parse per-module from verbose PASS/FAIL lines (if available)
    module_tests: dict[str, dict] = {}
    for line in output.split("\n"):
        m = re.match(r"(PASS|FAIL)\s+(\w+)[\\/]", line.strip())
        if m:
            status, module = m.group(1), m.group(2)
            if module not in module_tests:
                module_tests[module] = {"passed": 0, "failed": 0}
            module_tests[module]["passed" if status == "PASS" else "failed"] += 1

    # If no per-module data (compact dot format), try to attribute by --filter
    if not module_tests and filter_module:
        passed = result.get("passed", 0)
        failed = result.get("failed", 0)
        if passed or failed:
            module_tests[filter_module] = {"passed": passed, "failed": failed}

    result["by_module"] = module_tests
    return result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--module", help="Run tests for a single module")
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    print(f"Running tests{' for ' + args.module if args.module else ''}...")
    results = run_tests(args.module)
    total = results.get("passed", 0) + results.get("failed", 0)
    print(f"  Passed: {results.get('passed', 0)}, Failed: {results.get('failed', 0)}, Duration: {results.get('duration_seconds', 0)}s")

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "summary": {
            "passed": results.get("passed", 0),
            "failed": results.get("failed", 0),
            "assertions": results.get("assertions", 0),
            "duration_seconds": results.get("duration_seconds", 0),
            "skipped": results.get("skipped", False),
        },
        "by_module": results.get("by_module", {}),
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-tests.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"\nWritten to {out}")


if __name__ == "__main__":
    main()
