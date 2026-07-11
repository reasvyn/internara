#!/usr/bin/env python3
"""
scan_naming.py — Naming Convention Compliance
Checks file, class, method, and variable naming conventions.
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
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "naming"
SCAN_VERSION = "1.0.0"

# ─── Naming rules ───────────────────────────────────────────────────────────

# File → Class mapping expectations
FILE_CLASS_RULES = {
    "Actions": {
        "file_pattern": r"^(?:Store|Create|Update|Delete|Destroy|ForceDelete|Restore|Process|Send|Notify|Generate|Export|Import|Approve|Reject|Cancel|Complete|Assign|Unassign|Archive|Verify|Calculate)\w+Action\.php$",
        "class_pattern": r"^(?:Store|Create|Update|Delete|Destroy|ForceDelete|Restore|Process|Send|Notify|Generate|Export|Import|Approve|Reject|Cancel|Complete|Assign|Unassign|Archive|Verify|Calculate)\w+Action$",
        "description": "Actions",
    },
    "Entities": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^readonly\s+class\s+\w+$",
        "skip_files": ["Entity.php", "Exception.php"],
        "description": "Entities",
    },
    "Data": {
        "file_pattern": r"^[A-Z]\w+(?:Data|Request|DTO)\.php$",
        "class_pattern": r"^(?:readonly\s+)?class\s+\w+(?:Data|Request|DTO)$",
        "description": "DTOs",
    },
    "Models": {
        "file_pattern": r"^[A-Z]\w+\.php$",
        "class_pattern": r"^class\s+\w+\s+extends\s+(?:Model|Authenticatable|Pivot)$",
        "skip_files": ["Observer.php", "Policy.php", "Factory.php", "Pivot.php"],
        "description": "Models",
    },
    "Enums": {
        "file_pattern": r"^[A-Z]\w+(?:Enum|Status|Type|State|Role)\.php$",
        "class_pattern": r"^enum\s+\w+",
        "description": "Enums",
    },
    "Livewire": {
        "file_pattern": r"^[A-Z]\w+(?:Form|Table|Page|Modal|Show|Create|Edit|Index|Layout|Widget)\.php$",
        "class_pattern": r"^class\s+\w+(?:Form|Table|Page|Modal|Show|Create|Edit|Index|Layout|Widget)$",
        "description": "Livewire components",
    },
    "Policies": {
        "file_pattern": r"^[A-Z]\w+Policy\.php$",
        "class_pattern": r"^class\s+\w+Policy$",
        "description": "Policies",
    },
    "Events": {
        "file_pattern": r"^[A-Z]\w+(?:Created|Updated|Deleted|Restored|ForceDeleted|Approved|Rejected|Completed|Sent|Generated)\w*Event\.php$",
        "class_pattern": r"^class\s+\w+(?:Created|Updated|Deleted|Restored|ForceDeleted|Approved|Rejected|Completed|Sent|Generated)\w*Event$",
        "description": "Events",
    },
    "Listeners": {
        "file_pattern": r"^[A-Z]\w+(?:Created|Updated|Deleted|Restored|ForceDeleted|Approved|Rejected|Completed|Sent|Generated)\w*Listener\.php$",
        "class_pattern": r"^class\s+\w+(?:Created|Updated|Deleted|Restored|ForceDeleted|Approved|Rejected|Completed|Sent|Generated)\w*Listener$",
        "description": "Listeners",
    },
    "Services": {
        "file_pattern": r"^[A-Z]\w+Service\.php$",
        "class_pattern": r"^class\s+\w+Service$",
        "description": "Services",
    },
}

# Anti-patterns
ANTI_PATTERNS = {
    "handle_method": re.compile(r"public\s+function\s+handle\s*\("),
    "snake_case_var": re.compile(r"\$[a-z]+_[a-z]+"),
    "camel_case_file": re.compile(r"^[a-z][a-zA-Z]+\.php$"),
    "pascal_case_dir": re.compile(r"^[A-Z]"),
    "missing_execute": re.compile(r"Action\.php$"),
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
    scan_version: str
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


def extract_class_name(content: str) -> str | None:
    """Extract class/enum name from PHP file content."""
    patterns = [
        re.compile(r"class\s+(\w+)"),
        re.compile(r"enum\s+(\w+)"),
    ]
    for pattern in patterns:
        match = pattern.search(content)
        if match:
            return match.group(1)
    return None


# ─── File Naming Rules ──────────────────────────────────────────────────────

def scan_file_naming(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []

    for fp in files:
        rel = relative_path(fp)
        filename = fp.name
        content = read_file(fp)
        if not content:
            continue

        # Determine directory context
        parts = Path(rel).parts
        if len(parts) < 3:
            continue
        layer_dir = parts[1]  # e.g., "Actions", "Entities", etc.

        if layer_dir not in FILE_CLASS_RULES:
            continue

        rule = FILE_CLASS_RULES[layer_dir]
        skip_files = rule.get("skip_files", [])

        # Skip excluded files
        if any(filename.endswith(skip) for skip in skip_files):
            continue

        # Check file name matches expected pattern
        file_pattern = rule.get("file_pattern")
        if file_pattern and not re.match(file_pattern, filename):
            findings.append(Finding(
                id=f"NFILE-{len(findings)+1:03d}",
                rule="FILE_NAMING",
                severity="medium",
                category="naming",
                file=rel,
                line=1,
                message=f"{rule['description']} file '{filename}' doesn't match expected pattern",
                suggestion=f"Rename to follow convention: {file_pattern}",
                reference="docs/conventions.md#file-naming-conventions",
            ))

    return findings


# ─── Class Naming Rules ─────────────────────────────────────────────────────

def scan_class_naming(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []

    for fp in files:
        rel = relative_path(fp)
        filename = fp.name
        content = read_file(fp)
        if not content:
            continue

        parts = Path(rel).parts
        if len(parts) < 3:
            continue
        layer_dir = parts[1]

        if layer_dir not in FILE_CLASS_RULES:
            continue

        rule = FILE_CLASS_RULES[layer_dir]
        skip_files = rule.get("skip_files", [])
        if any(filename.endswith(skip) for skip in skip_files):
            continue

        class_name = extract_class_name(content)
        if not class_name:
            continue

        class_pattern = rule.get("class_pattern")
        if class_pattern and not re.search(class_pattern, class_name):
            # Only check the class name line
            class_line = 1
            for i, line in enumerate(content.split("\n"), 1):
                if f"class {class_name}" in line or f"enum {class_name}" in line:
                    class_line = i
                    break

            findings.append(Finding(
                id=f"NCLASS-{len(findings)+1:03d}",
                rule="CLASS_NAMING",
                severity="medium",
                category="naming",
                file=rel,
                line=class_line,
                message=f"Class name '{class_name}' doesn't match expected pattern for {layer_dir}",
                suggestion=f"Rename class to follow convention: {class_pattern}",
                reference="docs/conventions.md#class-naming-conventions",
            ))

    return findings


# ─── Anti-Pattern Detection ────────────────────────────────────────────────

def scan_anti_patterns(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []

    for fp in files:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            continue

        lines = content.split("\n")

        # Check for handle() method in Actions
        if "/Actions/" in rel:
            for i, line in enumerate(lines, 1):
                if ANTI_PATTERNS["handle_method"].search(line):
                    findings.append(Finding(
                        id=f"ANTI-{len(findings)+1:03d}",
                        rule="HANDLE_METHOD",
                        severity="high",
                        category="naming",
                        file=rel,
                        line=i,
                        message="Action uses handle() instead of execute()",
                        suggestion="Rename handle() to execute() — all Actions use execute()",
                        reference="docs/architecture/action-pattern.md#action-triad",
                    ))

        # Check for snake_case variables in PHP
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            if ANTI_PATTERNS["snake_case_var"].search(line):
                # Skip known exceptions: migration patterns, raw SQL, comments
                if "$table" in line or "$column" in line or "$query" in line:
                    continue
                if "/database/migrations/" in rel:
                    continue
                # Find the variable name
                match = ANTI_PATTERNS["snake_case_var"].search(line)
                if match:
                    var_name = match.group(0)
                    # Convert to camelCase suggestion
                    parts = var_name[1:].split("_")
                    camel = parts[0] + "".join(p.capitalize() for p in parts[1:])
                    findings.append(Finding(
                        id=f"ANTI-{len(findings)+1:03d}",
                        rule="SNAKE_CASE_VAR",
                        severity="low",
                        category="naming",
                        file=rel,
                        line=i,
                        message=f"Snake_case variable {var_name} — prefer camelCase in PHP",
                        suggestion=f"Rename to ${camel}",
                        reference="docs/conventions.md#variable-naming-conventions",
                    ))

    return findings


# ─── Directory Naming ──────────────────────────────────────────────────────

def scan_directory_naming(module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    modules_dir = APP_DIR

    if module:
        module_dirs = [modules_dir / module]
    else:
        module_dirs = sorted(
            d for d in modules_dir.iterdir()
            if d.is_dir() and not d.name.startswith(".")
        )

    for module_dir in module_dirs:
        if not module_dir.exists():
            continue
        for subdir in sorted(module_dir.iterdir()):
            if not subdir.is_dir():
                continue
            # All layer directories should be PascalCase
            if not re.match(r"^[A-Z]", subdir.name):
                findings.append(Finding(
                    id=f"NDIR-{len(findings)+1:03d}",
                    rule="DIR_NAMING",
                    severity="low",
                    category="naming",
                    file=f"{module_dir.name}/{subdir.name}/",
                    line=0,
                    message=f"Directory '{subdir.name}' not PascalCase",
                    suggestion="Rename directory to PascalCase (e.g., 'my-dir' → 'MyDir')",
                    reference="docs/conventions.md#directory-naming-conventions",
                ))

    return findings


# ─── Method Naming in Actions ───────────────────────────────────────────────

def scan_method_naming(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    action_files = [f for f in files if "/Actions/" in str(f)]

    for fp in action_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        # Check execute() return type
        execute_match = re.search(
            r"public\s+function\s+execute\s*\([^)]*\)\s*:\s*(\w+)",
            content,
        )
        if execute_match:
            return_type = execute_match.group(1)
            if return_type not in ("ActionResponse", "mixed", "void", "self", "static"):
                findings.append(Finding(
                    id=f"NMETH-{len(findings)+1:03d}",
                    rule="ACTION_RETURN_TYPE",
                    severity="low",
                    category="naming",
                    file=rel,
                    line=content[:execute_match.start()].count("\n") + 1,
                    message=f"Action execute() returns {return_type} — prefer ActionResponse",
                    suggestion="Return ActionResponse for structured feedback",
                    reference="docs/architecture/action-pattern.md#action-response",
                ))

    return findings


# ─── Entity Method Naming ──────────────────────────────────────────────────

def scan_entity_naming(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    entity_files = [f for f in files if "/Entities/" in str(f) and f.name != "Entity.php"]

    for fp in entity_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        # Check for fromModel method
        if "fromModel" not in content:
            findings.append(Finding(
                id=f"NENT-{len(findings)+1:03d}",
                rule="ENTITY_NO_FROM_MODEL",
                severity="medium",
                category="naming",
                file=rel,
                line=1,
                message="Entity missing fromModel() static factory method",
                suggestion="Add: public static function fromModel(Model $model): static",
                reference="docs/architecture/entity-pattern.md",
            ))

        # Check for business question methods (is* should return bool)
        methods = re.finditer(
            r"public\s+function\s+(is\w+)\s*\([^)]*\)\s*(?::\s*(\w+))?",
            content,
        )
        for match in methods:
            method_name = match.group(1)
            return_type = match.group(2)
            if return_type and return_type != "bool":
                findings.append(Finding(
                    id=f"NENT-{len(findings)+1:03d}",
                    rule="ENTITY_QUESTION_RETURN",
                    severity="low",
                    category="naming",
                    file=rel,
                    line=content[:match.start()].count("\n") + 1,
                    message=f"Entity method {method_name}() returns {return_type} — business questions should return bool",
                    suggestion=f"Return bool for {method_name}() — it's a business question",
                    reference="docs/architecture/entity-pattern.md#business-question-methods",
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

    return ScanResult(
        scan_version=SCAN_VERSION,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 5,
            "passed": 5 - len(set(f.rule for f in findings)),
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
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    files = find_php_files(args.module)

    findings: list[Finding] = []
    findings.extend(scan_file_naming(files, args.module))
    findings.extend(scan_class_naming(files, args.module))
    findings.extend(scan_anti_patterns(files, args.module))
    findings.extend(scan_directory_naming(args.module))
    findings.extend(scan_method_naming(files, args.module))
    findings.extend(scan_entity_naming(files, args.module))

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
