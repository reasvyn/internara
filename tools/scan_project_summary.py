#!/usr/bin/env python3
"""
scan_project_summary.py — Project orientation dashboard.

One-shot summary of everything a developer or AI agent needs to orient in the
Internara codebase: module inventory, component counts, domain structure,
documentation coverage, test suite composition, and code-health ratios.

Output modes:
  dashboard  — Rich colored terminal dashboard (default when TTY)
  json       — Full structured report for piping / tooling
  compact    — One-line-per-module for quick grep
  module     — Focused view of a single module (--module Foo)

Scan type: metadata only (no violations, no findings).
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import asdict, dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

try:
    from _common import ROOT, MODULES_DIR, APP_DIR, SCAN_VERSION
except ImportError:
    import sys as _sys
    _sys.path.insert(0, str(Path(__file__).parent))
    ROOT = Path(__file__).resolve().parent.parent
    MODULES_DIR = ROOT / "app" / "Modules"
    APP_DIR = ROOT / "app"

SCAN_NAME = "project-summary"

# ─── Component directory map ─────────────────────────────────────────────────

COMPONENT_DIRS: dict[str, str] = {
    "models": "Models",
    "entities": "Entities",
    "actions": "Actions",
    "dtos": "Data",
    "enums": "Enums",
    "livewire": "Livewire",
    "policies": "Policies",
    "events": "Events",
    "listeners": "Listeners",
    "services": "Services",
    "support": "Support",
    "controllers": "Http/Controllers",
    "middleware": "Http/Middleware",
    "requests": "Http/Requests",
    "commands": "Console/Commands",
    "jobs": "Jobs",
    "rules": "Rules",
    "forms": "Livewire/Forms",
    "channels": "Channels",
    "contracts": "Contracts",
    "exceptions": "Exceptions",
    "notifications": "Notifications",
}

# Module health labels
HEALTH_LABELS = {
    "Core": "production",
    "Auth": "production",
    "User": "production",
    "Settings": "production",
    "Setup": "production",
    "SysAdmin": "production",
    "Academics": "production",
    "Program": "stable",
    "Partners": "stable",
    "Enrollment": "stable",
    "Journals": "stable",
    "Incident": "stable",
    "Assignment": "stable",
    "Reports": "stable",
    "Assessment": "attention",
    "Certification": "attention",
    "Document": "attention",
    "Evaluation": "skeleton",
}


# ─── Data classes ────────────────────────────────────────────────────────────

@dataclass
class ModuleSummary:
    name: str
    health: str
    has_domain_dir: bool
    domains: list[str]
    components: dict[str, int]
    total_php: int
    test_php: int
    blade: int
    loc_app: int
    loc_tests: int
    has_routes: bool
    has_config: bool
    has_lang_en: bool
    has_lang_id: bool
    lang_keys_en: int
    lang_keys_id: int


@dataclass
class ProjectSummary:
    project_name: str
    version: str
    stack: dict[str, str]
    deploy_target: str
    license: str
    single_tenant: bool
    modules: list[ModuleSummary]
    totals: dict[str, Any]
    components: dict[str, int]
    documentation: dict[str, Any]
    test_suite: dict[str, Any]
    translation_coverage: dict[str, Any]


# ─── Counting helpers ────────────────────────────────────────────────────────

def count_files_glob(path: Path, pattern: str = "*.php") -> int:
    if not path.exists():
        return 0
    try:
        return sum(1 for _ in path.rglob(pattern))
    except (OSError, PermissionError):
        return 0


def count_md_glob(path: Path) -> int:
    """Count only top-level *.md files in path (not rglob)."""
    if not path.exists():
        return 0
    try:
        return sum(1 for _ in path.glob("*.md"))
    except (OSError, PermissionError):
        return 0


def count_lines_glob(path: Path, pattern: str = "*.php") -> int:
    if not path.exists():
        return 0
    total = 0
    for f in path.rglob(pattern):
        try:
            total += len(f.read_text(encoding="utf-8", errors="replace").splitlines())
        except (OSError, PermissionError, UnicodeDecodeError):
            continue
    return total


def count_lang_keys(locale_dir: Path) -> tuple[int, int]:
    """Returns (file_count, key_count). Counts both top-level and nested array keys."""
    if not locale_dir.exists():
        return 0, 0
    # Match `  'key' => ...` and `"key" => ...` at any depth (no leading -> or //)
    pattern = re.compile(r"""^\s*['"][\w.]+['"]\s*=>""", re.MULTILINE)
    files = list(locale_dir.glob("*.php"))
    key_count = 0
    for f in files:
        try:
            key_count += len(pattern.findall(f.read_text(encoding="utf-8", errors="replace")))
        except (OSError, PermissionError):
            continue
    return len(files), key_count


