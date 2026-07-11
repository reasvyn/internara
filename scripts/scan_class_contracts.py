#!/usr/bin/env python3
"""
scan_class_contracts.py — Class Contract Compliance
Checks Action, Entity, DTO, Model, and Enum contracts.
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
SCAN_NAME = "class-contracts"
SCAN_VERSION = "1.0.0"

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


# ─── Action Contracts ───────────────────────────────────────────────────────

RE_ACTION_CLASS = re.compile(
    r"class\s+(\w+)\s+extends\s+(BaseCommandAction|BaseReadAction|BaseProcessAction)"
)
RE_EXECUTE_METHOD = re.compile(r"public\s+function\s+execute\s*\(")
RE_HANDLE_METHOD = re.compile(r"public\s+function\s+handle\s*\(")
RE_TRANSACTION_METHOD = re.compile(r"(?:protected|private)\s+function\s+transaction\s*\(")
RE_LOG_METHOD = re.compile(r"(?:protected|private)\s+function\s+log\s*\(")


def scan_action_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    action_files = [f for f in files if "/Actions/" in str(f)]

    for fp in action_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_ACTION_CLASS.search(content)
        if not class_match:
            continue

        class_name = class_match.group(1)
        base_class = class_match.group(2)

        # Check for handle() method (should be execute())
        if RE_HANDLE_METHOD.search(content):
            findings.append(Finding(
                id=f"ACT-{len(findings)+1:03d}",
                rule="ACTION_HANDLE",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Action {class_name} has handle() instead of execute()",
                suggestion="Rename handle() to execute() — all Action types use execute()",
                reference="docs/architecture/action-pattern.md#action-triad",
            ))

        # Check for execute() method
        if not RE_EXECUTE_METHOD.search(content):
            findings.append(Finding(
                id=f"ACT-{len(findings)+1:03d}",
                rule="ACTION_NO_EXECUTE",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Action {class_name} has no execute() method",
                suggestion="Add a single public execute() method",
                reference="docs/architecture/action-pattern.md#action-triad",
            ))

        # Check transaction/log for Command/Process
        if base_class in ("BaseCommandAction", "BaseProcessAction"):
            has_transaction = bool(RE_TRANSACTION_METHOD.search(content))
            has_log = bool(RE_LOG_METHOD.search(content))

            # These are optional but recommended — mark as low severity
            # Only flag if class has > 50 lines (likely complex enough to need them)

        # Check for multiple public methods (violation of single execute rule)
        public_methods = re.findall(r"public\s+function\s+(\w+)\s*\(", content)
        non_lifecycle = [
            m for m in public_methods
            if m not in ("execute", "__construct", "boot", "render", "dehydrate",
                         "hydrate", "mount", "updating", "updated", "updatingProp",
                         "updatedProp", "getRules", "messages", "validationAttributes")
        ]
        if len(non_lifecycle) > 0 and base_class != "BaseReadAction":
            findings.append(Finding(
                id=f"ACT-{len(findings)+1:03d}",
                rule="ACTION_MULTIPLE_PUBLIC",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message=f"Action {class_name} has public methods beyond execute(): {', '.join(non_lifecycle[:3])}",
                suggestion="Actions should have a single public method: execute()",
                reference="docs/architecture/action-pattern.md#action-triad",
            ))

        # Check for DB::raw
        if "DB::raw(" in content:
            findings.append(Finding(
                id=f"ACT-{len(findings)+1:03d}",
                rule="ACTION_DB_RAW",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message=f"Action {class_name} uses DB::raw()",
                suggestion="Use parameterized queries",
                reference="docs/conventions.md#sql-injection-prevention",
            ))

    return findings


# ─── Entity Contracts ───────────────────────────────────────────────────────

RE_ENTITY_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+Entity")
RE_FINAL_READONLY = re.compile(r"final\s+readonly\s+class")
RE_FROM_MODEL = re.compile(r"public\s+static\s+function\s+fromModel\s*\(")
RE_TO_ARRAY = re.compile(r"public\s+function\s+toArray\s*\(")
RE_IS_QUESTIONS = re.compile(r"public\s+function\s+is\w+\s*\(")

ENTITY_FORBIDDEN = [
    "BaseCommandAction", "BaseReadAction", "BaseProcessAction",
    "ActionResponse", "ShouldQueue",
    "Illuminate\\Http\\Request",
    "Illuminate\\Support\\Facades\\",
    "DB::", "Cache::", "Http::",
]


def scan_entity_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    entity_files = [f for f in files if "/Entities/" in str(f) and f.name != "Entity.php"]

    for fp in entity_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        # Check final readonly
        if not RE_FINAL_READONLY.search(content):
            findings.append(Finding(
                id=f"ENT-{len(findings)+1:03d}",
                rule="ENTITY_NOT_FINAL_READONLY",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message="Entity class is not final readonly",
                suggestion="Declare as 'final readonly class'",
                reference="docs/architecture/entity-pattern.md#non-negotiable",
            ))

        # Check fromModel()
        if not RE_FROM_MODEL.search(content):
            findings.append(Finding(
                id=f"ENT-{len(findings)+1:03d}",
                rule="ENTITY_NO_FROM_MODEL",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message="Entity missing fromModel() static factory method",
                suggestion="Add public static function fromModel(Model $model): static",
                reference="docs/architecture/entity-pattern.md",
            ))

        # Check toArray()
        if not RE_TO_ARRAY.search(content):
            findings.append(Finding(
                id=f"ENT-{len(findings)+1:03d}",
                rule="ENTITY_NO_TO_ARRAY",
                severity="low",
                category="architecture",
                file=rel,
                line=1,
                message="Entity missing toArray() method",
                suggestion="Add public function toArray(): array for serialization",
                reference="docs/architecture/entity-pattern.md",
            ))

        # Check for forbidden imports
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if not stripped.startswith("use ") or not stripped.endswith(";"):
                continue
            for forbidden in ENTITY_FORBIDDEN:
                if forbidden in stripped:
                    findings.append(Finding(
                        id=f"ENT-{len(findings)+1:03d}",
                        rule="ENTITY_FORBIDDEN_IMPORT",
                        severity="high",
                        category="architecture",
                        file=rel,
                        line=i,
                        message=f"Entity imports forbidden: {forbidden}",
                        suggestion="Entities must be pure — no Actions, Services, Livewire, Controllers, DB, Cache",
                        reference="docs/architecture/entity-pattern.md#non-negotiable",
                    ))
                    break

    return findings


# ─── DTO Contracts ──────────────────────────────────────────────────────────

RE_DTO_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+(?:BaseData|Data)")
RE_FINAL_READONLY_DTO = re.compile(r"final\s+readonly\s+class")
RE_BASE_DATA_IMPORT = re.compile(r"use\s+.*BaseData")

DTO_FORBIDDEN_PATTERNS = [
    (r"App\\[^\\]+\\Models\\", "Models"),
    (r"App\\[^\\]+\\Entities\\", "Entities"),
    (r"App\\[^\\]+\\Actions\\", "Actions"),
    (r"App\\[^\\]+\\Repositories\\", "Repositories"),
    (r"Illuminate\\Database\\Eloquent\\Model", "Eloquent Model"),
    (r"Illuminate\\Database\\Query\\Builder", "Query Builder"),
]


def scan_dto_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    dto_files = [f for f in files if "/Data/" in str(f)]

    for fp in dto_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        # Check final readonly
        if not RE_FINAL_READONLY_DTO.search(content):
            findings.append(Finding(
                id=f"DTO-{len(findings)+1:03d}",
                rule="DTO_NOT_FINAL_READONLY",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message="DTO class is not final readonly",
                suggestion="Declare as 'final readonly class' extending BaseData",
                reference="docs/architecture/data-pattern.md#non-negotiable",
            ))

        # Check forbidden imports
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            stripped = line.strip()
            if not stripped.startswith("use ") or not stripped.endswith(";"):
                continue
            for pattern, desc in DTO_FORBIDDEN_PATTERNS:
                if re.search(pattern, stripped):
                    findings.append(Finding(
                        id=f"DTO-{len(findings)+1:03d}",
                        rule="DTO_FORBIDDEN_IMPORT",
                        severity="high",
                        category="architecture",
                        file=rel,
                        line=i,
                        message=f"DTO imports forbidden: {desc}",
                        suggestion="DTOs must only contain scalars, enums, Carbon — no Models, Entities, Actions",
                        reference="docs/architecture/data-pattern.md#non-negotiable",
                    ))
                    break

    return findings


# ─── Model Contracts ────────────────────────────────────────────────────────

RE_MODEL_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+(?:Model|Authenticatable)")
RE_FILLABLE_ATTR = re.compile(r"#\[Fillable\]")
RE_FILLABLE_PROP = re.compile(r"protected\s+(?:static\s+)?\$fillable\s*=")
RE_GUARDED_PROP = re.compile(r"protected\s+(?:static\s+)?\$guarded\s*=")
RE_ENTITY_METHOD = re.compile(r"public\s+function\s+entity\s*\(\)")
RE_BUSINESS_METHODS = re.compile(
    r"public\s+function\s+(?:get|calculate|validate|process|send|notify)\w*\s*\("
)


def scan_model_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    model_files = [
        f for f in files
        if "/Models/" in str(f)
        and not f.name.endswith("Observer.php")
        and not f.name.endswith("Policy.php")
        and not f.name.endswith("Factory.php")
        and not f.name.endswith("Pivot.php")
    ]

    for fp in model_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        if not RE_MODEL_CLASS.search(content):
            continue

        # Skip Pivot
        if "extends Pivot" in content:
            continue

        # Check Fillable attribute
        if RE_FILLABLE_PROP.search(content):
            findings.append(Finding(
                id=f"MOD-{len(findings)+1:03d}",
                rule="MODEL_LEGACY_FILLABLE",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Model uses legacy $fillable property",
                suggestion="Replace with #[Fillable] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#non-negotiable",
            ))

        if RE_GUARDED_PROP.search(content):
            findings.append(Finding(
                id=f"MOD-{len(findings)+1:03d}",
                rule="MODEL_LEGACY_GUARDED",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Model uses legacy $guarded property",
                suggestion="Replace with #[Fillable] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#non-negotiable",
            ))

        # Check for entity bridge method
        if not RE_ENTITY_METHOD.search(content):
            findings.append(Finding(
                id=f"MOD-{len(findings)+1:03d}",
                rule="MODEL_NO_ENTITY_BRIDGE",
                severity="low",
                category="architecture",
                file=rel,
                line=1,
                message="Model missing entity() bridge method",
                suggestion="Add public function entity(): {Module}Entity for domain object access",
                reference="docs/architecture/model-pattern.md",
            ))

        # Check for business methods on Model
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if RE_BUSINESS_METHODS.search(line) and "function" in line:
                # Skip relationship methods, scopes, accessors, mutators
                if any(skip in line for skip in [
                    "Scope", "Attribute", "accessor", "mutator",
                    "newQuery", "query", "getConnection", "getTable",
                    "getKeyName", "getKey", "exists",
                ]):
                    continue
                findings.append(Finding(
                    id=f"MOD-{len(findings)+1:03d}",
                    rule="MODEL_BUSINESS_METHOD",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Business logic method found on Model",
                    suggestion="Delegate business logic to Entity methods",
                    reference="docs/architecture/model-pattern.md",
                ))

    return findings


# ─── Enum Contracts ─────────────────────────────────────────────────────────

RE_ENUM_CLASS = re.compile(r"enum\s+(\w+)\s*:\s*(\w+)")
RE_LABEL_METHOD = re.compile(r"public\s+function\s+label\s*\(\)")
RE_VALID_TRANSITIONS = re.compile(r"public\s+function\s+validTransitions\s*\(\)")
RE_BACKED_ENUM = re.compile(r"enum\s+\w+\s*:\s*(?:string|int)")


def scan_enum_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    enum_files = [f for f in files if "/Enums/" in str(f) or "/States/" in str(f)]

    for fp in enum_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_ENUM_CLASS.search(content)
        if not class_match:
            continue

        enum_name = class_match.group(1)
        backing_type = class_match.group(2)

        # Check backing type
        if backing_type not in ("string", "int"):
            findings.append(Finding(
                id=f"ENUM-{len(findings)+1:03d}",
                rule="ENUM_NO_BACKING",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message=f"Enum {enum_name} has no backing type",
                suggestion="Add :string or :int backing type",
                reference="docs/architecture/enum-pattern.md",
            ))

        # Check for label() method
        if not RE_LABEL_METHOD.search(content):
            findings.append(Finding(
                id=f"ENUM-{len(findings)+1:03d}",
                rule="ENUM_NO_LABEL",
                severity="low",
                category="architecture",
                file=rel,
                line=1,
                message=f"Enum {enum_name} missing label() method",
                suggestion="Add public function label(): string for human-readable display",
                reference="docs/architecture/enum-pattern.md",
            ))

        # Check for StatusEnum-specific methods
        if "StatusEnum" in content or "status" in enum_name.lower():
            if not RE_VALID_TRANSITIONS.search(content):
                findings.append(Finding(
                    id=f"ENUM-{len(findings)+1:03d}",
                    rule="ENUM_STATUS_NO_TRANSITIONS",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=1,
                    message=f"StatusEnum {enum_name} missing validTransitions()",
                    suggestion="Add validTransitions(): array for state machine",
                    reference="docs/architecture/enum-pattern.md",
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
            "passed": 5 - len(set(f.rule.split("_")[0] for f in findings)),
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
    print(f"  CLASS CONTRACTS SCAN RESULTS")
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
        description="Scan Action/Entity/DTO/Model/Enum contracts",
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

    # Count by type
    action_count = len([f for f in files if "/Actions/" in str(f)])
    entity_count = len([f for f in files if "/Entities/" in str(f)])
    dto_count = len([f for f in files if "/Data/" in str(f)])
    model_count = len([f for f in files if "/Models/" in str(f)])
    enum_count = len([f for f in files if "/Enums/" in str(f) or "/States/" in str(f)])

    findings: list[Finding] = []
    findings.extend(scan_action_contracts(files, args.module))
    findings.extend(scan_entity_contracts(files, args.module))
    findings.extend(scan_dto_contracts(files, args.module))
    findings.extend(scan_model_contracts(files, args.module))
    findings.extend(scan_enum_contracts(files, args.module))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {
            "actions": action_count,
            "entities": entity_count,
            "dtos": dto_count,
            "models": model_count,
            "enums": enum_count,
        },
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
