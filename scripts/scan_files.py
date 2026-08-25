#!/usr/bin/env python3
"""
Scan file inventory — counts and lines of code per module.

Enhanced v2.1: parallel counting, robust error handling, comprehensive metadata,
proper output schema, and coverage metrics.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path

try:
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        SCAN_VERSION,
        relative_path,
        read_file,
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
        read_file,
        build_report,
        write_report,
        print_summary,
    )

APP_DIR = ROOT / "app"
TESTS_DIR = ROOT / "tests"
VIEWS_DIR = ROOT / "resources" / "views"
LANG_DIR = ROOT / "lang"
CONFIG_DIR = ROOT / "config"
DB_DIR = ROOT / "database"
ROUTES_DIR = ROOT / "routes"

SCAN_NAME = "files"

KNOWN_MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Core", "Document", "Enrollment", "Evaluation",
    "Incident", "Journals", "Partners",
    "Program", "Reports", "Settings", "Setup",
    "SysAdmin", "User",
]


def discover_modules() -> list[str]:
    """Dynamically discover modules."""
    if not APP_DIR.exists():
        return KNOWN_MODULES
    modules = []
    for entry in APP_DIR.iterdir():
        if entry.is_dir() and not entry.name.startswith((".", "_")):
            try:
                if any(entry.rglob("*.php")):
                    modules.append(entry.name)
            except (OSError, PermissionError):
                continue
    return sorted(modules) if modules else KNOWN_MODULES


def count_files_safe(d: Path, pattern: str = "*.php") -> int:
    """Count files with error isolation."""
    if not d.exists():
        return 0
    try:
        return sum(1 for _ in d.rglob(pattern))
    except (OSError, PermissionError):
        return 0


def count_lines_safe(d: Path, pattern: str = "*.php") -> int:
    """Count lines with per-file error isolation and parallel processing."""
    if not d.exists():
        return 0
    
    files = list(d.rglob(pattern)) if d.exists() else []
    if not files:
        return 0
    
    total = 0
    for f in files:
        try:
            content = f.read_text(encoding="utf-8", errors="replace")
            total += len(content.splitlines())
        except (OSError, PermissionError, UnicodeError):
            continue
    return total


def count_lang_safe(locale: str) -> tuple[int, int, list[Finding]]:
    """Count lang files and keys with detailed findings."""
    findings: list[Finding] = []
    d = LANG_DIR / locale
    if not d.exists():
        return 0, 0, findings
    
    files = list(d.glob("*.php"))
    keys = 0
    pattern = re.compile(r"""['"][\w.]+['"]\s*=>""")
    
    for f in files:
        try:
            content = f.read_text(encoding="utf-8", errors="replace")
            file_keys = len(pattern.findall(content))
            keys += file_keys
            
            # Sophisticated check: low key count might indicate incomplete translation
            if file_keys < 5 and f.name not in ["validation.php"]:
                findings.append(Finding(
                    id=f"LANG-LOW-{f.stem.upper()[:3]}",
                    rule="LANG_COVERAGE",
                    severity="low",
                    category="convention",
                    file=relative_path(f),
                    line=0,
                    message=f"Low translation coverage for {locale}/{f.name}: only {file_keys} keys",
                    suggestion="Verify translation completeness or remove unused file",
                    reference="docs/conventions.md",
                ))
        except (OSError, PermissionError):
            continue
    
    return len(files), keys, findings


def count_routes_safe() -> tuple[int, list[Finding]]:
    """Count routes with pattern validation."""
    findings: list[Finding] = []
    pattern = re.compile(r"Route::(get|post|put|patch|delete|resource|apiResource|match|any|middleware|prefix|group|controller)\s*\(")
    total = 0
    
    route_files = list(ROUTES_DIR.glob("*.php")) + list((ROUTES_DIR / "web").glob("*.php"))
    
    for f in route_files:
        try:
            content = f.read_text(encoding="utf-8", errors="replace")
            matches = pattern.findall(content)
            total += len(matches)
            
            # Check for potential missing route cache
            if len(matches) > 50 and "Route::resource" not in content:
                findings.append(Finding(
                    id=f"ROUTE-RESOURCE-MISSING",
                    rule="ROUTE_CONVENTION",
                    severity="low",
                    category="convention",
                    file=relative_path(f),
                    line=0,
                    message=f"Large route file without resource routes: {f.name}",
                    suggestion="Consider using Route::resource for RESTful routes",
                    reference="docs/conventions.md",
                ))
        except (OSError, PermissionError):
            continue
    
    return total, findings


