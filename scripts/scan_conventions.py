#!/usr/bin/env python3
"""
scan_conventions.py — Coding Convention Compliance
Scans PHP/Blade for D1 strict_types, D4 Fillable attribute, D2 debug calls,
and hardcoded user-facing strings.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
TESTS_DIR = ROOT / "tests"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "conventions"

REF_STRICT = "docs/conventions.md#2-general-php"
REF_FILLABLE = "docs/architecture/model-pattern.md#6-fillable-attribute-convention"
REF_DEBUG = "docs/conventions.md#2-general-php"
REF_L10N = "docs/conventions.md#14-localization"

RE_STRICT_TYPES = re.compile(r"declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;")
RE_DEBUG_CALLS = re.compile(
    r"\b(?:dd|dump|ray|var_dump|print_r)\s*\("
)
RE_FILLABLE_ATTR = re.compile(r"#\[\s*Fillable.*?\]", re.S)
RE_HARDCODED = re.compile(r"""(?<![A-Za-z])['"]([A-Z][A-Za-z ]{3,})['"]""")

# Technical tokens that are not user-facing display text (fonts, HTTP verbs,
# JS events, CSS keywords). Skipped by the hardcoded-string scan.
TECHNICAL_STRINGS = {
    "GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS", "HEAD", "CONNECT", "TRACE",
    "DOMContentLoaded", "Breadcrumb",
    "Arial", "Calibri", "Cambria", "Courier New", "Georgia", "Helvetica Neue",
    "Noto Sans", "Noto Color Emoji", "Segoe UI", "Segoe UI Emoji", "Segoe UI Symbol",
    "Times New Roman", "Trebuchet MS", "Verdana", "Apple Color Emoji", "Roboto",
}

# D1: files that must declare strict_types (all PHP except a small allowlist)
STRICT_ALLOWLIST = {
    "config/", "database/", "bootstrap/", "routes/", "resources/views/vendor/",
}

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


@dataclass
class ScanResult:
    scan_name: str
    scan_type: str
    module: str | None
    timestamp: str
    execution_time_ms: int
    summary: dict[str, Any]
    findings: list[dict[str, Any]]
    metadata: dict[str, Any]


# ─── Helpers ────────────────────────────────────────────────────────────────

def find_php_files(module: str | None = None) -> list[Path]:
    if module:
        module_dir = APP_DIR / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.php"))
    return sorted(APP_DIR.rglob("*.php"))


def find_blade_files(module: str | None = None) -> list[Path]:
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
    try:
        return path.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def relative_path(path: Path) -> str:
    try:
        return str(path.relative_to(ROOT))
    except ValueError:
        return str(path)


# ─── D1: strict_types ───────────────────────────────────────────────────────

