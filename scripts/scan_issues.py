#!/usr/bin/env python3
"""
Fetch GitHub issues and summarize by module and severity.

Enhanced v2.1: retry logic, rate-limit handling, caching, comprehensive
classification, proper output schema, and actionable findings.
"""

from __future__ import annotations

import argparse
import json
import re
import subprocess
import sys
import time
from datetime import datetime, timezone, timedelta
from pathlib import Path

try:
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        SCAN_VERSION,
        relative_path,
        build_report,
        write_report,
        print_summary,
    )
except ImportError:
    sys.path.insert(0, str(Path(__file__).parent))
    from _common import (
        Finding,
        ScanResult,
        ROOT,
        SCAN_VERSION,
        relative_path,
        build_report,
        write_report,
        print_summary,
    )

SCAN_NAME = "issues"

KNOWN_MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Core", "Document", "Enrollment", "Evaluation",
    "Incident", "Journals", "Partners",
    "Program", "Reports", "Settings", "Setup",
    "SysAdmin", "User",
]

# Cache for offline mode
CACHE_FILE = Path(__file__).parent / "outputs" / ".issues_cache.json"
CACHE_TTL_HOURS = 1


def fetch_issues_with_retry(max_retries: int = 3, use_cache: bool = True) -> tuple[list[dict], str]:
    """Fetch issues with retry, rate-limit handling, and caching."""
    # Try cache first for offline/fast mode
    if use_cache and CACHE_FILE.exists():
        try:
            cache_age = time.time() - CACHE_FILE.stat().st_mtime
            if cache_age < CACHE_TTL_HOURS * 3600:
                data = json.loads(CACHE_FILE.read_text(encoding="utf-8"))
                if isinstance(data, list) and len(data) > 0:
                    return data, "cache"
        except (OSError, json.JSONDecodeError):
            pass

    last_error = ""
    for attempt in range(max_retries):
        try:
            # Use --limit to handle pagination (GitHub CLI handles it)
            proc = subprocess.run(
                ["gh", "issue", "list", "--state", "open",
                 "--limit", "1000",
                 "--json", "number,title,labels,body,createdAt,updatedAt,assignees"],
                capture_output=True, text=True, cwd=str(ROOT), timeout=30,
            )
            
            if proc.returncode == 0:
                try:
                    data = json.loads(proc.stdout)
                    # Update cache on success
                    try:
                        CACHE_FILE.parent.mkdir(parents=True, exist_ok=True)
                        CACHE_FILE.write_text(json.dumps(data, indent=2), encoding="utf-8")
                    except OSError:
                        pass
                    return data, "api"
                except json.JSONDecodeError as e:
                    last_error = f"JSON parse error: {e}"
            elif "rate limit" in proc.stderr.lower():
                last_error = "GitHub rate limit exceeded"
                if attempt < max_retries - 1:
                    wait = 2 ** attempt  # Exponential backoff
                    time.sleep(wait)
                    continue
            elif "not authenticated" in proc.stderr.lower() or "auth" in proc.stderr.lower():
                last_error = "GitHub CLI not authenticated (run gh auth login)"
                break
            else:
                last_error = proc.stderr.strip()[:200]
                
        except subprocess.TimeoutExpired:
            last_error = f"Timeout on attempt {attempt + 1}"
        except FileNotFoundError:
            last_error = "gh CLI not found (install GitHub CLI)"
            break
        except Exception as e:
            last_error = str(e)
        
        if attempt < max_retries - 1:
            time.sleep(1 * (attempt + 1))
    
    # Fallback to cache if API failed
    if CACHE_FILE.exists():
        try:
            data = json.loads(CACHE_FILE.read_text(encoding="utf-8"))
            return data, "cache-stale"
        except (OSError, json.JSONDecodeError):
            pass
    
    # Return error info
    return [], last_error


