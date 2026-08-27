#!/usr/bin/env python3
"""
scan_module_boundaries.py — Module Boundary Enforcement (v1.0)

Enforces the Modular Monolith 4-layer rule and cross-module communication contracts per:
  docs/guides/arch/modular-pattern.md §1.1-§1.4, §1.6
  docs/architecture.md §4-Layer Model

Rules:
  MOD-CORE  Core must not import business modules (Core depends on nothing business)
  MOD-XMOD  Business modules must not import each other's internals (only public surface: Actions, Entities, Data, Events, Enums, Contracts)
  MOD-MODEL Cross-module Model imports should be via Action delegation, not direct Model use
  MOD-CIRC  Circular module dependencies
"""

from __future__ import annotations

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

SCAN_NAME = "module-boundaries"

# Discovered dynamically — fallback only for docs reference if app/ unreadable
_FALLBACK_MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Document", "Enrollment", "Evaluation", "Incident", "Journals",
    "Partners", "Program", "Reports", "Settings", "Setup", "SysAdmin", "User",
]


def discover_business_modules() -> list[str]:
    """Discover business modules from app/ dynamically; Core is excluded."""
    if not APP_DIR.exists():
        return list(_FALLBACK_MODULES)
    mods: list[str] = []
    for entry in APP_DIR.iterdir():
        if not entry.is_dir() or entry.name.startswith((".", "_")):
            continue
        if entry.name == "Core":
            continue
        # Valid module if contains php or known structure
        try:
            has_php = any(entry.rglob("*.php"))
            has_struct = any((entry / sub).exists() for sub in ["Actions", "Models", "Livewire", "Entities", "Data"])
        except Exception:
            has_php = False
            has_struct = False
        if has_php or has_struct:
            mods.append(entry.name)
    return sorted(mods) if mods else list(_FALLBACK_MODULES)

# What is considered public surface of a module (allowed to import cross-module)
PUBLIC_SURFACE = {"Actions", "Entities", "Data", "Events", "Enums", "Contracts", "Exceptions", "Models"}

RE_USE = re.compile(r"^\s*use\s+App\\(\w+)\\([^;]+);", re.M)
RE_MODEL_IMPORT = re.compile(r"use\s+App\\(\w+)\\Models\\(\w+)")


def is_core_file(rel: str) -> bool:
    return rel.startswith("app/Core/")


def get_module_from_rel(rel: str, modules: list[str] | None = None) -> str | None:
    # app/{Module}/...
    mods = modules if modules is not None else discover_business_modules()
    parts = rel.split("/")
    if len(parts) >= 2 and parts[0] == "app":
        mod = parts[1]
        if mod in mods:
            return mod
    return None


