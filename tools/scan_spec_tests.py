#!/usr/bin/env python3
"""
scan_spec_tests.py — Spec ↔ Tests Coverage Guard (v3.0)

Validates that every FR/NFR/UC requirement in docs/specs/*.md has a
corresponding Pest test that traces to it (spec-driven testing), and
that no test traces to a non-existent requirement (orphan).

Features:
  - Dynamic module discovery from docs/specs/index.md
  - Module-scoped scanning (--module)
  - Coverage score calculation
  - --list-modules to show available modules
  - Placeholder test detection (empty/trivially true bodies)
  - Scanner-gaming test detection (spec ID with only existence checks)

Rules:
  SPEC_TEST_UNCOVERED  Requirement ID in spec but not found in any test (medium)
  SPEC_TEST_ORPHAN     Requirement ID in test but not found in any spec (low)
  SPEC_TEST_MISSING_FILE  Spec file has FR/NFR/UC but no test file mentions its Spec ID (high)
  SPEC_TEST_PLACEHOLDER  Test body is empty or only has trivially true assertions (medium)
  SPEC_TEST_GAMING    Test has spec ID but body only checks existence, not behavior (medium)

Uses dynamic discovery — no hardcoded spec lists.
Follows _common helpers for parallel execution, JSON report, and CLI flags.

References:
  docs/guides/arch/testing-pattern.md
  .agents/rules/spec-first-doctrine.md
  .agents/rules/verification-strategy.md
"""

from __future__ import annotations

import re
import sys
import time
from pathlib import Path
from typing import Any

sys.path.insert(0, str(Path(__file__).parent))
from _common import (  # noqa: E402
    ROOT,
    Finding,
    ScanResult,
    build_report,
    parse_args_with_common,
    print_summary,
    read_file,
    relative_path,
)
try:
    from _output import handle_output
except ImportError:
    import sys as _sys
    _sys.path.insert(0, str(__import__("pathlib").Path(__file__).parent))
    from _output import handle_output

SCAN_NAME = "spec-tests"

# ─── Regex — dynamic, no hardcoding ─────────────────────────────────

# FR-SP1, FR-TST-01, NFR-U5, UC-1 — require at least one digit; allow hyphens; optional non-testable markers
RE_REQUIREMENT = re.compile(r"\b(?:FR|NFR|UC)-(?:X-)?[A-Z0-9][A-Z0-9\-]*[0-9][A-Z0-9\-]*(?:\*|~|!|-(?:NT|X))?(?=\s|$|[|,.;:)\]])")
# For spec-prefixed refs like 81SMS-FR-SP1*
RE_SPEC_REF = re.compile(r"\b([A-Z0-9]{3,})-(FR|NFR|UC)-(?:X-)?[A-Z0-9][A-Z0-9\-]*[0-9][A-Z0-9\-]*(?:\*|~|!|-(?:NT|X))?\b")
# Spec ID from header: > **Spec ID:** 81SMS
RE_SPEC_ID = re.compile(r"Spec ID:\W*([A-Z0-9]{3,})")
# Non-testable marker check
RE_NON_TESTABLE = re.compile(r"(?:\*|~|!|-X\b|-NT\b|(?:^|\b)(?:FR|NFR|UC)-X-)")

# Module mapping from spec index: | ID | [Name](file) | Module | ... |
RE_SPEC_INDEX_ROW = re.compile(r"^\|\s*([A-Z0-9]{3,})\s*\|.*\|\s*(\w+)\s*\|")

# ─── Placeholder & Gaming Detection ─────────────────────────────────

# Test definition patterns ( Pest test/it with closure or arrow )
RE_TEST_DEF = re.compile(
    r"(?:test|it)\s*\(\s*['\"](.+?)['\"]\s*,\s*(?:function\s*\(\s*\)\s*\{|fn\s*\(\s*\)\s*=>)",
    re.DOTALL,
)

# Trivially true / placeholder assertions
RE_TRIVIAL = re.compile(
    r"assertTrue\s*\(\s*true\s*\)|assertFalse\s*\(\s*false\s*\)|"
    r"expect\s*\(\s*true\s*\)->toBeTrue|1\s*===\s*1",
)

# Structural / existence-only checks (no behavioral substance)
# These are pure class/trait/interface/reflection checks — never behavioral alone
RE_EXISTENCE_ONLY = re.compile(
    r"class_exists\s*\(|is_subclass_of\s*\(|is_a\s*\(|method_exists\s*\(|"
    r"ReflectionClass\s*\(|trait_exists\s*\(|interface_exists\s*\(|enum_exists\s*\(",
)

