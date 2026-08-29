#!/usr/bin/env python3
"""
Enhanced v2.3: removed per-file freshness enforcement (git history is source of truth),
retains strict link validation (file, anchor, external HTTP 200).
scan_doc_links.py — Documentation Link Validation
Validates all relative markdown links across docs/, .agents/context/, README.md, AGENTS.md:
file targets must exist, and in-page anchors must resolve to a heading. Also validates every
external http(s) link with a live HTTP request — response must be 2xx/3xx (use --no-external to skip).
Enforces the spec filename convention. Freshness is tracked via git history, not inline metadata.
"""

from __future__ import annotations

import argparse
import json
import re
import ssl
import sys
try:
    from _output import handle_output
except ImportError:
    import sys as _sys2
    _sys2.path.insert(0, str(__import__("pathlib").Path(__file__).parent))
    from _output import handle_output
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
DOCS_DIR = ROOT / "docs"
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "doc-links"

# External link validation (opt-in via default-on; disable with --no-external)
EXTERNAL_TIMEOUT = 12  # seconds per request
EXTERNAL_WORKERS = 6  # parallel connections (lower to be gentler on remote hosts)
USER_AGENT = "Mozilla/5.0 (compatible; InternaraDocLinkChecker/1.0)"
EXTERNAL_RETRIES = 1  # retry once on timeout / transient error

LINK_PATTERN = re.compile(r"\[([^\]]*)\]\(([^)]+)\)")
ANCHOR_TARGET = re.compile(r"^#(.+)$")
HEADING_PATTERN = re.compile(r"^#{1,6}\s+(.+)$")
SPECS_DIR = ROOT / "docs" / "specs"
SPEC_FILE_PATTERN = re.compile(r"^([A-Z0-9]{5})-[a-z0-9-]+\.md$")
SPEC_ID_LINE = re.compile(r"^>\s*\*\*Spec ID:\*\*\s+([A-Z0-9]{5})\s*$", re.MULTILINE)

