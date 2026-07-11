#!/usr/bin/env python3
"""Validate all relative links in markdown documentation files."""

from __future__ import annotations

import argparse
import json
import re
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DOCS_DIR = ROOT / "docs"
LINK_PATTERN = re.compile(r'\[([^\]]*)\]\(([^)]+)\)')


def find_markdown_files() -> list[Path]:
    files = list(DOCS_DIR.rglob("*.md"))
    for name in ["README.md", "AGENTS.md"]:
        f = ROOT / name
        if f.exists():
            files.append(f)
    return files


def extract_links(filepath: Path) -> list[tuple[int, str, str]]:
    links = []
    try:
        lines = filepath.read_text(encoding="utf-8", errors="replace").splitlines()
        for i, line in enumerate(lines, 1):
            for match in LINK_PATTERN.finditer(line):
                text, target = match.group(1), match.group(2)
                links.append((i, text, target))
    except OSError:
        pass
    return links


def is_valid_link(target: str, source_file: Path) -> bool:
    if target.startswith(("http://", "https://", "mailto:", "#")):
        return True
    if target.startswith("phpstan:"):
        return True

    # Strip anchor
    path_part = target.split("#")[0]
    if not path_part:
        return True

    resolved = (source_file.parent / path_part).resolve()
    return resolved.exists()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", help="Output file path")
    args = parser.parse_args()

    print("Scanning documentation links...")
    files = find_markdown_files()

    total_links = 0
    valid_links = 0
    broken = []
    by_file = {}

    for filepath in files:
        rel = str(filepath.relative_to(ROOT))
        links = extract_links(filepath)
        file_total = len(links)
        file_broken = []

        for line_num, text, target in links:
            total_links += 1
            if is_valid_link(target, filepath):
                valid_links += 1
            else:
                file_broken.append({
                    "line": line_num,
                    "text": text[:60],
                    "target": target,
                })

        if file_broken:
            broken.extend([{"file": rel, **b} for b in file_broken])

        by_file[rel] = {
            "total": file_total,
            "broken": len(file_broken),
        }

    print(f"  Files scanned: {len(files)}")
    print(f"  Total links: {total_links}")
    print(f"  Valid: {valid_links}")
    print(f"  Broken: {len(broken)}")

    data = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "total_links": total_links,
        "valid": valid_links,
        "broken": broken,
        "by_file": by_file,
    }

    out = Path(args.output) if args.output else ROOT / "scripts" / "outputs" / f"{datetime.now().strftime('%Y%m%d%H%M%S')}-doc-links.json"
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"\nWritten to {out}")


if __name__ == "__main__":
    main()