def count_component(module_dir: Path, subdir: str) -> int:
    """Count PHP files in a subdirectory anywhere under module_dir.

    `subdir` may be a multi-segment path like "Http/Controllers" or "Http/Middleware".
    For multi-segment paths, we walk every intermediate directory and recurse one level.
    For single-segment paths, we match any dir whose name equals the segment.
    """
    if "/" in subdir:
        parts = subdir.split("/")
        # Walk every directory that matches `parts[0]`; for each, look for `parts[1]`.
        # If `parts` is longer than 2 (e.g. "Livewire/Forms"), continue.
        def recurse(current: Path, depth: int) -> int:
            if depth == len(parts):
                return count_files_glob(current)
            target = current / parts[depth]
            if not target.exists() or not target.is_dir():
                return 0
            return recurse(target, depth + 1)
        # Find all dirs whose name is parts[0]
        total = 0
        for p in module_dir.rglob(parts[0]):
            if p.is_dir() and p.name == parts[0]:
                total += recurse(p, 1)
        return total
    else:
        for p in module_dir.rglob(subdir):
            if p.is_dir() and p.name == subdir:
                return count_files_glob(p)
        return 0


def count_all_components(module_dir: Path) -> dict[str, int]:
    """Count all known component types in a module."""
    counts: dict[str, int] = {}
    for key, subdir in COMPONENT_DIRS.items():
        counts[key] = count_component(module_dir, subdir)
    return counts


def find_domains(module_dir: Path) -> list[str]:
    """Find all Domain subdirectories under a module."""
    domain_root = module_dir / "Domain"
    if domain_root.exists():
        try:
            return sorted(
                d.name for d in domain_root.iterdir()
                if d.is_dir() and not d.name.startswith(("_", "."))
            )
        except (OSError, PermissionError):
            pass
    return []


def find_module_test_dirs(module_name: str) -> list[Path]:
    """Find all test directories that contain this module's tests.

    Tests are at tests/{Type}/{Module}/{Name}Test.php. We look across all Type
    directories for any subdirectory matching the module name.
    """
    test_root = ROOT / "tests"
    if not test_root.exists():
        return []
    found = []
    for type_dir in test_root.iterdir():
        if not type_dir.is_dir() or type_dir.name.startswith((".", "_")):
            continue
        if type_dir.name in ("Support", "Pest.php", "TestCase.php"):
            continue
        candidate = type_dir / module_name
        if candidate.exists() and candidate.is_dir():
            found.append(candidate)
    return found


def count_module_tests(module_name: str) -> int:
    """Count test files for a module across all test types (Feature, Unit, Arch, Browser)."""
    test_dirs = find_module_test_dirs(module_name)
    total = 0
    for d in test_dirs:
        total += count_files_glob(d, "*Test.php")
    return total


def count_module_test_loc(module_name: str) -> int:
    """Count test LOC for a module."""
    test_dirs = find_module_test_dirs(module_name)
    total = 0
    for d in test_dirs:
        total += count_lines_glob(d, "*Test.php")
    return total


