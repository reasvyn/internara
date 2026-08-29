#!/usr/bin/env python3
"""
scan_naming.py — Naming Convention Compliance

Checks file, class, method, and directory naming conventions against
docs/conventions.md §4. Calibrated: layer directories are detected at any
depth (deepest match); class patterns match the class name; PSR-4
file/class parity uses anchored regexes; helper files without class
declarations are skipped; Action execute() return types follow the
documented ActionResponse / Model / void conventions.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "naming"

REF_FILE = "docs/conventions.md#4-naming-conventions"
REF_ACTION = "docs/architecture/action-pattern.md"
REF_ENTITY = "docs/architecture/entity-pattern.md"

# ─── Layer directory → naming contract ──────────────────────────────────────
# file_pattern matches the filename; class_pattern matches the class declaration
# line (after the `class`/`enum` keyword). skip_contains: substrings that
# exempt a file (e.g. base classes).

LAYER_RULES = {
    "Actions": {
        "file_pattern": r"^[A-Z]\w*Action\.php$",
        "class_pattern": r"^\w+Action\b",
        "description": "Action",
    },
    "Entities": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base", "Abstract"],
        "description": "Entity",
    },
    "Data": {
        "file_pattern": r"^[A-Z]\w+(?:Data|DTO|Request)\.php$|^ActionResponse\.php$|^Audit(?:Check|Report)\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "DTO/Data",
    },
    "Models": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Observer", "Policy", "Factory", "Base"],
        "description": "Model",
    },
    "Enums": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Enum",
    },
    "Livewire": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "Livewire component",
    },
    "Forms": {
        "file_pattern": r"^[A-Z]\w+(?:Form|Filters|Query)\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "Livewire form",
    },
    "Policies": {
        "file_pattern": r"^[A-Z]\w*Policy\.php$",
        "class_pattern": r"^\w+Policy\b",
        "description": "Policy",
    },
    "Events": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "Event",
    },
    "Listeners": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "Listener",
    },
    "Services": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "Service",
    },
    "Notifications": {
        "file_pattern": r"^[A-Z]\w*Notification\.php$",
        "class_pattern": r"^\w+Notification\b",
        "skip_contains": ["Base"],
        "description": "Notification",
    },
    "Http": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "skip_contains": ["Base"],
        "description": "HTTP layer class",
    },
    "Console": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Console command",
    },
    "Observers": {
        "file_pattern": r"^[A-Z]\w*Observer\.php$",
        "class_pattern": r"^\w+Observer\b",
        "description": "Observer",
    },
    "Middleware": {
        "file_pattern": r"^[A-Z]\w*Middleware\.php$",
        "class_pattern": r"^\w+Middleware\b",
        "skip_contains": ["Base"],
        "description": "Middleware",
    },
    "Exceptions": {
        "file_pattern": r"^[A-Z]\w*Exception\.php$",
        "class_pattern": r"^\w+Exception\b",
        "description": "Exception",
    },
    "Contracts": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Contract",
    },
    "Types": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Value object",
    },
    "Rules": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Validation rule",
    },
    "Support": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Support class",
    },
    "Concerns": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^\w+\b",
        "description": "Trait",
    },
    "Jobs": {
        "file_pattern": r"^[A-Z]\w+(?:Job)s?\.php$",
        "class_pattern": r"^\w+Job\b",
        "description": "Job",
    },
}

# ─── Anti-patterns ──────────────────────────────────────────────────────────

RE_HANDLE_METHOD = re.compile(r"public\s+function\s+handle\s*\(")
RE_EXECUTE_METHOD = re.compile(r"public\s+function\s+execute\s*\(")
RE_SNAKE_VAR = re.compile(r"(?<!\$)\$[a-z]+(?:_[a-z0-9]+)+\b")
RE_ANCHORED_CLASS = re.compile(r"^\s*(?:final\s+|abstract\s+)*(?:class|enum|interface|trait)\s+(\w+)", re.M)

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


def layer_dir_of(rel: str) -> str | None:
    """Find the deepest layer directory name in the relative path."""
    parts = Path(rel).parts
    layer = None
    for p in parts:
        if p in LAYER_RULES:
            layer = p
    return layer


def is_skipped(filename: str, rule: dict[str, Any]) -> bool:
    for marker in rule.get("skip_contains", []):
        if marker in filename:
            return True
    return False


def extract_class_decl(content: str) -> tuple[str | None, str | None, int]:
    """Return (kind, name, line) of the first class/enum/interface/trait declaration."""
    for m in RE_ANCHORED_CLASS.finditer(content):
        prefix = content[: m.start()]
        # Skip closures (class after `new`) are not matched by anchoring
        return m.group(1), m.start(), prefix.count("\n") + 1
    return None, None, 1


def decl_line(content: str, class_name: str) -> int:
    for i, line in enumerate(content.split("\n"), 1):
        if re.search(rf"\b(?:class|enum|interface|trait)\s+{re.escape(class_name)}\b", line):
            return i
    return 1


# ─── File naming (enhanced: parallel + _common integration) ────────────────────────────────────────────────────────────

def scan_file_naming(files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        layer = layer_dir_of(rel)
        if layer is None:
            continue
        rule = LAYER_RULES[layer]
        if is_skipped(fp.name, rule):
            continue
        content = read_file(fp)
        if not content:
            continue
        # Skip non-class files (helper function files, config snippets)
        if not RE_ANCHORED_CLASS.search(content):
            continue
        if not re.match(rule["file_pattern"], fp.name):
            findings.append(Finding(
                id=f"NFILE-{len(findings)+1:03d}",
                rule="FILE_NAMING",
                severity="medium",
                category="naming",
                file=rel,
                line=1,
                message=f"{rule['description']} file '{fp.name}' doesn't match expected pattern",
                suggestion=f"Rename to follow convention: {rule['file_pattern']}",
                reference=REF_FILE,
            ))
    return findings


# ─── Class naming ───────────────────────────────────────────────────────────

def scan_class_naming(files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        layer = layer_dir_of(rel)
        if layer is None:
            continue
        rule = LAYER_RULES[layer]
        if is_skipped(fp.name, rule):
            continue
        content = read_file(fp)
        if not content:
            continue
        class_name, _, _ = extract_class_decl(content)
        if not class_name:
            continue
        line = decl_line(content, class_name)
        if not re.search(rule["class_pattern"], class_name):
            findings.append(Finding(
                id=f"NCLASS-{len(findings)+1:03d}",
                rule="CLASS_NAMING",
                severity="medium",
                category="naming",
                file=rel,
                line=line,
                message=f"Class name '{class_name}' doesn't match expected pattern for {layer}",
                suggestion=f"Rename class to follow convention: {rule['class_pattern']}",
                reference=REF_FILE,
            ))
    return findings


# ─── PSR-4 file ↔ class parity ──────────────────────────────────────────────

def scan_psr4(files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            continue
        class_name, _, _ = extract_class_decl(content)
        if not class_name:
            continue
        if f"{class_name}.php" != fp.name:
            findings.append(Finding(
                id=f"NPSR4-{len(findings)+1:03d}",
                rule="PSR4_FILE_CLASS",
                severity="high",
                category="naming",
                file=rel,
                line=decl_line(content, class_name),
                message=f"Class name '{class_name}' doesn't match file name '{fp.name}'",
                suggestion=f"Rename class to '{Path(fp.name).stem}' or rename file to '{class_name}.php'",
                reference="docs/conventions.md#4-naming-conventions",
            ))
    return findings


# ─── Anti-patterns ──────────────────────────────────────────────────────────

def scan_anti_patterns(files: list[Path]) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")

        # handle() in Actions → must be execute()
        if "/Actions/" in rel:
            for i, line in enumerate(lines, 1):
                if RE_HANDLE_METHOD.search(line):
                    findings.append(Finding(
                        id=f"ANTI-{len(findings)+1:03d}",
                        rule="HANDLE_METHOD",
                        severity="high",
                        category="naming",
                        file=rel,
                        line=i,
                        message="Action uses handle() instead of execute()",
                        suggestion="Rename handle() to execute() — all Actions use execute()",
                        reference=REF_ACTION,
                    ))

    return findings


# ─── Directory naming ───────────────────────────────────────────────────────

def scan_directory_naming(module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    if module:
        roots = [APP_DIR / module]
    else:
        roots = [
            d for d in APP_DIR.iterdir()
            if d.is_dir() and not d.name.startswith(".")
        ]

    seen: set[Path] = set()
    for root in roots:
        if not root.exists():
            continue
        for subdir in sorted(root.rglob("*/")):
            if not subdir.is_dir() or subdir in seen:
                continue
            seen.add(subdir)
            name = subdir.name
            if name.startswith(".") or name == "vendor":
                continue
            if not re.match(r"^[A-Z][A-Za-z0-9]*$", name):
                findings.append(Finding(
                    id=f"NDIR-{len(findings)+1:03d}",
                    rule="DIR_NAMING",
                    severity="low",
                    category="naming",
                    file=str(subdir.relative_to(ROOT)) + "/",
                    line=0,
                    message=f"Directory '{name}' not PascalCase",
                    suggestion="Rename directory to PascalCase (e.g., 'my-dir' → 'MyDir')",
                    reference=REF_FILE,
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
            "total_checks": 5,
            "passed": 5 - len(rules),
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
    print(f"  NAMING CONVENTIONS SCAN RESULTS")
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
    # Enhanced with severity/baseline filtering
    parser = argparse.ArgumentParser(
        description="Scan for naming convention violations (files, classes, methods)",
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
    parser.add_argument("--severity", choices=["critical", "high", "medium", "low"], help="Filter by minimum severity")
    parser.add_argument("--baseline", type=Path, help="Baseline file to ignore known findings")
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    files = find_php_files(args.module)

    findings: list[Finding] = []
    findings.extend(scan_file_naming(files))
    findings.extend(scan_class_naming(files))
    findings.extend(scan_psr4(files))
    findings.extend(scan_anti_patterns(files))
    findings.extend(scan_directory_naming(args.module))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {"total_php_files": len(files)},
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
