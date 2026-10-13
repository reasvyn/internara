#!/usr/bin/env python3
"""
scan_spec_tests.py — Spec ↔ Tests Coverage Guard (v3.1)

Validates that every FR/NFR/UC requirement in GitHub specification issues
(labeled 'spec') has a corresponding Pest test that traces to it (spec-driven testing),
and that no test traces to a non-existent requirement (orphan).

Features:
  - Dynamic discovery from GitHub Issues (label: 'spec') via gh CLI & caching
  - Module-scoped scanning (--module)
  - Coverage score calculation
  - --list-modules to show available modules
  - --refresh to fetch fresh issues from GitHub
  - Placeholder test detection (empty/trivially true bodies)
  - Scanner-gaming test detection (spec ID with only existence checks)

Rules:
  SPEC_TEST_UNCOVERED     Requirement ID in spec issue but not found in any test (medium)
  SPEC_TEST_ORPHAN        Requirement ID in test but not found in any spec issue (low)
  SPEC_TEST_MISSING_FILE  Spec issue has FR/NFR/UC but no test file mentions its Spec ID (high)
  SPEC_TEST_PLACEHOLDER   Test body is empty or only has trivially true assertions (medium)
  SPEC_TEST_GAMING        Test has spec ID but body only checks existence, not behavior (medium)
  SPEC_TEST_LAYER_MISSING Requirement needs specific test layer(s) not covered by existing tests (medium)
  SPEC_TEST_NON_TESTABLE  Requirement marked non-testable (info)

References:
  docs/guides/arch/testing-pattern.md
  docs/conventions.md
"""

from __future__ import annotations

import argparse
import re
import sys
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Any