def scan_module(name: str) -> ModuleSummary:
    module_dir = MODULES_DIR / name

    has_domain = (module_dir / "Domain").exists()
    domains = find_domains(module_dir)
    components = count_all_components(module_dir)

    total_php = count_files_glob(module_dir)
    test_php = count_module_tests(name)
    blade = count_files_glob(ROOT / "resources" / "views" / name.lower(), "*.blade.php")

    loc_app = count_lines_glob(module_dir)
    loc_tests = count_module_test_loc(name)

    has_routes = (ROOT / "routes" / "web" / f"{name.lower()}.php").exists()
    has_config = (ROOT / "config" / f"{name.lower()}.php").exists()
    en_file = ROOT / "lang" / "en" / f"{name.lower()}.php"
    id_file = ROOT / "lang" / "id" / f"{name.lower()}.php"
    has_lang_en = en_file.exists()
    has_lang_id = id_file.exists()

    en_keys = 0
    id_keys = 0
    if has_lang_en:
        try:
            en_content = en_file.read_text(encoding="utf-8", errors="replace")
            en_keys = len(re.findall(r"""^\s*['"][\w.]+['"]\s*=>""", en_content, re.MULTILINE))
        except (OSError, PermissionError):
            pass
    if has_lang_id:
        try:
            id_content = id_file.read_text(encoding="utf-8", errors="replace")
            id_keys = len(re.findall(r"""^\s*['"][\w.]+['"]\s*=>""", id_content, re.MULTILINE))
        except (OSError, PermissionError):
            pass

    return ModuleSummary(
        name=name,
        health=HEALTH_LABELS.get(name, "unknown"),
        has_domain_dir=has_domain,
        domains=domains,
        components=components,
        total_php=total_php,
        test_php=test_php,
        blade=blade,
        loc_app=loc_app,
        loc_tests=loc_tests,
        has_routes=has_routes,
        has_config=has_config,
        has_lang_en=has_lang_en,
        has_lang_id=has_lang_id,
        lang_keys_en=en_keys,
        lang_keys_id=id_keys,
    )


def discover_modules() -> list[str]:
    if not MODULES_DIR.exists():
        return []
    modules = []
    for entry in MODULES_DIR.iterdir():
        if entry.is_dir() and not entry.name.startswith(("_", ".")):
            if any(entry.rglob("*.php")):
                modules.append(entry.name)
    return sorted(modules)


def load_project_identity() -> dict[str, str]:
    """Read composer.json and package.json for project metadata."""
    identity = {}
    composer = ROOT / "composer.json"
    if composer.exists():
        try:
            data = json.loads(composer.read_text(encoding="utf-8"))
            identity["version"] = data.get("version", "unknown")
            identity["project_name"] = data.get("name", "unknown")
            identity["description"] = data.get("description", "")
        except (OSError, json.JSONDecodeError):
            identity["version"] = "unknown"
            identity["project_name"] = "unknown"
    pkg = ROOT / "package.json"
    if pkg.exists():
        try:
            data = json.loads(pkg.read_text(encoding="utf-8"))
            identity["npm_version"] = data.get("version", "unknown")
        except (OSError, json.JSONDecodeError):
            identity["npm_version"] = "unknown"
    return identity


def load_stack() -> dict[str, str]:
    """Extract stack components from composer.json."""
    composer = ROOT / "composer.json"
    if not composer.exists():
        return {}
    try:
        data = json.loads(composer.read_text(encoding="utf-8"))
    except json.JSONDecodeError:
        return {}

    deps = data.get("require", {})
    return {
        "php": deps.get("php", "unknown"),
        "laravel": deps.get("laravel/framework", "unknown"),
        "livewire": deps.get("livewire/livewire", "unknown"),
        "tallstackui": deps.get("tallstackui/tallstackui", "unknown"),
    }