# Config existence checks (weak — only behavioral if combined with domain code)
RE_CONFIG_ONLY = re.compile(
    r"config\s*\(\s*['\"]\s*\)\s*\)|toHaveKey\s*\(|toBeArray\s*\(\s*\)",
)

# Behavioral code indicators — ANY of these means the test exercises real code
# Covers: static calls (::), new instantiation, method calls (->), fakes, assertions, helpers
RE_BEHAVIORAL = re.compile(
    r"::factory\s*\(\s*\)|::create\s*\(\s*\)|->execute\s*\(\s*\)|"
    r"assertModelExists\s*\(|assertDatabaseHas\s*\(|actingAs\s*\(|"
    r"Http::fake|Queue::fake|Event::fake|Notification::fake|Cache::fake|"
    r"Storage::fake|Bus::fake|Mail::fake|DB::table\s*\(|"
    r"->dispatch\s*\(|->save\s*\(|->delete\s*\(|->assignRole\s*\(|"
    r"->hasRole\s*\(|->paginate\s*\(|"
    r"->fromArray\s*\(|->fromModel\s*\(|->statusCode\s*\(|"
    r"->getContext\s*\(|->getMessage\s*\(|->getHint\s*\(|"
    r"->isCritical\s*\(|->label\s*\(|captureLogs\s*\(|"
    r"Request::create\s*\(|new\s+Response\s*\(|new\s+Request\s*\(|"
    r"->handle\s*\(|->headers->get\s*\(|->headers->set\s*\(|"
    r"->status\s*\(\s*\)|->getData\s*\(|->setContent\s*\(|"
    r"app\s*\(\s*['\"]|resolve\s*\(\s*['\"]|"
    r"config\s*\(\s*['\"][^)]+\)\s*->|config\s*\(\s*['\"][^)]+\)\s*,\s*['\"]|"
    r"RateLimiter::|Gate::|View::|File::put\s*\(|File::delete\s*\(|"
    r"json_decode\s*\(|file_get_contents\s*\(|base_path\s*\(|"
    r"storage_path\s*\(|config_path\s*\(|database_path\s*\(|"
    r"resource_path\s*\(|public_path\s*\(|"
    r"new\s+[A-Z]\w+\s*\(|"
    r"::\w+\s*\(|"
    r"->toArray\s*\(\s*\)|->jsonSerialize\s*\(\s*\)|"
    r"->toPayload\s*\(\s*\)|->eventName\s*\(\s*\)|->getName\s*\(\s*\)|"
    r"->clearCache\s*\(\s*\)|->all\s*\(\s*\)|"
    r"->forceFill\s*\(|->setAttribute\s*\(|->getAttribute\s*\(|"
    r"->makeVisible\s*\(|->makeHidden\s*\(|"
    r"discoverLivewireComponents\s*\(|discoverPolicies\s*\(|"
    r"Storage::disk\s*\(|File::exists\s*\(|File::get\s*\(",
)


def is_non_testable(req_id: str) -> bool:
    """Return True if requirement is marked non-testable via short marker."""
    if req_id.endswith(("*", "~", "!")):
        return True
    if req_id.endswith(("-X", "-NT")):
        return True
    if "-X-" in req_id:
        return True
    if re.search(r"\b(?:FR|NFR|UC)-X-", req_id):
        return True
    return False


