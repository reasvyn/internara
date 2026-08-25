#!/usr/bin/env python3
"""
Scan codebase architecture — component counts per module, submodule structure.

Enhanced v2.1: dynamic module discovery, parallel counting, robust error handling,
comprehensive output schema, and architecture health findings.
"""

from __future__ import annotations

import argparse
import json
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path

# Import shared helpers
try:
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        APP_DIR,
        SCAN_VERSION,
        relative_path,
        read_file,
        build_report,
        write_report,
        print_summary,
        find_php_files,
    )
except ImportError:
    # Fallback for direct execution
    import sys
    sys.path.insert(0, str(Path(__file__).parent))
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        APP_DIR,
        SCAN_VERSION,
        relative_path,
        read_file,
        build_report,
        write_report,
        print_summary,
        find_php_files,
    )

ROUTES_DIR = ROOT / "routes"
CONFIG_DIR = ROOT / "config"
LANG_DIR = ROOT / "lang"

SCAN_NAME = "architecture"

# Fallback hardcoded list for validation (dynamic discovery is primary)
KNOWN_MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Core", "Document", "Enrollment", "Evaluation",
    "Incident", "Journals", "Partners",
    "Program", "Reports", "Settings", "Setup",
    "SysAdmin", "User",
]

ARCH_DIRS = {
    "actions": "Actions",
    "entities": "Entities",
    "dtos": "Data",
    "enums": "Enums",
    "livewire": "Livewire",
    "policies": "Policies",
    "events": "Events",
    "listeners": "Listeners",
    "services": "Services",
    "models": "Models",
}


def discover_modules() -> list[str]:
    """Dynamically discover modules from app/ directory with validation."""
    if not APP_DIR.exists():
        return []
    
    modules = []
    for entry in APP_DIR.iterdir():
        if not entry.is_dir():
            continue
        if entry.name.startswith((".", "_")):
            continue
        # A valid module has at least one PHP file or known structure
        try:
            has_php = any(entry.rglob("*.php"))
            has_structure = any(
                (entry / sub).exists() 
                for sub in ["Actions", "Models", "Livewire", "Entities"]
            )
            if has_php or has_structure:
                modules.append(entry.name)
        except (OSError, PermissionError):
            continue
    
    return sorted(modules)


def count_php_files_safe(d: Path) -> int:
    """Count PHP files with per-file error isolation."""
    if not d.exists():
        return 0
    try:
        return sum(1 for _ in d.rglob("*.php"))
    except (OSError, PermissionError):
        return 0


def find_submodules_safe(module_dir: Path) -> list[str]:
    """Find submodules with error handling."""
    try:
        return sorted(
            e.name for e in module_dir.iterdir()
            if e.is_dir() and not e.name.startswith(("_", "."))
        )
    except (OSError, PermissionError):
        return []