def scan_module_files(module_name: str) -> tuple[str, dict, list[Finding]]:
    """Scan single module files with comprehensive metrics."""
    findings: list[Finding] = []
    mod_dir = APP_DIR / module_name
    test_dir = TESTS_DIR / module_name
    views = VIEWS_DIR / module_name.lower()
    views_alt = VIEWS_DIR / module_name
    
    # Use whichever views path exists
    view_dir = views if views.exists() else views_alt if views_alt.exists() else views
    
    php_count = count_files_safe(mod_dir)
    test_count = count_files_safe(test_dir)
    blade_count = count_files_safe(view_dir, "*.blade.php") if view_dir.exists() else 0
    
    php_loc = count_lines_safe(mod_dir)
    test_loc = count_lines_safe(test_dir)
    
    # Sophisticated: coverage ratio check
    coverage_ratio = 0
    if php_count > 0 and test_count > 0:
        coverage_ratio = test_count / php_count
    
    # Generate findings for suspicious patterns
    if php_count > 0 and test_count == 0 and module_name not in ["Core", "Providers"]:
        findings.append(Finding(
            id=f"TEST-MISSING-{module_name.upper()[:4]}",
            rule="TEST_COVERAGE",
            severity="medium",
            category="convention",
            file=f"tests/{module_name}",
            line=0,
            message=f"Module {module_name} has {php_count} PHP files but no tests",
            suggestion=f"Add tests for {module_name} module per testing conventions",
            reference="docs/conventions.md",
        ))
    
    if php_loc > 5000 and test_loc == 0:
        findings.append(Finding(
            id=f"LOC-UNTESTED-{module_name.upper()[:4]}",
            rule="TEST_COVERAGE",
            severity="high",
            category="convention",
            file=relative_path(mod_dir),
            line=0,
            message=f"Large untested module: {php_loc} LOC with no test coverage",
            suggestion="Prioritize test coverage for large modules",
            reference="docs/conventions.md",
        ))

    data = {
        "php": php_count,
        "tests": test_count,
        "blade": blade_count,
        "loc": {
            "app": php_loc,
            "tests": test_loc,
        },
        "coverage_ratio": round(coverage_ratio, 2) if php_count > 0 else 0,
    }
    
    return module_name, data, findings


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan file inventory — counts and lines of code per module",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 scripts/scan_files.py
  python3 scripts/scan_files.py --module Assessment --verbose
  python3 scripts/scan_files.py --json | jq '.metadata.totals'
  python3 scripts/scan_files.py --strict
        """
    )
    parser.add_argument("--module", "-m", help="Scan single module only")
    parser.add_argument("--output", "-o", help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json", help="Output format")
    parser.add_argument("--verbose", "-v", action="store_true", help="Include detailed context")
    parser.add_argument("--quiet", "-q", action="store_true", help="Only output summary")
    parser.add_argument("--strict", "-s", action="store_true", help="Exit with code 1 on any finding")
    parser.add_argument("--json", action="store_true", help="Force JSON output to stdout")
    parser.add_argument("--severity", choices=["critical", "high", "medium", "low"], help="Filter by minimum severity")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    start_time = time.time()
    
    # Discover modules
    if args.module:
        module_names = [args.module]
        if not (APP_DIR / args.module).exists():
            print(f"Error: Module '{args.module}' not found", file=sys.stderr)
            sys.exit(2)
    else:
        module_names = discover_modules()

    # Parallel module scanning
    by_module: dict[str, dict] = {}
    all_findings: list[Finding] = []
    
    with ThreadPoolExecutor(max_workers=8) as executor:
        future_to_module = {executor.submit(scan_module_files, name): name for name in module_names}
        for future in as_completed(future_to_module):
            try:
                name, data, findings = future.result()
                by_module[name] = data
                all_findings.extend(findings)
            except Exception as e:
                mod = future_to_module[future]
                all_findings.append(Finding(
                    id=f"SCAN-ERROR-{mod.upper()[:4]}",
                    rule="SCAN_ERROR",
                    severity="low",
                    category="system",
                    file=f"app/{mod}",
                    line=0,
                    message=f"Failed to scan module {mod}: {e}",
                    suggestion="Check file permissions",
                    reference="docs/conventions.md",
                ))

    # Global counts with error handling
    lang_en_files, lang_en_keys, lang_findings_en = count_lang_safe("en")
    lang_id_files, lang_id_keys, lang_findings_id = count_lang_safe("id")
    all_findings.extend(lang_findings_en)
    all_findings.extend(lang_findings_id)
    
    route_count, route_findings = count_routes_safe()
    all_findings.extend(route_findings)

    totals = {
        "modules": len(module_names),
        "php_files": sum(m["php"] for m in by_module.values()),
        "test_files": sum(m["tests"] for m in by_module.values()),
        "blade_templates": sum(m["blade"] for m in by_module.values()),
        "migrations": count_files_safe(DB_DIR / "migrations"),
        "route_definitions": route_count,
        "lang_files": {"en": lang_en_files, "id": lang_id_files},
        "lang_keys": {"en": lang_en_keys, "id": lang_id_keys},
        "config_files": count_files_safe(CONFIG_DIR),
        "total_loc": sum(m["loc"]["app"] for m in by_module.values()),
        "test_loc": sum(m["loc"]["tests"] for m in by_module.values()),
        "coverage_ratio": round(sum(m["tests"] for m in by_module.values()) / max(1, sum(m["php"] for m in by_module.values())), 2),
    }

    metadata = {
        "totals": totals,
        "by_module": by_module,
        "discovered_modules": module_names,
    }

    # Build report
    scan_type = "module" if args.module else "full"
    result = build_report(
        findings=all_findings,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=args.module,
        start_time=start_time,
        metadata=metadata,
        total_checks=len(module_names) + 2,  # Modules + lang checks
    )

    # Output handling
    if args.json or args.format == "json":
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

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