sys.path.insert(0, str(Path(__file__).parent))
from _common import (  # noqa: E402
    ROOT,
    Finding,
    ScanResult,
    build_report,
    fetch_spec_issues,
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

# FR-AUTH-001, NFR-AUTH-002, UC-AUTH-003, DD-AUTH-001
RE_REQUIREMENT = re.compile(r"\b(?:FR|NFR|UC|DD)-(?:X-)?[A-Z0-9]{1,8}-[0-9]{3}(?:\*|~|!|-(?:NT|X))?(?=\s|$|[*,.;:)<>\]`])")
# For spec-prefixed refs like 81SMS-FR-AUTH-001 in tests
RE_SPEC_REF = re.compile(r"\b([A-Z0-9]{3,})-(FR|NFR|UC|DD)-(?:X-)?[A-Z0-9]{1,8}-[0-9]{3}(?:\*|~|!|-(?:NT|X))?\b")
# Spec ID from header or issue title: feature: Core — [81SMS] School Profile
RE_SPEC_ID_IN_TITLE = re.compile(r"\[([A-Z0-9]{3,})\]")
RE_SPEC_ID_IN_BODY = re.compile(r"Spec ID:\W*`?([A-Z0-9]{3,})`?")
RE_MODULE_IN_TITLE = re.compile(r"^feature:\s*([A-Za-z0-9]+)")
RE_MODULE_IN_BODY = re.compile(r"- \*\*Module:\*\*\s*`?([A-Za-z0-9]+)`?")

# Non-testable marker check
RE_NON_TESTABLE = re.compile(r"(?:\*|~|!|-X\b|-NT\b|(?:^|\b)(?:FR|NFR|UC)-X-)")

# ─── Data Structures ────────────────────────────────────────────────


@dataclass(frozen=True)
class SpecTarget:
    spec_id: str
    number: int
    title: str
    module: str
    url: str
    body: str
    status: str = "Full"

    @property
    def name(self) -> str:
        return f"GH-#{self.number} [{self.spec_id}]"

    @property
    def display_name(self) -> str:
        return f"GH-#{self.number} [{self.spec_id}] {self.title}"

    @property
    def file_path(self) -> str:
        return f".github/issues/{self.number}"


def load_spec_targets(refresh: bool = False) -> list[SpecTarget]:
    """Load and parse specification issues from GitHub (cached)."""
    raw_issues = fetch_spec_issues(use_cache=True, refresh=refresh)
    targets: list[SpecTarget] = []

    for issue in raw_issues:
        title = issue.get("title", "")
        body = issue.get("body", "") or ""
        num = issue.get("number", 0)
        url = issue.get("url", f"https://github.com/reasvyn/internara/issues/{num}")

        # Spec ID
        sid_m = RE_SPEC_ID_IN_TITLE.search(title)
        if not sid_m:
            sid_m = RE_SPEC_ID_IN_BODY.search(body)
        spec_id = sid_m.group(1) if sid_m else f"ISSUE-{num}"

        # Module
        mod_m = RE_MODULE_IN_TITLE.search(title)
        if not mod_m:
            mod_m = RE_MODULE_IN_BODY.search(body)
        module = mod_m.group(1) if mod_m else "Core"

        # Status
        st_m = re.search(r"- \*\*Current Spec Status:\*\*\s*`?([A-Za-z0-9]+)`?", body)
        status = st_m.group(1) if st_m else "Full"

        targets.append(SpecTarget(
            spec_id=spec_id,
            number=num,
            title=title,
            module=module,
            url=url,
            body=body,
            status=status,
        ))

    return targets


def load_module_spec_mapping(targets: list[SpecTarget] | None = None) -> dict[str, list[str]]:
    """Build module -> [spec_ids] mapping from specification issues."""
    if targets is None:
        targets = load_spec_targets()

    mapping: dict[str, list[str]] = {}
    for t in targets:
        mapping.setdefault(t.module, []).append(t.spec_id)

    return mapping


def get_all_modules(targets: list[SpecTarget] | None = None) -> list[str]:
    """Return sorted list of all modules from spec targets."""
    mapping = load_module_spec_mapping(targets)
    return sorted(mapping.keys())


def filter_specs_by_module(
    targets: list[SpecTarget],
    module: str,
) -> list[SpecTarget]:
    """Filter spec targets to only those belonging to the given module."""
    module_lower = module.lower()
    return [t for t in targets if t.module.lower() == module_lower or module_lower in t.module.lower()]


def extract_requirements_from_spec(spec: SpecTarget) -> tuple[str, list[tuple[str, int]]]:
    """Return (spec_id, [(req_id, line_no)]) for a spec target."""
    reqs: list[tuple[str, int]] = []
    lines = spec.body.splitlines()

    for idx, line in enumerate(lines, start=1):
        line_str = line.strip()
        # Scan checklist lines, table lines, or lines with requirement tags
        is_relevant = (
            line_str.startswith("- [")
            or line_str.startswith("|")
            or "FR-" in line_str
            or "NFR-" in line_str
            or "UC-" in line_str
            or "DD-" in line_str
        )
        if not is_relevant:
            continue

        # Clean markdown bold or backticks
        cleaned = re.sub(r"\*\*([A-Z0-9~*!-]+)\*\*", r"\1", line)
        cleaned = re.sub(r"`([A-Z0-9~*!-]+)`", r"\1", cleaned)

        for match in RE_REQUIREMENT.finditer(cleaned):
            req_id = match.group(0)
            if len(req_id) >= 4:
                reqs.append((req_id, idx))

    return spec.spec_id, reqs


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

# Structural / existence-only checks
RE_EXISTENCE_ONLY = re.compile(
    r"class_exists\s*\(|is_subclass_of\s*\(|is_a\s*\(|method_exists\s*\(|"
    r"ReflectionClass\s*\(|trait_exists\s*\(|interface_exists\s*\(|enum_exists\s*\(",
)

# Config existence checks
RE_CONFIG_ONLY = re.compile(
    r"config\s*\(\s*['\"]\s*\)\s*\)|toHaveKey\s*\(|toBeArray\s*\(\s*\)",
)

# Behavioral code indicators
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


def classify_required_layers(
    req_id: str,
    line_text: str,
    spec: SpecTarget | None = None,
) -> set[str]:
    """Infer the minimum test layers from the requirement's declared behavior."""
    text = f"{req_id} {line_text} {(spec.title if spec else '')}".lower()
    layers: set[str] = set()

    # Direct layer tag check: *(...Layer: A...)*
    layer_tag_m = re.search(r"Layer:\s*([A-Za-z]+)", line_text, re.IGNORECASE)
    if layer_tag_m:
        tag = layer_tag_m.group(1).upper()
        if "A" in tag or "ARCH" in tag:
            layers.add("arch")
        if "U" in tag or "UNIT" in tag:
            layers.add("unit")
        if "B" in tag or "BROWSER" in tag:
            layers.add("browser")
        if "F" in tag or "FEATURE" in tag:
            layers.add("feature")

    architecture_terms = (
        "architecture", "module boundary", "dependency rule", "contract",
        "must not import", "directory structure", "auto-discover", "class contract",
    )
    unit_terms = (
        "entity", "dto", "data transfer", "enum", "state transition", "value object",
        "pure function", "business rule", "policy", "authorization rule", "formatter",
    )
    browser_terms = (
        "critical user journey", "theme persistence", "browser", "responsive", "visual",
        "keyboard", "screen reader", "landing page", "user interface", "ui layout",
    )
    feature_terms = (
        "action", "workflow", "process", "command", "livewire", "console", "route",
        "database", "persist", "create", "update", "delete", "upload", "notification",
        "queue", "event", "report", "api",
    )

    if any(term in text for term in architecture_terms) or req_id.startswith("DD-"):
        layers.add("arch")
    if any(term in text for term in unit_terms):
        layers.add("unit")
    if any(term in text for term in browser_terms) or is_ui_requirement(req_id, spec, line_text):
        layers.add("browser")
    if any(term in text for term in feature_terms):
        layers.add("feature")

    if not layers:
        layers.add("feature")
    return layers


def test_layer(path: Path, browser_files: set[Path]) -> str:
    """Return the scanner layer represented by a test path."""
    if path in browser_files or "Browser" in path.parts:
        return "browser"
    for layer in ("Arch", "Unit", "Feature"):
        if layer in path.parts:
            return layer.lower()
    return "unknown"


def is_ui_requirement(req_id: str, spec: SpecTarget | None = None, line_text: str | None = None) -> bool:
    """Heuristic for UI/client-side requirements."""
    ui_spec_ids = {"8XMYS", "52O1I", "K8HP1", "CKKZC"}
    if spec and spec.spec_id in ui_spec_ids:
        pass
    elif spec and any(kw in spec.title.lower() for kw in ["layout", "ui", "branding", "landing"]):
        pass
    else:
        if line_text is None:
            return False

    if line_text is not None:
        hints = (req_id + " " + line_text).lower()
    else:
        hints = req_id.lower()
        if spec:
            hints += " " + spec.title.lower()

    ui_keywords = [
        "ui", "view", "layout", "theme", "style", "css", "blade", "tailwind",
        "sidebar", "header", "dashboard", "navigation", "component", "alpine",
        "toast", "modal", "dropdown", "dark", "x-ts-", "wire:navigate",
    ]
    has_ui_kw = any(kw in hints for kw in ui_keywords)
    if "livewire" in hints.lower() and has_ui_kw:
        return True
    return has_ui_kw


def extract_test_bodies(content: str) -> list[tuple[str, int, str]]:
    """Extract individual test bodies from a test file."""
    tests = []
    lines = content.splitlines()
    i = 0
    while i < len(lines):
        line = lines[i]
        m = RE_TEST_DEF.search(line)
        if not m:
            i += 1
            continue
        test_name = m.group(1)
        start_line = i + 1
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
    """Classify a test body by what patterns it contains."""
    lines = body_text.splitlines()
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
    """Scan test files for placeholder tests (empty/trivially true bodies)."""
    results = []
    for tf in test_files:
        content = read_file(tf)
        if not content:
            continue
        tests = extract_test_bodies(content)
        for test_name, line_no, body in tests:
            classification = classify_test_body(body)
            lines = body.splitlines()
            content_lines = []
            in_body = False
            for line in lines:
                if "{" in line:
                    in_body = True
                    after_brace = line.split("{", 1)[1].strip()
                    if after_brace and after_brace != "}":
                        content_lines.append(after_brace)
                    continue
                if in_body:
                    stripped = line.strip()
                    if stripped and stripped != "}":
                        content_lines.append(stripped)
            content_lines = [l for l in content_lines if l != "}"]
            if not content_lines:
                results.append((tf, test_name, line_no, "empty body"))
            elif classification["has_trivial"] and not classification["has_behavioral"]:
                results.append((tf, test_name, line_no, "trivially true assertion"))
    return results


def detect_gaming_tests(
    test_files: list[Path],
) -> list[tuple[Path, str, int, str]]:
    """Scan test files for scanner-gaming tests."""
    results = []
    for tf in test_files:
        content = read_file(tf)
        if not content:
            continue
        tests = extract_test_bodies(content)
        for test_name, line_no, body in tests:
            if not RE_SPEC_REF.search(test_name):
                continue
            classification = classify_test_body(body)
            if classification["has_existence_only"] and not classification["has_behavioral"]:
                reasons = ["existence checks only"]
                if classification["has_config_only"]:
                    reasons.append("config only")
                reason = " + ".join(reasons) if reasons else "structural checks only"
                results.append((tf, test_name, line_no, reason))
    return results


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

        for match in RE_SPEC_REF.finditer(line):
            req_id = match.group(0).split('-', 1)[1]
            if req_id not in {requirement for requirement, _ in reqs}:
                reqs.append((req_id, idx))

            requirement_prefix, first_number = req_id.rsplit('-', 1)
            suffixes = re.match(r'(?:/(\d{3}))+', line[match.end():])
            if suffixes:
                for number in re.findall(r'\d{3}', suffixes.group(0)):
                    expanded = f'{requirement_prefix}-{number}'
                    if expanded not in {requirement for requirement, _ in reqs}:
                        reqs.append((expanded, idx))
    return reqs


def get_requirement_priority(req_id: str) -> tuple[str, int]:
    """Score a requirement ID by priority for spec-gap triage."""
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
    if req_id.startswith("DD-"):
        return ("low", 1)
    return ("low", 1)


def calculate_coverage_score(
    total_reqs: int,
    covered_reqs: int,
    non_testable_reqs: int = 0,
) -> dict[str, Any]:
    """Calculate coverage score. Returns dict with score, percentage, and grade."""
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
    targets: list[SpecTarget],
    req_to_specs: dict[str, list[tuple[SpecTarget, int]]],
    test_req_set: set[str],
) -> dict[str, dict[str, Any]]:
    """Calculate per-module coverage breakdown for prioritization."""
    module_stats: dict[str, dict[str, Any]] = {}
    for spec in targets:
        module = spec.module
        _, reqs = extract_requirements_from_spec(spec)
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
        if pct >= 95:
            stats["grade"] = "A+"
        elif pct >= 80:
            stats["grade"] = "B"
        else:
            stats["grade"] = "C"

    return module_stats


