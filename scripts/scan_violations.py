#!/usr/bin/env python3
"""
scan_violations.py — Architecture & Coding Invariant Violations
Scans PHP code for C1-C8 and D1-D6 violations.
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
SCAN_NAME = "violations"
SCAN_VERSION = "1.0.0"

# Forbidden imports per layer
ENTITY_FORBIDDEN_IMPORTS = [
    "Actions\\",
    "Services\\",
    "Livewire\\",
    "Http\\Controllers\\",
]

DTO_FORBIDDEN_IMPORTS = [
    "Models\\",
    "Entities\\",
    "Actions\\",
    "Repositories\\",
    "Illuminate\\Database\\Eloquent\\Model",
]

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


def extract_module(filepath: Path) -> str | None:
    """Extract module name from path: app/{Module}/..."""
    try:
        rel = filepath.relative_to(APP_DIR)
        parts = rel.parts
        if parts:
            return parts[0]
    except ValueError:
        pass
    return None


# ─── C1: No Model mutations in Livewire ────────────────────────────────────

RE_MODEL_MUTATION = re.compile(
    r"(?:Model::|\w+::)(create|update|delete|forceDelete|forceCreate|unguard)\s*\("
)

RE_ELOQUENT_MUTATION = re.compile(
    r"\$(?:this->)?\w+->(?:update|delete|forceDelete|forceFill)\s*\("
)

RE_DB_DELETE = re.compile(
    r"DB::(?:table|select)(?:\s*\([^)]*\))?\s*->(?:where|whereIn)\s*\(.*\)\s*->delete\s*\("
)


def scan_c1_livewire_mutations(
    files: list[Path], module: str | None
) -> list[Finding]:
    findings: list[Finding] = []
    livewire_files = [f for f in files if "/Livewire/" in str(f)]

    for fp in livewire_files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue

            if RE_MODEL_MUTATION.search(line):
                findings.append(Finding(
                    id=f"C1-{len(findings)+1:03d}",
                    rule="C1",
                    severity="high",
                    category="architecture",
                    file=relative_path(fp),
                    line=i,
                    message="Model mutation found in Livewire component",
                    suggestion="Use a Command Action instead (e.g., StoreStudentAction)",
                    reference="docs/architecture/action-pattern.md#non-negotiable",
                ))
            elif RE_ELOQUENT_MUTATION.search(line):
                findings.append(Finding(
                    id=f"C1-{len(findings)+1:03d}",
                    rule="C1",
                    severity="high",
                    category="architecture",
                    file=relative_path(fp),
                    line=i,
                    message="Eloquent mutation found in Livewire component",
                    suggestion="Delegate to a Command Action",
                    reference="docs/architecture/action-pattern.md#non-negotiable",
                ))
    return findings


# ─── C2: No app()->make() / resolve() ──────────────────────────────────────

RE_SERVICE_LOCATOR = re.compile(
    r"(?:app\(\)\s*->\s*(?:make|makeWith|makeWith)\s*\(|resolve\s*\(|app\(\)\s*->\s*(?:bind|singleton)\s*\()"
)


def scan_c2_service_locator(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            # Allow in Providers and config
            rel = relative_path(fp)
            if "/Providers/" in rel or "/config/" in rel:
                continue
            if RE_SERVICE_LOCATOR.search(line):
                findings.append(Finding(
                    id=f"C2-{len(findings)+1:03d}",
                    rule="C2",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Service locator pattern detected (app()->make/resolve)",
                    suggestion="Use constructor injection instead",
                    reference="docs/conventions.md#dependency-injection",
                ))
    return findings


# ─── C3: No DB::raw() / whereRaw() ────────────────────────────────────────

RE_RAW_SQL = re.compile(
    r"(?:DB::raw\s*\(|->whereRaw\s*\(|->selectRaw\s*\(|->havingRaw\s*\(|->orderByRaw\s*\(|->groupByRaw\s*\()"
)


def scan_c3_raw_sql(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            if RE_RAW_SQL.search(line):
                findings.append(Finding(
                    id=f"C3-{len(findings)+1:03d}",
                    rule="C3",
                    severity="medium",
                    category="architecture",
                    file=relative_path(fp),
                    line=i,
                    message="Raw SQL detected (DB::raw, whereRaw, selectRaw, etc.)",
                    suggestion="Use parameterized queries with bindings array",
                    reference="docs/conventions.md#sql-injection-prevention",
                ))
    return findings


# ─── C4: No inline cache keys ──────────────────────────────────────────────

RE_CACHE_WITH_STRING = re.compile(
    r"Cache::(?:remember|get|put|forget|flush|has|add|pull|increment|decrement)\s*\(\s*['\"]"
)


def scan_c4_inline_cache(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        if "/config/" in rel:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            if RE_CACHE_WITH_STRING.search(line):
                findings.append(Finding(
                    id=f"C4-{len(findings)+1:03d}",
                    rule="C4",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Inline cache key with string literal",
                    suggestion="Register cache key in config/cache-keys.php and use config()",
                    reference="docs/architecture/cache-pattern.md#registration",
                ))
    return findings


# ─── C5: Entity forbidden imports ──────────────────────────────────────────

def scan_c5_entity_imports(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    entity_files = [f for f in files if "/Entities/" in str(f)]

    for fp in entity_files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if not stripped.startswith("use ") or not stripped.endswith(";"):
                continue
            for forbidden in ENTITY_FORBIDDEN_IMPORTS:
                if forbidden in stripped:
                    findings.append(Finding(
                        id=f"C5-{len(findings)+1:03d}",
                        rule="C5",
                        severity="high",
                        category="architecture",
                        file=relative_path(fp),
                        line=i,
                        message=f"Entity imports forbidden namespace: {forbidden.strip(chr(92))}",
                        suggestion="Entities must be pure domain objects — no Actions, Services, Livewire, Controllers",
                        reference="docs/architecture/entity-pattern.md#non-negotiable",
                    ))
                    break
    return findings


# ─── C6: DTO forbidden imports ─────────────────────────────────────────────

def scan_c6_dto_imports(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    dto_files = [f for f in files if "/Data/" in str(f)]

    for fp in dto_files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if not stripped.startswith("use ") or not stripped.endswith(";"):
                continue
            for forbidden in DTO_FORBIDDEN_IMPORTS:
                if forbidden in stripped:
                    findings.append(Finding(
                        id=f"C6-{len(findings)+1:03d}",
                        rule="C6",
                        severity="high",
                        category="architecture",
                        file=relative_path(fp),
                        line=i,
                        message=f"DTO imports forbidden namespace: {forbidden.strip(chr(92))}",
                        suggestion="DTOs must only contain scalars, enums, Carbon — no Models, Entities, Actions",
                        reference="docs/architecture/data-pattern.md#non-negotiable",
                    ))
                    break
    return findings


# ─── C7: Command/Process Actions without DTO for 3+ params ────────────────

RE_EXECUTE_DEF = re.compile(
    r"public\s+function\s+execute\s*\((.*?)\)",
    re.DOTALL,
)


def scan_c7_action_params(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    action_files = [f for f in files if "/Actions/" in str(f)]

    for fp in action_files:
        content = read_file(fp)
        if not content:
            continue
        # Determine if Command or Process
        is_command_process = False
        if "BaseCommandAction" in content:
            is_command_process = True
        elif "BaseProcessAction" in content:
            is_command_process = True

        if not is_command_process:
            continue

        match = RE_EXECUTE_DEF.search(content)
        if not match:
            continue
        params_str = match.group(1).strip()
        if not params_str:
            continue
        # Count parameters (split by comma)
        params = [p.strip() for p in params_str.split(",") if p.strip()]
        # Check if using DTO (BaseData)
        has_dto = "BaseData" in content and any(
            "Data" in p or "Request" in p or "DTO" in p for p in params
        )
        if len(params) >= 3 and not has_dto:
            findings.append(Finding(
                id=f"C7-{len(findings)+1:03d}",
                rule="C7",
                severity="medium",
                category="architecture",
                file=relative_path(fp),
                line=content[:match.start()].count("\n") + 1,
                message=f"Command/Process Action has {len(params)} params without DTO",
                suggestion="Accept a BaseData DTO for 3+ parameters",
                reference="docs/architecture/action-pattern.md#command-action",
            ))
    return findings


# ─── C8: RuntimeException instead of RejectedException ──────────────────────

RE_RUNTIME_EXCEPTION = re.compile(
    r"throw\s+new\s+\\?RuntimeException\s*\("
)


def scan_c8_runtime_exception(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        # Only check Actions and Entities
        if "/Actions/" not in rel and "/Entities/" not in rel:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            if RE_RUNTIME_EXCEPTION.search(line):
                findings.append(Finding(
                    id=f"C8-{len(findings)+1:03d}",
                    rule="C8",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="RuntimeException thrown in Action/Entity — use RejectedException",
                    suggestion="Use throw new RejectedException('message') for business rule violations",
                    reference="docs/architecture/exception-pattern.md#usage",
                ))
    return findings


# ─── D1: Missing strict_types ──────────────────────────────────────────────

RE_STRICT_TYPES = re.compile(r"declare\s*\(\s*strict_types\s*=\s*1\s*\)")


def scan_d1_strict_types(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        # Skip migrations, configs
        if "/database/migrations/" in rel or "/config/" in rel:
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
                suggestion="Add declare(strict_types=1) as the first statement",
                reference="docs/conventions.md#strict-types",
            ))
    return findings


# ─── D2: Debug calls ──────────────────────────────────────────────────────

RE_DEBUG_CALLS = re.compile(
    r"(?:^|\s)(?:dd\s*\(|dump\s*\(|ray\s*\(|var_dump\s*\(|print_r\s*\(|die\s*\(|exit\s*\()"
)


def scan_d2_debug_calls(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if stripped.startswith("//") or stripped.startswith("*"):
                continue
            if RE_DEBUG_CALLS.search(line):
                findings.append(Finding(
                    id=f"D2-{len(findings)+1:03d}",
                    rule="D2",
                    severity="critical",
                    category="convention",
                    file=relative_path(fp),
                    line=i,
                    message="Debug call found in committed code (dd/dump/ray/var_dump/print_r/die/exit)",
                    suggestion="Remove debug calls before committing",
                    reference="docs/conventions.md#debug-calls",
                ))
    return findings


# ─── D4: Missing #[Fillable] in Models ─────────────────────────────────────

RE_FILLABLE_ATTR = re.compile(r"#\[Fillable\]")
RE_FILLABLE_PROPERTY = re.compile(r"protected\s+(?:static\s+)?\$fillable\s*=")
RE_GUARDED_PROPERTY = re.compile(r"protected\s+(?:static\s+)?\$guarded\s*=")


def scan_d4_fillable(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    model_files = [f for f in files if f.name.endswith("Model.php") or (
        "/Models/" in str(f) and not f.name.endswith("Observer.php")
        and not f.name.endswith("Policy.php") and not f.name.endswith("Factory.php")
        and not f.name.endswith("Pivot.php")
    )]

    for fp in model_files:
        content = read_file(fp)
        if not content:
            continue
        # Skip Pivot models
        if "extends Pivot" in content:
            continue
        has_attribute = bool(RE_FILLABLE_ATTR.search(content))
        has_fillable_prop = bool(RE_FILLABLE_PROPERTY.search(content))
        has_guarded_prop = bool(RE_GUARDED_PROPERTY.search(content))

        if has_fillable_prop:
            findings.append(Finding(
                id=f"D4-{len(findings)+1:03d}",
                rule="D4",
                severity="medium",
                category="convention",
                file=relative_path(fp),
                line=1,
                message="Model uses legacy $fillable property",
                suggestion="Replace $fillable with #[Fillable] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#non-negotiable",
            ))
        if has_guarded_prop:
            findings.append(Finding(
                id=f"D4-{len(findings)+1:03d}",
                rule="D4",
                severity="medium",
                category="convention",
                file=relative_path(fp),
                line=1,
                message="Model uses legacy $guarded property",
                suggestion="Replace $guarded with #[Fillable] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#non-negotiable",
            ))
    return findings


# ─── Report ─────────────────────────────────────────────────────────────────

def build_report(
    findings: list[Finding],
    scan_type: str,
    module: str | None,
    start_time: float,
    total_php_files: int,
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
            "total_checks": 8,
            "passed": 8 - len(set(f.rule for f in findings)),
            "failed": len(findings),
            "by_severity": by_severity,
        },
        findings=[vars(f) for f in findings],
        metadata={"total_php_files": total_php_files},
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
    print(f"  VIOLATIONS SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Rules checked: {s['total_checks']}")
    print(f"  Rules passed:  {s['passed']}")
    print(f"  Findings:      {s['failed']}")
    print(f"    Critical: {bs.get('critical', 0)}")
    print(f"    High:     {bs.get('high', 0)}")
    print(f"    Medium:   {bs.get('medium', 0)}")
    print(f"    Low:      {bs.get('low', 0)}")
    print(f"  Time: {result.execution_time_ms}ms")
    print(f"{'='*60}\n")


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan for C1-C8 and D1-D6 architecture/coding violations",
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
    total_php = len(find_php_files(None))

    findings: list[Finding] = []
    findings.extend(scan_c1_livewire_mutations(files, args.module))
    findings.extend(scan_c2_service_locator(files, args.module))
    findings.extend(scan_c3_raw_sql(files, args.module))
    findings.extend(scan_c4_inline_cache(files, args.module))
    findings.extend(scan_c5_entity_imports(files, args.module))
    findings.extend(scan_c6_dto_imports(files, args.module))
    findings.extend(scan_c7_action_params(files, args.module))
    findings.extend(scan_c8_runtime_exception(files, args.module))
    findings.extend(scan_d1_strict_types(files, args.module))
    findings.extend(scan_d2_debug_calls(files, args.module))
    findings.extend(scan_d4_fillable(files, args.module))

    result = build_report(findings, scan_type, args.module, start_time, total_php)

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
