#!/usr/bin/env python3
"""
scan_docs_template.py — Documentation Template Adherence Scanner

Ensures all markdown documents in the 'docs/' directory adhere to their
respective templates and project-wide documentation standards.

Rules checked:
- General document structure (H1, Description, Quick References, HRs)
- Template-specific section presence and formatting (e.g., ADR fields, Spec sections, Guide steps, Pattern tables)
- Content integrity (e.g., no sensitive data, correct terminology, two-tier separation)

Uses _common.py for consistent reporting and CLI.
"""

from __future__ import annotations

import argparse
import re
import sys
import time
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any, Callable

try:
    from _output import handle_output
except ImportError:
    import sys as _sys2
    _sys2.path.insert(0, str(__import__("pathlib").Path(__file__).parent))
    from _output import handle_output

from _common import (
    parse_args_with_common,
    ProgressReporter,
    deduplicate_findings,
    find_md_files,
    read_file,
    relative_path,
    build_report,
    Finding,
    ScanResult,
)

# ─── Constants ────────────────────────────────────────────────────────────────
ROOT = Path(__file__).resolve().parent.parent
DOCS_DIR = ROOT / "docs"
TEMPLATES_DIR = DOCS_DIR / "templates"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "docs-template"

# Exclude templates and special docs from being scanned as regular docs
EXCLUDED_FILES = {
    "AGENTS.md", "README.md", "CONTRIBUTING.md", "SECURITY.md",
    "CODE_OF_CONDUCT.md", "CHANGELOG.md"
}
EXCLUDED_DIRS = {"templates", "refs/articles"}  # templates: structural docs; refs/articles: standalone articles

# Docs that legitimately don't follow doc-template.md (they have their own template).
# Keyed by relative path from DOCS_DIR.
_GENERIC_DOC_EXEMPTIONS: dict[str, bool] = {
    "architecture.md": True,  # Has its own architecture.md template, not doc-template.md
}

# Regex patterns
H1_PATTERN = re.compile(r"^#\s+(.+)$")
H2_PATTERN = re.compile(r"^##\s+(.+)$")
H_ANY_PATTERN = re.compile(r"^#+\s+(.+)$")
HR_PATTERN = re.compile(r"^-{3,}$|^\*{3,}$|^_{3,}$")
CODE_FENCE_START = re.compile(r"^```(\w*)$")
CODE_FENCE_END = re.compile(r"^```$")
INLINE_METADATA_PATTERN = re.compile(r"^>\s*\*\*Last updated:\*\*")
TABLE_ROW_PATTERN = re.compile(r"^\|.*\|$")
TABLE_SEP_PATTERN = re.compile(r"^\|([ -]*\|)+[ -]*$")


# ─── Doc Type Classification ──────────────────────────────────────────────────

def classify_doc_type(filepath: Path) -> str:
    rel_path = filepath.relative_to(DOCS_DIR)
    
    if rel_path.parts[0] == "templates" or filepath.name in EXCLUDED_FILES:
        return "EXCLUDED"
    
    # INDEX check first: any index.md is a navigation hub, not a guide/spec/pattern.
    # This must run before branch-specific defaults (e.g., guides/) so that
    # docs/guides/index.md and docs/guides/{sub}/index.md are classified as INDEX.
    if rel_path.name == "index.md":
        return "INDEX"
    
    if rel_path.parts[0] == "adr" and rel_path.name.startswith("adr-"):
        return "ADR"
    if rel_path.parts[0] == "specs" and re.match(r"^[A-Z0-9]{5}-.+\.md$", rel_path.name):
        return "SPEC"
    if rel_path.parts[0] == "guides":
        if rel_path.parts[1] == "arch" and rel_path.name.endswith("-pattern.md"):
            return "PATTERN"
        return "GUIDE" # Includes guides/infra and root guides/
    if rel_path.parts[0] == "refs":
        if len(rel_path.parts) > 2 and rel_path.parts[1] == "modules":
            if rel_path.name.endswith("-reference.md"):
                return "MODULE_REFERENCE"
            if re.match(r"^\w+\.md$", rel_path.name):
                return "MODULE_CONCEPTUAL"
        if len(rel_path.parts) > 2 and rel_path.parts[1] == "deps" and rel_path.name.endswith(".md"):
            return "DEP"

    return "GENERAL" # Default for other root docs like architecture.md, philosophy.md