# ─── Formatting helpers ─────────────────────────────────────────────────────

def _c(text: str, color: str, enabled: bool = True) -> str:
    if not enabled:
        return text
    colors = {
        "bold": "\033[1m",
        "red": "\033[91m",
        "green": "\033[92m",
        "yellow": "\033[93m",
        "blue": "\033[94m",
        "magenta": "\033[95m",
        "cyan": "\033[96m",
        "white": "\033[97m",
        "dim": "\033[2m",
        "reset": "\033[0m",
    }
    return f"{colors.get(color, '')}{text}{colors.get('reset', '')}"


def _bar(value: int, max_val: int, width: int = 20, fill: str = "█", empty: str = "░") -> str:
    if max_val == 0:
        return empty * width
    filled = int(width * value / max_val)
    return fill * filled + empty * (width - filled)


def health_color(health: str) -> str:
    return {"production": "green", "stable": "cyan", "attention": "yellow", "skeleton": "dim"}.get(health, "white")


def format_dashboard(s: ProjectSummary, verbose: bool = False) -> str:
    """Render the full colored terminal dashboard."""
    use_color = sys.stdout.isatty()
    lines: list[str] = []

    # ── Header ─────────────────────────────────────────────────────────────
    pad = 80
    lines.append(_c("═" * pad, "blue", use_color))
    lines.append(_c(f"  Internara Project Summary", "bold", use_color))
    lines.append(_c(f"  {s.project_name}  |  v{s.version}  |  {s.deploy_target}  |  {s.license}", "dim", use_color))
    lines.append(_c("═" * pad, "blue", use_color))

    # ── Identity strip ───────────────────────────────────────────────────────
    t = s.totals
    identity_items = [
        ("PHP files", f"{t['php_files']:,}"),
        ("Test files", f"{t['test_files']:,}"),
        ("Blade templates", f"{t['blade_templates']:,}"),
        ("Migrations", f"{t['migrations']:,}"),
        ("Modules", f"{t['modules']}"),
        ("Domains", f"{t['domains']}"),
        ("Components", f"{t['components']}"),
        ("LOC (app)", f"{t['loc_app']:,}"),
        ("LOC (tests)", f"{t['loc_tests']:,}"),
        ("Test ratio", f"{t['test_ratio']:.1%}"),
    ]

    lines.append("")
    for i in range(0, len(identity_items), 3):
        row = identity_items[i:i + 3]
        parts = [f"  {_c(k + ':', 'dim', use_color)} {_c(v, 'white', use_color)}" for k, v in row]
        lines.append("  ".join(f"{p:<35}" for p in parts))

    # ── Stack ────────────────────────────────────────────────────────────────
    lines.append("")
    stack_items = [
        ("PHP", s.stack.get("php", "?")),
        ("Laravel", s.stack.get("laravel", "?")),
        ("Livewire", s.stack.get("livewire", "?")),
        ("TallstackUI", s.stack.get("tallstackui", "?")),
    ]
    lines.append(f"  {_c('Stack:', 'bold', use_color)}  " + "  ".join(
        f"{_c(k, 'dim', use_color)} {_c(v, 'green', use_color)}" for k, v in stack_items
    ))

    # ── Module table ─────────────────────────────────────────────────────────
    lines.append("")
    lines.append(_c(f"  {'Module':<16} {'Health':<12} {'Domains':<24} "
                    f"{'Models':>6} {'Actions':>7} {'Livewire':>8} "
                    f"{'Entities':>8} {'DTOs':>5} {'Enums':>5} "
                    f"{'Events':>6} {'Tests':>6} {'Blade':>6} "
                    f"{'LOC':>7} {'R':>2} {'C':>2} {'L':>2} {'T':>2}",
                    "bold", use_color))
    lines.append(_c("  " + "-" * 130, "dim", use_color))

    all_loc = [m.loc_app for m in s.modules]
    max_loc = max(all_loc) if all_loc else 1

    for m in sorted(s.modules, key=lambda x: -x.loc_app):
        domains_str = "|".join(m.domains) if m.domains else "—"
        hc = health_color(m.health)
        name_f = _c(f"{m.name:<16}", hc, use_color)
        health_f = _c(f"{m.health:<12}", hc, use_color)
        domains_f = _c(f"{domains_str:<24}", "dim", use_color)
        row = (
            f"  {name_f} {health_f} {domains_f} "
            f"{m.components.get('models', 0):>6} "
            f"{m.components.get('actions', 0):>7} "
            f"{m.components.get('livewire', 0):>8} "
            f"{m.components.get('entities', 0):>8} "
            f"{m.components.get('dtos', 0):>5} "
            f"{m.components.get('enums', 0):>5} "
            f"{m.components.get('events', 0):>6} "
            f"{m.test_php:>6} "
            f"{m.blade:>6} "
            f"{m.loc_app:>7,} "
            f"{'✓' if m.has_routes else '·':>2} "
            f"{'✓' if m.has_config else '·':>2} "
            f"{'✓' if m.has_lang_en else '·':>2} "
            f"{'✓' if m.has_lang_id else '·':>2}"
        )
        lines.append(row)

    lines.append(_c("  " + "-" * 130, "dim", use_color))
    lines.append(
        f"  {_c('Totals:', 'bold', use_color)}"
        f"{'':15}"
        f"{'':12}"
        f"{'':24}"
        f"{s.totals['models']:>6} "
        f"{s.totals['actions']:>7} "
        f"{s.totals['livewire']:>8} "
        f"{s.totals['entities']:>8} "
        f"{s.totals['dtos']:>5} "
        f"{s.totals['enums']:>5} "
        f"{s.totals['events']:>6} "
        f"{s.totals['test_files']:>6} "
        f"{s.totals['blade_templates']:>6} "
        f"{s.totals['loc_app']:>7,} "
    )

    # ── Component totals ─────────────────────────────────────────────────────
    lines.append("")
    lines.append(_c(f"  Component Breakdown", "bold", use_color))
    lines.append(_c("  " + "-" * 60, "dim", use_color))

    all_components = {k: sum(m.components.get(k, 0) for m in s.modules)
                      for k in COMPONENT_DIRS}
    sorted_comps = sorted(all_components.items(), key=lambda x: -x[1])
    max_comp = max((v for v in sorted_comps if v[1] > 0), default=(0, 1))[1]

    for key, count in sorted_comps:
        if count > 0:
            bar = _bar(count, max_comp, 20)
            lines.append(f"  {key:<16} {count:>4}  {bar}")

    # ── Documentation coverage ────────────────────────────────────────────────
    lines.append("")
    lines.append(_c(f"  Documentation & Localization", "bold", use_color))
    lines.append(_c("  " + "-" * 60, "dim", use_color))

    doc = s.documentation
    lines.append(f"  {'Specs:':<18} {doc['specs']} ({doc['phases']} phases)")
    lines.append(f"  {'ADRs:':<18} {doc['adrs']}")
    lines.append(f"  {'Pattern docs:':<18} {doc['pattern_docs']}")
    lines.append(f"  {'Lang files (en/id):':<18} {doc['lang_files_en']} / {doc['lang_files_id']}")
    lines.append(f"  {'Translation keys:':<18} {doc['lang_keys_en']} (en) / {doc['lang_keys_id']} (id)")

    cov = s.translation_coverage
    diff = cov.get("key_parity_diff", 0)
    parity_str = f"diff: {diff:+d}" if diff else "in sync"
    parity_color = "green" if diff == 0 else "yellow"
    lines.append(f"  {'Key parity (en-id):':<18} {_c(parity_str, parity_color, use_color)}")
    sub_lang = sum(1 for f in (ROOT / "lang" / "en").glob("*.php"))
    lines.append(f"  {'Subdomain lang files:':<18} {sub_lang} (modules use per-subdomain translation)")

    # ── Test suite ──────────────────────────────────────────────────────────
    lines.append("")
    lines.append(_c(f"  Test Suite", "bold", use_color))
    lines.append(_c("  " + "-" * 60, "dim", use_color))

    ts = s.test_suite
    lines.append(f"  {'Feature tests:':<18} {ts['feature']}")
    lines.append(f"  {'Unit tests:':<18} {ts['unit']}")
    lines.append(f"  {'Arch tests:':<18} {ts['arch']}")
    lines.append(f"  {'Browser tests:':<18} {ts['browser']}")
    lines.append(f"  {'Total test files:':<18} {ts['total']}")
    lines.append(f"  {'Coverage ratio:':<18} {ts['coverage_ratio']:.1%} ({ts['test_files']} tests / {ts['php_files']} app files)")

    lines.append("")
    lines.append(_c(f"  {_c('R=has routes  C=has config  L=has lang/en  T=has lang/id', 'dim', use_color)}", use_color))
    lines.append(_c("═" * pad, "blue", use_color))
    return "\n".join(lines)