def main():
    parser = argparse.ArgumentParser(description="Spec ↔ Tests Coverage Guard (via GitHub Issues)")
    parser.add_argument("--module", help="Filter scan to a specific module")
    parser.add_argument("--list-modules", action="store_true", help="List all available modules and exit")
    parser.add_argument("--refresh", action="store_true", help="Fetch fresh issues from GitHub API")
    parser.add_argument("--json", action="store_true", help="Output JSON report")
    parser.add_argument("--output", help="Save output to file")
    parser.add_argument("--quiet", action="store_true", help="Suppress non-error output")
    parser.add_argument("--verbose", action="store_true", help="Verbose output")
    parser.add_argument("--strict", action="store_true", help="Fail if any findings exist")
    parser.add_argument("--ci", action="store_true", help="CI mode (exit 1 on medium+ findings)")

    args = parser.parse_args()

    # ─── Load Spec Targets from GitHub Issues ─────────────────────────
    spec_targets = load_spec_targets(refresh=args.refresh)

    if args.list_modules:
        modules = get_all_modules(spec_targets)
        print("\nAvailable modules from GitHub Spec Issues:")
        print("=" * 50)
        mapping = load_module_spec_mapping(spec_targets)
        for mod in modules:
            specs = mapping[mod]
            spec_preview = ", ".join(specs[:3])
            if len(specs) > 3:
                spec_preview += "..."
            print(f"  {mod:<20} ({len(specs)} specs: {spec_preview})")
        print(f"\nTotal: {len(modules)} modules")
        return

    start = time.time()
    findings: list[Finding] = []

    # ─── Apply module filter ──────────────────────────────────────────
    if args.module:
        spec_targets = filter_specs_by_module(spec_targets, args.module)
        if not spec_targets:
            print(f"No specs found for module '{args.module}'. Use --list-modules to see available modules.")
            sys.exit(1)

    # ─── Discover test files dynamically ──────────────────────────────
    tests_dir = ROOT / "tests"
    test_files: list[Path] = []
    browser_files: list[Path] = []
    if tests_dir.exists():
        test_files = sorted(tests_dir.rglob("*.php"))
        test_files = [p for p in test_files if p.is_file()]
        browser_dir = tests_dir / "Browser"
        if browser_dir.exists():
            browser_files = sorted(browser_dir.rglob("*.mjs")) + sorted(browser_dir.rglob("*.js"))
            browser_files = [p for p in browser_files if p.is_file()]

    # ─── Build maps ───────────────────────────────────────────────────
    spec_id_to_target: dict[str, SpecTarget] = {}
    req_to_specs: dict[str, list[tuple[SpecTarget, int]]] = {}
    req_to_layers: dict[str, set[str]] = {}
    spec_req_counts: dict[SpecTarget, int] = {}
    spec_ids_in_specs: set[str] = set()

    non_testable_entries: list[tuple[SpecTarget, str, int]] = []
    non_testable_reqs: set[str] = set()

    for spec in spec_targets:
        spec_id, reqs = extract_requirements_from_spec(spec)
        if spec_id:
            spec_id_to_target[spec_id] = spec
            spec_ids_in_specs.add(spec_id)
        spec_req_counts[spec] = len(reqs)

        lines = spec.body.splitlines()
        for req_id, line in reqs:
            if is_non_testable(req_id):
                non_testable_reqs.add(req_id)
                non_testable_entries.append((spec, req_id, line))
            else:
                req_to_specs.setdefault(req_id, []).append((spec, line))
                row = lines[line - 1] if 1 <= line <= len(lines) else ""
                req_to_layers.setdefault(req_id, set()).update(
                    classify_required_layers(req_id, row, spec)
                )

    all_spec_reqs: set[str] = set(req_to_specs.keys())

    req_to_tests: dict[str, list[tuple[Path, int]]] = {}
    test_req_set: set[str] = set()
    test_non_testable: set[str] = set()
    spec_ref_in_tests: set[str] = set()
    browser_req_set: set[str] = set()
    req_to_browser: dict[str, list[tuple[Path, int]]] = {}
    req_to_test_layers: dict[str, set[str]] = {}

    all_test_files = test_files + browser_files
    for tf in all_test_files:
        reqs = extract_requirements_from_test(tf)
        for req_id, line in reqs:
            if is_non_testable(req_id):
                test_non_testable.add(req_id)
                continue
            req_to_tests.setdefault(req_id, []).append((tf, line))
            test_req_set.add(req_id)
            layer = test_layer(tf, set(browser_files))
            req_to_test_layers.setdefault(req_id, set()).add(layer)
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
    for spec, req_id, line in non_testable_entries:
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_NON_TESTABLE",
            severity="low",
            category="convention",
            file=spec.file_path,
            line=line,
            message=f"Requirement {req_id} marked non-testable (marker *~/!/-X/-NT) — no test required",
            suggestion="No test needed; marker indicates manual verification, UI/UX, or infra requirement.",
            reference=spec.url,
            context={"requirement": req_id, "spec": spec.name, "url": spec.url},
        ))

    # ─── Rule: SPEC_TEST_UNCOVERED ─────────────────────────────────
    uncovered = sorted(all_spec_reqs - test_req_set)
    scored_uncovered = sorted(
        uncovered,
        key=lambda r: (get_requirement_priority(r)[1], r),
        reverse=True,
    )
    for req_id in scored_uncovered:
        spec, line = req_to_specs[req_id][0]
        priority, _ = get_requirement_priority(req_id)
        severity = {"critical": "high", "high": "high", "medium": "medium", "low": "low"}[priority]
        lines = spec.body.splitlines()
        line_text = lines[line - 1] if 1 <= line <= len(lines) else None
        is_ui = is_ui_requirement(req_id, spec, line_text)
        if is_ui:
            suggestion = f"Add Browser test (tests/Browser, puppeteer-core) that traces to {req_id} — UI requirement ({priority})"
        else:
            suggestion = f"Add Pest test that traces to {req_id} (high-impact, low-effort: {priority})"
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_UNCOVERED",
            severity=severity,
            category="convention",
            file=spec.file_path,
            line=line,
            message=f"Requirement {req_id} in {spec.name} has no test coverage [{priority} priority{' — UI/client' if is_ui else ''}]",
            suggestion=suggestion,
            reference=spec.url,
            context={"requirement": req_id, "spec": spec.name, "priority": priority, "is_ui": is_ui, "url": spec.url},
        ))

    # ─── Rule: SPEC_TEST_LAYER_MISSING ──────────────────────────────
    for req_id in sorted(all_spec_reqs):
        required = req_to_layers.get(req_id, {"feature"})
        covered_layers = req_to_test_layers.get(req_id, set())
        missing_layers = sorted(required - covered_layers)
        if not missing_layers:
            continue
        spec, line = req_to_specs[req_id][0]
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_LAYER_MISSING",
            severity="medium" if req_id in test_req_set else "high",
            category="convention",
            file=spec.file_path,
            line=line,
            message=f"Requirement {req_id} needs {', '.join(missing_layers)} test layer(s); covered by {', '.join(sorted(covered_layers)) or 'none'}",
            suggestion=f"Add a traceable test under tests/{missing_layers[0].title()}/ for {req_id}",
            reference=spec.url,
            context={
                "requirement": req_id,
                "required_layers": sorted(required),
                "covered_layers": sorted(covered_layers),
                "missing_layers": missing_layers,
                "url": spec.url,
            },
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
            message=f"Test traces to {req_id} but no spec issue defines it (orphan test)",
            suggestion="Verify requirement ID spelling or add the missing FR/NFR/UC to the governing spec issue",
            reference="https://github.com/reasvyn/internara/issues?q=label%3Aspec",
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
            suggestion="Replace with a test that exercises domain code (Action/Entity/DTO/Model)",
            reference="docs/guides/arch/testing-pattern.md",
            context={"test": test_name, "reason": reason, "file": rel},
        ))

    # ─── Rule: SPEC_TEST_GAMING ───────────────────────────────────
    gaming_tests = detect_gaming_tests(test_files)
    for tf, test_name, line_no, reason in gaming_tests:
        rel = relative_path(tf)
        has_spec_id = bool(RE_SPEC_REF.search(test_name))
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_GAMING",
            severity="medium" if has_spec_id else "low",
            category="convention",
            file=rel,
            line=line_no,
            message=f"Scanner-gaming test '{test_name}' ({reason}) — satisfies traceability but tests no behavior",
            suggestion="Add behavioral assertions (Action::execute, Model operations, event dispatch)",
            reference="docs/guides/arch/testing-pattern.md",
            context={"test": test_name, "reason": reason, "has_spec_id": has_spec_id, "file": rel},
        ))

    # ─── Rule: SPEC_TEST_MISSING_FILE ───────────────────────────────
    for spec in spec_targets:
        spec_id, reqs = extract_requirements_from_spec(spec)
        if not spec_id or not reqs:
            continue
        if spec_id not in spec_ref_in_tests:
            skip_specs = {"D2FT3", "architecture", "QLHDO"}
            if spec_id in skip_specs:
                continue
            findings.append(Finding(
                id="SPEC-0000",
                rule="SPEC_TEST_MISSING_FILE",
                severity="high",
                category="convention",
                file=spec.file_path,
                line=1,
                message=f"Spec issue {spec.name} has {len(reqs)} requirements but no test file references it",
                suggestion=f"Create tests that trace to {spec.spec_id}",
                reference=spec.url,
                context={"spec_id": spec_id, "requirements": len(reqs), "spec": spec.name, "url": spec.url},
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
    module_breakdown = calculate_module_breakdown(spec_targets, req_to_specs, test_req_set)
    layer_breakdown: dict[str, dict[str, int]] = {}
    for req_id in sorted(all_spec_reqs):
        for layer in sorted(req_to_layers.get(req_id, {"feature"})):
            stats = layer_breakdown.setdefault(layer, {"required": 0, "covered": 0, "missing": 0})
            stats["required"] += 1
            if layer in req_to_test_layers.get(req_id, set()):
                stats["covered"] += 1
            else:
                stats["missing"] += 1

    top_gaps = sorted(
        [(mod, s) for mod, s in module_breakdown.items() if s["uncovered"] > 0],
        key=lambda x: (x[1]["uncovered"], 100 - x[1]["coverage"]),
        reverse=True,
    )[:5]

    # ─── Build report ───────────────────────────────────────────────
    metadata = {
        "total_files": len(spec_targets) + len(test_files),
        "spec_issues": len(spec_targets),
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
        "layer_breakdown": layer_breakdown,
        "top_gaps": [{"module": m, **s} for m, s in top_gaps],
    }
    total_checks = len(all_spec_reqs) + len([s for s in spec_targets if spec_req_counts.get(s, 0) > 0])
    result = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=total_checks or len(findings) or 1)

    # ─── Print coverage summary ─────────────────────────────────────
    if not args.quiet:
        print(f"\n{'='*60}")
        print(f"  Coverage Score: {coverage['percentage']}% (Grade: {coverage['grade']})")
        print(f"  Total Requirements (from GitHub Issues): {coverage['total_requirements']}")
        print(f"  Testable: {coverage['testable_requirements']}")
        print(f"  Covered: {coverage['covered_requirements']}")
        print(f"  Non-Testable: {coverage['non_testable_requirements']}")
        print(f"  Uncovered: {len(uncovered)}")
        if top_gaps:
            print(f"\n  Top spec gaps by module (high-impact first):")
            for mod, stats in top_gaps:
                print(f"    {mod:<15} {stats['uncovered']} uncovered / {stats['total']} total ({stats['coverage']}% {stats['grade']})")
        if layer_breakdown:
            print(f"\n  Required test layers:")
            for layer in ("arch", "unit", "feature", "browser"):
                stats = layer_breakdown.get(layer)
                if stats:
                    print(f"    {layer:<8} {stats['missing']} missing / {stats['required']} required ({stats['covered']} covered)")
        critical_uncovered = [r for r in scored_uncovered if get_requirement_priority(r)[0] in ("critical", "high")][:5]
        if critical_uncovered:
            print(f"\n  Top 5 critical/high uncovered (high-impact, low-effort first):")
            for req_id in critical_uncovered:
                spec, _ = req_to_specs[req_id][0]
                print(f"    {req_id:<20} in {spec.name}")
        print(f"{'='*60}")

    # ─── Prune old outputs before writing new one ───────────────────
    if not args.quiet:
        print("Pruning old outputs...")
    import subprocess
    clean_script = ROOT / "scripts" / "clean_outputs.py"
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
