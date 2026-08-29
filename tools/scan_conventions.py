#!/usr/bin/env python3
"""
scan_conventions.py — Coding Convention Compliance

Enhanced v2.1: parallel scanning, shared helper integration, robust error handling,
severity filtering, baseline support, and sophisticated hardcoded string detection.

Scans PHP/Blade for D1 strict_types, D4 Fillable attribute, D2 debug calls,
and hardcoded user-facing strings with improved accuracy.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

try:
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        APP_DIR,
        MODULES_DIR,
        SCAN_VERSION,
        relative_path,
        read_file,
        build_report,
        write_report,
        print_summary,
        filter_by_severity,
        filter_by_baseline,
        load_baseline,
    )
except ImportError:
    sys.path.insert(0, str(Path(__file__).parent))
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        APP_DIR,
        MODULES_DIR,
        SCAN_VERSION,
        relative_path,
        read_file,
        build_report,
        write_report,
        print_summary,
        filter_by_severity,
        filter_by_baseline,
        load_baseline,
    )

SCAN_NAME = "conventions"

REF_STRICT = "docs/conventions.md#2-general-php"
REF_FILLABLE = "docs/guides/arch/model-pattern.md#6-fillable-attribute-convention"
REF_DEBUG = "docs/conventions.md#2-general-php"
REF_L10N = "docs/conventions.md#14-localization"

# Pre-compiled patterns for performance (module-level)
RE_STRICT_TYPES = re.compile(r"declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;")
RE_DEBUG_CALLS = re.compile(r"\b(?:dd|dump|ray|var_dump|print_r)\s*\(")
RE_FILLABLE_ATTR = re.compile(r"#\[\s*Fillable.*?\]", re.S)
RE_HARDCODED = re.compile(r"""(?<![A-Za-z])['"]([A-Z][A-Za-z ]{3,})['"]""")
RE_PHP_TAG = re.compile(r"<\?php")
RE_COMMENT_LINE = re.compile(r"^\s*(//|\*|/\*|\{\{--)")
RE_TECHNICAL_ATTR = re.compile(r'^[A-Z][A-Za-z ]*:$')
RE_BLADE_DIRECTIVE = re.compile(r"@\w+")

TECHNICAL_STRINGS = {
    "GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD", "CONNECT", "TRACE",
    "DOMContentLoaded", "Breadcrumb",
    "Arial", "Calibri", "Cambria", "Courier New", "Georgia", "Helvetica Neue",
    "Noto Sans", "Noto Color Emoji", "Segoe UI", "Segoe UI Emoji", "Segoe UI Symbol",
    "Times New Roman", "Trebuchet MS", "Verdana", "Apple Color Emoji", "Roboto",
    "Blade", "Livewire", "PHP", "HTML", "CSS", "JavaScript", "JSON", "XML",
}

STRICT_ALLOWLIST = {
    "config/", "database/", "bootstrap/", "routes/", "resources/views/vendor/",
}

# ─── Helpers ────────────────────────────────────────────────────────────────


def find_php_files(module: str | None = None) -> list[Path]:
    if module:
        module_dir = MODULES_DIR / module if (MODULES_DIR / module).exists() else APP_DIR / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.php"))
    return sorted(APP_DIR.rglob("*.php"))


def find_blade_files(module: str | None = None) -> list[Path]:
    views_dir = ROOT / "resources" / "views"
    if not views_dir.exists():
        return []
    if module:
        # Try both lower and original case
        for cand in [views_dir / module.lower(), views_dir / module]:
            if cand.exists():
                return sorted(cand.rglob("*.blade.php"))
        return []
    return sorted(views_dir.rglob("*.blade.php"))


# ─── D1: strict_types ───────────────────────────────────────────────────────


