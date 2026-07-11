#!/usr/bin/env python3
"""Scan file inventory — counts and lines of code per module."""

from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
APP_DIR = ROOT / "app"
TESTS_DIR = ROOT / "tests"
VIEWS_DIR = ROOT / "resources" / "views"
LANG_DIR = ROOT / "lang"
CONFIG_DIR = ROOT / "config"
DB_DIR = ROOT / "database"
ROUTES_DIR = ROOT / "routes"

MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Console", "Core", "Document", "Enrollment", "Evaluation",
    "Guidance", "Incident", "Jobs", "Journals", "Partners",
    "Program", "Providers", "Reports", "Settings", "Setup",
    "SysAdmin", "User",
]


def count_files(d: Path, pattern: str = "*.php") -> int:
    return sum(1 for _ in d.rglob(pattern)) if d.exists() else 0


def count_lines(d: Path, pattern: str = "*.php") -> int:
    if not d.exists():
        return 0
    total = 0
    for f in d.rglob(pattern):
        try:
            total += len(f.read_text(encoding="utf-8", errors="replace").splitlines())
        except OSError:
            continue
    return total


def count_lang(locale: str) -> tuple[int, int]:
    d = LANG_DIR / locale
    if not d.exists():
        return 0, 0
    import re
    files = list(d.glob("*.php"))
    keys = 0
    for f in files:
        try:
            content = f.read_text(encoding="utf-8", errors="replace")
            keys += len(re.findall(r"""['"][\w.]+['"]\s*=>""", content))
        except OSError:
            continue
    return len(files), keys


def count_routes() -> int:
    import re
    total = 0
    for f in list(ROUTES_DIR.glob("*.php")) + list((ROUTES_DIR / "web").glob("*.php")):
        try:
            content = f.read_text(encoding="utf-8", errors="replace")
            total += len(re.findall(r"Route::(get|post|put|patch|delete|resource|apiResource|match|any|middleware|prefix|group|controller)\s*\(", content))
        except OSError:
            continue
    return total


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--module", help="Scan single module only")
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    print("Scanning files...")
    names = [args.module] if args.module else MODULES

    by_module = {}
    for name in names:
        mod_dir = APP_DIR / name
        test_dir = TESTS_DIR / name
        views = VIEWS_DIR / name.lower()
        by_module[name] = {
            "php": count_files(mod_dir),
            "tests": count_files(test_dir),
            "blade": count_files(views, "*.blade.php"),
            "loc": {
                "app": count_lines(mod_dir),
                "tests": count_lines(test_dir),
            },
        }

    lang_en_files, lang_en_keys = count_lang("en")
    lang_id_files, lang_id_keys = count_lang("id")

    totals = {
        "modules": len(names),
        "php_files": sum(m["php"] for m in by_module.values()),
        "test_files": sum(m["tests"] for m in by_module.values()),
        "blade_templates": sum(m["blade"] for m in by_module.values()),
        "migrations": count_files(DB_DIR / "migrations"),
        "route_files": count_routes(),
        "lang_files": {"en": lang_en_files, "id": lang_id_files},
        "lang_keys": {"en": lang_en_keys, "id": lang_id_keys},
        "config_files": count_files(CONFIG_DIR),
    }

    print(f"  Modules: {totals['modules']}")
    print(f"  PHP files: {totals['php_files']}")
    print(f"  Test files: {totals['test_files']}")
    print(f"  Blade templates: {totals['blade_templates']}")

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "totals": totals,
        "by_module": by_module,
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-files.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"\nWritten to {out}")


if __name__ == "__main__":
    main()