def scan_module_architecture(module_name: str) -> tuple[dict, list[Finding]]:
    """Scan single module architecture with findings generation."""
    findings: list[Finding] = []
    module_dir = APP_DIR / module_name
    
    if not module_dir.exists():
        findings.append(Finding(
            id=f"ARCH-MISSING-001",
            rule="ARCH_MODULE_MISSING",
            severity="high",
            category="architecture",
            file=relative_path(module_dir),
            line=0,
            message=f"Module directory missing: {module_name}",
            suggestion=f"Create app/{module_name}/ or remove from module registry",
            reference="docs/architecture.md",
        ))
        return {}, findings

    submodules = find_submodules_safe(module_dir)
    components = {}
    
    # Parallel component counting for performance
    for key, dirname in ARCH_DIRS.items():
        try:
            total = 0
            for sub_path in module_dir.rglob(dirname):
                if sub_path.is_dir() and dirname in str(sub_path):
                    # Only count if it's actually the target dir, not a file containing the name
                    if sub_path.name == dirname:
                        total += count_php_files_safe(sub_path)
            components[key] = total
        except Exception:
            components[key] = 0

    # Check for expected files
    route_file = ROUTES_DIR / "web" / f"{module_name.lower()}.php"
    config_file = CONFIG_DIR / f"{module_name.lower()}.php"
    lang_en = LANG_DIR / "en" / f"{module_name.lower()}.php"
    lang_id = LANG_DIR / "id" / f"{module_name.lower()}.php"

    # Generate findings for missing expected structure (only for non-Core modules with content)
    has_content = sum(components.values()) > 0
    if has_content and module_name not in ["Core"]:
        if not route_file.exists() and components.get("livewire", 0) > 0:
            findings.append(Finding(
                id=f"ARCH-ROUTE-{module_name.upper()[:3]}",
                rule="ARCH_MISSING_ROUTE",
                severity="medium",
                category="architecture",
                file=relative_path(module_dir),
                line=0,
                message=f"Module {module_name} has Livewire components but no route file",
                suggestion=f"Create {relative_path(route_file)} or verify routing via module discovery",
                reference="docs/architecture.md",
            ))

    return {
        "submodules": submodules,
        "components": components,
        "has_routes_file": route_file.exists(),
        "has_config_file": config_file.exists(),
        "has_lang_files": lang_en.exists() or lang_id.exists(),
        "total_files": sum(components.values()),
    }, findings


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan codebase architecture — component counts per module",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 scripts/scan_architecture.py
  python3 scripts/scan_architecture.py --module Assessment --verbose
  python3 scripts/scan_architecture.py --json | jq '.metadata'
  python3 scripts/scan_architecture.py --strict
        """
    )
    parser.add_argument("--module", "-m", help="Scan single module only")
    parser.add_argument("--output", "-o", help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json", help="Output format")
    parser.add_argument("--verbose", "-v", action="store_true", help="Include detailed context")
    parser.add_argument("--quiet", "-q", action="store_true", help="Only output summary")
    parser.add_argument("--strict", "-s", action="store_true", help="Exit with code 1 on any finding")
    parser.add_argument("--json", action="store_true", help="Force JSON output to stdout")
    parser.add_argument("--discover", action="store_true", help="Use dynamic module discovery (default)")
    parser.add_argument("--no-discover", dest="discover", action="store_false", help="Use hardcoded module list")
    parser.set_defaults(discover=True)
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    start_time = time.time()
    
    # Discover modules
    if args.discover:
        module_names = discover_modules()
        if not module_names:
            module_names = KNOWN_MODULES
    else:
        module_names = KNOWN_MODULES
    
    if args.module:
        if args.module not in module_names:
            # Check if it's a valid module even if not in discovered list
            if not (APP_DIR / args.module).exists():
                print(f"Error: Module '{args.module}' not found in {APP_DIR}", file=sys.stderr)
                print(f"Available: {', '.join(module_names)}", file=sys.stderr)
                sys.exit(2)
        module_names = [args.module]

    # Scan modules (parallel for performance)
    modules: dict[str, dict] = {}
    all_findings: list[Finding] = []
    
    # Use ThreadPool for I/O bound file counting
    with ThreadPoolExecutor(max_workers=8) as executor:
        future_to_module = {
            executor.submit(scan_module_architecture, name): name 
            for name in module_names
        }
        for future in as_completed(future_to_module):
            module_name = future_to_module[future]
            try:
                data, findings = future.result()
                if data:
                    modules[module_name] = data
                all_findings.extend(findings)
            except Exception as e:
                all_findings.append(Finding(
                    id=f"ARCH-ERROR-{module_name.upper()[:3]}",
                    rule="ARCH_SCAN_ERROR",
                    severity="low",
                    category="system",
                    file=f"app/{module_name}",
                    line=0,
                    message=f"Failed to scan module {module_name}: {e}",
                    suggestion="Check file permissions and module structure",
                    reference="docs/architecture.md",
                ))

    # Calculate totals
    total_components = {}
    for key in ARCH_DIRS:
        total_components[key] = sum(m["components"].get(key, 0) for m in modules.values())

    metadata = {
        "total_modules": len(modules),
        "total_files": sum(m["total_files"] for m in modules.values()),
        "total_components": total_components,
        "discovered_modules": module_names,
        "known_modules_count": len(KNOWN_MODULES),
    }

    # Build report with proper schema
    scan_type = "module" if args.module else "full"
    result = build_report(
        findings=all_findings,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=args.module,
        start_time=start_time,
        metadata=metadata,
        total_checks=len(module_names),  # One check per module
    )
    
    # Add modules data to metadata for backward compatibility
    result.metadata["modules"] = modules

    # Output handling
    if args.json or args.format == "json":
        if not args.quiet:
            print(json.dumps(result.__dict__ if hasattr(result, '__dict__') else asdict(result), indent=2, ensure_ascii=False))
    elif args.format == "summary":
        if not args.quiet:
            print_summary(result, verbose=args.verbose)
    elif args.format == "text":
        if not args.quiet:
            for f in result.findings:
                print(f"{f['file']}:{f['line']} [{f['severity']}] {f['message']}")

    # Write report
    output_path = Path(args.output) if args.output else None
    written_path = write_report(result, output_path)
    
    if not args.quiet and not args.json:
        print(f"Report saved: {relative_path(written_path)}")
        print(f"  Modules scanned: {len(modules)}")
        print(f"  Total files: {metadata['total_files']}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