def is_ui_requirement(req_id: str, spec_file: Path | None = None, line_text: str | None = None) -> bool:
    """
    Heuristic: UI/client-side requirements are those whose spec or ID
    suggests a view, layout, theme, or interaction. They are best
    verified by browser tests (tests/Browser) in addition to Pest.

    Fix 2026-09: previous heuristic used first 3000 chars of the whole spec
    as signal, causing false positives (e.g., OCEMS FR-UP flagged as UI
    because spec snippet contains "Livewire" even though FR-UP is a Command
    Action business logic). Now we check only:
      - requirement ID itself (e.g., FR-UI, FR-L for layout)
      - spec file name (only for known UI specs like 8XMYS, 52O1I, K8HP1)
      - the specific requirement row text (not whole spec)

    Justification: UI/client requirements are those where the requirement's
    own description or ID explicitly mentions UI concepts, not where the spec
    happens to mention Livewire elsewhere. This aligns with
    docs/guides/infra/testing.md#browser-tests (Browser tests for critical
    journeys: login, navigation, theme, sidebar; Pest for Action/Livewire).
    """
    # 1. Check requirement ID pattern for UI-ish prefixes
    #    e.g., FR-UI, FR-L (layout), FR-R (responsive), FR-S (shell), FR-A (a11y)
    #    But FR-UP, FR-RP, FR-PE are profile business logic, not UI
    ui_id_prefixes = ("FR-UI", "FR-L", "FR-R", "FR-S", "FR-A", "FR-C", "FR-N", "FR-SH", "FR-VD", "FR-P", "FR-D")
    # Only treat as UI if the requirement's own row text contains UI keywords
    # (checked below), not just ID prefix alone — ID prefix is weak signal.

    # 2. Spec file name signal — only for specs that are primarily UI
    #    (layout, branding, landing page). Profile (OCEMS) is mixed, so not UI-only.
    ui_spec_ids = {"8XMYS", "52O1I", "K8HP1", "CKKZC", "06IB6"}  # layout, branding, landing, dashboard, deployment UI parts
    spec_id = spec_file.stem.split("-")[0] if spec_file and "-" in spec_file.stem else (spec_file.stem if spec_file else "")
    if spec_id in ui_spec_ids:
        # For UI-primary specs, still check line text before flagging — not all FRs in a UI spec are UI
        # Fall through to line-text check
        pass
    elif spec_file and any(kw in spec_file.name.lower() for kw in ["layout", "ui-system", "branding", "landing", "deployment"]):
        # File name suggests UI, but still need line-text confirmation
        pass
    else:
        # Not a UI-primary spec; require stronger line-text evidence
        if line_text is None:
            # No line context — be conservative, assume not UI (Pest-testable)
            return False

    # 3. Check the specific requirement row text (most precise signal)
    if line_text is not None:
        hints = (req_id + " " + line_text).lower()
    else:
        hints = req_id.lower()
        if spec_file:
            hints += " " + spec_file.name.lower()

    ui_keywords = [
        "ui", "view", "layout", "theme", "style", "css", "blade", "tailwind",
        "sidebar", "header", "dashboard", "navigation", "component", "alpine",
        "toast", "modal", "dropdown", "dark", "x-ts-", "wire:navigate",
    ]
    # Note: "livewire" removed from generic UI list — many non-UI specs mention Livewire
    # as implementation detail (e.g., ProfileEditor) but the requirement is business logic
    # tested via Pest Livewire, not Browser. Only flag Livewire as UI when combined
    # with explicit view/layout keywords in the row text.
    has_ui_kw = any(kw in hints for kw in ui_keywords)
    # Special case: if row explicitly says "Livewire" AND mentions view/component, treat as UI
    if "livewire" in hints.lower() and has_ui_kw:
        return True
    return has_ui_kw


# ─── Placeholder & Gaming Analysis ──────────────────────────────────

def extract_test_bodies(content: str) -> list[tuple[str, int, str]]:
    """
    Extract individual test bodies from a test file.
    Returns list of (test_name, start_line, body_text).
    """
    tests = []
    lines = content.splitlines()
    i = 0
    while i < len(lines):
        line = lines[i]
        # Match test/it definition
        m = RE_TEST_DEF.search(line)
        if not m:
            i += 1
            continue
        test_name = m.group(1)
        start_line = i + 1  # 1-indexed
        # Find the closing of the function body by tracking braces
        body_lines = []
        brace_count = 0
        found_open = False
        for j in range(i, len(lines)):
            for ch in lines[j]:
                if ch == "{":
                    brace_count += 1
                    found_open = True
                elif ch == "}":
                    brace_count -= 1
            if found_open:
                body_lines.append(lines[j])
                if brace_count <= 0:
                    break
        body_text = "\n".join(body_lines)
        tests.append((test_name, start_line, body_text))
        i += len(body_lines) if body_lines else 1
    return tests


def classify_test_body(body_text: str) -> dict[str, bool]:
    """
    Classify a test body by what patterns it contains.
    Returns dict with flags: has_trivial, has_existence_only, has_config_only, has_behavioral.
    """
    # Strip the test definition line itself (first line usually)
    lines = body_text.splitlines()
    # Find content after the opening brace
    content_start = 0
    for idx, line in enumerate(lines):
        if "{" in line:
            content_start = idx + 1
            break
    body_content = "\n".join(lines[content_start:])

    has_trivial = bool(RE_TRIVIAL.search(body_content))
    has_existence = bool(RE_EXISTENCE_ONLY.search(body_content))
    has_config_only = bool(RE_CONFIG_ONLY.search(body_content))
    has_behavioral = bool(RE_BEHAVIORAL.search(body_content))

    return {
        "has_trivial": has_trivial,
        "has_existence_only": has_existence,
        "has_config_only": has_config_only,
        "has_behavioral": has_behavioral,
    }


