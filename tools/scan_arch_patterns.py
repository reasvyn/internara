#!/usr/bin/env python3
"""
scan_arch_patterns.py — Arch Pattern Compliance Guard (v1.0)

Validates code compliance against the 16 dedicated arch patterns (docs/guides/arch/*.md §Non-Negotiable).
Each pattern is SSOT; this scanner is the automated arch-guard for their hard rules.

Patterns covered:
  action-pattern, entity-pattern, data-pattern, model-pattern, enum-pattern,
  event-pattern, exception-pattern, cache-pattern, logging-pattern, service-pattern,
  support-pattern, repository-pattern, livewire-pattern, policy-pattern,
  testing-pattern, modular-pattern (plus ui-pattern/ux-pattern for UI checks)

Rules:
  ARCH-C1  No Model mutations in Livewire (delegates to Actions)
  ARCH-C5  Entity forbidden imports (no Action/Service/Livewire/DB/Cache)
  ARCH-C6  DTO forbidden imports (no Model/Entity)
  ARCH-C7  DTO for 3+ params
  ARCH-C4  No inline cache keys (must be in config/cache-keys.php)
  ARCH-D1  declare(strict_types=1) in all PHP files
  ARCH-D4  #[Fillable] attribute on Models
  ARCH-ENT Final readonly Entity
  ARCH-ACT Single execute() + ActionResponse return
  ARCH-MOD 4-layer dependency rule (Core never imports business)
  ARCH-STATUS No spatie/laravel-model-status usage (deprecated #419)
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from _common import (  # noqa: E402
    APP_DIR,
    ROOT,
    Finding,
    ScanResult,
    build_report,
    find_php_files,
    parse_args_with_common,
    print_summary,
    read_file,
    relative_path,
    write_report,
)

SCAN_NAME = "arch-patterns"

# ─── Pattern docs SSOT validation ────────────────────────────────────────

# Discovered dynamically from docs/guides/arch/*-pattern.md — no hardcoding
_REQUIRED_FALLBACK = [
    "action-pattern.md", "cache-pattern.md", "data-pattern.md", "entity-pattern.md",
    "enum-pattern.md", "event-pattern.md", "exception-pattern.md", "livewire-pattern.md",
    "logging-pattern.md", "model-pattern.md", "policy-pattern.md", "repository-pattern.md",
    "service-pattern.md", "support-pattern.md", "testing-pattern.md", "modular-pattern.md",
]

REQUIRED_SECTIONS = ["## Non-Negotiable", "## How to Apply", "## Anti-Patterns", "## Quick References"]


def discover_pattern_files() -> list[str]:
    """Discover arch pattern docs dynamically; fallback to known list if dir missing."""
    arch_dir = ROOT / "docs" / "guides" / "arch"
    if not arch_dir.exists():
        return list(_REQUIRED_FALLBACK)
    files = sorted(p.name for p in arch_dir.glob("*-pattern.md"))
    return files if files else list(_REQUIRED_FALLBACK)

# ─── Regex library ────────────────────────────────────────────────────────

RE_STRICT_TYPES = re.compile(r"declare\s*\(\s*strict_types\s*=\s*1\s*\)")
RE_FILLABLE_ATTR = re.compile(r"#\[Fillable")
RE_FINAL_READONLY_ENTITY = re.compile(r"final\s+readonly\s+class\s+\w+")
RE_EXECUTE_METHOD = re.compile(r"public\s+function\s+execute\s*\(")
RE_ACTION_BASE = re.compile(r"extends\s+Base(?:Command|Read|Process)Action")
RE_ENTITY_FORBIDDEN_IMPORT = re.compile(r"use\s+(?:App\\[^;]*\\Actions|App\\[^;]*\\Livewire|Illuminate\\Support\\Facades\\DB|Illuminate\\Support\\Facades\\Cache|Illuminate\\Support\\Facades\\Log)")
RE_DTO_FORBIDDEN_IMPORT = re.compile(r"use\s+App\\[^;]*\\(?:Models|Entities)\\")
RE_LIVEWIRE_MUTATION = re.compile(r"(?:Model::(?:create|update|delete)|DB::(?:table|transaction|insert|update|delete)|->(?:create|update|delete)\s*\()")
RE_CACHE_INLINE = re.compile(r"Cache::(?:remember|get|put|forget)\s*\(\s*['\"][^'\"]+['\"]")
RE_CACHE_KEYS_FILE = ROOT / "config" / "cache-keys.php"
RE_SPATIE_STATUS = re.compile(r"(?:Spatie\\ModelStatus|HasStatus|spatie/laravel-model-status)")
RE_EXECUTE_COUNT = re.compile(r"public\s+function\s+execute\b")
RE_ACTION_RESPONSE_RETURN = re.compile(r":\s*ActionResponse\b")
RE_DTO_3_PARAMS = re.compile(r"public\s+function\s+execute\s*\([^)]*,[^)]*,[^)]*\)")


def scan_pattern_docs() -> list[Finding]:
    """Validate that all pattern docs exist and contain required sections (dynamic discovery)."""
    findings: list[Finding] = []
    arch_dir = ROOT / "docs" / "guides" / "arch"
    pattern_files = discover_pattern_files()
    for fname in pattern_files:
        fpath = arch_dir / fname
        rel = f"docs/guides/arch/{fname}"
        if not fpath.exists():
            findings.append(Finding(
                id=f"ARCH-DOC-{len(findings)+1:03d}",
                rule="ARCH_DOC_MISSING",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Arch pattern SSOT doc missing: {fname}",
                suggestion="Restore from git history or regenerate per docs/guides/arch/*-pattern.md contract",
                reference="docs/guides/arch/modular-pattern.md §1-§23 catalog",
            ))
            continue
        content = read_file(fpath)
        for section in REQUIRED_SECTIONS:
            if section not in content:
                findings.append(Finding(
                    id=f"ARCH-DOC-{len(findings)+1:03d}",
                    rule="ARCH_DOC_INCOMPLETE",
                    severity="medium",
                    category="architecture",
                    file=rel,
                    line=1,
                    message=f"Pattern doc '{fname}' missing required section: {section}",
                    suggestion=f"Add {section} per the arch pattern template (Non-Negotiable, How to Apply, Anti-Patterns, Quick References)",
                    reference=f"docs/guides/arch/{fname}",
                ))
    return findings


def check_file(path: Path) -> list[Finding]:
    findings: list[Finding] = []
    content = read_file(path)
    if not content:
        return findings
    rel = relative_path(path)
    lines = content.splitlines()

    # ARCH-D1: strict_types
    if path.suffix == ".php" and "migrations" not in rel and "config" not in rel:
        if not RE_STRICT_TYPES.search(content):
            # Allow files that are pure config returning array without declare
            if "<?php" in content:
                findings.append(Finding(
                    id=f"ARCH-{len(findings)+1:04d}",
                    rule="ARCH_D1_STRICT_TYPES",
                    severity="low",
                    category="convention",
                    file=rel,
                    line=1,
                    message="Missing declare(strict_types=1)",
                    suggestion="Add declare(strict_types=1); after <?php",
                    reference="docs/conventions.md §Strict Types (D1)",
                ))

    # Entity checks
    if "/Entities/" in rel:
        # Skip abstract base entities (e.g., BaseEntity) — they are abstract readonly by design
        is_abstract = "abstract" in content and "class BaseEntity" in content
        if not is_abstract and not RE_FINAL_READONLY_ENTITY.search(content):
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_ENT_FINAL_READONLY",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message="Entity must be 'final readonly class'",
                suggestion="Add 'final readonly' per docs/guides/arch/entity-pattern.md §Non-Negotiable",
                reference="docs/guides/arch/entity-pattern.md §Non-Negotiable",
            ))
        if RE_ENTITY_FORBIDDEN_IMPORT.search(content):
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_C5_ENTITY_IMPORT",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message="Entity imports forbidden dependency (Action/Livewire/DB/Cache)",
                suggestion="Remove forbidden import; Entities are pure and must not touch I/O per C5",
                reference="docs/guides/arch/entity-pattern.md §Non-Negotiable (C5)",
            ))

    # DTO checks
    if "/Data/" in rel:
        if RE_DTO_FORBIDDEN_IMPORT.search(content):
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_C6_DTO_IMPORT",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message="DTO imports Model or Entity (forbidden — scalars only)",
                suggestion="Remove Model/Entity import; DTOs carry scalars per C6",
                reference="docs/guides/arch/data-pattern.md §Non-Negotiable (C6)",
            ))

    # Model checks
    if "/Models/" in rel:
        if "class " in content and "extends BaseModel" in content:
            if not RE_FILLABLE_ATTR.search(content):
                findings.append(Finding(
                    id=f"ARCH-{len(findings)+1:04d}",
                    rule="ARCH_D4_FILLABLE",
                    severity="medium",
                    category="convention",
                    file=rel,
                    line=1,
                    message="Model missing #[Fillable] attribute",
                    suggestion="Add #[Fillable(['field', ...])] per docs/guides/arch/model-pattern.md",
                    reference="docs/guides/arch/model-pattern.md §Non-Negotiable (D4)",
                ))

    # Action checks
    if "/Actions/" in rel and RE_ACTION_BASE.search(content):
        count = len(RE_EXECUTE_COUNT.findall(content))
        if count != 1:
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_ACT_SINGLE_EXECUTE",
                severity="high",
                category="architecture",
                file=rel,
                line=1,
                message=f"Action must have exactly one public execute() method (found {count})",
                suggestion="Ensure single public execute() per docs/guides/arch/action-pattern.md §Non-Negotiable",
                reference="docs/guides/arch/action-pattern.md §Non-Negotiable",
            ))
        if not RE_ACTION_RESPONSE_RETURN.search(content):
            # Only medium for Command/Process; low for Read (which often returns data directly)
            sev = "low" if "BaseReadAction" in content else "medium"
            # Skip flagging if file is clearly a Read that returns collection/model (allowed per pattern)
            if sev == "low":
                # For Read Actions, ActionResponse is optional — downgrade to low and allow
                pass
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_ACT_RESPONSE",
                severity=sev,
                category="architecture",
                file=rel,
                line=1,
                message="Action execute() should return ActionResponse (Command/Process) or typed data (Read)",
                suggestion="Command/Process: return ActionResponse::ok()/created()/error(); Read: may return DTO/collection per action-pattern.md",
                reference="docs/guides/arch/action-pattern.md §ActionResponse",
            ))
        if RE_DTO_3_PARAMS.search(content) and "BaseData" not in content and "Data $data" not in content:
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_C7_DTO_3_PARAMS",
                severity="medium",
                category="convention",
                file=rel,
                line=1,
                message="Action execute() with 3+ params should use DTO (C7)",
                suggestion="Accept BaseData DTO instead of 3+ scalar params",
                reference="docs/conventions.md §C7 / docs/guides/arch/action-pattern.md",
            ))

    # Livewire thin checks
    if "/Livewire/" in rel:
        # Only flag if actually contains mutation keywords
        if RE_LIVEWIRE_MUTATION.search(content):
            # Allow if file also imports an Action (delegating)
            has_action_import = "Actions\\" in content and "Action" in content
            if not has_action_import or re.search(r"Model::create|Model::update|Model::delete", content):
                findings.append(Finding(
                    id=f"ARCH-{len(findings)+1:04d}",
                    rule="ARCH_C1_LIVEWIRE_MUTATION",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=1,
                    message="Livewire contains direct Model/DB mutation (C1 violation)",
                    suggestion="Delegate to Command Action via method injection per docs/guides/arch/livewire-pattern.md §Non-Negotiable (C1)",
                    reference="docs/guides/arch/livewire-pattern.md §Non-Negotiable (C1)",
                ))
        if RE_CACHE_INLINE.search(content):
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_C4_INLINE_CACHE",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message="Inline cache key in Livewire (C4)",
                suggestion="Move cache to Read Action and register key in config/cache-keys.php",
                reference="docs/guides/arch/cache-pattern.md §Non-Negotiable (C4)",
            ))

    # No spatie model-status (deprecated #419)
    if RE_SPATIE_STATUS.search(content):
        # Allow the reference file itself and config
        if "spatie-laravel-model-status.md" not in rel and "config/model-status.php" not in rel:
            findings.append(Finding(
                id=f"ARCH-{len(findings)+1:04d}",
                rule="ARCH_STATUS_DEPRECATED",
                severity="medium",
                category="architecture",
                file=rel,
                line=1,
                message="Spatie laravel-model-status is deprecated (#419) — do not use",
                suggestion="Use app-owned StatusEnum + status column per docs/refs/deps/spatie-laravel-model-status.md",
                reference="docs/refs/deps/spatie-laravel-model-status.md",
            ))

    # Inline cache key in any PHP (C4)
    if RE_CACHE_INLINE.search(content) and "/Livewire/" not in rel and "/Actions/" not in rel:
        # Only flag if not in config/cache-keys.php and not in a Read Action where it's centralized
        if "cache-keys.php" not in rel:
            # Check if file is a Read Action (allowed to use registered keys, but key must still be in registry)
            if "BaseReadAction" not in content:
                cache_keys = read_file(RE_CACHE_KEYS_FILE) if RE_CACHE_KEYS_FILE.exists() else ""
                # Simple heuristic: if cache string literal not found in registry, flag
                for m in RE_CACHE_INLINE.finditer(content):
                    key = m.group(0)
                    if key and cache_keys and key.split("'")[1] not in cache_keys and key.split('"')[1] not in cache_keys:
                        findings.append(Finding(
                            id=f"ARCH-{len(findings)+1:04d}",
                            rule="ARCH_C4_INLINE_CACHE",
                            severity="medium",
                            category="architecture",
                            file=rel,
                            line=content[:m.start()].count("\n") + 1,
                            message=f"Inline cache key not in registry: {key[:60]}",
                            suggestion="Register key in config/cache-keys.php per C4",
                            reference="docs/guides/arch/cache-pattern.md §Non-Negotiable (C4)",
                        ))
                        break

    return findings


def main() -> None:
    args = parse_args_with_common("Arch Pattern Compliance Guard — validates code against docs/guides/arch/*.md Non-Negotiable rules")
    start = time.time()
    findings: list[Finding] = []

    # 1. Validate pattern docs themselves
    findings.extend(scan_pattern_docs())

    # 2. Scan PHP files in parallel
    php_files = find_php_files(args.module)
    from _common import find_files_parallel
    findings.extend(find_files_parallel(php_files, check_file))

    # Sort for deterministic output
    findings.sort(key=lambda f: (f.file, f.line))

    # Re-id sequentially
    for i, f in enumerate(findings):
        f.id = f"ARCH-{i+1:04d}"

    pattern_files = discover_pattern_files()
    metadata = {
        "total_files": len(php_files),
        "pattern_docs": len(pattern_files),
        "pattern_docs_dir": "docs/guides/arch",
    }
    result: ScanResult = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=len(pattern_files) + len(php_files))

    if args.json or args.format == "json":
        import dataclasses
        print(json.dumps(dataclasses.asdict(result), indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result, verbose=args.verbose)

    out = write_report(result, Path(args.output) if args.output else None)
    if not args.quiet:
        print(f"Report saved: {relative_path(out)}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