def check_file(path: Path) -> list[Finding]:
    content = read_file(path)
    if not content:
        return []
    rel = relative_path(path)
    findings: list[Finding] = []

    modules = discover_business_modules()
    current_module = get_module_from_rel(rel, modules)
    is_core = is_core_file(rel)

    for m in RE_USE.finditer(content):
        imported_module = m.group(1)
        imported_rest = m.group(2).strip()
        imported_parts = imported_rest.split("\\")
        imported_sub = imported_parts[0] if imported_parts else ""

        line = content[:m.start()].count("\n") + 1

        # MOD-CORE: Core importing business
        if is_core and imported_module in modules:
            findings.append(Finding(
                id="MOD-0000",
                rule="MOD_CORE_IMPORT",
                severity="high",
                category="architecture",
                file=rel,
                line=line,
                message=f"Core imports business module '{imported_module}' — Core must depend on nothing business",
                suggestion="Move shared code to app/Core/Support or use an interface/contract in Core; business modules depend on Core, not vice versa",
                reference="docs/guides/arch/modular-pattern.md §1.2, docs/architecture.md §4-Layer",
                context={"import": f"App\\{imported_module}\\{imported_rest}"},
            ))

        # MOD-XMOD: Business importing other business internals
        if current_module and imported_module in modules and imported_module != current_module:
            # Check if import is via public surface — allow if any public layer appears in path (handles SubModule/Layer)
            is_public = any(f"\\{layer}\\" in f"\\{imported_rest}\\" or imported_rest.startswith(f"{layer}\\") or imported_rest == layer for layer in PUBLIC_SURFACE)
            # Also check submodule pattern: App\Module\SubModule\Layer — extract layer as 3rd segment if present
            parts = imported_rest.split("\\")
            layer = parts[1] if len(parts) >= 2 else imported_sub
            if not is_public and layer not in PUBLIC_SURFACE:
                findings.append(Finding(
                    id="MOD-0000",
                    rule="MOD_XMOD_INTERNAL",
                    severity="high",
                    category="architecture",
                    file=rel,
                    line=line,
                    message=f"Module '{current_module}' imports internal '{layer}' of '{imported_module}' — only public surface allowed",
                    suggestion=f"Import only via {', '.join(sorted(PUBLIC_SURFACE))}; for operations delegate to {imported_module} Actions; for shared types move to Core",
                    reference="docs/guides/arch/modular-pattern.md §1.4 Cross-Module Communication",
                    context={"import": f"App\\{imported_module}\\{imported_rest}", "current": current_module},
                ))
            # MOD-MODEL: direct cross-module Model import — informational (allowed for relations/type-hints, but prefer Action delegation for operations)
            if imported_sub == "Models":
                findings.append(Finding(
                    id="MOD-0000",
                    rule="MOD_XMOD_MODEL",
                    severity="low",
                    category="architecture",
                    file=rel,
                    line=line,
                    message=f"Cross-module Model import: '{current_module}' → '{imported_module}\\Models' (informational)",
                    suggestion=f"For operations delegate to {imported_module} Read/Command Action; direct Model reads are tolerated for relations/type-hints",
                    reference="docs/guides/arch/modular-pattern.md §1.4, docs/guides/arch/repository-pattern.md",
                    context={"import": f"App\\{imported_module}\\{imported_rest}"},
                ))

    return findings


def detect_circular() -> list[Finding]:
    """Lightweight circular dependency detection via import graph."""
    modules = discover_business_modules()
    findings: list[Finding] = []
    graph: dict[str, set[str]] = {m: set() for m in modules}
    php_files = find_php_files()

    for path in php_files:
        rel = relative_path(path)
        mod = get_module_from_rel(rel, modules)
        if not mod:
            continue
        content = read_file(path)
        for m in RE_USE.finditer(content):
            imported = m.group(1)
            if imported in modules and imported != mod:
                graph[mod].add(imported)

    # Simple cycle detection: if A→B and B→A (deduplicate unordered pairs)
    seen: set[tuple[str, str]] = set()
    for a in modules:
        for b in graph[a]:
            if a in graph.get(b, set()):
                key = tuple(sorted((a, b)))
                if key in seen:
                    continue
                seen.add(key)
                findings.append(Finding(
                    id="MOD-0000",
                    rule="MOD_CIRCULAR",
                    severity="medium",
                    category="architecture",
                    file=f"app/{a}",
                    line=1,
                    message=f"Circular dependency: {a} ↔ {b} (mutual imports)",
                    suggestion="Break the cycle: extract shared code to Core, or use events for fire-and-forget",
                    reference="docs/guides/arch/modular-pattern.md §1.4",
                    context={"a": a, "b": b},
                ))
    return findings


def main() -> None:
    args = parse_args_with_common("Module Boundary Enforcement — 4-layer rule + cross-module surface")
    start = time.time()
    findings: list[Finding] = []

    php_files = find_php_files(args.module)
    from _common import find_files_parallel
    findings.extend(find_files_parallel(php_files, check_file))
    if not args.module:
        findings.extend(detect_circular())

    findings.sort(key=lambda f: (f.file, f.line))
    for i, f in enumerate(findings):
        f.id = f"MOD-{i+1:04d}"

    modules = discover_business_modules()
    metadata = {"total_files": len(php_files), "modules": len(modules)}
    result = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=len(php_files))

    if args.json or args.format == "json":
        import dataclasses, json
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