def scan_strict_types_parallel(files: list[Path]) -> list[Finding]:
    """Parallel scan for missing strict_types with per-file isolation."""
    findings: list[Finding] = []
    
    def check_file(fp: Path) -> list[Finding]:
        rel = relative_path(fp)
        if any(rel.startswith(prefix) for prefix in STRICT_ALLOWLIST):
            return []
        content = read_file(fp)
        if not content or not RE_PHP_TAG.search(content):
            return []
        if not RE_STRICT_TYPES.search(content):
            return [Finding(
                id="D1-000",
                rule="D1",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Missing declare(strict_types=1)",
                suggestion="Add 'declare(strict_types=1);' after <?php opening tag",
                reference=REF_STRICT,
            )]
        return []
    
    with ThreadPoolExecutor(max_workers=8) as executor:
        future_to_path = {executor.submit(check_file, f): f for f in files}
        for future in as_completed(future_to_path):
            try:
                result = future.result()
                findings.extend(result)
            except Exception:
                continue
    
    # Re-id with sequential numbers
    for i, f in enumerate(findings):
        f.id = f"D1-{i+1:03d}"
    return findings


# ─── D4: Fillable attribute ─────────────────────────────────────────────────

def scan_fillable_parallel(files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    model_files = [
        f for f in files
        if "/Models/" in str(f)
        and not f.name.endswith(("Observer.php", "Policy.php", "Factory.php", "Pivot.php"))
    ]
    
    def check_model(fp: Path) -> list[Finding]:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            return []
        if re.search(r"\btrait\s+\w+", content) or re.search(r"abstract\s+(?:final\s+)?class", content):
            return []
        if re.search(r"extends\s+Pivot\b", content):
            return []
        if re.search(r"extends\s+(?!Model|Authenticatable|BaseModel|BaseAuthenticatable|Pivot)\w+", content):
            return []
        if not RE_FILLABLE_ATTR.search(content):
            # Double-check it's actually a model class
            if not re.search(r"\bclass\s+\w+", content):
                return []
            return [Finding(
                id="D4-000",
                rule="D4",
                severity="high",
                category="convention",
                file=rel,
                line=1,
                message="Model missing #[Fillable] attribute",
                suggestion="Add '#[Fillable([...])]' attribute listing allowed mass-assignment columns",
                reference=REF_FILLABLE,
            )]
        return []
    
    with ThreadPoolExecutor(max_workers=8) as executor:
        future_to_path = {executor.submit(check_model, f): f for f in model_files}
        for future in as_completed(future_to_path):
            try:
                findings.extend(future.result())
            except Exception:
                continue
    
    for i, f in enumerate(findings):
        f.id = f"D4-{i+1:03d}"
    return findings


# ─── D2: debug calls ────────────────────────────────────────────────────────


def scan_debug_calls_parallel(files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    
    def check_debug(fp: Path) -> list[Finding]:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            return []
        local_findings = []
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith(("//", "*")) or "/*" in line:
                continue
            # Skip commented lines and blade comments
            if "{{--" in line or "@php" in stripped:
                continue
            m = RE_DEBUG_CALLS.search(line)
            if m:
                # Verify not in string/comment context
                before = line[:m.start()]
                # Simple heuristic: if odd number of quotes before, it's inside string
                if before.count('"') % 2 == 1 or before.count("'") % 2 == 1:
                    continue
                local_findings.append(Finding(
                    id="D2-000",
                    rule="D2",
                    severity="high",
                    category="convention",
                    file=rel,
                    line=i,
                    message=f"Debug call {m.group(0).strip()} left in code",
                    suggestion="Remove debug call before committing",
                    reference=REF_DEBUG,
                ))
        return local_findings
    
    with ThreadPoolExecutor(max_workers=8) as executor:
        future_to_path = {executor.submit(check_debug, f): f for f in files}
        for future in as_completed(future_to_path):
            try:
                findings.extend(future.result())
            except Exception:
                continue
    
    for i, f in enumerate(findings):
        f.id = f"D2-{i+1:03d}"
    return findings


# ─── Hardcoded user strings ─────────────────────────────────────────────────


def scan_hardcoded_strings_parallel(blade_files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    
    def check_blade(fp: Path) -> list[Finding]:
        rel = relative_path(fp)
        if "views/vendor/" in rel:
            return []
        content = read_file(fp)
        if not content:
            return []
        local = []
        lines = content.split("\n")
        in_block = False
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith(("//", "*", "{{--")):
                continue
            # Skip CSS/JS blocks — their content is technical, not user-facing
            if "<style" in stripped or "<script" in stripped:
                in_block = True
            if in_block:
                if "</style" in stripped or "</script" in stripped:
                    in_block = False
                continue
            for m in RE_HARDCODED.finditer(stripped):
                string_val = m.group(1)
                # Skip HTML attributes, icon names, data-tags, and technical tokens
                if re.match(r"^[A-Z][A-Za-z ]*:$", string_val):
                    continue
                if string_val in ("Blade", "Livewire", "PHP", "HTML", "CSS", "JavaScript"):
                    continue
                if string_val in TECHNICAL_STRINGS:
                    continue
                local.append(Finding(
                    id="L10N-000",
                    rule="HARDCODED_STRING",
                    severity="low",
                    category="convention",
                    file=rel,
                    line=i,
                    message=f"Possible hardcoded user-facing string: '{string_val}'",
                    suggestion="Replace with __('key') translation string",
                    reference=REF_L10N,
                ))
        return local
    
    with ThreadPoolExecutor(max_workers=8) as executor:
        future_to_path = {executor.submit(check_blade, f): f for f in blade_files}
        for future in as_completed(future_to_path):
            try:
                findings.extend(future.result())
            except Exception:
                continue
    
    for i, f in enumerate(findings):
        f.id = f"L10N-{i+1:03d}"
    return findings


# ─── CLI ────────────────────────────────────────────────────────────────────


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan coding conventions — strict_types, Fillable, debug calls",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 tools/scan_conventions.py
  python3 tools/scan_conventions.py --module Assessment --verbose
  python3 tools/scan_conventions.py --json | jq '.findings[] | select(.severity=="high")'
  python3 tools/scan_conventions.py --strict --severity high
        """
    )
    parser.add_argument("--module", "-m", help="Target specific module")
    parser.add_argument("--output", "-o", type=Path, help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json")
    parser.add_argument("--verbose", "-v", action="store_true")
    parser.add_argument("--quiet", "-q", action="store_true")
    parser.add_argument("--strict", "-s", action="store_true")
    parser.add_argument("--json", action="store_true")
    parser.add_argument("--severity", choices=["critical", "high", "medium", "low"], help="Filter by minimum severity")
    parser.add_argument("--baseline", type=Path, help="Baseline file to ignore known findings")
    return parser.parse_args()


def main() -> None:
    import time
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    php_files = find_php_files(args.module)
    blade_files = find_blade_files(args.module)

    # Parallel scanning across categories
    findings: list[Finding] = []
    findings.extend(scan_strict_types_parallel(php_files))
    findings.extend(scan_fillable_parallel(php_files))
    findings.extend(scan_debug_calls_parallel(php_files))
    findings.extend(scan_hardcoded_strings_parallel(blade_files))

    # Filtering
    if args.severity:
        findings = filter_by_severity(findings, args.severity)
    if args.baseline:
        baseline = load_baseline(args.baseline)
        findings = filter_by_baseline(findings, baseline)

    # Build report with proper schema
    metadata = {
        "total_php_files": len(php_files),
        "total_blade_files": len(blade_files),
        "scanned_modules": [args.module] if args.module else [],
    }
    
    # Sophisticated summary: total_checks = 4 categories, passed = categories with 0 findings
    rules = set(f.rule for f in findings)
    total_checks = 4
    passed = total_checks - len(rules)
    
    # Use build_report for consistency but override summary for conventions-specific logic
    result = build_report(
        findings=findings,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=args.module,
        start_time=start_time,
        metadata=metadata,
        total_checks=total_checks,
    )
    # Fix summary for conventions (categories-based, not finding-count-based)
    result.summary["total_checks"] = total_checks
    result.summary["passed"] = passed

    # Output handling per spec
    if args.json or args.format == "json":
        print(json.dumps(result.__dict__, indent=2, ensure_ascii=False))
    elif not args.quiet:
        from _common import print_summary as _ps
        _ps(result, verbose=args.verbose)

    output_path = args.output
    written_path = write_report(result, output_path)
    if not args.quiet and not args.json:
        print(f"Report saved: {relative_path(written_path)}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