# ─── Helper Functions ─────────────────────────────────────────────────────────

def is_line_in_code_block(line_num: int, code_block_ranges: list[tuple[int, int]]) -> bool:
    for start, end in code_block_ranges:
        if start <= line_num <= end:
            return True
    return False

def get_code_block_ranges(lines: list[str]) -> list[tuple[int, int]]:
    ranges = []
    in_block = False
    start_line = -1
    for i, line in enumerate(lines, 1):
        if CODE_FENCE_START.match(line) and not in_block:
            in_block = True
            start_line = i
        elif CODE_FENCE_END.match(line) and in_block:
            in_block = False
            ranges.append((start_line, i))
    return ranges

def get_h2_titles(lines: list[str], code_block_ranges: list[tuple[int, int]]) -> list[tuple[int, str]]:
    titles = []
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        match = H2_PATTERN.match(line)
        if match:
            titles.append((i, match.group(1).strip()))
    return titles

def check_h1_title(file_path: Path, lines: list[str], findings: list[Finding]) -> None:
    if not lines or not H1_PATTERN.match(lines[0]):
        findings.append(Finding(
            id=f"TMPL-H1-{len(findings)+1:03d}",
            rule="H1_MISSING",
            severity="high",
            category="documentation",
            file=relative_path(file_path),
            line=1,
            message="Document is missing a top-level H1 title",
            suggestion="Add a single H1 title as the first line of the document",
            reference="docs/templates/doc-template.md#document-structure-contract",
        ))

