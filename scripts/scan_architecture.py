#!/usr/bin/env python3
"""Scan codebase architecture — component counts per module, submodule structure."""

from __future__ import annotations

import argparse
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
ROUTES_DIR = ROOT / "routes"
CONFIG_DIR = ROOT / "config"
LANG_DIR = ROOT / "lang"

MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Console", "Core", "Document", "Enrollment", "Evaluation",
    "Guidance", "Incident", "Jobs", "Journals", "Partners",
    "Program", "Providers", "Reports", "Settings", "Setup",
    "SysAdmin", "User",
]

ARCH_DIRS = {
    "actions": "Actions", "entities": "Entities", "dtos": "Data",
    "enums": "Enums", "livewire": "Livewire", "policies": "Policies",
    "events": "Events", "listeners": "Listeners", "services": "Services",
    "models": "Models",
}


def count_php_files(d: Path) -> int:
    return sum(1 for _ in d.rglob("*.php")) if d.exists() else 0


def find_submodules(module_dir: Path) -> list[str]:
    return sorted(
        e.name for e in module_dir.iterdir()
        if e.is_dir() and not e.name.startswith(("_", "."))
    )


def scan_module(module_name: str) -> dict:
    module_dir = APP_DIR / module_name
    if not module_dir.exists():
        return {}

    submodules = find_submodules(module_dir)
    components = {}
    for key, dirname in ARCH_DIRS.items():
        total = sum(count_php_files(sd) for sd in module_dir.rglob(dirname) if sd.is_dir())
        components[key] = total

    route_file = ROUTES_DIR / "web" / f"{module_name.lower()}.php"
    config_file = CONFIG_DIR / f"{module_name.lower()}.php"
    lang_en = LANG_DIR / "en" / f"{module_name.lower()}.php"

    return {
        "submodules": submodules,
        "components": components,
        "has_routes_file": route_file.exists(),
        "has_config_file": config_file.exists(),
        "has_lang_files": lang_en.exists(),
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--module", help="Scan single module only")
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    modules = {}
    names = [args.module] if args.module else MODULES
    for name in names:
        data = scan_module(name)
        if data:
            modules[name] = data

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "modules": modules,
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-architecture.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Written to {out}")
    print(f"  Modules scanned: {len(modules)}")


if __name__ == "__main__":
    main()