def scan_strict_types(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        if any(rel.startswith(prefix) for prefix in STRICT_ALLOWLIST):
            continue
        content = read_file(fp)
        if not content:
            continue
        if not RE_STRICT_TYPES.search(content):
            findings.append(Finding(
                id=f"D1-{len(findings)+1:03d}",
                rule="D1",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Missing declare(strict_types=1)",
                suggestion="Add 'declare(strict_types=1);' at the top of the file",
                reference=REF_STRICT,
            ))
    return findings


# ─── D4: Fillable attribute ─────────────────────────────────────────────────

def scan_fillable(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    model_files = [
        f for f in files
        if "/Models/" in str(f)
        and not f.name.endswith(("Observer.php", "Policy.php", "Factory.php", "Pivot.php"))
    ]
    for fp in model_files:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            continue
        # Skip non-class files (traits/concerns) and abstract base models
        if re.search(r"\btrait\s+\w+", content) or re.search(r"abstract\s+(?:final\s+)?class", content):
            continue
        if re.search(r"extends\s+Pivot\b", content):
            continue
        # Skip vendor-model extensions (e.g. Spatie Activity)
        if re.search(r"extends\s+(?!Model|Authenticatable|BaseModel|BaseAuthenticatable|Pivot)\w+", content):
            continue
        if not RE_FILLABLE_ATTR.search(content):
            findings.append(Finding(
                id=f"D4-{len(findings)+1:03d}",
                rule="D4",
                severity="high",
                category="convention",
                file=rel,
                line=1,
                message="Model missing #[Fillable] attribute",
                suggestion="Add '#[Fillable]' attribute listing allowed mass-assignment columns",
                reference=REF_FILLABLE,
            ))
    return findings


# ─── D2: debug calls ────────────────────────────────────────────────────────

def scan_debug_calls(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith(("//", "*")) or "/*" in line:
                continue
            m = RE_DEBUG_CALLS.search(line)
            if m:
                findings.append(Finding(
                    id=f"D2-{len(findings)+1:03d}",
                    rule="D2",
                    severity="high",
                    category="convention",
                    file=rel,
                    line=i,
                    message=f"Debug call {m.group(0)} left in code",
                    suggestion="Remove debug call before committing",
                    reference=REF_DEBUG,
                ))
    return findings


# ─── Hardcoded user strings ─────────────────────────────────────────────────

def scan_hardcoded_strings(blade_files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in blade_files:
        rel = relative_path(fp)
        if "views/vendor/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
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
                findings.append(Finding(
                    id=f"L10N-{len(findings)+1:03d}",
                    rule="HARDCODED_STRING",
                    severity="low",
                    category="convention",
                    file=rel,
                    line=i,
                    message=f"Possible hardcoded user-facing string: '{string_val}'",
                    suggestion="Replace with __('key') translation string",
                    reference=REF_L10N,
                ))
    return findings


# ─── Report ─────────────────────────────────────────────────────────────────

def build_report(
    findings: list[Finding],
    scan_type: str,
    module: str | None,
    start_time: float,
    metadata: dict[str, Any],
) -> ScanResult:
    elapsed_ms = int((time.time() - start_time) * 1000)
    by_severity: dict[str, int] = {"critical": 0, "high": 0, "medium": 0, "low": 0}
    for f in findings:
        by_severity[f.severity] = by_severity.get(f.severity, 0) + 1

    rules = set(f.rule for f in findings)
    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 4,
            "passed": 4 - len(rules),
            "failed": len(findings),
            "by_severity": by_severity,
        },
        findings=[vars(f) for f in findings],
        metadata=metadata,
    )


def write_report(result: ScanResult, output_path: Path | None = None) -> Path:
    if output_path is None:
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
        output_path = OUTPUT_DIR / f"{timestamp}-{SCAN_NAME}.json"
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(
        json.dumps(vars(result), indent=2, ensure_ascii=False), encoding="utf-8"
    )
    return output_path


def print_summary(result: ScanResult) -> None:
    s = result.summary
    bs = s["by_severity"]
    print(f"\n{'='*60}")
    print(f"  CONVENTIONS SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Categories checked: {s['total_checks']}")
    print(f"  Categories passed:  {s['passed']}")
    print(f"  Findings:           {s['failed']}")
    print(f"    Critical: {bs.get('critical', 0)}")
    print(f"    High:     {bs.get('high', 0)}")
    print(f"    Medium:   {bs.get('medium', 0)}")
    print(f"    Low:      {bs.get('low', 0)}")
    print(f"  Time: {result.execution_time_ms}ms")
    print(f"{'='*60}\n")


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan coding conventions — strict_types, Fillable, debug calls",
    )
    parser.add_argument("--module", "-m", help="Target specific module")
    parser.add_argument("--output", "-o", type=Path, help="Output file path")
    parser.add_argument(
        "--format", "-f", choices=["json", "text", "summary"], default="json"
    )
    parser.add_argument("--verbose", "-v", action="store_true")
    parser.add_argument("--quiet", "-q", action="store_true")
    parser.add_argument("--strict", "-s", action="store_true")
    parser.add_argument("--json", action="store_true")
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    php_files = find_php_files(args.module)
    blade_files = find_blade_files(args.module)

    findings: list[Finding] = []
    findings.extend(scan_strict_types(php_files, args.module))
    findings.extend(scan_fillable(php_files, args.module))
    findings.extend(scan_debug_calls(php_files, args.module))
    findings.extend(scan_hardcoded_strings(blade_files, args.module))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {"total_php_files": len(php_files), "total_blade_files": len(blade_files)},
    )

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
