#!/usr/bin/env python3
"""Scan coding conventions — strict_types, Fillable, debug calls, hardcoded strings."""

from __future__ import annotations

import argparse
import json
import re
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
TESTS_DIR = ROOT / "tests"

DEBUG_PATTERN = re.compile(r'\b(dd|dump|ray|var_dump|print_r|die)\s*\(')
FILLABLE_PATTERN = re.compile(r'#\[Fillable')
STRICT_TYPES = "declare(strict_types=1)"
HARDCODED_PATTERN = re.compile(r"""(?<!\w)['"][A-Z][a-zA-Z ]{3,}['"]""")


def scan_strict_types() -> dict:
    total, compliant = 0, 0
    missing = []
    for d in [APP_DIR, TESTS_DIR]:
        for f in d.rglob("*.php"):
            total += 1
            try:
                content = f.read_text(encoding="utf-8", errors="replace")
                if STRICT_TYPES in content:
                    compliant += 1
                else:
                    missing.append(str(f.relative_to(ROOT)))
            except OSError:
                continue
    return {"total": total, "compliant": compliant, "missing": missing[:50]}


def scan_fillable() -> dict:
    total, compliant = 0, 0
    non_compliant = []
    for f in APP_DIR.rglob("Models/*.php"):
        total += 1
        try:
            content = f.read_text(encoding="utf-8", errors="replace")
            if FILLABLE_PATTERN.search(content):
                compliant += 1
            else:
                non_compliant.append(str(f.relative_to(ROOT)))
        except OSError:
            continue
    return {"total": total, "compliant": compliant, "non_compliant": non_compliant}


def scan_debug_calls() -> list[dict]:
    violations = []
    for f in APP_DIR.rglob("*.php"):
        try:
            lines = f.read_text(encoding="utf-8", errors="replace").splitlines()
            for i, line in enumerate(lines, 1):
                if DEBUG_PATTERN.search(line):
                    violations.append({
                        "file": str(f.relative_to(ROOT)),
                        "line": i,
                        "content": line.strip()[:120],
                    })
        except OSError:
            continue
    return violations


def scan_hardcoded_strings() -> list[dict]:
    violations = []
    for f in APP_DIR.rglob("*.blade.php"):
        try:
            lines = f.read_text(encoding="utf-8", errors="replace").splitlines()
            for i, line in enumerate(lines, 1):
                stripped = line.strip()
                if stripped.startswith("//") or stripped.startswith("*"):
                    continue
                matches = HARDCODED_PATTERN.findall(stripped)
                if matches:
                    violations.append({
                        "file": str(f.relative_to(ROOT)),
                        "line": i,
                        "strings": matches[:5],
                    })
        except OSError:
            continue
    return violations[:200]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    print("Scanning conventions...")
    strict = scan_strict_types()
    print(f"  strict_types: {strict['compliant']}/{strict['total']}")

    fillable = scan_fillable()
    print(f"  Fillable: {fillable['compliant']}/{fillable['total']}")

    debug = scan_debug_calls()
    print(f"  debug calls: {len(debug)}")

    hardcoded = scan_hardcoded_strings()
    print(f"  hardcoded strings: {len(hardcoded)}")

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "strict_types": strict,
        "fillable_attribute": fillable,
        "debug_calls": debug,
        "hardcoded_strings": hardcoded,
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-conventions.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"\nWritten to {out}")


if __name__ == "__main__":
    main()