def detect_placeholder_tests(
    test_files: list[Path],
) -> list[tuple[Path, str, int, str]]:
    """
    Scan test files for placeholder tests (empty/trivially true bodies).
    Returns list of (file, test_name, line_no, reason).
    """
    results = []
    for tf in test_files:
        content = read_file(tf)
        if not content:
            continue
        tests = extract_test_bodies(content)
        for test_name, line_no, body in tests:
            classification = classify_test_body(body)
            # Placeholder: body is empty or only has trivially true assertions
            lines = body.splitlines()
            # Find content after opening brace
            content_lines = []
            in_body = False
            for line in lines:
                if "{" in line:
                    in_body = True
                    # Check if there's content after the brace on the same line
                    after_brace = line.split("{", 1)[1].strip()
                    if after_brace and after_brace != "}":
                        content_lines.append(after_brace)
                    continue
                if in_body:
                    stripped = line.strip()
                    if stripped and stripped != "}":
                        content_lines.append(stripped)
            # Remove closing brace
            content_lines = [l for l in content_lines if l != "}"]
            if not content_lines:
                results.append((tf, test_name, line_no, "empty body"))
            elif classification["has_trivial"] and not classification["has_behavioral"]:
                results.append((tf, test_name, line_no, "trivially true assertion"))
    return results


def detect_gaming_tests(
    test_files: list[Path],
) -> list[tuple[Path, str, int, str]]:
    """
    Scan test files for scanner-gaming tests (spec ID with only existence checks).
    A test is gaming if it has a spec ID prefix but body contains ONLY existence/structural
    checks and NO behavioral code.
    Returns list of (file, test_name, line_no, reason).
    """
    results = []
    for tf in test_files:
        content = read_file(tf)
        if not content:
            continue
        tests = extract_test_bodies(content)
        for test_name, line_no, body in tests:
            # Only check tests with spec ID prefix
            if not RE_SPEC_REF.search(test_name):
                continue
            classification = classify_test_body(body)
            # Gaming: has existence checks OR config-only, but NO behavioral code
            has_structural = classification["has_existence_only"] or classification["has_config_only"]
            if has_structural and not classification["has_behavioral"]:
                # Determine the specific reason
                reasons = []
                if classification["has_existence_only"]:
                    reasons.append("existence/class checks only")
                if classification["has_config_only"] and not classification["has_existence_only"]:
                    reasons.append("config/key existence checks only")
                reason = " + ".join(reasons) if reasons else "structural checks only"
                results.append((tf, test_name, line_no, reason))
    return results


def load_module_spec_mapping() -> dict[str, list[str]]:
    """
    Parse docs/specs/index.md to build module -> [spec_ids] mapping.
    Returns dict like {'Core': ['QLHDO', 'D2FT3', ...], 'Auth': [...], ...}
    """
    index_path = ROOT / "docs" / "specs" / "index.md"
    content = read_file(index_path)
    if not content:
        return {}

    mapping: dict[str, list[str]] = {}
    for line in content.splitlines():
        m = RE_SPEC_INDEX_ROW.match(line)
        if m:
            spec_id = m.group(1)
            module = m.group(2)
            # Skip non-module rows (like header separators)
            if module in {"Depends", "On"}:
                continue
            mapping.setdefault(module, []).append(spec_id)

    return mapping


def get_all_modules() -> list[str]:
    """Return sorted list of all modules from spec index."""
    mapping = load_module_spec_mapping()
    return sorted(mapping.keys())


def filter_specs_by_module(
    spec_files: list[Path],
    module: str,
) -> list[Path]:
    """
    Filter spec files to only those belonging to the given module.
    Uses the spec index mapping.
    """
    mapping = load_module_spec_mapping()
    module_lower = module.lower()

    # Find matching module names (case-insensitive)
    matching_modules = [m for m in mapping if m.lower() == module_lower]
    if not matching_modules:
        # Try partial match
        matching_modules = [m for m in mapping if module_lower in m.lower()]

    if not matching_modules:
        return []

    # Collect all spec IDs for matching modules
    spec_ids: set[str] = set()
    for m in matching_modules:
        spec_ids.update(mapping[m])

    # Filter spec files by ID (filename starts with ID)
    filtered: list[Path] = []
    for sf in spec_files:
        stem = sf.stem  # e.g., "81SMS-school-profile"
        spec_id = stem.split("-")[0] if "-" in stem else stem
        if spec_id in spec_ids:
            filtered.append(sf)

    return filtered