def format_compact(s: ProjectSummary) -> str:
    """One-line-per-module for quick grep."""
    lines = [f"# module  health  domains  models  actions  livewire  entities  dtos  enums  events  tests  blade  loc  r  c  l  t"]
    for m in sorted(s.modules, key=lambda x: x.name):
        domains = ",".join(m.domains) if m.domains else "-"
        parts = [
            m.name,
            m.health,
            domains,
            str(m.components.get("models", 0)),
            str(m.components.get("actions", 0)),
            str(m.components.get("livewire", 0)),
            str(m.components.get("entities", 0)),
            str(m.components.get("dtos", 0)),
            str(m.components.get("enums", 0)),
            str(m.components.get("events", 0)),
            str(m.test_php),
            str(m.blade),
            str(m.loc_app),
            "r" if m.has_routes else "-",
            "c" if m.has_config else "-",
            "l" if m.has_lang_en else "-",
            "t" if m.has_lang_id else "-",
        ]
        lines.append("  ".join(f"{p}" for p in parts))
    return "\n".join(lines)


def format_json(s: ProjectSummary) -> str:
    """Full structured JSON for piping."""
    data = asdict(s)
    return json.dumps(data, indent=2, ensure_ascii=False) + "\n"


def format_module(s: ProjectSummary, module_name: str) -> str:
    """Focused view of a single module."""
    m = next((x for x in s.modules if x.name == module_name), None)
    if m is None:
        return f"Module '{module_name}' not found."

    use_color = sys.stdout.isatty()
    lines = []
    pad = 80

    lines.append(_c("═" * pad, "blue", use_color))
    lines.append(_c(f"  Module: {m.name}", "bold", use_color))
    lines.append(_c(f"  Health: {m.health}", health_color(m.health), use_color))
    lines.append(_c(f"  Has Domain/: {m.has_domain_dir}", "dim", use_color))
    lines.append(f"  Domains: {', '.join(m.domains) if m.domains else 'none (flat)'}")
    lines.append(_c("═" * pad, "blue", use_color))
    lines.append("")
    lines.append(_c("  Component counts", "bold", use_color))
    lines.append(_c("  " + "-" * 40, "dim", use_color))

    for key in sorted(m.components, key=lambda k: -m.components.get(k, 0)):
        val = m.components.get(key, 0)
        if val > 0:
            lines.append(f"  {key:<16} {val:>4}")

    lines.append("")
    lines.append(_c("  Files & LOC", "bold", use_color))
    lines.append(_c("  " + "-" * 40, "dim", use_color))
    lines.append(f"  PHP files:       {m.total_php}")
    lines.append(f"  Test files:       {m.test_php}")
    lines.append(f"  Blade templates:  {m.blade}")
    lines.append(f"  LOC (app):        {m.loc_app:,}")
    lines.append(f"  LOC (tests):      {m.loc_tests:,}")
    lines.append("")
    lines.append(_c("  Infrastructure", "bold", use_color))
    lines.append(_c("  " + "-" * 40, "dim", use_color))
    lines.append(f"  Routes file:      {'yes' if m.has_routes else 'NO'}")
    lines.append(f"  Config file:      {'yes' if m.has_config else 'NO'}")
    lines.append(f"  lang/en:          {'yes' if m.has_lang_en else 'NO'}")
    lines.append(f"  lang/id:          {'yes' if m.has_lang_id else 'NO'}")
    lines.append(_c("═" * pad, "blue", use_color))
    return "\n".join(lines)


