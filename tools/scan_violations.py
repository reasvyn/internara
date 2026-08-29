#!/usr/bin/env python3
"""
Enhanced v2.1: parallel execution, robust error isolation, shared _common helpers,
severity/baseline filtering, and performance optimizations.
scan_violations.py — Architecture & Coding Invariant Violations
Scans PHP code for C1-C8, D1-D6 violations plus Livewire layer checks and
performance rules (P1, P2, P3, P5). Detection is calibrated against the real
codebase: model mutations are whitelisted to known Model classes, raw SQL is
only flagged when it interpolates variables, and `resolve()` must be a call
(not a method declaration) to count as a service locator.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
try:
    from _output import handle_output
except ImportError:
    import sys as _sys2
    _sys2.path.insert(0, str(__import__("pathlib").Path(__file__).parent))
    from _output import handle_output
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

ENTITY_FORBIDDEN_IMPORTS = [
    "Actions\\",
    "Services\\",
    "Livewire\\",
    "Http\\Controllers\\",
]
# The settings store (App\Settings\Services\Settings) is a data-access facade, not a business
# Service. Settings-backed entities (entity-pattern.md §10 & §11.5) may read from it via a get()
# static factory — documented exemption, so its import must not be flagged as C5.
SETTINGS_STORE_IMPORT = "App\\Settings\\Services\\Settings;"

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
        module_dir = MODULES_DIR / module if (MODULES_DIR / module).exists() else APP_DIR / module
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
    try:
        rel = filepath.relative_to(APP_DIR)
        if rel.parts:
            return rel.parts[0]
    except ValueError:
        pass
    return None


def is_comment_or_doc(line: str) -> bool:
    stripped = line.lstrip()
    return stripped.startswith("//") or stripped.startswith("*") or stripped.startswith("#")


def collect_model_classes(files: list[Path]) -> set[str]:
    """Collect short class names of every Model so C1 only flags real models."""
    names: set[str] = set()
    model_files = [f for f in files if "/Models/" in str(f)]
    for fp in model_files:
        content = read_file(fp)
        if not content:
            continue
        match = re.search(r"(?:final\s+)?(?:readonly\s+)?class\s+(\w+)", content)
        if match:
            names.add(match.group(1))
    return names


def is_vendor_model_extension(content: str) -> bool:
    """True when a Model class extends a vendor base (e.g. Spatie's Activity)."""
    parent = re.search(r"\bclass\s+\w+\s+extends\s+(\w+)", content)
    if not parent:
        return False
    parent_name = parent.group(1)
    for use_match in re.finditer(r"^use\s+([A-Z][\w\\\\]+?)\s*;\s*$", content, re.M):
        fqcn = use_match.group(1)
        if fqcn.endswith("\\" + parent_name):
            return not fqcn.startswith("App\\")
    return False


def line_of(content: str, position: int) -> int:
    return content[:position].count("\n") + 1


def split_params(params_str: str) -> list[str]:
    """Split a function parameter list at top-level commas, respecting nesting."""
    params: list[str] = []
    depth = 0
    current: list[str] = []
    for ch in params_str:
        if ch in "([{<":
            depth += 1
        elif ch in ")]}>":
            depth -= 1
        if ch == "," and depth == 0:
            params.append("".join(current).strip())
            current = []
        else:
            current.append(ch)
    if current:
        params.append("".join(current).strip())
    return [p for p in params if p]


def param_type_hint(param: str) -> str:
    """Extract a normalized type hint (without defaults/attributes) from a param."""
    param = param.strip()
    attr = re.search(r"#\[[^\]]*\]", param)
    if attr:
        param = param[attr.end():].strip()
    param = re.split(r"\s*=\s*", param, maxsplit=1)[0]
    param = re.sub(r"\.\.\.", "", param)
    type_part = re.sub(r"\s*&\s*\$\w+.*$", "", param)
    type_part = re.sub(r"\$\w+.*$", "", type_part).strip()
    return type_part


# ─── C1: No Model mutations in Livewire ────────────────────────────────────

RE_MODEL_STATIC_MUTATION = re.compile(r"(\w+)\s*::\s*(?:create|forceCreate|update|delete|forceDelete)\s*\(")
RE_MODEL_INSTANCE_MUTATION = re.compile(r"\$(\w+)\s*->\s*(?:save|delete|forceDelete|forceFill)\s*\(")
RE_MODEL_INSTANCE_UPDATE = re.compile(r"\$(\w+)\s*->\s*update\s*\(")


def scan_c1_livewire_mutations(files: list[Path], module: str | None, models: set[str]) -> list[Finding]:
    findings: list[Finding] = []
    livewire_files = [f for f in files if "/Livewire/" in str(f)]

    for fp in livewire_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue

            match = RE_MODEL_STATIC_MUTATION.search(line)
            if match and match.group(1) in models:
                findings.append(Finding(
                    id=f"C1-{len(findings)+1:03d}",
                    rule="C1",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message=f"Model mutation found in Livewire component: {match.group(0).strip()}",
                    suggestion="Use a Command Action instead (e.g., StoreStudentAction)",
                    reference="docs/architecture/livewire-pattern.md#1-thin-component-rule",
                ))

            match = RE_MODEL_INSTANCE_MUTATION.search(line) or RE_MODEL_INSTANCE_UPDATE.search(line)
            if match:
                findings.append(Finding(
                    id=f"C1-{len(findings)+1:03d}",
                    rule="C1",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message=f"Eloquent mutation found in Livewire component on ${match.group(1)}",
                    suggestion="Delegate to a Command Action",
                    reference="docs/architecture/livewire-pattern.md#1-thin-component-rule",
                ))
    return findings


# ─── C2: No service locator ────────────────────────────────────────────────

RE_SERVICE_LOCATOR = re.compile(
    r"app\(\)\s*->\s*(?:make|makeWith|make|get|makeShared)\s*\("
    r"|Container::getInstance\(\)\s*->\s*make\s*\("
    r"|(?<![\\\w])\bapp\s*\(\s*(?:['\"]|\w+::class)"
)
RE_RESOLVE_CALL = re.compile(r"(?<![\\\w])resolve\s*\(")


def scan_c2_service_locator(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        if "/Providers/" in rel or "/config/" in rel:
            continue
        # Global helper files (helpers.php) define functions, not classes — constructor
        # injection is impossible there, so `app()` is the documented Laravel idiom.
        if Path(rel).name == "helpers.php":
            continue
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if RE_SERVICE_LOCATOR.search(line):
                findings.append(Finding(
                    id=f"C2-{len(findings)+1:03d}",
                    rule="C2",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Service locator pattern detected (app()->make / app('...') / resolve)",
                    suggestion="Use constructor injection instead",
                    reference="docs/conventions.md#10-dependency-injection-conventions",
                ))
                continue
            # resolve() must be a CALL, not a method/function declaration or self:: call
            if RE_RESOLVE_CALL.search(line):
                if re.search(r"(?:function\s+|self::|static::|parent::|->)\s*resolve\s*\(", line):
                    continue
                if re.search(r"resolve\s*\(\s*(?:function|\s*\.\s*)", line):
                    continue
                findings.append(Finding(
                    id=f"C2-{len(findings)+1:03d}",
                    rule="C2",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Service locator pattern detected (resolve() container call)",
                    suggestion="Use constructor injection instead",
                    reference="docs/conventions.md#10-dependency-injection-conventions",
                ))
    return findings


# ─── C3: Raw SQL must not interpolate unbound variables ────────────────────

RE_RAW_SQL = re.compile(
    r"(?:DB::raw|DB::select|DB::statement|DB::insert|DB::update|DB::delete"
    r"|->whereRaw|->selectRaw|->havingRaw|->orderByRaw|->groupByRaw)\s*\("
)


def scan_c3_raw_sql(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if not RE_RAW_SQL.search(line):
                continue
            # Only flag when the SQL string interpolates a variable ($x, {$x}, . $x)
            if re.search(r"['\"][^'\"]*(\$|\{)", line) or re.search(r"['\"]\s*\.\s*\$", line):
                findings.append(Finding(
                    id=f"C3-{len(findings)+1:03d}",
                    rule="C3",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Raw SQL interpolating a variable — use parameterized bindings",
                    suggestion="Pass bindings array as second argument (e.g., ->whereRaw('x = ?', [$v]))",
                    reference="docs/conventions.md#32-sql-injection-prevention",
                ))
    return findings


# ─── C4: No inline cache keys ──────────────────────────────────────────────

RE_CACHE_FACADE = re.compile(
    r"(?:Cache|cache\(\))\s*(?:->\s*tags\s*\([^)]*\)\s*->)?->\s*"
    r"(?:remember|rememberForever|get|put|forever|forget|flush|has|add|pull|increment|decrement)\s*\(\s*['\"]"
)
RE_QUERY_REMEMBER = re.compile(r"->\s*remember\s*\(\s*['\"]")


def scan_c4_inline_cache(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        if "/config/" in rel:
            continue
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if RE_CACHE_FACADE.search(line):
                findings.append(Finding(
                    id=f"C4-{len(findings)+1:03d}",
                    rule="C4",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Inline cache key with string literal",
                    suggestion="Register cache key in config/cache-keys.php and use config('cache-keys.xxx')",
                    reference="docs/architecture/cache-pattern.md#2-centralized-key-registry",
                ))
            elif RE_QUERY_REMEMBER.search(line):
                findings.append(Finding(
                    id=f"C4-{len(findings)+1:03d}",
                    rule="C4",
                    severity="low",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Query builder remember() with inline cache key",
                    suggestion="Register cache key in config/cache-keys.php and use config('cache-keys.xxx')",
                    reference="docs/architecture/cache-pattern.md#2-centralized-key-registry",
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
        for i, line in enumerate(content.split("\n"), 1):
            stripped = line.strip()
            if not stripped.startswith("use ") or not stripped.endswith(";"):
                continue
            if stripped.endswith(SETTINGS_STORE_IMPORT):
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
                        reference="docs/architecture/entity-pattern.md#5-entity-purity-rules",
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
        for i, line in enumerate(content.split("\n"), 1):
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
                        reference="docs/architecture/data-pattern.md#2-basedata-contract",
                    ))
                    break
    return findings


# ─── C7: Command/Process Actions without DTO for 3+ params ────────────────

RE_EXECUTE_DEF = re.compile(r"public\s+function\s+execute\s*\((.*?)\)\s*:", re.DOTALL)


def scan_c7_action_params(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    action_files = [f for f in files if "/Actions/" in str(f)]

    for fp in action_files:
        content = read_file(fp)
        if not content:
            continue
        is_command_process = "BaseCommandAction" in content or "BaseProcessAction" in content
        if not is_command_process:
            continue

        match = RE_EXECUTE_DEF.search(content)
        if not match:
            continue
        params_str = match.group(1).strip()
        if not params_str:
            continue

        params = split_params(params_str)
        has_dto = any(
            re.search(r"\b\w*(?:Data|Request|DTO)\b", param_type_hint(p))
            for p in params
        )
        if len(params) >= 3 and not has_dto:
            hints = [param_type_hint(p) for p in params]
            findings.append(Finding(
                id=f"C7-{len(findings)+1:03d}",
                rule="C7",
                severity="medium",
                category="architecture",
                file=relative_path(fp),
                line=line_of(content, match.start()),
                message=f"Command/Process Action has {len(params)} params without DTO: {', '.join(hints)}",
                suggestion="Accept a BaseData DTO for 3+ parameters",
                reference="docs/architecture/action-pattern.md#command-actions",
            ))
    return findings


# ─── C8: RuntimeException instead of RejectedException ─────────────────────

RE_RUNTIME_EXCEPTION = re.compile(r"throw\s+new\s+\\?(?:RuntimeException|Exception)\s*\(")
# A RuntimeException/Exception whose final argument is a previous exception (e.g. `, 0, $e)`)
# is a generic wrapper around an unexpected failure — documented in
# docs/architecture/exception-pattern.md §Error Handling in Actions. It is not a business-rule
# violation, so it must not be flagged as C8.
RE_WRAPPER_EXCEPTION = re.compile(r"throw\s+new\s+\\?(?:RuntimeException|Exception)\s*\(.*,\s*\$\w+\s*\)")


def scan_c8_runtime_exception(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        if "/Actions/" not in rel and "/Entities/" not in rel:
            continue
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if RE_RUNTIME_EXCEPTION.search(line) and not RE_WRAPPER_EXCEPTION.search(line):
                findings.append(Finding(
                    id=f"C8-{len(findings)+1:03d}",
                    rule="C8",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="RuntimeException/Exception thrown in Action/Entity — use RejectedException",
                    suggestion="Use throw new RejectedException('message') for business rule violations",
                    reference="docs/architecture/exception-pattern.md#error-handling-in-actions",
                ))
    return findings


# ─── D1: Missing strict_types ──────────────────────────────────────────────

RE_STRICT_TYPES = re.compile(r"declare\s*\(\s*strict_types\s*=\s*1\s*\)")


def scan_d1_strict_types(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        if "/database/migrations/" in rel or "/config/" in rel:
            continue
        content = read_file(fp)
        if not content:
            continue
        if not RE_STRICT_TYPES.search(content[:600]):
            findings.append(Finding(
                id=f"D1-{len(findings)+1:03d}",
                rule="D1",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Missing declare(strict_types=1)",
                suggestion="Add declare(strict_types=1) as the first statement",
                reference="docs/conventions.md#2-general-php",
            ))
    return findings


# ─── D2: Debug calls ──────────────────────────────────────────────────────

RE_DEBUG_CALLS = re.compile(r"\b(?:dd|dump|ray|var_dump|print_r|die|exit|ddd)\s*\(")
RE_DEBUG_CHAIN = re.compile(r"->\s*(?:dd|dump|ray|dumpQuietly)\s*\(")


def scan_d2_debug_calls(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if RE_DEBUG_CALLS.search(line) or RE_DEBUG_CHAIN.search(line):
                findings.append(Finding(
                    id=f"D2-{len(findings)+1:03d}",
                    rule="D2",
                    severity="critical",
                    category="convention",
                    file=rel,
                    line=i,
                    message="Debug call found in committed code (dd/dump/ray/var_dump/print_r/die/exit)",
                    suggestion="Remove debug calls before committing",
                    reference="docs/conventions.md#2-general-php",
                ))
    return findings


# ─── D3: Hardcoded user-facing strings in Blade ───────────────────────────

RE_LOCALIZED_CALL = re.compile(
    r"(?:__\s*\(|@lang\s*\(|\btrans\s*\(|@choice\s*\(|trans_choice\s*\()\s*(['\"]).*?\1",
    re.DOTALL,
)
RE_HARDCODED_BLADE_STRING = re.compile(r"(?<![=:(>])(['\"])[A-Z][A-Za-z0-9 ,.'-]{3,}\1")


def scan_d3_hardcoded_strings(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    blade_files = [f for f in files if f.name.endswith(".blade.php")]

    for fp in blade_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        for i, line in enumerate(content.split("\n"), 1):
            stripped = line.strip()
            if is_comment_or_doc(stripped):
                continue
            masked = RE_LOCALIZED_CALL.sub("", stripped)
            matches = RE_HARDCODED_BLADE_STRING.findall(masked)
            if matches:
                findings.append(Finding(
                    id=f"D3-{len(findings)+1:03d}",
                    rule="D3",
                    severity="medium",
                    category="convention",
                    file=rel,
                    line=i,
                    message="Hardcoded user-facing string in Blade — wrap in __()",
                    suggestion="Use __('{key}') and add the key to lang/en/ and lang/id/",
                    reference="docs/conventions.md#14-localization",
                ))
    return findings


# ─── D4: Missing #[Fillable] in Models ─────────────────────────────────────

RE_FILLABLE_ATTR = re.compile(r"#\[\s*Fillable.*?\]", re.S)
RE_FILLABLE_PROPERTY = re.compile(r"protected\s+(?:static\s+)?\$fillable\s*=")
RE_GUARDED_PROPERTY = re.compile(r"protected\s+(?:static\s+)?\$guarded\s*=")


def scan_d4_fillable(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    model_files = [f for f in files if (
        "/Models/" in str(f)
        and not f.name.endswith(("Observer.php", "Policy.php", "Factory.php", "Pivot.php"))
    )]

    for fp in model_files:
        content = read_file(fp)
        if not content:
            continue
        if "extends Pivot" in content:
            continue
        if re.search(r"^\s*(?:final\s+)?(?:abstract\s+)?class\s", content, re.M) is None:
            continue
        if re.search(r"^\s*(?:final\s+)?abstract\s+class\s", content, re.M):
            continue
        if re.search(r"^\s*(?:abstract\s+)?trait\s", content, re.M):
            continue
        if is_vendor_model_extension(content):
            continue
        rel = relative_path(fp)
        has_attribute = bool(RE_FILLABLE_ATTR.search(content))
        has_fillable_prop = bool(RE_FILLABLE_PROPERTY.search(content))
        has_guarded_prop = bool(RE_GUARDED_PROPERTY.search(content))

        if has_fillable_prop:
            findings.append(Finding(
                id=f"D4-{len(findings)+1:03d}",
                rule="D4",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Model uses legacy $fillable property",
                suggestion="Replace $fillable with #[Fillable] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#6-fillable-attribute-convention",
            ))
        if has_guarded_prop:
            findings.append(Finding(
                id=f"D4-{len(findings)+1:03d}",
                rule="D4",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Model uses legacy $guarded property",
                suggestion="Replace $guarded with #[Fillable] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#6-fillable-attribute-convention",
            ))
        if not has_attribute and not has_fillable_prop and not has_guarded_prop:
            findings.append(Finding(
                id=f"D4-{len(findings)+1:03d}",
                rule="D4",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Model missing #[Fillable] attribute",
                suggestion="Add #[Fillable([...])] attribute (PHP 8.4)",
                reference="docs/architecture/model-pattern.md#6-fillable-attribute-convention",
            ))
    return findings


# ─── D5: No raw request input to create/update ─────────────────────────────

RE_RAW_REQUEST_MASS_ASSIGN = re.compile(
    r"(?:->\s*(?:create|update|firstOrCreate|updateOrCreate)\s*\(\s*"
    r"|\w+::\s*(?:create|firstOrCreate|updateOrCreate)\s*\(\s*)"
    r"\$request\s*->\s*(?:all|input|post|except|only)\s*\("
)


def scan_d5_raw_request(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if RE_RAW_REQUEST_MASS_ASSIGN.search(line):
                findings.append(Finding(
                    id=f"D5-{len(findings)+1:03d}",
                    rule="D5",
                    severity="high",
                    category="architecture",
                    file=relative_path(fp),
                    line=i,
                    message="Raw request input passed to create/update — must use validated DTO",
                    suggestion="Pass a validated DTO or $request->validated() instead",
                    reference="docs/conventions.md#33-mass-assignment-protection",
                ))
    return findings


# ─── D6: Foreign keys must declare onDelete/onUpdate ───────────────────────

RE_FOREIGN_DECL = re.compile(r"->\s*foreign\s*\(|->\s*constrained\s*\(")
RE_FK_ACTIONS = re.compile(
    r"(?:onDelete|onUpdate|cascadeOnDelete|nullOnDelete|restrictOnDelete"
    r"|setNullOnDelete|cascadeOnUpdate|restrictOnUpdate|noActionOnDelete|noActionOnUpdate)"
)


def scan_d6_foreign_keys(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    migration_files = sorted((ROOT / "database" / "migrations").rglob("*.php"))

    for fp in migration_files:
        content = read_file(fp)
        if not content:
            continue
        for match in RE_FOREIGN_DECL.finditer(content):
            end = content.find(";", match.end())
            statement = content[match.start():end if end != -1 else match.end() + 80]
            if RE_FK_ACTIONS.search(statement):
                continue
            findings.append(Finding(
                id=f"D6-{len(findings)+1:03d}",
                rule="D6",
                severity="medium",
                category="convention",
                file=relative_path(fp),
                line=line_of(content, match.start()),
                message="Foreign key missing explicit onDelete/onUpdate behavior",
                suggestion="Chain ->onDelete('cascade') and ->onUpdate('cascade') (or nullOnDelete/restrictOnDelete)",
                reference="docs/conventions.md#7-migrations-factories-seeders",
            ))
    return findings


# ─── Livewire layer checks ─────────────────────────────────────────────────

RE_LIVEWIRE_DB_TRANSACTION = re.compile(r"DB::\s*(?:transaction|beginTransaction|commit|rollBack)\s*\(")
RE_LIVEWIRE_NEW_ACTION = re.compile(r"new\s+\w*Action\s*\(")
RE_MARY_TOAST = re.compile(r"\$this\s*->\s*(?:success|error|warning|info)\s*\(")


def scan_livewire_layer(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []
    livewire_files = [f for f in files if "/Livewire/" in str(f)]

    for fp in livewire_files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            if RE_LIVEWIRE_DB_TRANSACTION.search(line):
                findings.append(Finding(
                    id=f"LW-{len(findings)+1:03d}",
                    rule="LW_TX",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="DB transaction started inside Livewire component",
                    suggestion="Move transactional work into a Command/Process Action",
                    reference="docs/architecture/livewire-pattern.md",
                ))
            if RE_LIVEWIRE_NEW_ACTION.search(line):
                findings.append(Finding(
                    id=f"LW-{len(findings)+1:03d}",
                    rule="LW_NEW_ACTION",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="Action instantiated with new in Livewire component",
                    suggestion="Inject the Action via constructor/method injection instead",
                    reference="docs/architecture/livewire-pattern.md",
                ))
            if RE_MARY_TOAST.search(line):
                findings.append(Finding(
                    id=f"LW-{len(findings)+1:03d}",
                    rule="LW_TOAST",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=i,
                    message="maryUI toast helper used — use flasher instead",
                    suggestion="Use flasher() (php-flasher) for flash messages",
                    reference="docs/architecture/livewire-pattern.md",
                ))
    return findings


# ─── Performance rules ─────────────────────────────────────────────────────

RE_MODEL_ALL = re.compile(r"(?<![\\\w])\b\w+::\s*all\s*\(")
RE_FOREACH_LAZY = re.compile(r"@foreach\s*\(\s*\$[\w]+->[\w]+(?:\s+as\s+|\s*=>\s*)")
RE_COUNT_ZERO = re.compile(r"->\s*count\s*\(\s*\)\s*>\s*0")
RE_COUNT_FUNC = re.compile(r"count\s*\(\s*\$[\w>-]+->[\w]+\s*\)\s*>\s*0")


def scan_performance(files: list[Path], module: str | None, model_names: set[str]) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        rel = relative_path(fp)
        for i, line in enumerate(content.split("\n"), 1):
            if is_comment_or_doc(line):
                continue
            match = RE_MODEL_ALL.search(line)
            if match:
                class_name = match.group(0).split("::", 1)[0].lstrip("\\")
                if class_name in model_names:
                    findings.append(Finding(
                    id=f"P-{len(findings)+1:03d}",
                    rule="P2",
                    severity="medium",
                    category="performance",
                    file=rel,
                    line=i,
                    message="Model::all() loads every row without column selection",
                    suggestion="Use ->select([...])->get() and paginate/chunk large tables",
                    reference="docs/conventions.md#6-performance-conventions",
                ))
                continue
            if RE_COUNT_ZERO.search(line) or RE_COUNT_FUNC.search(line):
                findings.append(Finding(
                    id=f"P-{len(findings)+1:03d}",
                    rule="P5",
                    severity="low",
                    category="performance",
                    file=rel,
                    line=i,
                    message="count() > 0 — use exists() for a cheaper existence check",
                    suggestion="Replace ->count() > 0 with ->exists()",
                    reference="docs/conventions.md#6-performance-conventions",
                ))
        if fp.name.endswith(".blade.php"):
            for i, line in enumerate(content.split("\n"), 1):
                if RE_FOREACH_LAZY.search(line):
                    findings.append(Finding(
                        id=f"P-{len(findings)+1:03d}",
                        rule="P1",
                        severity="high",
                        category="performance",
                        file=rel,
                        line=i,
                        message="Loop iterates a lazy-loaded relationship (potential N+1)",
                        suggestion="Eager load the relationship with ->with('{relation}')",
                        reference="docs/conventions.md#53-eager-loading-convention",
                    ))
    return findings


# ─── Report ─────────────────────────────────────────────────────────────────

RULES = [
    "C1", "C2", "C3", "C4", "C5", "C6", "C7", "C8",
    "D1", "D2", "D3", "D4", "D5", "D6",
    "LW_TX", "LW_NEW_ACTION", "LW_TOAST",
    "P1", "P2", "P5",
]


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

    rules_checked = len(RULES)
    rules_passed = len(set(RULES) - set(f.rule for f in findings))
    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": rules_checked,
            "passed": rules_passed,
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
        description="Scan for C1-C8, D1-D6, Livewire-layer and performance violations",
    )
    parser.add_argument("--module", "-m", help="Target specific module")
    parser.add_argument("--output", "-o", type=Path, help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json")
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
    models = collect_model_classes(find_php_files(None))

    findings: list[Finding] = []
    findings.extend(scan_c1_livewire_mutations(files, args.module, models))
    findings.extend(scan_c2_service_locator(files, args.module))
    findings.extend(scan_c3_raw_sql(files, args.module))
    findings.extend(scan_c4_inline_cache(files, args.module))
    findings.extend(scan_c5_entity_imports(files, args.module))
    findings.extend(scan_c6_dto_imports(files, args.module))
    findings.extend(scan_c7_action_params(files, args.module))
    findings.extend(scan_c8_runtime_exception(files, args.module))
    findings.extend(scan_d1_strict_types(files, args.module))
    findings.extend(scan_d2_debug_calls(files, args.module))
    findings.extend(scan_d3_hardcoded_strings(files, args.module))
    findings.extend(scan_d4_fillable(files, args.module))
    findings.extend(scan_d5_raw_request(files, args.module))
    findings.extend(scan_d6_foreign_keys(files, args.module))
    findings.extend(scan_livewire_layer(files, args.module))
    findings.extend(scan_performance(files, args.module, models))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {"total_php_files": total_php, "model_classes": len(models)},
    )

    # Uniform output via _output.py
    exit_code = handle_output(result, args)
    if exit_code:
        sys.exit(exit_code)


if __name__ == "__main__":
    main()