RULE_TYPES = [
    "BROKEN_FILE_LINK",
    "BROKEN_ANCHOR",
    "BROKEN_EXTERNAL_LINK",
    "UNVERIFIED_EXTERNAL_LINK",
    "SPEC_ID",
    "SPEC_ID_METADATA",
    "OUTDATED_DOC",
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

def git_last_modified(path: Path) -> datetime | None:
    """Return the last commit date for a file using git log, or None if not tracked."""
    try:
        import subprocess
        result = subprocess.run(
            ["git", "log", "-1", "--format=%ct", "--follow", "--", str(path)],
            capture_output=True, text=True, cwd=ROOT, timeout=5
        )
        if result.returncode == 0 and result.stdout.strip():
            timestamp = int(result.stdout.strip())
            return datetime.fromtimestamp(timestamp, tz=timezone.utc)
    except Exception:
        pass
    return None


def is_outdated(path: Path, days: int = 14) -> bool:
    """Check if a file hasn't been modified in more than `days` days."""
    last_modified = git_last_modified(path)
    if last_modified is None:
        return False  # Not tracked by git, skip freshness check
    cutoff = datetime.now(timezone.utc) - timedelta(days=days)
    return last_modified < cutoff


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
    in_fenced_block = False
    for i, line in enumerate(lines, 1):
        stripped = line.strip()
        if stripped.startswith("```"):
            in_fenced_block = not in_fenced_block
            continue
        if in_fenced_block:
            continue
        # Remove inline code spans (`...`) to avoid false positives from examples like `Verify every [text](path)`
        sanitized = re.sub(r"`[^`]*`", lambda m: " " * len(m.group(0)), line)
        for match in LINK_PATTERN.finditer(sanitized):
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


# ─── External link validation ───────────────────────────────────────────────

def check_external_url(url: str) -> tuple[bool, str]:
    """Return (is_valid, detail). Valid only when the final HTTP response is 2xx/3xx.

    Network/SSL errors and non-2xx responses are reported as invalid. Some hosts
    return 401/403/429 to automated agents even for valid pages — those are flagged
    as "likely invalid or bot-blocked" so a human can verify manually.
    Retries once on transient timeout / connection errors.
    """
    last_detail = ""
    for attempt in range(EXTERNAL_RETRIES + 1):
        try:
            req = urllib.request.Request(
                url, method="GET", headers={"User-Agent": USER_AGENT}
            )
            with urllib.request.urlopen(req, timeout=EXTERNAL_TIMEOUT) as resp:
                status = getattr(resp, "status", resp.getcode())
            if 200 <= status < 400:
                return True, f"HTTP {status}"
            if status in (401, 403, 429):
                return False, f"HTTP {status} (may be bot-blocked; verify manually)"
            return False, f"HTTP {status}"
        except urllib.error.HTTPError as e:
            code = getattr(e, "code", "?")
            if code in (401, 403, 429):
                return False, f"HTTP {code} (may be bot-blocked; verify manually)"
            return False, f"HTTP {code}"
        except (urllib.error.URLError, ssl.SSLError, TimeoutError, OSError) as e:
            reason = getattr(e, "reason", str(e))
            last_detail = f"connection error: {reason}"
            if attempt < EXTERNAL_RETRIES:
                time.sleep(0.6)
                continue
            return False, last_detail
    return False, last_detail or "unknown error"


def validate_external_links(
    external_links: list[tuple[str, str, int]],
    findings: list[Finding],
    workers: int = EXTERNAL_WORKERS,
) -> None:
    """Validate external (http/https) links in parallel; flag non-200 responses.

    `external_links` is a list of (file_rel, url, line). Each unique URL is checked
    once; results are cached and shared across all references to that URL.
    """
    if not external_links:
        return

    url_to_refs: dict[str, list[tuple[str, int]]] = {}
    for file_rel, url, line in external_links:
        url_to_refs.setdefault(url, []).append((file_rel, line))

    cache: dict[str, tuple[bool, str]] = {}
    with ThreadPoolExecutor(max_workers=workers) as pool:
        future_to_url = {
            pool.submit(check_external_url, url): url for url in sorted(url_to_refs)
        }
        for future in as_completed(future_to_url):
            url = future_to_url[future]
            try:
                ok, detail = future.result()
            except Exception as e:  # noqa: BLE001 — surface unexpected failures as broken
                ok, detail = False, f"check failed: {e}"
            cache[url] = (ok, detail)

    for url, refs in url_to_refs.items():
        ok, detail = cache.get(url, (False, "unknown"))
        if ok:
            continue
        is_unverified = detail.startswith("connection error") or "bot-blocked" in detail
        for file_rel, line in refs:
            findings.append(Finding(
                id=f"EXT-{len(findings) + 1:03d}",
                rule="UNVERIFIED_EXTERNAL_LINK" if is_unverified else "BROKEN_EXTERNAL_LINK",
                severity="low" if is_unverified else "medium",
                category="documentation",
                file=file_rel,
                line=line,
                message=(
                    f"External link could not be verified (network/timeout): {url} ({detail})"
                    if is_unverified else
                    f"External link not reachable (expected HTTP 2xx): {url} ({detail})"
                ),
                suggestion=(
                    "Verify manually; if unreachable, replace with a valid URL or remove"
                    if is_unverified else
                    "Replace with a valid, reachable URL or remove the link"
                ),
                reference="docs/conventions.md §Documentation Conventions",
                context={"target": url, "detail": detail},
            ))


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

    # Non-spec docs living in specs/ (registry index, implementation matrix, copy-paste
    # templates) are not governed by the spec-ID filename convention.
    non_spec_files = {"index.md", "implementation-matrix.md"}

    for p in sorted(SPECS_DIR.glob("*.md")):
        if p.name in non_spec_files or p.name.endswith("-template.md"):
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


# ─── Doc freshness (removed) ──────────────────────────────────────────────
# Freshness is now tracked via git history (git log --follow -- <file>, git diff),
# not via inline `> **Last updated:**` metadata. See docs/conventions.md §0 and
# .agents/rules/metadata-structure.md for the new contract.


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
            "broken_external": sum(1 for f in findings if f.rule == "BROKEN_EXTERNAL_LINK"),
            "unverified_external": sum(1 for f in findings if f.rule == "UNVERIFIED_EXTERNAL_LINK"),
        },
        findings=[vars(f) for f in findings],
        metadata={
            "files_scanned": len(files),
            "link_rule_broken_file": sum(1 for f in findings if f.rule == "BROKEN_FILE_LINK"),
            "link_rule_broken_anchor": sum(1 for f in findings if f.rule == "BROKEN_ANCHOR"),
            "link_rule_broken_external": sum(1 for f in findings if f.rule == "BROKEN_EXTERNAL_LINK"),
            "link_rule_unverified_external": sum(1 for f in findings if f.rule == "UNVERIFIED_EXTERNAL_LINK"),
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
    print(f"  DOC LINK SCAN RESULTS")
    print(f"{'='*60}")
    print(f"  Files scanned:      {result.metadata['files_scanned']}")
    print(f"  Total links:        {s['total_links']}")
    print(f"  Valid links:        {s['valid_links']}")
    print(f"  Broken external:    {s.get('broken_external', 0)}")
    print(f"  Unverified ext:     {s.get('unverified_external', 0)} (timeout/bot-blocked)")
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
        description="Validate relative/anchor links and external (HTTP 200) links in markdown docs",
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
    parser.add_argument(
        "--no-external",
        action="store_true",
        help="Skip external HTTP link validation (offline/fast mode)",
    )
    parser.add_argument(
        "--external-timeout",
        type=int,
        default=EXTERNAL_TIMEOUT,
        help=f"Timeout in seconds for external link checks (default {EXTERNAL_TIMEOUT})",
    )
    parser.add_argument(
        "--external-workers",
        type=int,
        default=EXTERNAL_WORKERS,
        help=f"Parallel workers for external checks (default {EXTERNAL_WORKERS})",
    )
    parser.add_argument(
        "--stale-days",
        type=int,
        default=14,
        help="Warn if document not modified in N days (default 14, 0 to disable)",
    )
    parser.add_argument(
        "--no-freshness",
        action="store_true",
        help="Skip git-based freshness check",
    )
    return parser.parse_args()


# ─── Main ───────────────────────────────────────────────────────────────────

def main() -> None:
    args = parse_args()
    start_time = time.time()
    scan_type = "module" if args.module else "full"

    files = find_markdown_files(args.module)
    findings: list[Finding] = []
    total_links = 0

    # Git-based freshness check
    if not args.no_freshness and args.stale_days > 0:
        for filepath in files:
            rel = relative_path(filepath)
            if is_outdated(filepath, args.stale_days):
                last_modified = git_last_modified(filepath)
                findings.append(Finding(
                    id=f"FRESH-{len(findings)+1:03d}",
                    rule="OUTDATED_DOC",
                    severity="low",
                    category="documentation",
                    file=rel,
                    line=1,
                    message=f"Document not modified in {args.stale_days}+ days (last: {last_modified.strftime('%Y-%m-%d') if last_modified else 'unknown'})",
                    suggestion="Review and update if content is stale, or accept if intentionally stable",
                    reference="docs/conventions.md §Documentation Conventions",
                    context={"stale_days": args.stale_days, "last_modified": last_modified.isoformat() if last_modified else None},
                ))

    if not args.module:
        total_links += validate_spec_conventions(findings)

    external_links: list[tuple[str, str, int]] = []
    for filepath in files:
        rel = relative_path(filepath)
        heading_slugs = collect_headings(filepath)
        for line_num, text, target in extract_links(filepath):
            total_links += 1
            if target.startswith(("http://", "https://")):
                external_links.append((rel, target, line_num))
            validate_target(target, filepath, heading_slugs, findings, rel, line_num)

    if not args.no_external and external_links:
        # Allow CLI to override timeout/workers
        global EXTERNAL_TIMEOUT, EXTERNAL_WORKERS  # noqa: PLW0603
        EXTERNAL_TIMEOUT = args.external_timeout
        EXTERNAL_WORKERS = args.external_workers
        if not args.quiet:
            print(f"Validating {len(external_links)} external link(s) ({len(set(u for _, u, _ in external_links))} unique) — HTTP 200 required...")
        validate_external_links(external_links, findings, workers=EXTERNAL_WORKERS)

    result = build_report(findings, files, start_time, total_links)

    # Uniform output via _output.py
    exit_code = handle_output(result, args)
    if exit_code:
        sys.exit(exit_code)


if __name__ == "__main__":
    main()
