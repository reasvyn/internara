#!/usr/bin/env python3
"""
scan_doc_links.py — Documentation Link & Freshness Validation
Validates all relative markdown links across docs/, .agents/context/, README.md, AGENTS.md:
file targets must exist, and in-page anchors must resolve to a heading. Also enforces the spec
filename convention and flags ALL markdown files whose `Last updated` metadata is missing or older than 7 days.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
DOCS_DIR = ROOT / "docs"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "doc-links"
STALE_DAYS = 7

LINK_PATTERN = re.compile(r"\[([^\]]*)\]\(([^)]+)\)")
ANCHOR_TARGET = re.compile(r"^#(.+)$")
HEADING_PATTERN = re.compile(r"^#{1,6}\s+(.+)$")
SPECS_DIR = ROOT / "docs" / "specs"
SPEC_FILE_PATTERN = re.compile(r"^([A-Z0-9]{5})-[a-z0-9-]+\.md$")
SPEC_ID_LINE = re.compile(r"^>\s*\*\*Spec ID:\*\*\s+([A-Z0-9]{5})\s*$", re.MULTILINE)
LAST_UPDATED_LINE = re.compile(r"\*\*Last updated:\*\*\s+(\d{4}-\d{2}-\d{2})")

RULE_TYPES = [
    "BROKEN_FILE_LINK",
    "BROKEN_ANCHOR",
    "SPEC_ID",
    "SPEC_ID_METADATA",
    "OUTDATED_DOC",
    "MISSING_METADATA",
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

def relative_path(path: Path) -> str:
    try:
        return str(path.relative_to(ROOT))
    except ValueError:
        return str(path)


def slugify_anchor(text: str) -> str:
    """GitHub-style anchor slug for a heading."""
    text = text.strip().lower()
    text = re.sub(r"[^\w\- ]", "", text)
    text = re.sub(r" ", "-", text)
    return text


def collect_headings(filepath: Path) -> set[str]:
    """All GitHub-style anchor slugs for a file's headings."""
    slugs: set[str] = set()
    try:
        lines = filepath.read_text(encoding="utf-8", errors="replace").splitlines()
    except OSError:
        return slugs
    for line in lines:
        m = HEADING_PATTERN.match(line)
        if m:
            slugs.add(slugify_anchor(m.group(1)))
    return slugs


# ─── Link extraction ────────────────────────────────────────────────────────

def find_markdown_files(module: str | None = None) -> list[Path]:
    if module:
        module_path = DOCS_DIR / module
        if module_path.exists():
            return sorted(module_path.rglob("*.md"))
        return []
    files = list(DOCS_DIR.rglob("*.md"))
    contexts_dir = ROOT / ".agents" / "context"
    if contexts_dir.exists():
        files.extend(contexts_dir.rglob("*.md"))
    for name in ["README.md", "AGENTS.md"]:
        f = ROOT / name
        if f.exists():
            files.append(f)
    return sorted(files)


def extract_links(filepath: Path) -> list[tuple[int, str, str]]:
    links: list[tuple[int, str, str]] = []
    try:
        lines = filepath.read_text(encoding="utf-8", errors="replace").splitlines()
    except OSError:
        return links
    for i, line in enumerate(lines, 1):
        for match in LINK_PATTERN.finditer(line):
            text, target = match.group(1), match.group(2)
            if target.lstrip().startswith(("<")):
                continue
            links.append((i, text, target))
    return links


# ─── Validation ─────────────────────────────────────────────────────────────