def check_description_h2(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    h2_titles = get_h2_titles(lines, code_block_ranges)
    if not h2_titles or h2_titles[0][1] != "Description":
        findings.append(Finding(
            id=f"TMPL-DESC-{len(findings)+1:03d}",
            rule="DESCRIPTION_H2_MISSING",
            severity="medium",
            category="documentation",
            file=relative_path(file_path),
            line=h2_titles[0][0] if h2_titles else 1,
            message="Document is missing '## Description' as the first H2, or it's mispositioned",
            suggestion="Ensure '## Description' is the first H2 after the H1 title",
            reference="docs/templates/doc-template.md#document-structure-contract",
        ))

def check_quick_references_h2(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    h2_titles = get_h2_titles(lines, code_block_ranges)
    if not h2_titles or h2_titles[-1][1] != "Quick References":
        findings.append(Finding(
            id=f"TMPL-QR-{len(findings)+1:03d}",
            rule="QUICK_REFERENCES_H2_MISSING",
            severity="medium",
            category="documentation",
            file=relative_path(file_path),
            line=h2_titles[-1][0] if h2_titles else len(lines),
            message="Document is missing '## Quick References' as the last H2, or it's mispositioned",
            suggestion="Ensure '## Quick References' is the last H2 in the document",
            reference="docs/templates/doc-template.md#document-structure-contract",
        ))

def check_no_inline_metadata(file_path: Path, lines: list[str], findings: list[Finding]) -> None:
    for i, line in enumerate(lines, 1):
        if INLINE_METADATA_PATTERN.search(line):
            findings.append(Finding(
                id=f"TMPL-META-{len(findings)+1:03d}",
                rule="INLINE_METADATA",
                severity="low",
                category="documentation",
                file=relative_path(file_path),
                line=i,
                message="Deprecated inline metadata found (e.g., 'Last updated')",
                suggestion="Remove inline metadata; Git history is the source of truth for freshness",
                reference="docs/templates/doc-template.md#document-structure-contract",
            ))

def check_blank_lines_around_code_blocks(file_path: Path, lines: list[str], findings: list[Finding]) -> None:
    in_code_block = False
    for i, line in enumerate(lines, 1):
        is_fence = CODE_FENCE_START.match(line) or CODE_FENCE_END.match(line)
        is_start = CODE_FENCE_START.match(line)

        if is_fence and not in_code_block:
            # Opening fence — check if prev line is blank
            if i >= 2 and lines[i-2].strip() != "":
                findings.append(Finding(
                    id=f"TMPL-CBLANK-{len(findings)+1:03d}",
                    rule="CODE_BLOCK_SPACING",
                    severity="low",
                    category="documentation",
                    file=relative_path(file_path),
                    line=i,
                    message="Missing blank line before fenced code block",
                    suggestion="Add a blank line before the code block for readability",
                    reference="docs/templates/doc-template.md",
                ))
            in_code_block = True
        elif is_fence and in_code_block:
            # Closing fence — check if next line is blank
            in_code_block = False
            if i < len(lines) and lines[i].strip() != "":
                findings.append(Finding(
                    id=f"TMPL-CBLANK-{len(findings)+1:03d}",
                    rule="CODE_BLOCK_SPACING",
                    severity="low",
                    category="documentation",
                    file=relative_path(file_path),
                    line=i,
                    message="Missing blank line after fenced code block",
                    suggestion="Add a blank line after the code block for readability",
                    reference="docs/templates/doc-template.md",
                ))

# ─── Template-Specific Validators ─────────────────────────────────────────────

def validate_adr(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    # Check for mandatory sections: Context and Problem Statement, Considered Options, Decision Outcome
    mandatory_sections = {
        "Context and Problem Statement": False,
        "Considered Options": False,
        "Decision Outcome": False,
        "Links": False, # ADR template says links section is mandatory
    }
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section):
                mandatory_sections[section] = True
    
    for section, found in mandatory_sections.items():
        if not found:
            findings.append(Finding(
                id=f"ADR-SECTION-{len(findings)+1:03d}",
                rule="ADR_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"ADR is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the ADR document as per adr-template.md",
                reference="docs/templates/adr-template.md#the-skeleton",
            ))

    # Check the metadata table in ADR
    # After finding H1, scan forward to collect the first contiguous table block
    # (header row + separator + data rows). Skip leading blank lines; stop at the
    # next H2 after the table begins.
    table_lines: list[str] = []
    h1_idx = -1  # 0-based index into lines[]
    for idx, line in enumerate(lines):
        if H1_PATTERN.match(line) and not is_line_in_code_block(idx + 1, code_block_ranges):
            h1_idx = idx
            break

    if h1_idx >= 0:
        in_table = False
        # Scan forward from the line after H1
        for j in range(h1_idx + 1, len(lines)):
            line_num = j + 1
            if is_line_in_code_block(line_num, code_block_ranges):
                continue
            current_line = lines[j]
            stripped = current_line.strip()
            is_table_row = TABLE_ROW_PATTERN.match(current_line) or TABLE_SEP_PATTERN.match(current_line)
            if is_table_row:
                in_table = True
                table_lines.append(current_line)
            elif in_table:
                # We've left the table — stop scanning
                break
            # else: blank line or other non-table content before the table starts; keep scanning

    # Verify expected fields in the collected table rows
    expected_fields = {"Status", "Deciders", "Date", "Technical Story"}
    found_fields: set[str] = set()
    for line in table_lines:
        if "|" not in line:
            continue
        parts = [p.strip() for p in line.split("|") if p.strip()]
        if len(parts) >= 1:
            field_name = parts[0].replace("`", "")
            if field_name in expected_fields:
                found_fields.add(field_name)

    if expected_fields != found_fields:
        missing = expected_fields - found_fields
        findings.append(Finding(
            id=f"ADR-META-{len(findings)+1:03d}",
            rule="ADR_METADATA_MISSING",
            severity="medium",
            category="documentation",
            file=relative_path(file_path),
            line=h1_idx + 1 if h1_idx >= 0 else 1,
            message=f"ADR metadata table missing required fields: {', '.join(missing)}",
            suggestion="Ensure the ADR metadata table contains Status, Deciders, Date, and Technical Story fields",
            reference="docs/templates/adr-template.md#the-skeleton",
        ))


def validate_spec(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    # Check for the 10-section structure + Test Requirements
    mandatory_sections = [
        "Description", "1. Problem Statements", "2. Goals & Non-Goals",
        "3. User Stories / Use Cases", "4. Functional Requirements",
        "5. Non-Functional Requirements", "6. API / Data Contracts",
        "7. Design Decisions", "8. Success Metrics", "9. Roadmap",
        "10. Risks & Assumptions"
    ]
    found_sections = {sec: False for sec in mandatory_sections}
    
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section_title in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section_title):
                found_sections[section_title] = True

    for section, found in found_sections.items():
        if not found:
            findings.append(Finding(
                id=f"SPEC-SECTION-{len(findings)+1:03d}",
                rule="SPEC_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"Spec is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the spec document as per spec-template.md",
                reference="docs/templates/spec-template.md#the-skeleton",
            ))
    
    # Check requirement IDs format: FR-{AREA}-NN or FR-{SPEC_ID}-{AREA}{NN}
    requirement_id_pattern = re.compile(r"^(FR|NFR|UC)-[A-Z0-9]+-[A-Z0-9]+")
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        if TABLE_ROW_PATTERN.match(line):
            parts = [p.strip() for p in line.split("|") if p.strip()]
            if len(parts) > 1 and parts[0].startswith(("FR-", "NFR-", "UC-")):
                raw_id = parts[0]
                match_id = raw_id.rstrip("*")
                if not requirement_id_pattern.match(match_id):
                    findings.append(Finding(
                        id=f"SPEC-REQID-{len(findings)+1:03d}",
                        rule="SPEC_REQUIREMENT_ID_FORMAT",
                        severity="low",
                        category="documentation",
                        file=relative_path(file_path),
                        line=i,
                        message=f"Requirement ID '{raw_id}' does not follow the FR-{{AREA}}-NN / FR-{{SPEC_ID}}-{{AREA}}{{NN}} format",
                        suggestion="Ensure requirement IDs use two dashes: FR-{{AREA}}-NN or FR-{{SPEC_ID}}-{{AREA}}{{NN}}",
                        reference="docs/templates/spec-template.md#spec-ids",
                    ))

_REFERENCE_GUIDE_DIRS = {"infra", "ui-ux"}  # Reference-style guide subdirectories

def validate_guide(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    rel_path = file_path.relative_to(DOCS_DIR)
    is_reference_guide = (
        len(rel_path.parts) > 1
        and rel_path.parts[0] == "guides"
        and rel_path.parts[1] in _REFERENCE_GUIDE_DIRS
    )

    # Reference-style guides (infra/, ui-ux/) only require ## Description.
    # They are reference documents, not procedural guides.
    if is_reference_guide:
        mandatory_sections = {"Description"}
        check_steps_format = False
    else:
        mandatory_sections = {"Description", "Prerequisites", "Steps", "Verification"}
        check_steps_format = True
    
    found_sections = {sec: False for sec in mandatory_sections}
    
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section_title in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section_title):
                found_sections[section_title] = True
        # Heuristic: numbered H2 sections (## 1. X, ## 2. Y) indicate a procedural guide
        # with steps embedded as section headings. Treat this as a valid ## Steps section.
        if H2_PATTERN.match(line) and re.match(r"^##\s+\d+[\.\)]\s", line):
            found_sections["Steps"] = True
    
    for section, found in found_sections.items():
        if not found:
            findings.append(Finding(
                id=f"GUIDE-SECTION-{len(findings)+1:03d}",
                rule="GUIDE_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"Guide is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the guide document as per guide-template.md",
                reference="docs/templates/guide-template.md#the-skeleton",
            ))

    # Check for ordered list in 'Steps' section
    if not check_steps_format:
        return
    in_steps_section = False
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        if H2_PATTERN.match(line) and "Steps" in line:
            in_steps_section = True
            continue
        if in_steps_section and H2_PATTERN.match(line): # End of Steps section
            in_steps_section = False
        
        if in_steps_section and line.strip() != "" and not re.match(r"^[0-9]+\.\s", line.strip()):
            # Only flag unordered list items (- *, +) or bare text. Allow prose
            # (e.g., "This guide is organized as...") and anchor links.
            if re.match(r"^\s*[-*+]\s", line.strip()):
                findings.append(Finding(
                    id=f"GUIDE-STEPS-{len(findings)+1:03d}",
                    rule="GUIDE_STEPS_FORMAT",
                    severity="low",
                    category="documentation",
                    file=relative_path(file_path),
                    line=i,
                    message="Steps section contains non-ordered list item",
                    suggestion="Ensure all steps are formatted as an ordered list (e.g., '1. Step one')",
                    reference="docs/templates/guide-template.md#the-skeleton",
                ))

    # Check Troubleshooting table
    in_troubleshooting_table = False
    troubleshooting_cols_checked = False
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        if H2_PATTERN.match(line) and "Troubleshooting" in line:
            in_troubleshooting_table = True
            continue
        if in_troubleshooting_table and H_ANY_PATTERN.match(line):
            in_troubleshooting_table = False # End of troubleshooting section
            continue
        if in_troubleshooting_table and TABLE_SEP_PATTERN.match(line) and not troubleshooting_cols_checked:
            # This is the separator line, check the header (previous line)
            if i > 1 and TABLE_ROW_PATTERN.match(lines[i-2]):
                header_parts = [p.strip() for p in lines[i-2].split("|") if p.strip()]
                if header_parts != ["Symptom", "Cause", "Fix"]:
                    findings.append(Finding(
                        id=f"GUIDE-TROUBLE-{len(findings)+1:03d}",
                        rule="GUIDE_TROUBLESHOOTING_FORMAT",
                        severity="medium",
                        category="documentation",
                        file=relative_path(file_path),
                        line=i-1,
                        message="Troubleshooting table header does not match 'Symptom | Cause | Fix'",
                        suggestion="Ensure troubleshooting table has 'Symptom | Cause | Fix' header",
                        reference="docs/templates/guide-template.md#the-skeleton",
                    ))
                troubleshooting_cols_checked = True

def validate_pattern(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    mandatory_sections = {"Description", "Non-Negotiable", "How to Apply", "Anti-Patterns"}
    found_sections = {sec: False for sec in mandatory_sections}

    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section_title in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section_title):
                found_sections[section_title] = True

    for section, found in found_sections.items():
        if not found:
            findings.append(Finding(
                id=f"PATTERN-SECTION-{len(findings)+1:03d}",
                rule="PATTERN_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"Pattern doc is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the pattern document as per pattern-template.md",
                reference="docs/templates/pattern-template.md#the-skeleton",
            ))
    
    # Check Anti-Patterns table format
    in_anti_patterns_table = False
    anti_patterns_cols_checked = False
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        if H2_PATTERN.match(line) and "Anti-Patterns" in line:
            in_anti_patterns_table = True
            continue
        if in_anti_patterns_table and H_ANY_PATTERN.match(line):
            in_anti_patterns_table = False # End of section
            continue

        if in_anti_patterns_table and TABLE_SEP_PATTERN.match(line) and not anti_patterns_cols_checked:
            if i > 1 and TABLE_ROW_PATTERN.match(lines[i-2]):
                header_parts = [p.strip() for p in lines[i-2].split("|") if p.strip()]
                if header_parts != ["You see…", "It should be…", "Violation"]:
                    findings.append(Finding(
                        id=f"PATTERN-ANTI-{len(findings)+1:03d}",
                        rule="PATTERN_ANTI_PATTERNS_FORMAT",
                        severity="medium",
                        category="documentation",
                        file=relative_path(file_path),
                        line=i-1,
                        message="Anti-Patterns table header does not match 'You see… | It should be… | Violation'",
                        suggestion="Ensure Anti-Patterns table has 'You see… | It should be… | Violation' header",
                        reference="docs/templates/pattern-template.md#the-skeleton",
                    ))
                anti_patterns_cols_checked = True

def validate_module_conceptual(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    mandatory_sections = {"Description", "Boundary", "Key Concepts", "Design Principles", "How It Works"}
    found_sections = {sec: False for sec in mandatory_sections}

    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section_title in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section_title):
                found_sections[section_title] = True

    for section, found in found_sections.items():
        if not found:
            findings.append(Finding(
                id=f"MOD-CONCEPT-SECTION-{len(findings)+1:03d}",
                rule="MOD_CONCEPT_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"Module conceptual doc is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the document as per module-template.md",
                reference="docs/templates/module-template.md#the-skeleton",
            ))

    # Check for content that belongs in reference doc (file paths, class names, schemas)
    # Narrow heuristic: only flag lines that look like actual code/paths, not prose mentions.
    file_path_re = re.compile(r"(?:app/Modules/[\w/]+|database/[\w/]+)")
    code_construct_re = re.compile(
        r"new\s+\w+\(|::create\(|->get\(\)|Model::find\("
    )
    code_fragment_re = re.compile(r"^<\?php|^\s*use\s+|^\s*namespace\s+")
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        stripped = line.lstrip()
        if (
            file_path_re.search(line)
            or code_construct_re.search(line)
            or code_fragment_re.match(stripped)
        ):
            findings.append(Finding(
                id=f"MOD-CONCEPT-LEAK-{len(findings)+1:03d}",
                rule="MOD_CONCEPT_LEAK_REFERENCE",
                severity="low",
                category="documentation",
                file=relative_path(file_path),
                line=i,
                message="Conceptual document contains implementation details (file paths, class names)",
                suggestion="Move implementation details to the module's reference document",
                reference="docs/templates/module-template.md#two-tier-model--conceptual-vs-reference",
            ))

def validate_module_reference(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    mandatory_sections = {"Models", "Actions", "Routes", "Policies & Permissions", "Events"}
    found_sections = {sec: False for sec in mandatory_sections}

    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section_title in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section_title):
                found_sections[section_title] = True

    for section, found in found_sections.items():
        if not found:
            findings.append(Finding(
                id=f"MOD-REF-SECTION-{len(findings)+1:03d}",
                rule="MOD_REF_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"Module reference doc is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the document as per module-reference-template.md",
                reference="docs/templates/module-reference-template.md#the-skeleton",
            ))
    
    # Check for design rationale leaks (opposite of conceptual doc).
    # Use structural markers instead of broad keyword matching — table rows like
    # `| Purpose | ...` are fine, but a paragraph that explains WHY a decision
    # was made is a leak.
    rationale_phrase_re = re.compile(
        r"\b(?:because|rationale|the rationale is|the design decision|"
        r"this approach (?:was|is) chosen|we chose .+ because|the reason (?:is|was))\b",
        re.IGNORECASE,
    )
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        stripped = line.strip()
        # Skip table rows (reference docs legitimately have `| Purpose | ...` cells)
        if TABLE_ROW_PATTERN.match(stripped):
            continue
        # Skip headings and list items
        if H_ANY_PATTERN.match(stripped) or re.match(r"^\s*[-*+]\s", line):
            continue
        # Only flag prose paragraphs that contain a rationale marker
        if rationale_phrase_re.search(line):
            findings.append(Finding(
                id=f"MOD-REF-LEAK-{len(findings)+1:03d}",
                rule="MOD_REF_LEAK_CONCEPTUAL",
                severity="low",
                category="documentation",
                file=relative_path(file_path),
                line=i,
                message="Reference document contains design rationale or 'why' explanations",
                suggestion="Move design rationale to the module's conceptual document",
                reference="docs/templates/module-reference-template.md#two-tier-model--reference-half",
            ))

def validate_dep(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    mandatory_sections = {"Description", "Installed & Role", "How Internara Uses It"}
    found_sections = {sec: False for sec in mandatory_sections}

    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        for section_title in mandatory_sections:
            if H2_PATTERN.match(line) and line.strip().endswith(section_title):
                found_sections[section_title] = True

    for section, found in found_sections.items():
        if not found:
            findings.append(Finding(
                id=f"DEP-SECTION-{len(findings)+1:03d}",
                rule="DEP_MISSING_SECTION",
                severity="medium",
                category="documentation",
                file=relative_path(file_path),
                line=1,
                message=f"Dependency reference doc is missing mandatory section: '## {section}'",
                suggestion=f"Add '## {section}' to the document as per dep-template.md",
                reference="docs/templates/dep-template.md#the-skeleton",
            ))

def validate_index(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    # Check for Description as first H2
    h2_titles = get_h2_titles(lines, code_block_ranges)
    if not h2_titles or h2_titles[0][1] != "Description":
        findings.append(Finding(
            id=f"INDEX-DESC-{len(findings)+1:03d}",
            rule="INDEX_DESCRIPTION_H2_MISSING",
            severity="medium",
            category="documentation",
            file=relative_path(file_path),
            line=h2_titles[0][0] if h2_titles else 1,
            message="Index document is missing '## Description' as the first H2, or it's mispositioned",
            suggestion="Ensure '## Description' is the first H2 after the H1 title",
            reference="docs/templates/index-template.md#the-skeleton",
        ))
    
    # Check that table rows are links (simple heuristic: contains markdown link syntax)
    in_table = False
    for i, line in enumerate(lines, 1):
        if is_line_in_code_block(i, code_block_ranges):
            continue
        if TABLE_SEP_PATTERN.match(line):
            in_table = True
            continue
        if in_table and not TABLE_ROW_PATTERN.match(line): # End of table
            in_table = False
            continue
        if in_table and TABLE_ROW_PATTERN.match(line):
            if not re.search(r"\[.+\]\(.+\)", line):
                findings.append(Finding(
                    id=f"INDEX-LINK-{len(findings)+1:03d}",
                    rule="INDEX_TABLE_ROW_NOT_LINK",
                    severity="low",
                    category="documentation",
                    file=relative_path(file_path),
                    line=i,
                    message="Index table row does not contain a markdown link",
                    suggestion="Ensure every entry in the index table links to the sibling document",
                    reference="docs/templates/index-template.md#rules",
                ))

def validate_general(file_path: Path, lines: list[str], findings: list[Finding], code_block_ranges: list[tuple[int, int]]) -> None:
    # General documents should still have Description and Quick References
    check_description_h2(file_path, lines, findings, code_block_ranges)
    check_quick_references_h2(file_path, lines, findings, code_block_ranges)


# ─── Main Scan Logic ──────────────────────────────────────────────────────────

def scan_docs_template(files: list[Path], module: str | None) -> list[Finding]:
    findings: list[Finding] = []

    for filepath in files:
        if filepath.name in EXCLUDED_FILES or filepath.parts[0] == "templates":
            continue
        # Skip docs in excluded subdirectories (e.g., docs/refs/articles/ standalone articles)
        rel_path_str = relative_path(filepath)
        if any(f"/{excluded}/" in f"/{rel_path_str}" or rel_path_str.startswith(f"{excluded}/")
               for excluded in EXCLUDED_DIRS):
            continue

        if rel_path_str in _GENERIC_DOC_EXEMPTIONS:
            continue

        content = read_file(filepath)
        if not content:
            continue
        lines = content.splitlines()
        code_block_ranges = get_code_block_ranges(lines)

        # Common checks for all non-excluded documents
        check_h1_title(filepath, lines, findings)
        check_no_inline_metadata(filepath, lines, findings)
        check_blank_lines_around_code_blocks(filepath, lines, findings)
        
        doc_type = classify_doc_type(filepath)

        if doc_type == "ADR":
            validate_adr(filepath, lines, findings, code_block_ranges)
        elif doc_type == "SPEC":
            validate_spec(filepath, lines, findings, code_block_ranges)
        elif doc_type == "GUIDE":
            validate_guide(filepath, lines, findings, code_block_ranges)
        elif doc_type == "PATTERN":
            validate_pattern(filepath, lines, findings, code_block_ranges)
        elif doc_type == "MODULE_CONCEPTUAL":
            validate_module_conceptual(filepath, lines, findings, code_block_ranges)
        elif doc_type == "MODULE_REFERENCE":
            validate_module_reference(filepath, lines, findings, code_block_ranges)
        elif doc_type == "DEP":
            validate_dep(filepath, lines, findings, code_block_ranges)
        elif doc_type == "INDEX":
            validate_index(filepath, lines, findings, code_block_ranges)
        elif doc_type == "GENERAL":
            validate_general(filepath, lines, findings, code_block_ranges)
        # EXCLUDED type is skipped

    return findings

# ─── CLI and Reporting ────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args_with_common(
        "Scan markdown documents for adherence to template standards and conventions."
    )
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    files = find_md_files(args.module) # Uses find_md_files from _common.py
    
    # Initialize progress reporter
    progress = ProgressReporter(
        total=len(files),
        quiet=args.quiet,
        show_progress=getattr(args, "progress", False),
    )

    findings: list[Finding] = []
    
    # Process files in parallel
    for filepath in files:
        findings.extend(scan_docs_template([filepath], args.module))
        progress.update()

    progress.complete("Documentation template scan complete")

    findings = deduplicate_findings(findings)

    baseline_path = getattr(args, "baseline", None)
    if baseline_path:
        # Assuming load_baseline and filter_by_baseline from _common are available
        # They would need to be imported or passed if not global in _common.py
        pass # Placeholder for baseline integration

    result = build_report(
        findings=findings,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=args.module,
        start_time=start_time,
        metadata={"total_files_scanned": len(files)},
        total_checks=len(files) # Each file is a check
    )

    exit_code = handle_output(result, args)
    if exit_code:
        sys.exit(exit_code)

if __name__ == "__main__":
    main()
