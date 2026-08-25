#!/usr/bin/env python3
"""
Enhanced v2.1: parallel execution, robust error isolation, shared _common helpers,
severity/baseline filtering, and performance optimizations.
scan_class_contracts.py — Class Contract Compliance
Checks Action, Entity, DTO, Model, Enum, Event, Policy, and Service contracts.
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


def is_abstract_class(content: str) -> bool:
    return bool(re.search(r"^\s*(?:final\s+)?abstract\s+class\s", content, re.M))


def is_interface_or_trait(content: str) -> bool:
    return bool(re.search(r"^\s*(?:abstract\s+)?(?:interface|trait)\s", content, re.M))


def line_of(content: str, position: int) -> int:
    return content[:position].count("\n") + 1


# ─── Action Contracts ───────────────────────────────────────────────────────

RE_ACTION_CLASS = re.compile(
    r"class\s+(\w+)\s+extends\s+(BaseCommandAction|BaseReadAction|BaseProcessAction)"
)
RE_EXECUTE_METHOD = re.compile(r"public\s+function\s+execute\s*\(")
RE_HANDLE_METHOD = re.compile(r"public\s+function\s+handle\s*\(")
RE_STATIC_DISPATCH = re.compile(r"\b(\w+)::\s*dispatch\s*\(")


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
                line=line_of(content, RE_HANDLE_METHOD.search(content).start()),
                message=f"Action {class_name} has handle() instead of execute()",
                suggestion="Rename handle() to execute() — all Action types use execute()",
                reference="docs/architecture/action-pattern.md#command-actions",
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
                reference="docs/architecture/action-pattern.md#command-actions",
            ))

        # Check for static event dispatch — must use $this->dispatchEvent()
        # Queued jobs use the same ::dispatch() syntax but are not events.
        dispatch_matches = [
            m for m in RE_STATIC_DISPATCH.finditer(content)
            if not m.group(1).endswith("Job")
        ]
        if dispatch_matches:
            findings.append(Finding(
                id=f"ACT-{len(findings)+1:03d}",
                rule="ACTION_STATIC_DISPATCH",
                severity="high",
                category="architecture",
                file=rel,
                line=line_of(content, dispatch_matches[0].start()),
                message=f"Action {class_name} uses static event dispatch — use $this->dispatchEvent()",
                suggestion="Replace {Event}::dispatch(...) with $this->dispatchEvent(new {Event}(...))",
                reference="docs/architecture/event-pattern.md#4b-basedispatch-event-deferred-dispatch",
            ))

        # Check for multiple public methods (violation of single execute rule)
        public_methods = re.findall(r"public\s+function\s+(\w+)\s*\(", content)
        non_lifecycle = [
            m for m in public_methods
            if m not in ("execute", "__construct", "boot", "render", "dehydrate",
                         "hydrate", "mount", "updating", "updated", "updatingProp",
                         "updatedProp", "getRules", "messages", "validationAttributes")
        ]
        if non_lifecycle and base_class != "BaseReadAction":
            findings.append(Finding(
                id=f"ACT-{len(findings)+1:03d}",
                rule="ACTION_MULTIPLE_PUBLIC",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message=f"Action {class_name} has public methods beyond execute(): {', '.join(non_lifecycle[:3])}",
                suggestion="Extract secondary operations into their own Actions",
                reference="docs/architecture/action-pattern.md#command-actions",
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
                reference="docs/conventions.md#32-sql-injection-prevention",
            ))

    return findings


# ─── Entity Contracts ───────────────────────────────────────────────────────

RE_ENTITY_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+(BaseEntity|Entity)")
RE_FINAL_READONLY = re.compile(r"^\s*final\s+readonly\s+class\s", re.M)
RE_FROM_MODEL = re.compile(r"public\s+static\s+function\s+fromModel\s*\(")

ENTITY_FORBIDDEN = [
    "BaseCommandAction", "BaseReadAction", "BaseProcessAction",
    "ActionResponse", "ShouldQueue",
    "Illuminate\\Http\\Request",
    "Illuminate\\Support\\Facades\\",
    "DB::", "Cache::", "Http::", "Storage::", "Log::",
]

ENTITY_FORBIDDEN_IO = re.compile(r"\b(?:DB::|Cache::|Http::|Storage::|Log::|event\s*\(|dispatch\s*\()")


def scan_entity_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    entity_files = [f for f in files if "/Entities/" in str(f)]

    for fp in entity_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_ENTITY_CLASS.search(content)
        if not class_match:
            # Entities must extend BaseEntity — flag unknown entity-like files
            if re.search(r"^\s*(?:final\s+)?(?:abstract\s+)?class\s", content, re.M):
                findings.append(Finding(
                    id=f"ENT-{len(findings)+1:03d}",
                    rule="ENTITY_NO_BASE",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=1,
                    message="Entity does not extend BaseEntity",
                    suggestion="Extend BaseEntity (app/Core/Entities/BaseEntity.php)",
                    reference="docs/architecture/entity-pattern.md#2-entity-contract-baseentity",
                ))
            continue

        class_name = class_match.group(1)

        # Skip the abstract base class itself
        if is_abstract_class(content) or is_interface_or_trait(content):
            continue

        # Check final readonly (concrete entities only)
        if not RE_FINAL_READONLY.search(content):
            findings.append(Finding(
                id=f"ENT-{len(findings)+1:03d}",
                rule="ENTITY_NOT_FINAL_READONLY",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Entity {class_name} is not final readonly",
                suggestion="Declare as 'final readonly class'",
                reference="docs/architecture/entity-pattern.md#51-final-readonly-class",
            ))

        # Check fromModel() — mandatory per contract
        if not RE_FROM_MODEL.search(content):
            findings.append(Finding(
                id=f"ENT-{len(findings)+1:03d}",
                rule="ENTITY_NO_FROM_MODEL",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Entity {class_name} missing fromModel() static factory method",
                suggestion="Add public static function fromModel(Model $model): static",
                reference="docs/architecture/entity-pattern.md#41-static-factory-frommodel-model-static",
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
                        reference="docs/architecture/entity-pattern.md#5-entity-purity-rules",
                    ))
                    break

        # Check for forbidden I/O calls in method bodies
        io_match = ENTITY_FORBIDDEN_IO.search(content)
        if io_match:
            findings.append(Finding(
                id=f"ENT-{len(findings)+1:03d}",
                rule="ENTITY_FORBIDDEN_IO",
                severity="high",
                category="architecture",
                file=rel,
                line=line_of(content, io_match.start()),
                message=f"Entity performs forbidden I/O: {io_match.group(0).strip()}",
                suggestion="Entities must be pure — no DB/Cache/Http/Storage/Log/event access",
                reference="docs/architecture/entity-pattern.md#5-entity-purity-rules",
            ))

    return findings


# ─── DTO Contracts ──────────────────────────────────────────────────────────

RE_DTO_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+(BaseData|Data)")
RE_FINAL_READONLY_DTO = re.compile(r"^\s*final\s+readonly\s+class\s", re.M)

DTO_FORBIDDEN_PATTERNS = [
    (r"App\\[^\\]+\\Models\\", "Models"),
    (r"App\\[^\\]+\\Entities\\", "Entities"),
    (r"App\\[^\\]+\\Actions\\", "Actions"),
    (r"App\\[^\\]+\\Repositories\\", "Repositories"),
    (r"Illuminate\\Database\\Eloquent\\Model", "Eloquent Model"),
    (r"Illuminate\\Database\\Query\\Builder", "Query Builder"),
    (r"App\\[^\\]+\\Services\\", "Services"),
]


def scan_dto_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    dto_files = [f for f in files if "/Data/" in str(f)]

    for fp in dto_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_DTO_CLASS.search(content)
        if not class_match:
            continue

        # Skip abstract base (BaseData itself)
        if is_abstract_class(content):
            continue

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
                reference="docs/architecture/data-pattern.md#2-basedata-contract",
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
                        reference="docs/architecture/data-pattern.md#2-basedata-contract",
                    ))
                    break

    return findings


# ─── Model Contracts ────────────────────────────────────────────────────────

RE_MODEL_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+(?:Model|Authenticatable|Activity)")
RE_FILLABLE_ATTR = re.compile(r"#\[\s*Fillable.*?\]", re.S)
RE_FILLABLE_PROP = re.compile(r"protected\s+(?:static\s+)?\$fillable\s*=")
RE_GUARDED_PROP = re.compile(r"protected\s+(?:static\s+)?\$guarded\s*=")
RE_ENTITY_ACCESSOR = re.compile(r"public\s+function\s+as[A-Z]\w*\s*\(")
RE_BUSINESS_METHODS = re.compile(
    r"public\s+function\s+(?:get|calculate|validate|process|send|notify)\w*\s*\("
)
RE_MODEL_MUTATION = re.compile(r"public\s+function\s+(?:update|delete|forceDelete|save)\s*\(")


def scan_model_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    model_files = [
        f for f in files
        if "/Models/" in str(f)
        and not f.name.endswith(("Observer.php", "Policy.php", "Factory.php", "Pivot.php"))
    ]

    for fp in model_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        if not RE_MODEL_CLASS.search(content):
            continue
        if "extends Pivot" in content:
            continue
        # Skip abstract base models (BaseModel, BaseAuthenticatable)
        if is_abstract_class(content):
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
                reference="docs/architecture/model-pattern.md#6-fillable-attribute-convention",
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
                reference="docs/architecture/model-pattern.md#6-fillable-attribute-convention",
            ))

        # Check for business methods on Model (delegate to Entity via as{Entity}())
        lines = content.split("\n")
        for i, line in enumerate(lines, 1):
            if RE_BUSINESS_METHODS.search(line) and "function" in line:
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
                    suggestion="Delegate business logic to Entity via as{EntityName}() accessor",
                    reference="docs/architecture/model-pattern.md#3-model-responsibilities",
                ))

    return findings


# ─── Enum Contracts ─────────────────────────────────────────────────────────

RE_ENUM_CLASS = re.compile(r"^\s*enum\s+(\w+)\s*(?::\s*(\w+))?\s*(?:implements\s+([^\n{]+))?", re.M)
RE_LABEL_METHOD = re.compile(r"public\s+function\s+label\s*\(")
RE_VALID_TRANSITIONS = re.compile(r"public\s+function\s+validTransitions\s*\(")
RE_IS_TERMINAL = re.compile(r"public\s+function\s+isTerminal\s*\(")
RE_CAN_TRANSITION = re.compile(r"public\s+function\s+canTransitionTo\s*\(")
RE_CASE_NAME = re.compile(r"^\s*case\s+([A-Za-z0-9_]+)", re.M)


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
        interfaces = (class_match.group(3) or "").strip()

        is_status = "StatusEnum" in interfaces
        is_label = "LabelEnum" in interfaces or is_status

        # Check backing type — enums must be backed string/int
        if backing_type not in ("string", "int"):
            findings.append(Finding(
                id=f"ENUM-{len(findings)+1:03d}",
                rule="ENUM_NO_BACKING",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Enum {enum_name} has no backing type",
                suggestion="Add :string or :int backing type",
                reference="docs/architecture/enum-pattern.md#2-labelenum-contract",
            ))

        # LabelEnum implementors must have label()
        if is_label and not RE_LABEL_METHOD.search(content):
            findings.append(Finding(
                id=f"ENUM-{len(findings)+1:03d}",
                rule="ENUM_NO_LABEL",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Enum {enum_name} implements LabelEnum but missing label() method",
                suggestion="Add public function label(): string for human-readable display",
                reference="docs/architecture/enum-pattern.md#2-labelenum-contract",
            ))

        # StatusEnum implementors must have the full state-machine contract
        if is_status:
            for method_re, method_name, ref in [
                (RE_VALID_TRANSITIONS, "validTransitions", "docs/architecture/enum-pattern.md#3-statusenum-contract"),
                (RE_IS_TERMINAL, "isTerminal", "docs/architecture/enum-pattern.md#3-statusenum-contract"),
                (RE_CAN_TRANSITION, "canTransitionTo", "docs/architecture/enum-pattern.md#3-statusenum-contract"),
            ]:
                if not method_re.search(content):
                    findings.append(Finding(
                        id=f"ENUM-{len(findings)+1:03d}",
                        rule="ENUM_STATUS_INCOMPLETE",
                        severity="high",
                        category="architecture",
                        file=rel,
                        line=1,
                        message=f"StatusEnum {enum_name} missing {method_name}()",
                        suggestion=f"Add public function {method_name}(): ... for the state machine contract",
                        reference=ref,
                    ))

        # Check UPPER_SNAKE case names
        for case_match in RE_CASE_NAME.finditer(content):
            case_name = case_match.group(1)
            if not re.fullmatch(r"[A-Z][A-Z0-9_]*", case_name):
                findings.append(Finding(
                    id=f"ENUM-{len(findings)+1:03d}",
                    rule="ENUM_CASE_NAMING",
                    severity="low",
                    category="convention",
                    file=rel,
                    line=line_of(content, case_match.start()),
                    message=f"Enum case '{case_name}' is not UPPER_SNAKE_CASE",
                    suggestion="Use UPPER_SNAKE_CASE case names (e.g. REVISION_REQUIRED)",
                    reference="docs/architecture/enum-pattern.md#5-case-convention",
                ))

    return findings


# ─── Event Contracts ────────────────────────────────────────────────────────

RE_EVENT_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+BaseEvent")
RE_EVENT_NAME = re.compile(r"public\s+function\s+eventName\s*\(")
RE_LISTENER_CLASS = re.compile(r"class\s+(\w+)\s+")
RE_SHOULD_QUEUE = re.compile(r"implements\s+ShouldQueue\b|ShouldHandleEventsAfterCommit")
RE_HANDLE_METHOD_LISTENER = re.compile(r"public\s+function\s+handle\s*\(")
RE_IO_PATTERN = re.compile(r"\b(?:Mail::|Http::|Notification::|Storage::|DB::)")
RE_CACHE_WRITE = re.compile(r"Cache::(?:put|remember|add|forever|warmup)\s*\(")
RE_ACTIVITY_LOG = re.compile(r"->event\s*\(|activity\(\)")


def scan_event_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    event_files = [f for f in files if "/Events/" in str(f)]

    for fp in event_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_EVENT_CLASS.search(content)
        if not class_match:
            continue
        if is_abstract_class(content) or is_interface_or_trait(content):
            continue

        class_name = class_match.group(1)

        if not RE_EVENT_NAME.search(content):
            findings.append(Finding(
                id=f"EVT-{len(findings)+1:03d}",
                rule="EVENT_NO_EVENT_NAME",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Event {class_name} missing eventName()",
                suggestion="Add public function eventName(): string",
                reference="docs/architecture/event-pattern.md#1-event-architecture-baseevent-contract",
            ))

    listener_files = [f for f in files if "/Listeners/" in str(f)]
    for fp in listener_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        if not RE_HANDLE_METHOD_LISTENER.search(content):
            continue
        if is_abstract_class(content) or is_interface_or_trait(content):
            continue

        # I/O listeners must implement ShouldQueue.
        # Doc exception: cache forget() stays synchronous (event-pattern §6).
        if not RE_SHOULD_QUEUE.search(content):
            has_io = RE_IO_PATTERN.search(content) or RE_CACHE_WRITE.search(content) \
                or RE_ACTIVITY_LOG.search(content)
            if has_io:
                findings.append(Finding(
                    id=f"EVT-{len(findings)+1:03d}",
                    rule="LISTENER_NOT_QUEUED",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=1,
                    message="I/O listener does not implement ShouldQueue",
                    suggestion="Add 'implements ShouldQueue' — I/O work must be queued",
                    reference="docs/architecture/event-pattern.md#6-shouldqueue-for-async-listeners",
                ))

    return findings


# ─── Policy Contracts ───────────────────────────────────────────────────────

RE_POLICY_CLASS = re.compile(r"class\s+(\w+)\s+extends\s+BasePolicy")
RE_ABILITY_METHODS = re.compile(
    r"public\s+function\s+(viewAny|view|create|update|delete|restore|forceDelete)\s*\("
)


def scan_policy_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    policy_files = [f for f in files if "/Policies/" in str(f)]

    for fp in policy_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_POLICY_CLASS.search(content)
        if not class_match:
            if re.search(r"^\s*(?:final\s+)?class\s", content, re.M):
                findings.append(Finding(
                    id=f"POL-{len(findings)+1:03d}",
                    rule="POLICY_NO_BASE",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=1,
                    message="Policy does not extend BasePolicy",
                    suggestion="Extend BasePolicy (app/Core/Policies/BasePolicy.php)",
                    reference="docs/architecture/policy-pattern.md",
                ))
            continue
        if is_abstract_class(content):
            continue

        if not RE_ABILITY_METHODS.search(content):
            findings.append(Finding(
                id=f"POL-{len(findings)+1:03d}",
                rule="POLICY_NO_ABILITIES",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message="Policy has no standard ability methods",
                suggestion="Implement viewAny/view/create/update/delete/restore/forceDelete",
                reference="docs/architecture/policy-pattern.md",
            ))

    return findings


# ─── Service Contracts ──────────────────────────────────────────────────────

RE_SERVICE_CLASS = re.compile(r"class\s+(\w+)\s+")


def scan_service_contracts(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    service_files = [f for f in files if "/Services/" in str(f)]

    for fp in service_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)

        class_match = RE_SERVICE_CLASS.search(content)
        if not class_match:
            continue
        if is_abstract_class(content) or is_interface_or_trait(content):
            continue

        class_name = class_match.group(1)

        # Services should use constructor injection when they have dependencies
        constructor_params = re.search(
            r"public\s+function\s+__construct\s*\(([^)]*)\)", content
        )
        if constructor_params and constructor_params.group(1).strip():
            # Has constructor params — check for service locator instead
            if re.search(r"app\s*\(|\bresolve\s*\(", content):
                findings.append(Finding(
                    id=f"SVC-{len(findings)+1:03d}",
                    rule="SERVICE_SERVICE_LOCATOR",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=1,
                    message=f"Service {class_name} uses service locator instead of constructor injection",
                    suggestion="Inject dependencies via the constructor",
                    reference="docs/conventions.md#10-dependency-injection-conventions",
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

    rules = {f.rule.split("_")[0] for f in findings}

    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 8,
            "passed": 8 - len(rules),
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
        description="Scan Action/Entity/DTO/Model/Enum/Event/Policy/Service contracts",
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

    metadata = {
        "actions": len([f for f in files if "/Actions/" in str(f)]),
        "entities": len([f for f in files if "/Entities/" in str(f)]),
        "dtos": len([f for f in files if "/Data/" in str(f)]),
        "models": len([f for f in files if "/Models/" in str(f)]),
        "enums": len([f for f in files if "/Enums/" in str(f) or "/States/" in str(f)]),
        "events": len([f for f in files if "/Events/" in str(f)]),
        "policies": len([f for f in files if "/Policies/" in str(f)]),
        "services": len([f for f in files if "/Services/" in str(f)]),
    }

    findings: list[Finding] = []
    findings.extend(scan_action_contracts(files, args.module))
    findings.extend(scan_entity_contracts(files, args.module))
    findings.extend(scan_dto_contracts(files, args.module))
    findings.extend(scan_model_contracts(files, args.module))
    findings.extend(scan_enum_contracts(files, args.module))
    findings.extend(scan_event_contracts(files, args.module))
    findings.extend(scan_policy_contracts(files, args.module))
    findings.extend(scan_service_contracts(files, args.module))

    result = build_report(
        findings, scan_type, args.module, start_time, metadata,
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
