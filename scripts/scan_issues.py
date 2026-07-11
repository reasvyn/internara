#!/usr/bin/env python3
"""Fetch GitHub issues and summarize by module and severity."""

from __future__ import annotations

import argparse
import json
import subprocess
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

MODULES = [
    "Academics", "Assessment", "Assignment", "Auth", "Certification",
    "Console", "Core", "Document", "Enrollment", "Evaluation",
    "Guidance", "Incident", "Jobs", "Journals", "Partners",
    "Program", "Providers", "Reports", "Settings", "Setup",
    "SysAdmin", "User",
]


def fetch_issues() -> list[dict]:
    try:
        proc = subprocess.run(
            ["gh", "issue", "list", "--state", "open",
             "--json", "number,title,labels,body"],
            capture_output=True, text=True, cwd=str(ROOT), timeout=30,
        )
        if proc.returncode != 0:
            return []
        return json.loads(proc.stdout)
    except (subprocess.TimeoutExpired, FileNotFoundError, json.JSONDecodeError):
        return []


def classify_issue(raw: dict) -> dict:
    labels = [l["name"] for l in raw.get("labels", [])]
    title = raw.get("title", "")

    # Try labels first, then title
    module = ""
    for label in labels:
        for m in MODULES:
            if m.lower() in label.lower():
                module = m
                break
    if not module:
        import re
        for m in MODULES:
            if re.search(r'\b' + m + r'\b', title, re.IGNORECASE):
                module = m
                break

    # Severity from labels, or from title keywords
    severity = ""
    for label in labels:
        low = label.lower()
        if "p0" in low:
            severity = "p0"
        elif "p1" in low:
            severity = "p1"
        elif "p2" in low:
            severity = "p2"

    # Extract score from title if present (e.g., "Score: 72/100")
    score = 0
    import re
    m = re.search(r"Score:\s*(\d+)", title)
    if m:
        score = int(m.group(1))

    return {
        "number": raw["number"],
        "module": module,
        "title": title,
        "severity": severity,
        "score": score,
        "labels": labels,
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    print("Fetching GitHub issues...")
    raw_issues = fetch_issues()
    issues = [classify_issue(i) for i in raw_issues]

    by_severity = {"p0": 0, "p1": 0, "p2": 0, "unclassified": 0}
    by_module: dict[str, list] = {}

    for issue in issues:
        sev = issue["severity"] or "unclassified"
        by_severity[sev] = by_severity.get(sev, 0) + 1
        mod = issue["module"] or "Unknown"
        by_module.setdefault(mod, []).append(issue)

    print(f"  Total open: {len(issues)}")
    print(f"  By severity: {by_severity}")
    print(f"  By module: {len(by_module)} modules")

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "total_open": len(issues),
        "by_severity": by_severity,
        "by_module": {k: v for k, v in sorted(by_module.items())},
        "list": issues,
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-issues.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"\nWritten to {out}")


if __name__ == "__main__":
    main()
