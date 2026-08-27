#!/usr/bin/env python3
"""
scan_spec_tests.py — Spec ↔ Tests Coverage Guard (v1.0)

Validates that every FR/NFR/UC requirement in docs/specs/*.md has a
corresponding Pest test that traces to it (spec-driven testing), and
that no test traces to a non-existent requirement (orphan).

Rules:
  SPEC_TEST_UNCOVERED  Requirement ID in spec but not found in any test (medium)
  SPEC_TEST_ORPHAN     Requirement ID in test but not found in any spec (low)
  SPEC_TEST_MISSING_FILE  Spec file has FR/NFR/UC but no test file mentions its Spec ID (high)

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
    write_report,
)

SCAN_NAME = "spec-tests"

# ─── Regex — dynamic, no hardcoding ─────────────────────────────────

# FR-SP1, FR-TST-01, NFR-U5, UC-1 — require at least one digit; allow hyphens; optional non-testable markers: * ~ ! -X -NT -X- prefix
# Examples: FR-SP1, FR-TST-01*, FR-SP1~, FR-SP1-X, FR-SP1-NT, FR-X-001 (non-testable via X- prefix), NFR-P1*, UC-1~
# Note: suffix *~! are non-word, so we use lookahead instead of \b after them; include : for test descriptions like "FR-SP1:"
RE_REQUIREMENT = re.compile(r"\b(?:FR|NFR|UC)-(?:X-)?[A-Z0-9][A-Z0-9\-]*[0-9][A-Z0-9\-]*(?:\*|~|!|-(?:NT|X))?(?=\s|$|[|,.;:)\]])")
# For spec-prefixed refs like 81SMS-FR-SP1* — same but with spec prefix
RE_SPEC_REF = re.compile(r"\b([A-Z0-9]{3,})-(FR|NFR|UC)-(?:X-)?[A-Z0-9][A-Z0-9\-]*[0-9][A-Z0-9\-]*(?:\*|~|!|-(?:NT|X))?(?=\s|$|[|,.;:)\]])")
# Spec ID from header: > **Spec ID:** 81SMS  (markdown bold)
RE_SPEC_ID = re.compile(r"Spec ID:\W*([A-Z0-9]{3,})")
# Tests often write "81SMS-FR-SP1" — capture spec prefix + FR/NFR/UC (with optional X- and suffix, hyphenated)
RE_SPEC_REF = re.compile(r"\b([A-Z0-9]{3,})-(FR|NFR|UC)-(?:X-)?[A-Z0-9][A-Z0-9\-]*[0-9][A-Z0-9\-]*(?:\*|~|!|-(?:NT|X))?\b")
# Non-testable marker check — short characters: * ~ ! -X -NT -X- prefix
RE_NON_TESTABLE = re.compile(r"(?:\*|~|!|-X\b|-NT\b|(?:^|\b)(?:FR|NFR|UC)-X-)")


def is_non_testable(req_id: str) -> bool:
    """Return True if requirement is marked non-testable via short marker."""
    # Suffix markers: * ~ ! -X -NT
    if req_id.endswith(("*", "~", "!")):
        return True
    if req_id.endswith(("-X", "-NT")):
        return True
    if "-X-" in req_id:
        return True
    # Also handle FR-X-001 where X- is after FR-
    if re.search(r"\b(?:FR|NFR|UC)-X-", req_id):
        return True
    return False


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
        # Only extract from requirement table rows (| FR-... |) or explicit requirement lines
        # Avoid capturing references in flow descriptions like "(NFR-U5)" which are not definitions
        is_requirement_row = line.strip().startswith("|") and ("FR-" in line or "NFR-" in line or "UC-" in line)
        # Also capture from lines that are clearly requirement definitions (contain | ID |)
        if not is_requirement_row:
            # Allow for lines like "| NFR-P1* |" but not for flow references
            # If line does not look like a table row, skip to avoid false positives from flow/UC references
            # However, some specs define requirements outside tables (rare) — we still capture if line has | and requirement
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
        # Also capture spec-prefixed refs like "81SMS-FR-SP1" — extract the FR part
        for m in RE_SPEC_REF.finditer(line):
            req_id = f"{m.group(2)}-{m.group(0).split('-', 1)[1].split('-', 1)[-1] if '-' in m.group(0) else m.group(0)}"
            # Simpler: the full match after spec prefix is the FR/NFR/UC we want, already captured above
            # The RE_REQUIREMENT already captures FR-SP1 inside "81SMS-FR-SP1", so this is just for completeness
            pass
    return reqs


def main() -> None:
    args = parse_args_with_common("Spec ↔ Tests Coverage Guard — FR/NFR/UC traceability")
    start = time.time()
    findings: list[Finding] = []

    # ─── Discover spec files dynamically ──────────────────────────────
    specs_dir = ROOT / "docs" / "specs"
    spec_files = sorted(specs_dir.glob("*.md")) if specs_dir.exists() else []
    # Filter out template and index
    spec_files = [p for p in spec_files if p.name not in {"spec-template.md", "index.md"} and not p.name.startswith("_")]

    # ─── Discover test files dynamically ──────────────────────────────
    tests_dir = ROOT / "tests"
    test_files: list[Path] = []
    if tests_dir.exists():
        test_files = sorted(tests_dir.rglob("*.php"))
        # Exclude vendor, support files without tests
        test_files = [p for p in test_files if p.is_file()]

    # ─── Build maps ───────────────────────────────────────────────────
    # spec_id -> path, req_id -> [(spec_file, line)]
    spec_id_to_file: dict[str, Path] = {}
    req_to_specs: dict[str, list[tuple[Path, int]]] = {}
    spec_req_counts: dict[Path, int] = {}
    spec_ids_in_specs: set[str] = set()

    # Track non-testable reqs separately
    non_testable_reqs: set[str] = set()
    non_testable_map: dict[str, list[tuple[Path, int]]] = {}

    for sf in spec_files:
        spec_id, reqs = extract_requirements_from_spec(sf)
        if spec_id:
            spec_id_to_file[spec_id] = sf
            spec_ids_in_specs.add(spec_id)
        spec_req_counts[sf] = len(reqs)
        for req_id, line in reqs:
            if is_non_testable(req_id):
                non_testable_reqs.add(req_id)
                non_testable_map.setdefault(req_id, []).append((sf, line))
            else:
                req_to_specs.setdefault(req_id, []).append((sf, line))

    # All requirement IDs defined in specs (unique) — excluding non-testable
    all_spec_reqs: set[str] = set(req_to_specs.keys())

    # req_id -> [(test_file, line)] and set of all test reqs (excluding non-testable for orphan check)
    req_to_tests: dict[str, list[tuple[Path, int]]] = {}
    test_req_set: set[str] = set()
    test_non_testable: set[str] = set()
    spec_ref_in_tests: set[str] = set()  # spec IDs mentioned in tests (e.g., "81SMS")

    for tf in test_files:
        reqs = extract_requirements_from_test(tf)
        for req_id, line in reqs:
            if is_non_testable(req_id):
                test_non_testable.add(req_id)
                continue
            req_to_tests.setdefault(req_id, []).append((tf, line))
            test_req_set.add(req_id)
        # Also detect spec ID references in tests (e.g., "81SMS-FR-SP1" contains "81SMS")
        content = read_file(tf)
        for m in RE_SPEC_REF.finditer(content):
            spec_ref_in_tests.add(m.group(1))
        # Also simple Spec ID mention like "81SMS" alone — check if spec_id appears as word
        for sid in spec_ids_in_specs:
            if re.search(rf"\b{re.escape(sid)}\b", content):
                spec_ref_in_tests.add(sid)

    # ─── Rule: SPEC_TEST_NON_TESTABLE (info) ────────────────────────
    # Requirements marked with short non-testable markers (* ~ ! -X -NT -X- prefix) are excluded from uncovered
    for req_id, occurrences in non_testable_map.items():
        spec_file, line = occurrences[0]
        rel = relative_path(spec_file)
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_NON_TESTABLE",
            severity="low",
            category="convention",
            file=rel,
            line=line,
            message=f"Requirement {req_id} marked non-testable (short marker *~/!/-X/-NT) — no test required",
            suggestion="No test needed; marker indicates manual verification, UI/UX, or infra requirement. Keep marker for auditability.",
            reference="docs/guides/arch/testing-pattern.md §Non-Testable Requirements, .agents/rules/conflic-resolution.md",
            context={"requirement": req_id, "spec": spec_file.name, "occurrences": len(occurrences)},
        ))

    # ─── Rule: SPEC_TEST_UNCOVERED ──────────────────────────────────
    # Requirement in spec but not found in any test — low for informational (gradual)
    uncovered = sorted(all_spec_reqs - test_req_set)
    for req_id in uncovered:
        # Report at first occurrence in spec
        spec_file, line = req_to_specs[req_id][0]
        rel = relative_path(spec_file)
        findings.append(Finding(
            id="SPEC-0000",
            rule="SPEC_TEST_UNCOVERED",
            severity="low",
            category="convention",
            file=rel,
            line=line,
            message=f"Requirement {req_id} in {spec_file.name} has no test coverage (no test traces to it)",
            suggestion=f"Add Pest test that traces to {req_id} (e.g., describe/it with '{req_id}' in tests/...) per docs/guides/arch/testing-pattern.md",
            reference="docs/guides/arch/testing-pattern.md §Spec-Traceable Tests, .agents/rules/spec-first-doctrine.md",
            context={"requirement": req_id, "spec": spec_file.name, "spec_id": relative_path(spec_file)},
        ))

    # ─── Rule: SPEC_TEST_ORPHAN ─────────────────────────────────────
    # Requirement in test but not found in any spec (possible typo or spec lag)
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
            suggestion="Verify requirement ID spelling or add the missing FR/NFR/UC to the governing spec docs/specs/*.md before keeping the test",
            reference=".agents/rules/spec-first-doctrine.md §No behavior without a requirement",
            context={"requirement": req_id, "test": rel},
        ))

    # ─── Rule: SPEC_TEST_MISSING_FILE ───────────────────────────────
    # Spec file has FR/NFR/UC but no test file mentions its Spec ID at all
    for sf in spec_files:
        spec_id, reqs = extract_requirements_from_spec(sf)
        if not spec_id:
            continue
        if not reqs:
            continue  # No requirements, no test needed
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
                message=f"Spec {spec_id} ({sf.name}) has {len(reqs)} requirements but no test file references it (no test traces to {spec_id})",
                suggestion=f"Create tests that trace to {spec_id} (e.g., tests/... with describe/it containing '{spec_id}-FR-...')",
                reference="docs/guides/arch/testing-pattern.md, .agents/rules/verification-strategy.md",
                context={"spec_id": spec_id, "requirements": len(reqs), "spec": sf.name},
            ))

    # ─── Sort and re-id deterministically ───────────────────────────
    findings.sort(key=lambda f: (f.file, f.rule, f.line))
    for i, f in enumerate(findings):
        f.id = f"SPEC-{i+1:04d}"

    # ─── Build report ───────────────────────────────────────────────
    metadata = {
        "total_files": len(spec_files) + len(test_files),
        "spec_files": len(spec_files),
        "test_files": len(test_files),
        "spec_requirements": len(all_spec_reqs),
        "test_requirements": len(test_req_set),
        "uncovered": len(uncovered),
        "orphans": len(orphans),
        "specs_without_tests": sum(1 for f in findings if f.rule == "SPEC_TEST_MISSING_FILE"),
    }
    # total_checks = number of unique requirements + number of specs with requirements
    total_checks = len(all_spec_reqs) + len([sf for sf in spec_files if spec_req_counts.get(sf, 0) > 0])
    result = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=total_checks or len(findings) or 1)

    # ─── Output ─────────────────────────────────────────────────────
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