def extract_requirements_from_spec(path: Path) -> tuple[str | None, list[tuple[str, int]]]:
    """Return (spec_id, [(req_id, line_no)]) for a spec file."""
    content = read_file(path)
    if not content:
        return None, []
    lines = content.splitlines()
    spec_id: str | None = None
    m = RE_SPEC_ID.search(content)
    if m:
        spec_id = m.group(1)

    reqs: list[tuple[str, int]] = []
    for idx, line in enumerate(lines, start=1):
        is_requirement_row = line.strip().startswith("|") and ("FR-" in line or "NFR-" in line or "UC-" in line)
        if not is_requirement_row:
            if "|" not in line:
                continue
        for match in RE_REQUIREMENT.finditer(line):
            req_id = match.group(0)
            if len(req_id) >= 4:
                reqs.append((req_id, idx))
    return spec_id, reqs


def extract_requirements_from_test(path: Path) -> list[tuple[str, int]]:
    """Return [(req_id, line_no)] for a test file."""
    content = read_file(path)
    if not content:
        return []
    reqs: list[tuple[str, int]] = []
    for idx, line in enumerate(content.splitlines(), start=1):
        for match in RE_REQUIREMENT.finditer(line):
            req_id = match.group(0)
            if len(req_id) >= 4:
                reqs.append((req_id, idx))
    return reqs


def get_requirement_priority(req_id: str) -> tuple[str, int]:
    """
    Score a requirement ID by priority for spec-gap triage.
    Returns (priority_label, score) where higher score = more critical.
    High-impact, low-effort items (core business logic) score highest.
    """
    # FR-A* (Actions), FR-L* (Livewire), FR-M* (Models) are core - high impact
    if re.match(r"FR-[ALM]\d*", req_id):
        return ("critical", 10)
    if re.match(r"FR-[AE]\d*", req_id):
        return ("high", 8)
    if re.match(r"FR-[DR]\d*", req_id):
        return ("high", 7)
    if req_id.startswith("FR-"):
        return ("medium", 5)
    if req_id.startswith("UC-"):
        return ("medium", 4)
    if req_id.startswith("NFR-"):
        return ("low", 2)
    return ("low", 1)


def calculate_coverage_score(
    total_reqs: int,
    covered_reqs: int,
    non_testable_reqs: int = 0,
) -> dict[str, Any]:
    """
    Calculate coverage score.
    Returns dict with score, percentage, grade, and breakdown.
    """
    if total_reqs == 0:
        return {
            "score": 0,
            "percentage": 0.0,
            "grade": "N/A",
            "total_requirements": 0,
            "testable_requirements": 0,
            "covered_requirements": 0,
            "non_testable_requirements": non_testable_reqs,
        }

    testable = total_reqs - non_testable_reqs
    if testable <= 0:
        return {
            "score": 0,
            "percentage": 100.0,
            "grade": "A+",
            "total_requirements": total_reqs,
            "testable_requirements": 0,
            "covered_requirements": 0,
            "non_testable_requirements": non_testable_reqs,
        }

    percentage = (covered_reqs / testable) * 100

    # Grade scale
    if percentage >= 95:
        grade = "A+"
    elif percentage >= 90:
        grade = "A"
    elif percentage >= 80:
        grade = "B"
    elif percentage >= 70:
        grade = "C"
    elif percentage >= 60:
        grade = "D"
    else:
        grade = "F"

    return {
        "score": covered_reqs,
        "percentage": round(percentage, 1),
        "grade": grade,
        "total_requirements": total_reqs,
        "testable_requirements": testable,
        "covered_requirements": covered_reqs,
        "non_testable_requirements": non_testable_reqs,
    }


def calculate_module_breakdown(
    spec_files: list[Path],
    req_to_specs: dict[str, list[tuple[Path, int]]],
    test_req_set: set[str],
) -> dict[str, dict[str, Any]]:
    """Calculate per-module coverage breakdown for prioritization."""
    mapping = load_module_spec_mapping()
    # Reverse: spec_id -> module
    spec_to_module: dict[str, str] = {}
    for mod, sids in mapping.items():
        for sid in sids:
            spec_to_module[sid] = mod

    module_stats: dict[str, dict[str, Any]] = {}
    for sf in spec_files:
        spec_id = sf.stem.split("-")[0] if "-" in sf.stem else sf.stem
        module = spec_to_module.get(spec_id, "Unknown")
        _, reqs = extract_requirements_from_spec(sf)
        total = len([r for r, _ in reqs if not is_non_testable(r)])
        if total == 0:
            continue
        covered = len([r for r, _ in reqs if r in test_req_set and not is_non_testable(r)])
        uncovered = total - covered
        if module not in module_stats:
            module_stats[module] = {"total": 0, "covered": 0, "uncovered": 0, "specs": 0}
        module_stats[module]["total"] += total
        module_stats[module]["covered"] += covered
        module_stats[module]["uncovered"] += uncovered
        module_stats[module]["specs"] += 1

    for mod, stats in module_stats.items():
        pct = (stats["covered"] / stats["total"] * 100) if stats["total"] else 0
        stats["coverage"] = round(pct, 1)
        stats["grade"] = "F" if pct < 60 else "D" if pct < 70 else "C" if pct < 80 else "B" if pct < 90 else "A" if pct < 95 else "A+"

    return module_stats