# ─── Argument parsing ─────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Project orientation dashboard — one-shot stats for all modules",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 tools/scan_project_summary.py                    # dashboard (TTY) / json (pipe)
  python3 tools/scan_project_summary.py --format dashboard  # always use dashboard
  python3 tools/scan_project_summary.py --format json       # pipe to jq
  python3 tools/scan_project_summary.py --format compact    # one line per module
  python3 tools/scan_project_summary.py --module Enrollment # focused module view
  python3 tools/scan_project_summary.py --json              # shortcut for --format json
        """
    )
    parser.add_argument(
        "--format", "-f",
        choices=["dashboard", "json", "compact", "module"],
        default=None,
        help="Output format (default: dashboard if TTY, json otherwise)"
    )
    parser.add_argument(
        "--module", "-m",
        help="Focus on a single module (implies --format module if --format is not set)"
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Shortcut for --format json"
    )
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()

    fmt = args.format
    if args.json:
        fmt = "json"
    elif args.module and fmt is None:
        fmt = "module"
    if fmt is None:
        fmt = "dashboard" if sys.stdout.isatty() else "json"

    # ── Load project identity ───────────────────────────────────────────────
    identity = load_project_identity()
    stack = load_stack()

    # ── Scan all modules ────────────────────────────────────────────────────
    modules = discover_modules()

    all_module_data: list[ModuleSummary] = []
    with ThreadPoolExecutor(max_workers=8) as executor:
        futures = {executor.submit(scan_module, name): name for name in modules}
        for future in as_completed(futures):
            try:
                all_module_data.append(future.result())
            except Exception:
                pass

    # ── Totals ──────────────────────────────────────────────────────────────
    totals = {
        "modules": len(all_module_data),
        "domains": sum(len(m.domains) for m in all_module_data),
        "php_files": sum(m.total_php for m in all_module_data),
        "test_files": sum(m.test_php for m in all_module_data),
        "blade_templates": sum(m.blade for m in all_module_data),
        "migrations": count_files_glob(ROOT / "database" / "migrations"),
        "loc_app": sum(m.loc_app for m in all_module_data),
        "loc_tests": sum(m.loc_tests for m in all_module_data),
        "config_files": count_files_glob(ROOT / "config"),
        "route_files": count_files_glob(ROOT / "routes"),
        "components": sum(max(m.components.values()) > 0 and sum(m.components.values()) for m in all_module_data),
    }
    totals["test_ratio"] = totals["test_files"] / max(1, totals["php_files"])

    # Component totals
    comp_totals = {}
    for key in COMPONENT_DIRS:
        comp_totals[key] = sum(m.components.get(key, 0) for m in all_module_data)
    totals["models"] = comp_totals.get("models", 0)
    totals["actions"] = comp_totals.get("actions", 0)
    totals["livewire"] = comp_totals.get("livewire", 0)
    totals["entities"] = comp_totals.get("entities", 0)
    totals["dtos"] = comp_totals.get("dtos", 0)
    totals["enums"] = comp_totals.get("enums", 0)
    totals["events"] = comp_totals.get("events", 0)

    # ── Documentation ───────────────────────────────────────────────────────
    spec_count = count_md_glob(ROOT / "docs" / "specs")
    adr_count = count_md_glob(ROOT / "docs" / "adr")
    pattern_count = count_md_glob(ROOT / "docs" / "guides" / "arch")
    lang_en_dir = ROOT / "lang" / "en"
    lang_id_dir = ROOT / "lang" / "id"
    en_files, en_keys = count_lang_keys(lang_en_dir)
    id_files, id_keys = count_lang_keys(lang_id_dir)

    documentation = {
        "specs": spec_count,
        "phases": 12,
        "adrs": adr_count,
        "pattern_docs": pattern_count,
        "lang_files_en": en_files,
        "lang_files_id": id_files,
        "lang_keys_en": en_keys,
        "lang_keys_id": id_keys,
    }

    translation_coverage = {
        "modules_with_en": sum(1 for m in all_module_data if m.has_lang_en),
        "modules_with_id": sum(1 for m in all_module_data if m.has_lang_id),
        "key_parity_diff": en_keys - id_keys,
        "modules_missing_module_lang_file": [],
    }
    # Note: many modules use subdomain lang files (e.g. lang/en/academic_year.php
    # instead of lang/en/academics.php), so this is a soft signal, not a hard fail.
    for m in all_module_data:
        if not m.has_lang_en:
            translation_coverage["modules_missing_module_lang_file"].append(f"{m.name}/en")
        if not m.has_lang_id:
            translation_coverage["modules_missing_module_lang_file"].append(f"{m.name}/id")

    # ── Test suite ────────────────────────────────────────────────────────
    feature_tests = count_files_glob(ROOT / "tests" / "Feature", "*Test.php")
    unit_tests = count_files_glob(ROOT / "tests" / "Unit", "*Test.php")
    arch_tests = count_files_glob(ROOT / "tests" / "Arch", "*Test.php")
    browser_tests = count_files_glob(ROOT / "tests" / "Browser", "*Test.php")

    test_suite = {
        "feature": feature_tests,
        "unit": unit_tests,
        "arch": arch_tests,
        "browser": browser_tests,
        "total": feature_tests + unit_tests + arch_tests + browser_tests,
        "test_files": totals["test_files"],
        "php_files": totals["php_files"],
        "coverage_ratio": totals["test_ratio"],
    }

    # ── Assemble ──────────────────────────────────────────────────────────
    summary = ProjectSummary(
        project_name=identity.get("project_name", "unknown"),
        version=identity.get("version", "unknown"),
        stack=stack,
        deploy_target="Shared hosting ($5/mo) or VPS / Docker Compose",
        license="MIT",
        single_tenant=True,
        modules=all_module_data,
        totals=totals,
        components=comp_totals,
        documentation=documentation,
        test_suite=test_suite,
        translation_coverage=translation_coverage,
    )

    # ── Output ────────────────────────────────────────────────────────────
    exec_ms = int((time.time() - start_time) * 1000)

    if fmt == "module":
        if args.module:
            output = format_module(summary, args.module)
        else:
            output = "Error: --module <name> is required for module format.\n"
    elif fmt == "compact":
        output = format_compact(summary)
    elif fmt == "json":
        output = format_json(summary)
    else:
        output = format_dashboard(summary)

    # JSON mode writes to stdout; dashboard/compact also write to stdout
    # Always write JSON to outputs/ for tooling
    try:
        from _common import OUTPUT_DIR
    except ImportError:
        OUTPUT_DIR = Path(__file__).parent / "outputs"

    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
    json_path = OUTPUT_DIR / f"{timestamp}-{SCAN_NAME}.json"
    json_path.write_text(format_json(summary), encoding="utf-8")

    sys.stdout.write(output)
    sys.stderr.write(f"[{exec_ms}ms] Report saved: {json_path}\n")


if __name__ == "__main__":
    main()