def validate_target(
    target: str,
    source_file: Path,
    heading_slugs: set[str],
    findings: list[Finding],
    file_rel: str,
    line_num: int,
) -> bool:
    # External / protocol links are always valid
    if target.startswith(("http://", "https://", "mailto:", "tel:", "//")):
        return True
    if target.startswith(("phpstan:", "vscode://")):
        return True

    anchor = ANCHOR_TARGET.match(target)
    if anchor:
        slug = slugify_anchor(anchor.group(1))
        if slug not in heading_slugs:
            findings.append(Finding(
                id=f"LINK-{len(findings)+1:03d}",
                rule="BROKEN_ANCHOR",
                severity="low",
                category="documentation",
                file=file_rel,
                line=line_num,
                message=f"Anchor '#{anchor.group(1)}' not found in {file_rel}",
                suggestion="Match the anchor to an existing heading, or remove it",
                reference="docs/conventions.md §Documentation Conventions",
                context={"target": target},
            ))
            return False
        return True

    path_part, _, anchor_part = target.partition("#")
    if not path_part:
        return True

    resolved = (source_file.parent / path_part).resolve()
    if not resolved.exists():
        findings.append(Finding(
            id=f"LINK-{len(findings)+1:03d}",
            rule="BROKEN_FILE_LINK",
            severity="low",
            category="documentation",
            file=file_rel,
            line=line_num,
            message=f"Target '{path_part}' does not exist",
            suggestion="Point the link to an existing file, or remove it",
            reference="docs/conventions.md §Documentation Conventions",
            context={"target": target},
        ))
        return False

    # Anchor on another file: verify heading exists in that file
    if anchor_part:
        other_slugs = collect_headings(resolved)
        slug = slugify_anchor(anchor_part)
        if slug not in other_slugs:
            findings.append(Finding(
                id=f"LINK-{len(findings)+1:03d}",
                rule="BROKEN_ANCHOR",
                severity="low",
                category="documentation",
                file=file_rel,
                line=line_num,
                message=f"Anchor '#{anchor_part}' not found in {relative_path(resolved)}",
                suggestion="Match the anchor to an existing heading, or remove it",
                reference="docs/conventions.md §Documentation Conventions",
                context={"target": target},
            ))
            return False

    return True


# ─── Report ─────────────────────────────────────────────────────────────────

# ─── Spec convention validation ──────────────────────────────────────────────

def validate_spec_conventions(findings: list[Finding]) -> int:
    """Enforce the XXXXX-description.md spec-ID convention (see spec-writing skill).

    Rules:
      S-1  spec filename matches ^[A-Z0-9]{5}-[a-z0-9-]+\\.md$
      S-2  the file's `> **Spec ID:**` metadata line matches the filename ID
    Returns the number of spec-related findings added.
    """
    added = 0
    if not SPECS_DIR.exists():
        return added

    # Non-spec docs living in specs/ (registry index, implementation matrix) are not
    # governed by the spec-ID filename convention.
    non_spec_files = {"index.md", "implementation-matrix.md"}

    for p in sorted(SPECS_DIR.glob("*.md")):
        if p.name in non_spec_files:
            continue
        rel = relative_path(p)
        m = SPEC_FILE_PATTERN.match(p.name)
        if not m:
            findings.append(Finding(
                id=f"SPEC-{len(findings)+1:03d}",
                rule="SPEC_ID",
                severity="medium",
                category="documentation",
                file=rel,
                line=1,
                message=f"Spec file '{p.name}' does not follow the XXXXX-description.md naming convention",
                suggestion="Rename to docs/specs/{ID}-{description}.md where {ID} is a unique 5-char A-Z0-9 ID",
                reference=".agents/skills/spec-writing/SKILL.md §Spec IDs",
            ))
            added += 1
            continue
        spec_id = m.group(1)
        content = p.read_text(encoding="utf-8", errors="replace")
        meta_match = SPEC_ID_LINE.search(content)
        meta_id = meta_match.group(1) if meta_match else None
        if meta_id != spec_id:
            findings.append(Finding(
                id=f"SPEC-{len(findings)+1:03d}",
                rule="SPEC_ID_METADATA",
                severity="medium",
                category="documentation",
                file=rel,
                line=3,
                message=(
                    f"Spec ID metadata mismatch in '{p.name}': filename ID '{spec_id}' "
                    f"{'but metadata is missing' if meta_id is None else f'vs metadata ID {meta_id}'}"
                ),
                suggestion=f"Add/align the `> **Spec ID:** {spec_id}` metadata line",
                reference=".agents/skills/spec-writing/SKILL.md §Spec IDs",
            ))
            added += 1

    return added


# ─── Doc freshness validation ────────────────────────────────────────────────

