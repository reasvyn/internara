#!/usr/bin/env python3
"""
scan_skills.py — Agent Skill Consistency Scan

Validates structural consistency of `.agents/skills/{name}/SKILL.md` files against
the AGENTS.md meta-framework: frontmatter, reference to the canonical `agent-workflow`
skill (single source of truth for the 5-step pipeline Understand → Plan → Implement →
Verify → Summarize), spec-first doctrine, size triage, git verification, cross-skill
handoffs, and — critically — NO duplicated generic workflow boilerplate (skills must
reference `agent-workflow` instead of restating the workflow). Skills with legitimately
different structures (orientation hub, quality gate, blind audit, tooling standards,
custom pipelines) are documented per-rule exemptions.
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
SKILLS_DIR = ROOT / ".agents" / "skills"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "skills"

REF_META = "AGENTS.md #Agent-Workflow"
REF_SIZE = "AGENTS.md #Size-Triage"
REF_WORKFLOW_SKILL = ".agents/skills/agent-workflow/SKILL.md"

# ─── Rules ──────────────────────────────────────────────────────────────────
# Each rule is a dict: id, severity, category, exempt (skills excluded from the
# rule), description, and a regex/requirement. Skills in `exempt` are documented
# structural exceptions (orientation hub, quality gate, blind audit, tooling).

RULES: list[dict[str, Any]] = [
    {
        "id": "SKILL_FRONTMATTER",
        "severity": "high",
        "category": "convention",
        "exempt": [],
        "name": "Frontmatter metadata",
        "description": "SKILL.md must declare `name:` and `description:` in YAML frontmatter",
        "reference": "AGENTS.md #Skill-Map",
    },
    {
        "id": "SKILL_WORKFLOW_REF",
        "severity": "high",
        "category": "convention",
        "exempt": [],
        "name": "References agent-workflow skill",
        "description": "Skill must reference the canonical `agent-workflow` skill (the single "
        "source of truth for the 5-step pipeline Understand → Plan → Implement → Verify → "
        "Summarize) instead of restating the workflow. Standard workflow skills must NOT "
        "duplicate the pipeline skeleton.",
        "reference": REF_WORKFLOW_SKILL,
    },
    {
        "id": "SKILL_NO_DUP_WORKFLOW",
        "severity": "high",
        "category": "convention",
        "exempt": ["agent-workflow"],
        "name": "No duplicated workflow boilerplate",
        "description": "Skill must NOT restate the generic workflow steps that live in "
        "`agent-workflow` (5-step pipeline mapping, generic Understand/Plan/Implement/Verify/"
        "Summarize steps). Restating them re-injects the same workflow into context on every "
        "skill load.",
        "reference": REF_WORKFLOW_SKILL,
    },
    {
        "id": "SKILL_SPEC_FIRST",
        "severity": "medium",
        "category": "convention",
        "exempt": ["qa-protocol"],
        "name": "Spec-first doctrine",
        "description": "Skill must reference the governing spec / spec-first doctrine "
        "(Spec-First: no behavior without a requirement)",
        "reference": REF_META,
    },
    {
        "id": "SKILL_SIZE_TRIAGE",
        "severity": "medium",
        "category": "convention",
        "exempt": [],
        "name": "Size triage",
        "description": "Skill must reference AGENTS.md Size Triage (S/M/L) and the L-size "
        "session-splitting protocol",
        "reference": REF_SIZE,
    },
    {
        "id": "SKILL_GIT_VERIFY",
        "severity": "medium",
        "category": "convention",
        "exempt": ["arch-guard", "context-awareness"],
        "name": "Git verification",
        "description": "Verify phase must reference `git status` + `git diff` "
        "(version-control verification, Edit Policy). Exempt: arch-guard (quality gate "
        "runs scanners, not code changes), context-awareness (orientation only, no code)",
        "reference": REF_META,
    },
    {
        "id": "SKILL_HANDOFFS",
        "severity": "medium",
        "category": "convention",
        "exempt": ["context-awareness"],
        "name": "Cross-skill handoffs",
        "description": "Skill must document upstream/downstream via 'Skill Handoffs', "
        "'Phase Context', or 'Integration with Other Skills'",
        "reference": "AGENTS.md #Skill-Handoffs",
    },
    {
        "id": "SKILL_RULES_DIR",
        "severity": "medium",
        "category": "convention",
        "exempt": [],
        "name": "Rules directory and Skill Rules mapping",
        "description": "Every skill must be rules-first: extract its rules into "
        "`skills/{name}/rules/*.md` (comprehensive prose — intent, rationale, how-to-apply, "
        "pitfalls, verification — never bare checklists) and map them in a `## Skill Rules` "
        "table in SKILL.md. Keeps rule context lean and the rules aliasable.",
        "reference": "AGENTS.md #Skill-Map",
    },
]

RE_WORKFLOW_REF = re.compile(r"agent-workflow|Agent Workflow|`agent-workflow`", re.IGNORECASE)
RE_NO_DUP_WORKFLOW = re.compile(
    r"Using this skill follows 4 phases|Using this skill follows 5 steps|mapped to AGENTS\.md 9-step|mapped to AGENTS\.md 5-step",
    re.IGNORECASE,
)
RE_SPEC_FIRST = re.compile(
    r"governing spec|spec[-\s]?first|Spec[-\s]?First|spec requirements", re.IGNORECASE
)
RE_SIZE = re.compile(
    r"Size Triage|Size-aware|Size first|Classify the size", re.IGNORECASE
)
RE_GIT = re.compile(r"git status[\s\S]{0,200}?git diff|git diff[\s\S]{0,200}?git status", re.IGNORECASE)
RE_HANDOFFS = re.compile(
    r"Skill Handoffs|Phase Context|Integration with Other Skills|upstream:|downstream:"
)
RE_SKILL_RULES = re.compile(r"^##\s+Skill Rules$", re.MULTILINE)
RE_LAST_UPDATED = re.compile(r">\s*\*\*Last updated:\*\*")

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

def find_skill_files(skill: str | None = None) -> list[Path]:
    """Find SKILL.md files, optionally filtered by skill name."""
    if not SKILLS_DIR.exists():
        return []
    if skill:
        skill_dir = SKILLS_DIR / skill
        if not skill_dir.exists():
            return []
        return sorted(skill_dir.rglob("SKILL.md"))
    return sorted(SKILLS_DIR.rglob("SKILL.md"))


def read_file(path: Path) -> str:
    try:
        return path.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return ""


def relative_path(path: Path) -> str:
    try:
        return str(path.relative_to(ROOT))
    except ValueError:
        return str(path)


def skill_name_of(path: Path) -> str:
    """Derive the skill name (directory basename) from the SKILL.md path."""
    parts = path.parts
    try:
        idx = parts.index("skills")
        return parts[idx + 1]
    except (ValueError, IndexError):
        return path.parent.name


# ─── Scanners ───────────────────────────────────────────────────────────────

def scan_frontmatter(path: Path, content: str, name: str) -> list[Finding]:
    findings: list[Finding] = []
    front = re.match(r"^---\n(.*?)\n---", content, re.DOTALL)
    if not front:
        findings.append(Finding(
            id=f"SKILL-FM-{name}",
            rule="SKILL_FRONTMATTER",
            severity="high",
            category="convention",
            file=relative_path(path),
            line=1,
            message=f"Skill '{name}' is missing YAML frontmatter",
            suggestion="Add `name:` and `description:` frontmatter at the top of the file",
            reference=RULES[0]["reference"],
        ))
        return findings
    body = front.group(1)
    if not re.search(r"^name:\s*\S", body, re.M):
        findings.append(Finding(
            id=f"SKILL-FM-{name}",
            rule="SKILL_FRONTMATTER",
            severity="high",
            category="convention",
            file=relative_path(path),
            line=1,
            message=f"Skill '{name}' frontmatter is missing `name:`",
            suggestion="Add `name: {skill-name}` to frontmatter",
            reference=RULES[0]["reference"],
        ))
    if not re.search(r"^description:", body, re.M):
        findings.append(Finding(
            id=f"SKILL-FM-{name}",
            rule="SKILL_FRONTMATTER",
            severity="high",
            category="convention",
            file=relative_path(path),
            line=1,
            message=f"Skill '{name}' frontmatter is missing `description:`",
            suggestion="Add a `description:` summarizing when the skill activates",
            reference=RULES[0]["reference"],
        ))
    return findings


def scan_workflow_ref(path: Path, content: str, name: str) -> list[Finding]:
    if name in RULES[1]["exempt"]:
        return []
    if RE_WORKFLOW_REF.search(content):
        return []
    return [Finding(
        id=f"SKILL-WR-{name}",
        rule="SKILL_WORKFLOW_REF",
        severity="high",
        category="convention",
        file=relative_path(path),
        line=1,
        message=f"Skill '{name}' does not reference the canonical `agent-workflow` skill",
        suggestion="Add a one-line reference to the workflow: 'Follow the `agent-workflow` "
        "skill (5-step pipeline Understand → Plan → Implement → Verify → Summarize) — this skill "
        "adds task-specific steps.'",
        reference=RULES[1]["reference"],
    )]


def scan_no_dup_workflow(path: Path, content: str, name: str) -> list[Finding]:
    if name in RULES[2]["exempt"]:
        return []
    if not RE_NO_DUP_WORKFLOW.search(content):
        return []
    return [Finding(
        id=f"SKILL-NDW-{name}",
        rule="SKILL_NO_DUP_WORKFLOW",
        severity="high",
        category="convention",
        file=relative_path(path),
        line=1,
        message=f"Skill '{name}' duplicates the canonical workflow (5-step skeleton / "
        "AGENTS.md 5-step mapping) instead of referencing `agent-workflow`",
        suggestion="Remove the duplicated workflow section and reference `agent-workflow`: "
        "keep only this skill's unique execution steps, rules, and references",
        reference=RULES[2]["reference"],
    )]


def scan_spec_first(path: Path, content: str, name: str) -> list[Finding]:
    if name in RULES[3]["exempt"]:
        return []
    if RE_SPEC_FIRST.search(content):
        return []
    return [Finding(
        id=f"SKILL-SF-{name}",
        rule="SKILL_SPEC_FIRST",
        severity="medium",
        category="convention",
        file=relative_path(path),
        line=1,
        message=f"Skill '{name}' does not reference the governing spec / spec-first doctrine",
        suggestion="Add to the Understand/Plan phase: locate the governing spec (`docs/specs/`) and "
        "list the FR/NFR/UC IDs it defines before any work (Spec-First Doctrine)",
        reference=RULES[3]["reference"],
    )]


def scan_size_triage(path: Path, content: str, name: str) -> list[Finding]:
    if RE_SIZE.search(content):
        return []
    return [Finding(
        id=f"SKILL-ST-{name}",
        rule="SKILL_SIZE_TRIAGE",
        severity="medium",
        category="convention",
        file=relative_path(path),
        line=1,
        message=f"Skill '{name}' does not reference AGENTS.md Size Triage",
        suggestion="Add to the Understand phase: classify the size (S/M/L) per AGENTS.md Size "
        "Triage; if L-size, inform the user and split into sessions",
        reference=RULES[4]["reference"],
    )]


def scan_git_verify(path: Path, content: str, name: str) -> list[Finding]:
    if name in RULES[5]["exempt"]:
        return []
    # Satisfied by a direct git status/diff reference OR by referencing agent-workflow
    # (which owns the generic version-control verification step).
    if RE_GIT.search(content) or RE_WORKFLOW_REF.search(content):
        return []
    return [Finding(
        id=f"SKILL-GV-{name}",
        rule="SKILL_GIT_VERIFY",
        severity="medium",
        category="convention",
        file=relative_path(path),
        line=1,
        message=f"Skill '{name}' does not reference git status/diff verification",
        suggestion="Add to the Verify phase: run `git status` + `git diff` to confirm only "
        "intended files changed (version-control verification)",
        reference=RULES[5]["reference"],
    )]


def scan_handoffs(path: Path, content: str, name: str) -> list[Finding]:
    if name in RULES[6]["exempt"]:
        return []
    if RE_HANDOFFS.search(content):
        return []
    return [Finding(
        id=f"SKILL-HO-{name}",
        rule="SKILL_HANDOFFS",
        severity="medium",
        category="convention",
        file=relative_path(path),
        line=1,
        message=f"Skill '{name}' does not document cross-skill handoffs",
        suggestion="Add a 'Phase Context' table (upstream/this/downstream) or a "
        "'Skill Handoffs (Actionable)' condition→action table",
        reference=RULES[6]["reference"],
    )]


def scan_rules_dir(path: Path, content: str, name: str) -> list[Finding]:
    if name in RULES[7]["exempt"]:
        return []
    rules_dir = path.parent / "rules"
    if not rules_dir.is_dir():
        return [Finding(
            id=f"SKILL-RD-{name}",
            rule="SKILL_RULES_DIR",
            severity="medium",
            category="convention",
            file=relative_path(path),
            line=1,
            message=f"Skill '{name}' has no `rules/` directory",
            suggestion="Extract the skill's rules into `skills/{name}/rules/*.md` "
            "(comprehensive prose, never bare checklists) and map them in a `## Skill Rules` "
            "table in SKILL.md",
            reference=RULES[7]["reference"],
        )]
    findings: list[Finding] = []
    if not RE_SKILL_RULES.search(content):
        findings.append(Finding(
            id=f"SKILL-RD-{name}",
            rule="SKILL_RULES_DIR",
            severity="medium",
            category="convention",
            file=relative_path(path),
            line=1,
            message=f"Skill '{name}' has a `rules/` dir but SKILL.md has no `## Skill Rules` "
            "mapping table",
            suggestion="Add a `## Skill Rules` table ('| Rule | Asset | Applies when |') "
            "mapping each rule file, one row per asset",
            reference=RULES[7]["reference"],
        ))
    rule_files = sorted(rules_dir.glob("*.md"))
    if rule_files and not any(f.stem in content for f in rule_files):
        if not re.search(r"rules/", content):
            findings.append(Finding(
                id=f"SKILL-RD-{name}",
                rule="SKILL_RULES_DIR",
                severity="medium",
                category="convention",
                file=relative_path(path),
                line=1,
                message=f"Skill '{name}' has {len(rule_files)} rule file(s) in `rules/` but "
                "SKILL.md does not reference any of them",
                suggestion="Ensure every rule file is referenced in the `## Skill Rules` table "
                "(asset links like `rules/{file}.md`)",
                reference=RULES[7]["reference"],
            ))
    return findings


# ─── Report ─────────────────────────────────────────────────────────────────

def build_report(
    findings: list[Finding],
    scan_type: str,
    module: str | None,
    start_time: float,
    metadata: dict[str, Any],
) -> ScanResult:
    elapsed_ms = int((time.time() - start_time) * 1000)
    by_severity: dict[str, int] = {"critical": 0, "high": 0, "medium": 0, "low": 0}
    for f in findings:
        by_severity[f.severity] = by_severity.get(f.severity, 0) + 1

    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": len(RULES),
            "passed": len(RULES),
            "failed": len(findings),
            "by_severity": by_severity,
        },
        findings=[vars(f) for f in findings],
        metadata=metadata,
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
    print(f"  SKILL CONSISTENCY SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Rules checked:     {s['total_checks']}")
    print(f"  Findings:          {s['failed']}")
    print(f"    High:     {bs.get('high', 0)}")
    print(f"    Medium:   {bs.get('medium', 0)}")
    print(f"    Low:      {bs.get('low', 0)}")
    print(f"  Time: {result.execution_time_ms}ms")
    print(f"{'='*60}\n")


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Scan .agents/skills/*/SKILL.md for meta-framework consistency",
    )
    parser.add_argument("--module", "-m", help="Target specific skill name")
    parser.add_argument("--output", "-o", type=Path, help="Output file path")
    parser.add_argument(
        "--format", "-f", choices=["json", "text", "summary"], default="json"
    )
    parser.add_argument("--verbose", "-v", action="store_true")
    parser.add_argument("--quiet", "-q", action="store_true")
    parser.add_argument("--strict", "-s", action="store_true")
    parser.add_argument("--json", action="store_true")
    parser.add_argument("--severity", choices=["critical", "high", "medium", "low"], help="Filter by minimum severity")
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    files = find_skill_files(args.module)

    findings: list[Finding] = []
    for fp in files:
        content = read_file(fp)
        if not content:
            continue
        name = skill_name_of(fp)
        findings.extend(scan_frontmatter(fp, content, name))
        findings.extend(scan_workflow_ref(fp, content, name))
        findings.extend(scan_no_dup_workflow(fp, content, name))
        findings.extend(scan_spec_first(fp, content, name))
        findings.extend(scan_size_triage(fp, content, name))
        findings.extend(scan_git_verify(fp, content, name))
        findings.extend(scan_handoffs(fp, content, name))
        findings.extend(scan_rules_dir(fp, content, name))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {"total_skill_files": len(files), "rules": [r["id"] for r in RULES]},
    )

    if args.json or args.format == "json":
        print(json.dumps(vars(result), indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result)
        for f in findings:
            print(f"  [{f.rule}] {f.file}: {f.message}")

    output_path = write_report(result, args.output)
    if not args.quiet:
        print(f"Report saved: {relative_path(output_path)}")

    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