def main() -> None:
    # Check for --list-modules first (before standard parsing)
    if "--list-modules" in sys.argv:
        modules = get_all_modules()
        print("\nAvailable modules from docs/specs/index.md:")
        print("=" * 50)
        mapping = load_module_spec_mapping()
        for mod in modules:
            specs = mapping[mod]
            spec_preview = ", ".join(specs[:3])
            if len(specs) > 3:
                spec_preview += "..."
            print(f"  {mod:<20} ({len(specs)} specs: {spec_preview})")
        print(f"\nTotal: {len(modules)} modules")
        return

    args = parse_args_with_common("Spec ↔ Tests Coverage Guard — FR/NFR/UC traceability with module support")

    start = time.time()
    findings: list[Finding] = []

    # ─── Discover spec files dynamically ──────────────────────────────
    specs_dir = ROOT / "docs" / "specs"
    spec_files = sorted(specs_dir.glob("*.md")) if specs_dir.exists() else []
    # Filter out template and index
    spec_files = [p for p in spec_files if p.name not in {"spec-template.md", "index.md", "implementation-matrix.md"} and not p.name.startswith("_")]

    # ─── Apply module filter ──────────────────────────────────────────
    if args.module:
        spec_files = filter_specs_by_module(spec_files, args.module)
        if not spec_files:
            print(f"No specs found for module '{args.module}'. Use --list-modules to see available modules.")
            sys.exit(1)

    # ─── Discover test files dynamically ──────────────────────────────
    tests_dir = ROOT / "tests"
    test_files: list[Path] = []
    browser_files: list[Path] = []
    if tests_dir.exists():
        test_files = sorted(tests_dir.rglob("*.php"))
        test_files = [p for p in test_files if p.is_file()]
        # Browser tests (headless): tests/Browser/**/*.mjs, *.js
        browser_dir = tests_dir / "Browser"
        if browser_dir.exists():
            browser_files = sorted(browser_dir.rglob("*.mjs")) + sorted(browser_dir.rglob("*.js"))
            browser_files = [p for p in browser_files if p.is_file()]

    # ─── Build maps ───────────────────────────────────────────────────
    spec_id_to_file: dict[str, Path] = {}
    req_to_specs: dict[str, list[tuple[Path, int]]] = {}
    spec_req_counts: dict[Path, int] = {}
    spec_ids_in_specs: set[str] = set()

    non_testable_entries: list[tuple[Path, str, int]] = []
    non_testable_reqs: set[str] = set()

    for sf in spec_files:
        spec_id, reqs = extract_requirements_from_spec(sf)
        if spec_id:
            spec_id_to_file[spec_id] = sf
            spec_ids_in_specs.add(spec_id)
        spec_req_counts[sf] = len(reqs)
        for req_id, line in reqs:
            if is_non_testable(req_id):
                non_testable_reqs.add(req_id)
                non_testable_entries.append((sf, req_id, line))
            else:
                req_to_specs.setdefault(req_id, []).append((sf, line))

    all_spec_reqs: set[str] = set(req_to_specs.keys())

    req_to_tests: dict[str, list[tuple[Path, int]]] = {}
    test_req_set: set[str] = set()
    test_non_testable: set[str] = set()
    spec_ref_in_tests: set[str] = set()
    browser_req_set: set[str] = set()
    req_to_browser: dict[str, list[tuple[Path, int]]] = {}

    all_test_files = test_files + browser_files
    for tf in all_test_files:
        reqs = extract_requirements_from_test(tf)
        for req_id, line in reqs:
            if is_non_testable(req_id):
                test_non_testable.add(req_id)
                continue
            req_to_tests.setdefault(req_id, []).append((tf, line))
            test_req_set.add(req_id)
            if tf in browser_files:
                browser_req_set.add(req_id)
                req_to_browser.setdefault(req_id, []).append((tf, line))
        content = read_file(tf)
        for m in RE_SPEC_REF.finditer(content):
            spec_ref_in_tests.add(m.group(1))
        for sid in spec_ids_in_specs:
            if re.search(rf"\b{re.escape(sid)}\b", content):
                spec_ref_in_tests.add(sid)

    # ─── Rule: SPEC_TEST_NON_TESTABLE (info) ────────────────────────
    for spec_file, req_id, line in non_testable_entries:
        rel = relative_path(spec_file)
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_NON_TESTABLE",
            severity="low",
            category="convention",
            file=rel,
            line=line,
            message=f"Requirement {req_id} marked non-testable (marker *~/!/-X/-NT) — no test required",
            suggestion="No test needed; marker indicates manual verification, UI/UX, or infra requirement.",
            reference="docs/guides/arch/testing-pattern.md",
            context={"requirement": req_id, "spec": spec_file.name},
        ))

    # ─── Rule: SPEC_TEST_UNCOVERED (with priority + UI hint) ─────
    uncovered = sorted(all_spec_reqs - test_req_set)
    # Score and sort by priority (high-impact first)
    scored_uncovered = sorted(
        uncovered,
        key=lambda r: (get_requirement_priority(r)[1], r),
        reverse=True,
    )
    for req_id in scored_uncovered:
        spec_file, line = req_to_specs[req_id][0]
        rel = relative_path(spec_file)
        priority, _ = get_requirement_priority(req_id)
        # Map priority to severity for triage
        severity = {"critical": "high", "high": "high", "medium": "medium", "low": "low"}[priority]
        # Retrieve the specific requirement row text for precise UI heuristic (fix false positives)
        line_text = None
        try:
            lines = read_file(spec_file).splitlines()
            if 1 <= line <= len(lines):
                line_text = lines[line - 1]
        except Exception:
            line_text = None
        is_ui = is_ui_requirement(req_id, spec_file, line_text)
        if is_ui:
            suggestion = f"Add Browser test (tests/Browser, puppeteer-core) that traces to {req_id} — UI requirement ({priority})"
        else:
            suggestion = f"Add Pest test that traces to {req_id} (high-impact, low-effort: {priority})"
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_UNCOVERED",
            severity=severity,
            category="convention",
            file=rel,
            line=line,
            message=f"Requirement {req_id} in {spec_file.name} has no test coverage [{priority} priority{' — UI/client' if is_ui else ''}]",
            suggestion=suggestion,
            reference="docs/guides/arch/testing-pattern.md" if not is_ui else "docs/guides/infra/testing.md#browser-tests",
            context={"requirement": req_id, "spec": spec_file.name, "priority": priority, "is_ui": is_ui},
        ))

    # ─── Rule: SPEC_TEST_ORPHAN ─────────────────────────────────────
    orphans = sorted(test_req_set - all_spec_reqs)
    for req_id in orphans:
        test_file, line = req_to_tests[req_id][0]
        rel = relative_path(test_file)
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_ORPHAN",
            severity="low",
            category="convention",
            file=rel,
            line=line,
            message=f"Test traces to {req_id} but no spec defines it (orphan test)",
            suggestion="Verify requirement ID spelling or add the missing FR/NFR/UC to the governing spec",
            reference=".agents/rules/spec-first-doctrine.md",
            context={"requirement": req_id, "test": rel},
        ))

    # ─── Rule: SPEC_TEST_PLACEHOLDER ──────────────────────────────
    placeholder_tests = detect_placeholder_tests(test_files)
    for tf, test_name, line_no, reason in placeholder_tests:
        rel = relative_path(tf)
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_PLACEHOLDER",
            severity="medium",
            category="convention",
            file=rel,
            line=line_no,
            message=f"Placeholder test '{test_name}' ({reason}) — not testing real behavior",
            suggestion="Replace with a test that exercises domain code (Action/Entity/DTO/Model) or remove if the spec requirement was dropped",
            reference="docs/guides/arch/testing-pattern.md",
            context={"test": test_name, "reason": reason, "file": rel},
        ))

    # ─── Rule: SPEC_TEST_GAMING ───────────────────────────────────
    gaming_tests = detect_gaming_tests(test_files)
    for tf, test_name, line_no, reason in gaming_tests:
        rel = relative_path(tf)
        # Check if this test also has a spec ID — if so, it's gaming the tracer
        has_spec_id = bool(RE_SPEC_REF.search(test_name))
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_GAMING",
            severity="medium" if has_spec_id else "low",
            category="convention",
            file=rel,
            line=line_no,
            message=f"Scanner-gaming test '{test_name}' ({reason}) — satisfies traceability but tests no behavior",
            suggestion="Add behavioral assertions (Action::execute, Model operations, event dispatch) or remove the spec ID if the requirement is architectural-only",
            reference="docs/guides/arch/testing-pattern.md",
            context={"test": test_name, "reason": reason, "has_spec_id": has_spec_id, "file": rel},
        ))

    # ─── Rule: SPEC_TEST_MISSING_FILE ───────────────────────────────
    for sf in spec_files:
        spec_id, reqs = extract_requirements_from_spec(sf)
        if not spec_id:
            continue
        if not reqs:
            continue
        if spec_id not in spec_ref_in_tests:
            skip_specs = {"implementation-matrix", "spec-template", "index", "D2FT3", "architecture"}
            if any(skip in sf.name for skip in skip_specs):
                continue
            rel = relative_path(sf)
            findings.append(Finding(
                id="SPEC-0000",
                rule="SPEC_TEST_MISSING_FILE",
                severity="high",
                category="convention",
                file=rel,
                line=1,
                message=f"Spec {spec_id} ({sf.name}) has {len(reqs)} requirements but no test file references it",
                suggestion=f"Create tests that trace to {spec_id}",
                reference="docs/guides/arch/testing-pattern.md",
                context={"spec_id": spec_id, "requirements": len(reqs), "spec": sf.name},
            ))

    # ─── Sort and re-id deterministically ───────────────────────────
    findings.sort(key=lambda f: (f.file, f.rule, f.line))
    for i, f in enumerate(findings):
        f.id = f"SPEC-{i+1:04d}"

    # ─── Calculate coverage score & breakdown ───────────────────────
    covered_reqs = len(all_spec_reqs & test_req_set)
    coverage = calculate_coverage_score(
        total_reqs=len(all_spec_reqs),
        covered_reqs=covered_reqs,
        non_testable_reqs=len(non_testable_reqs),
    )
    module_breakdown = calculate_module_breakdown(spec_files, req_to_specs, test_req_set)
    # Prioritize modules by uncovered count (high-impact first)
    top_gaps = sorted(
        [(mod, s) for mod, s in module_breakdown.items() if s["uncovered"] > 0],
        key=lambda x: (x[1]["uncovered"], 100 - x[1]["coverage"]),
        reverse=True,
    )[:5]

    # ─── Build report ───────────────────────────────────────────────
    metadata = {
        "total_files": len(spec_files) + len(test_files),
        "spec_files": len(spec_files),
        "test_files": len(test_files),
        "spec_requirements": len(all_spec_reqs),
        "test_requirements": len(test_req_set),
        "uncovered": len(uncovered),
        "orphans": len(orphans),
        "placeholder_tests": len(placeholder_tests),
        "gaming_tests": len(gaming_tests),
        "specs_without_tests": sum(1 for f in findings if f.rule == "SPEC_TEST_MISSING_FILE"),
        "coverage": coverage,
        "module_breakdown": module_breakdown,
        "top_gaps": [{"module": m, **s} for m, s in top_gaps],
    }
    total_checks = len(all_spec_reqs) + len([sf for sf in spec_files if spec_req_counts.get(sf, 0) > 0])
    result = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=total_checks or len(findings) or 1)

    # ─── Print coverage summary ─────────────────────────────────────
    if not args.quiet:
        print(f"\n{'='*60}")
        print(f"  Coverage Score: {coverage['percentage']}% (Grade: {coverage['grade']})")
        print(f"  Total Requirements: {coverage['total_requirements']}")
        print(f"  Testable: {coverage['testable_requirements']}")
        print(f"  Covered: {coverage['covered_requirements']}")
        print(f"  Non-Testable: {coverage['non_testable_requirements']}")
        print(f"  Uncovered: {len(uncovered)}")
        if top_gaps:
            print(f"\n  Top spec gaps by module (high-impact first):")
            for mod, stats in top_gaps:
                print(f"    {mod:<15} {stats['uncovered']} uncovered / {stats['total']} total ({stats['coverage']}% {stats['grade']})")
        # Show top 5 uncovered critical/high priority
        critical_uncovered = [r for r in scored_uncovered if get_requirement_priority(r)[0] in ("critical", "high")][:5]
        if critical_uncovered:
            print(f"\n  Top 5 critical/high uncovered (high-impact, low-effort first):")
            for req_id in critical_uncovered:
                sf, _ = req_to_specs[req_id][0]
                print(f"    {req_id:<20} in {sf.name}")
        print(f"{'='*60}")

    # ─── Prune old outputs before writing new one ───────────────────
    if not args.quiet:
        print("Pruning old outputs...")
    import subprocess
    clean_script = ROOT / "tools" / "clean_outputs.py"
    if clean_script.exists():
        subprocess.run(
            [sys.executable, str(clean_script), "--prune"],
            capture_output=not args.verbose,
            check=False,
        )

    # ─── Output ─────────────────────────────────────────────────────
    exit_code = handle_output(result, args)
    if exit_code:
        sys.exit(exit_code)


if __name__ == "__main__":
    main()