def classify_issue_advanced(raw: dict) -> tuple[dict, list[Finding]]:
    """Classify issue with sophisticated pattern matching and findings."""
    findings: list[Finding] = []
    labels = [l["name"] for l in raw.get("labels", [])]
    title = raw.get("title", "")
    body = raw.get("body", "") or ""
    number = raw.get("number", 0)
    
    # Module classification with scoring
    module_scores: dict[str, int] = {}
    for m in KNOWN_MODULES:
        score = 0
        # Label exact match (highest weight)
        for label in labels:
            if m.lower() == label.lower():
                score += 10
            elif m.lower() in label.lower():
                score += 5
        # Title mention
        if re.search(r'\b' + re.escape(m) + r'\b', title, re.IGNORECASE):
            score += 3
        # Body mention
        if re.search(r'\b' + re.escape(m) + r'\b', body, re.IGNORECASE):
            score += 1
        if score > 0:
            module_scores[m] = score
    
    module = max(module_scores, key=module_scores.get) if module_scores else ""
    
    # Generate finding for unclassified issues
    if not module and raw.get("number"):
        findings.append(Finding(
            id=f"ISSUE-UNCLASSIFIED-{number}",
            rule="ISSUE_CLASSIFICATION",
            severity="low",
            category="convention",
            file=f".github/issues/{number}",
            line=0,
            message=f"Issue #{number} has no module classification: '{title[:50]}...'",
            suggestion=f"Add module label ({', '.join(KNOWN_MODULES[:5])}...) or mention module in title",
            reference="docs/specs/index.md",
            context={"title": title, "labels": labels},
        ))

    # Severity with more nuance
    severity = ""
    severity_labels = []
    for label in labels:
        low = label.lower()
        if "p0" in low or "critical" in low:
            severity = "p0"
            severity_labels.append(label)
        elif "p1" in low or "high" in low:
            if severity not in ["p0"]:
                severity = "p1"
        elif "p2" in low or "medium" in low:
            if severity not in ["p0", "p1"]:
                severity = "p2"
        elif "p3" in low or "low" in low:
            if not severity:
                severity = "p3"
    
    # Check for stale issues (no update in 30 days)
    try:
        updated = raw.get("updatedAt", "")
        if updated:
            updated_dt = datetime.fromisoformat(updated.replace("Z", "+00:00"))
            age_days = (datetime.now(timezone.utc) - updated_dt).days
            if age_days > 60:
                findings.append(Finding(
                    id=f"ISSUE-STALE-{number}",
                    rule="ISSUE_STALENESS",
                    severity="low",
                    category="convention",
                    file=f".github/issues/{number}",
                    line=0,
                    message=f"Issue #{number} stale: no update in {age_days} days",
                    suggestion="Update, close, or add stale label",
                    reference="docs/conventions.md",
                    context={"age_days": age_days, "updatedAt": updated},
                ))
    except (ValueError, TypeError):
        pass

    # Extract score
    score = 0
    m = re.search(r"Score:\s*(\d+)", title)
    if m:
        try:
            score = int(m.group(1))
        except ValueError:
            pass

    # Type classification
    issue_type = "unknown"
    title_lower = title.lower()
    if title_lower.startswith("bug:"):
        issue_type = "bug"
    elif title_lower.startswith("feature:"):
        issue_type = "feature"
    elif title_lower.startswith("security:"):
        issue_type = "security"
    elif "refactor" in title_lower:
        issue_type = "refactor"

    return {
        "number": number,
        "module": module,
        "title": title,
        "severity": severity or "unclassified",
        "score": score,
        "labels": labels,
        "type": issue_type,
        "createdAt": raw.get("createdAt", ""),
        "updatedAt": raw.get("updatedAt", ""),
        "module_scores": module_scores,
    }, findings


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Fetch GitHub issues and summarize by module and severity",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  python3 scripts/scan_issues.py
  python3 scripts/scan_issues.py --module Assessment --verbose
  python3 scripts/scan_issues.py --json | jq '.metadata.by_severity'
  python3 scripts/scan_issues.py --strict
        """
    )
    parser.add_argument("--module", "-m", help="Filter by module")
    parser.add_argument("--output", "-o", help="Output file path")
    parser.add_argument("--format", "-f", choices=["json", "text", "summary"], default="json", help="Output format")
    parser.add_argument("--verbose", "-v", action="store_true", help="Include detailed context")
    parser.add_argument("--quiet", "-q", action="store_true", help="Only output summary")
    parser.add_argument("--strict", "-s", action="store_true", help="Exit with code 1 on critical findings")
    parser.add_argument("--json", action="store_true", help="Force JSON output to stdout")
    parser.add_argument("--no-cache", action="store_true", help="Bypass cache and fetch fresh")
    parser.add_argument("--severity", choices=["p0", "p1", "p2", "p3"], help="Filter by minimum severity")
    return parser.parse_args()


def main() -> None:
    args = parse_args()
    start_time = time.time()
    
    if not args.quiet:
        print("Fetching GitHub issues...")
    
    raw_issues, source = fetch_issues_with_retry(use_cache=not args.no_cache)
    
    if not raw_issues and source not in ["cache", "cache-stale"]:
        # Fetch failed and no cache
        print(f"Warning: {source}", file=sys.stderr)
        if not args.quiet:
            print(f"  Source: {source} (no data)")
    
    # Classify with findings
    issues: list[dict] = []
    all_findings: list[Finding] = []
    
    for raw in raw_issues:
        classified, findings = classify_issue_advanced(raw)
        # Module filter
        if args.module and classified["module"] != args.module:
            continue
        # Severity filter
        if args.severity:
            order = {"p0": 3, "p1": 2, "p2": 1, "p3": 0, "unclassified": -1}
            if order.get(classified["severity"], -1) < order.get(args.severity, 0):
                continue
        issues.append(classified)
        all_findings.extend(findings)
    
    # Add source finding if stale cache
    if source == "cache-stale":
        all_findings.append(Finding(
            id="ISSUE-CACHE-STALE",
            rule="ISSUE_FETCH",
            severity="low",
            category="system",
            file="scripts/scan_issues.py",
            line=0,
            message="Using stale cache: GitHub API unavailable",
            suggestion="Check gh auth status and network connectivity",
            reference="scripts/README.md",
        ))
    
    by_severity: dict[str, int] = {"p0": 0, "p1": 0, "p2": 0, "p3": 0, "unclassified": 0}
    by_module: dict[str, list] = {}
    by_type: dict[str, int] = {}
    
    for issue in issues:
        sev = issue["severity"] or "unclassified"
        by_severity[sev] = by_severity.get(sev, 0) + 1
        by_type[issue["type"]] = by_type.get(issue["type"], 0) + 1
        mod = issue["module"] or "Unknown"
        by_module.setdefault(mod, []).append(issue)
    
    if not args.quiet:
        print(f"  Total open: {len(issues)} (source: {source})")
        print(f"  By severity: {by_severity}")
        print(f"  By type: {by_type}")
        print(f"  By module: {len(by_module)} modules")
        if all_findings:
            print(f"  Findings: {len(all_findings)} (unclassified/stale)")
    
    metadata = {
        "total_open": len(issues),
        "by_severity": by_severity,
        "by_type": by_type,
        "by_module": {k: len(v) for k, v in by_module.items()},
        "source": source,
        "cache_file": str(CACHE_FILE) if CACHE_FILE.exists() else None,
    }
    
    # Build report
    scan_type = "module" if args.module else "full"
    result = build_report(
        findings=all_findings,
        scan_name=SCAN_NAME,
        scan_type=scan_type,
        module=args.module,
        start_time=start_time,
        metadata=metadata,
        total_checks=len(issues) if issues else 1,
    )
    # Add issues data to metadata for backward compat
    result.metadata["issues"] = issues
    result.metadata["detailed_by_module"] = by_module

    # Output handling
    if args.json or args.format == "json":
        if not args.quiet:
            print(json.dumps(result.__dict__, indent=2, ensure_ascii=False))
    elif args.format == "summary":
        if not args.quiet:
            print_summary(result, verbose=args.verbose)
    elif args.format == "text":
        if not args.quiet:
            for f in result.findings:
                print(f"{f['file']}:{f['line']} [{f['severity']}] {f['message']}")

    output_path = Path(args.output) if args.output else None
    written_path = write_report(result, output_path)
    
    if not args.quiet and not args.json:
        print(f"\nReport saved: {relative_path(written_path)}")

    if args.strict and any(f.severity in ["high", "critical"] for f in all_findings):
        sys.exit(1)


if __name__ == "__main__":
    main()