def scan_doc_freshness(files: list[Path], findings: list[Finding]) -> int:
    """Flag markdown files whose `Last updated` metadata is older than STALE_DAYS.

    Rules:
      F-1  `> **Last updated:** YYYY-MM-DD` date is older than STALE_DAYS → OUTDATED_DOC
      F-2  `> **Last updated:**` metadata line missing → MISSING_METADATA
    Returns the number of freshness-related findings added.
    """
    added = 0
    cutoff = datetime.now(timezone(timedelta(hours=7))).date() - timedelta(days=STALE_DAYS)

    for filepath in files:
        rel = relative_path(filepath)
        try:
            content = filepath.read_text(encoding="utf-8", errors="replace")
        except OSError:
            continue
        match = LAST_UPDATED_LINE.search(content)
        if not match:
            findings.append(Finding(
                id=f"STALE-{len(findings)+1:03d}",
                rule="MISSING_METADATA",
                severity="low",
                category="documentation",
                file=rel,
                line=1,
                message="Missing `> **Last updated:** YYYY-MM-DD` metadata line",
                suggestion="Add the metadata blockquote per doc conventions (line 3 after H1)",
                reference="docs/conventions.md §Documentation Conventions",
            ))
            added += 1
            continue
        last_updated = datetime.strptime(match.group(1), "%Y-%m-%d").date()
        if last_updated < cutoff:
            findings.append(Finding(
                id=f"STALE-{len(findings)+1:03d}",
                rule="OUTDATED_DOC",
                severity="medium",
                category="documentation",
                file=rel,
                line=1,
message=f"`Last updated` {match.group(1)} is older than {STALE_DAYS} days (cutoff {cutoff})",
            suggestion="Verify and synchronize this document's content against the actual codebase and governing specs to ensure consistency, then update `Last updated`",
            reference=".agents/skills/sync-docs/SKILL.md §Review Recent Git History",
            ))
            added += 1

    return added


# ─── Report ──────────────────────────────────────────────────────────────────

def build_report(
    findings: list[Finding],
    files: list[Path],
    start_time: float,
    total_links: int,
) -> ScanResult:
    elapsed_ms = int((time.time() - start_time) * 1000)
    by_severity: dict[str, int] = {"critical": 0, "high": 0, "medium": 0, "low": 0}
    for f in findings:
        by_severity[f.severity] = by_severity.get(f.severity, 0) + 1

    rules = set(f.rule for f in findings)
    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type="full",
        module=None,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": len(RULE_TYPES),
            "passed": len(RULE_TYPES) - len(rules),
            "failed": len(findings),
            "by_severity": by_severity,
            "total_links": total_links,
            "valid_links": total_links - len(findings),
            "outdated_docs": sum(1 for f in findings if f.rule == "OUTDATED_DOC"),
            "missing_metadata": sum(1 for f in findings if f.rule == "MISSING_METADATA"),
        },
        findings=[vars(f) for f in findings],
        metadata={
            "files_scanned": len(files),
            "link_rule_broken_file": sum(1 for f in findings if f.rule == "BROKEN_FILE_LINK"),
            "link_rule_broken_anchor": sum(1 for f in findings if f.rule == "BROKEN_ANCHOR"),
            "doc_rule_outdated": sum(1 for f in findings if f.rule == "OUTDATED_DOC"),
            "doc_rule_missing_metadata": sum(1 for f in findings if f.rule == "MISSING_METADATA"),
        },
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
    print(f"  DOC LINK & FRESHNESS SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Files scanned:      {result.metadata['files_scanned']}")
    print(f"  Total links:        {s['total_links']}")
    print(f"  Valid links:        {s['valid_links']}")
    print(f"  Outdated docs:      {s['outdated_docs']}")
    print(f"  Missing metadata:   {s['missing_metadata']}")
    print(f"  Categories passed:  {s['passed']}")
    print(f"  Findings:           {s['failed']}")
    print(f"    Critical: {bs.get('critical', 0)}")
    print(f"    High:     {bs.get('high', 0)}")
    print(f"    Medium:   {bs.get('medium', 0)}")
    print(f"    Low:      {bs.get('low', 0)}")
    print(f"  Time: {result.execution_time_ms}ms")
    print(f"{'='*60}\n")


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Validate relative links and anchors in markdown docs",
    )
    parser.add_argument("--module", "-m", help="Target specific docs subdirectory")
    parser.add_argument("--output", "-o", type=Path, help="Output file path")
    parser.add_argument(
        "--format", "-f", choices=["json", "text", "summary"], default="json"
    )
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

    files = find_markdown_files(args.module)
    findings: list[Finding] = []
    total_links = 0

    if not args.module:
        total_links += validate_spec_conventions(findings)
        scan_doc_freshness(files, findings)

    for filepath in files:
        rel = relative_path(filepath)
        heading_slugs = collect_headings(filepath)
        for line_num, text, target in extract_links(filepath):
            total_links += 1
            validate_target(target, filepath, heading_slugs, findings, rel, line_num)

    result = build_report(findings, files, start_time, total_links)

    if args.json or args.format == "json":
        print(json.dumps(vars(result), indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result)

    output_path = write_report(result, args.output)
    if not args.quiet:
        print(f"Report saved: {relative_path(output_path)}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
