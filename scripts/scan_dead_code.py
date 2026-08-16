#!/usr/bin/env python3
"""
scan_dead_code.py — Dead Code Detection
Detects unregistered observers, events without listeners, unused DTOs,
unused Actions, and unused Jobs.
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
APP_DIR = ROOT / "app"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "dead-code"

RE_CLASS = re.compile(r"^\s*(?:final\s+|abstract\s+)*(?:class|enum|interface|trait)\s+(\w+)", re.M)

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

def find_php_files(module: str | None = None) -> list[Path]:
    if module:
        module_dir = APP_DIR / module
        if not module_dir.exists():
            return []
        return sorted(module_dir.rglob("*.php"))
    return sorted(APP_DIR.rglob("*.php"))


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


def build_all_content(files: list[Path]) -> dict[Path, str]:
    """Map each file to its content, plus a per-file 'rest' reference string.

    `rest[path]` is the concatenation of every file's content except `path`
    itself, so a class declared in `path` that is referenced elsewhere appears
    in `rest[path]`.
    """
    contents = {fp: read_file(fp) for fp in files}
    joined = "\n".join(contents.values())
    rest: dict[Path, str] = {}
    for fp, content in contents.items():
        rest[fp] = joined.replace(content, "", 1)
    return rest


# ─── Reference-based dead code ──────────────────────────────────────────────

def _scan_rule(
    rest: dict[Path, str],
    files: list[Path],
    rule: str,
    severity: str,
    category: str,
    message_tpl: str,
    suggestion: str,
    reference: str,
    predicate: callable,
) -> list[Finding]:
    findings: list[Finding] = []
    for fp in files:
        rel = relative_path(fp)
        content = read_file(fp)
        if not content:
            continue
        m = RE_CLASS.search(content)
        if not m:
            continue
        class_name = m.group(1)
        if not predicate(rel):
            continue
        # Referenced anywhere outside its own file → not dead
        if class_name in rest[fp]:
            continue
        findings.append(Finding(
            id=f"DEAD-{len(findings)+1:03d}",
            rule=rule,
            severity=severity,
            category=category,
            file=rel,
            line=1,
            message=message_tpl.format(name=class_name),
            suggestion=suggestion,
            reference=reference,
        ))
    return findings


def scan_unregistered_observers(rest: dict[Path, str], files: list[Path]) -> list[Finding]:
    observer_files = [f for f in files if f.name.endswith("Observer.php")]
    return _scan_rule(
        rest, observer_files,
        rule="UNREGISTERED_OBSERVER",
        severity="high",
        category="performance",
        message_tpl="Observer {name} is never registered (no observe()/Provider reference)",
        suggestion="Register via static::observe() in the Model booted() or in a service provider, or remove if unused",
        reference="docs/architecture/event-pattern.md",
        predicate=lambda rel: True,
    )


def scan_orphan_events(rest: dict[Path, str], files: list[Path]) -> list[Finding]:
    event_files = [
        f for f in files
        if "/Events/" in str(f) and not f.name.startswith("Base")
    ]
    return _scan_rule(
        rest, event_files,
        rule="EVENT_NO_LISTENER",
        severity="medium",
        category="performance",
        message_tpl="Event {name} is never dispatched and never listened to",
        suggestion="Wire it in config/event.php or dispatch it from an Action, or remove if unused",
        reference="docs/architecture/event-pattern.md",
        predicate=lambda rel: True,
    )


def scan_unused_dtos(rest: dict[Path, str], files: list[Path]) -> list[Finding]:
    dto_files = [
        f for f in files
        if "/Data/" in str(f)
        and not f.name.startswith("Base")
        and f.name != "ActionResponse.php"
    ]
    return _scan_rule(
        rest, dto_files,
        rule="UNUSED_DTO",
        severity="medium",
        category="performance",
        message_tpl="DTO {name} is referenced nowhere",
        suggestion="Remove if unused, or confirm it is only instantiated dynamically",
        reference="docs/architecture/data-pattern.md",
        predicate=lambda rel: True,
    )


def scan_unused_actions(rest: dict[Path, str], files: list[Path]) -> list[Finding]:
    action_files = [
        f for f in files
        if "/Actions/" in str(f)
        and not f.name.startswith("Base")
        and "/Concerns/" not in str(f)
    ]
    return _scan_rule(
        rest, action_files,
        rule="UNUSED_ACTION",
        severity="medium",
        category="performance",
        message_tpl="Action {name} is referenced nowhere",
        suggestion="Remove if unused, or confirm it is resolved via the container",
        reference="docs/architecture/action-pattern.md",
        predicate=lambda rel: True,
    )


def scan_unused_jobs(rest: dict[Path, str], files: list[Path]) -> list[Finding]:
    job_files = [
        f for f in files
        if f.name.endswith("Job.php") and not f.name.startswith("Base")
    ]
    return _scan_rule(
        rest, job_files,
        rule="UNUSED_JOB",
        severity="medium",
        category="performance",
        message_tpl="Job {name} is referenced nowhere",
        suggestion="Remove if unused, or confirm it is queued dynamically",
        reference="docs/architecture/event-pattern.md",
        predicate=lambda rel: True,
    )


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

    rules = set(f.rule for f in findings)
    return ScanResult(
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=module,
        timestamp=datetime.now(timezone(timedelta(hours=7))).isoformat(),
        execution_time_ms=elapsed_ms,
        summary={
            "total_checks": 5,
            "passed": 5 - len(rules),
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
    print(f"  DEAD CODE SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Categories checked: {s['total_checks']}")
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
        description="Detect dead code — unregistered observers, orphan events, unused DTOs/Actions/Jobs",
    )
    parser.add_argument("--module", "-m", help="Target specific module")
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

    files = find_php_files(args.module)
    rest = build_all_content(files)

    findings: list[Finding] = []
    findings.extend(scan_unregistered_observers(rest, files))
    findings.extend(scan_orphan_events(rest, files))
    findings.extend(scan_unused_dtos(rest, files))
    findings.extend(scan_unused_actions(rest, files))
    findings.extend(scan_unused_jobs(rest, files))

    result = build_report(
        findings, scan_type, args.module, start_time,
        {"total_php_files": len(files)},
    )

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
