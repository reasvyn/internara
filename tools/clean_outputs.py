#!/usr/bin/env python3
"""
Clean Old Outputs — Remove script output files based on age, date range, or prune.
Deletes JSON files from tools/outputs/ based on file modification time.

Presets:  --yesterday, --3days, --7days (default), --2weeks, --1month
Custom:   --older-than YYYY-MM-DD [--newer-than YYYY-MM-DD]
Numeric:  --days N
Prune:    --prune (keep only the latest timestamped output per category)
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCRIPTS_JSON = Path(__file__).parent / "tools.json"
SCAN_NAME = "clean-outputs"

PRESETS: dict[str, int] = {
    "yesterday": 1,
    "3days": 3,
    "7days": 7,
    "2weeks": 14,
    "1month": 30,
}

RE_OUTPUT_NAME = re.compile(r"^(\d{14})-([a-z0-9-]+)\.json$")


# ─── Data ───────────────────────────────────────────────────────────────────

@dataclass
class CleanupResult:
    scan_name: str
    timestamp: str
    filter_desc: str
    dry_run: bool
    deleted: list[str] = field(default_factory=list)
    kept: list[str] = field(default_factory=list)
    errors: list[dict[str, str]] = field(default_factory=list)
    summary: dict[str, Any] = field(default_factory=dict)


# ─── Helpers ────────────────────────────────────────────────────────────────

def get_output_files() -> list[Path]:
    """Get all JSON output files, excluding .gitkeep."""
    if not OUTPUT_DIR.exists():
        return []
    return sorted(
        f for f in OUTPUT_DIR.iterdir()
        if f.is_file() and f.suffix == ".json"
    )


def format_size(size_bytes: int) -> str:
    """Human-readable file size."""
    for unit in ("B", "KB", "MB"):
        if size_bytes < 1024:
            return f"{size_bytes:.1f} {unit}"
        size_bytes /= 1024
    return f"{size_bytes:.1f} GB"


def parse_date(value: str) -> datetime:
    """Parse YYYY-MM-DD date string to datetime (start of day)."""
    try:
        return datetime.strptime(value, "%Y-%m-%d")
    except ValueError:
        raise argparse.ArgumentTypeError(
            f"Invalid date format: '{value}'. Use YYYY-MM-DD."
        )


def parse_output_name(filepath: Path) -> tuple[datetime, str] | None:
    """Parse '{YYYYMMDDHHMMSS}-{category}.json' into (timestamp, category)."""
    m = RE_OUTPUT_NAME.match(filepath.name)
    if not m:
        return None
    return datetime.strptime(m.group(1), "%Y%m%d%H%M%S"), m.group(2)


def load_script_categories() -> dict[str, dict[str, Any]]:
    """Load output-category registry from tools/tools.json."""
    if not SCRIPTS_JSON.exists():
        return {}
    try:
        data = json.loads(SCRIPTS_JSON.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return {}
    outputs = data.get("outputs")
    return outputs if isinstance(outputs, dict) else {}


# ─── Core ───────────────────────────────────────────────────────────────────

def clean_outputs(
    cutoff: datetime | None = None,
    newer_than: datetime | None = None,
    dry_run: bool = False,
    verbose: bool = False,
) -> CleanupResult:
    """Delete output files matching the date filter.

    If only cutoff is set:   delete files OLDER than cutoff.
    If only newer_than:      delete files NEWER than newer_than.
    If both:                 delete files BETWEEN newer_than and cutoff.
    """
    now = datetime.now()

    if cutoff is None and newer_than is None:
        cutoff = now - timedelta(days=7)

    # Build description
    if cutoff and newer_than:
        filter_desc = f"between {newer_than:%Y-%m-%d} and {cutoff:%Y-%m-%d}"
    elif cutoff:
        filter_desc = f"older than {cutoff:%Y-%m-%d} (> {(now - cutoff).days} days)"
    else:
        filter_desc = f"newer than {newer_than:%Y-%m-%d}"

    result = CleanupResult(
        scan_name=SCAN_NAME,
        timestamp=now.isoformat(),
        filter_desc=filter_desc,
        dry_run=dry_run,
    )

    files = get_output_files()
    if not files:
        return result

    cutoff_ts = cutoff.timestamp() if cutoff else None
    newer_ts = newer_than.timestamp() if newer_than else None

    total_deleted_size = 0

    for filepath in files:
        try:
            mtime = datetime.fromtimestamp(filepath.stat().st_mtime)
            mtime_ts = mtime.timestamp()
            rel = f"tools/outputs/{filepath.name}"
            should_delete = True

            if cutoff_ts is not None and mtime_ts >= cutoff_ts:
                should_delete = False
            if newer_ts is not None and mtime_ts <= newer_ts:
                should_delete = False

            if should_delete:
                size = filepath.stat().st_size
                if not dry_run:
                    filepath.unlink()
                result.deleted.append(
                    f"{rel} ({format_size(size)}, modified {mtime:%Y-%m-%d %H:%M})"
                )
                total_deleted_size += size
            elif verbose:
                result.kept.append(
                    f"{rel} (modified {mtime:%Y-%m-%d %H:%M})"
                )
        except Exception as e:
            result.errors.append({"file": filepath.name, "error": str(e)})

    result.summary = {
        "deleted_count": len(result.deleted),
        "kept_count": len(result.kept),
        "error_count": len(result.errors),
        "total_size_deleted_bytes": total_deleted_size,
        "total_size_deleted": format_size(total_deleted_size),
    }

    return result


def prune_outputs(
    dry_run: bool = False,
    verbose: bool = False,
) -> CleanupResult:
    """Delete all output files except the latest timestamped file per category.

    Categories come from the tools/tools.json registry. Files whose
    category is not registered, or whose name is not a timestamped output,
    are kept untouched.
    """
    now = datetime.now()

    result = CleanupResult(
        scan_name=SCAN_NAME,
        timestamp=now.isoformat(),
        filter_desc="prune: keep latest timestamped output per category",
        dry_run=dry_run,
    )

    categories = load_script_categories()
    if not categories:
        result.errors.append({
            "file": SCRIPTS_JSON.name,
            "error": "no categories found or scripts.json missing",
        })
        return result

    grouped: dict[str, list[tuple[datetime, Path]]] = {c: [] for c in categories}

    for filepath in get_output_files():
        parsed = parse_output_name(filepath)
        if parsed is None:
            result.kept.append(
                f"tools/outputs/{filepath.name} (non-standard name, kept)"
            )
            continue
        _, category = parsed
        if category not in grouped:
            result.kept.append(
                f"tools/outputs/{filepath.name} (unknown category '{category}', kept)"
            )
            continue
        grouped[category].append((parsed[0], filepath))

    total_deleted_size = 0

    for category, entries in grouped.items():
        if not entries:
            continue
        entries.sort(key=lambda e: e[0], reverse=True)
        keep_path = entries[0][1]
        for _, filepath in entries[1:]:
            size = filepath.stat().st_size
            if not dry_run:
                filepath.unlink()
            result.deleted.append(
                f"tools/outputs/{filepath.name} ({format_size(size)})"
            )
            total_deleted_size += size
        if verbose:
            result.kept.append(
                f"tools/outputs/{keep_path.name} (latest {category})"
            )

    result.summary = {
        "deleted_count": len(result.deleted),
        "kept_count": len(result.kept),
        "error_count": len(result.errors),
        "total_size_deleted_bytes": total_deleted_size,
        "total_size_deleted": format_size(total_deleted_size),
    }

    return result


# ─── Report ─────────────────────────────────────────────────────────────────

def print_summary(result: CleanupResult) -> None:
    """Print human-readable summary."""
    s = result.summary
    mode = "[DRY RUN] " if result.dry_run else ""

    print(f"\n{'='*60}")
    print(f"  {mode}CLEAN OLD OUTPUTS")
    print(f"{'='*60}")
    print(f"  Filter:     {result.filter_desc}")
    print(f"  Deleted:    {s['deleted_count']} files ({s.get('total_size_deleted', '0 B')})")
    print(f"  Kept:       {s['kept_count']} files")
    print(f"  Errors:     {s['error_count']}")
    print(f"{'='*60}")

    if result.deleted:
        print("\n  Deleted files:")
        for entry in result.deleted:
            print(f"    - {entry}")

    if result.kept:
        print("\n  Kept files:")
        for entry in result.kept:
            print(f"    - {entry}")

    if result.errors:
        print("\n  Errors:")
        for err in result.errors:
            print(f"    - {err['file']}: {err['error']}")

    print()


# ─── CLI ────────────────────────────────────────────────────────────────────

def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Clean script output files based on age or date range.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""\
presets:
  --yesterday               Files older than 1 day
  --3days                   Files older than 3 days
  --7days                   Files older than 7 days (default)
  --2weeks                  Files older than 14 days
  --1month                  Files older than 30 days

custom range:
  --older-than 2026-07-01   Files modified before this date
  --newer-than 2026-07-10   Files modified after this date
  --older-than X --newer-than Y   Files between Y and X

numeric:
  --days N                  Files older than N days

prune:
  --prune                   Keep only the latest timestamped output per category
                            (categories from tools/tools.json), delete the rest

examples:
  python3 tools/clean_outputs.py --yesterday --dry-run
  python3 tools/clean_outputs.py --older-than 2026-07-01 --newer-than 2026-06-15
  python3 tools/clean_outputs.py --days 3 -v
  python3 tools/clean_outputs.py --prune --dry-run
  python3 tools/clean_outputs.py --prune -v
""",
    )

    mode = parser.add_mutually_exclusive_group()
    mode.add_argument(
        "--prune",
        action="store_true",
        help="Keep only the latest timestamped output per category",
    )
    mode.add_argument(
        "--yesterday",
        action="store_const",
        const="yesterday",
        dest="preset",
        help="Files older than 1 day",
    )
    mode.add_argument(
        "--3days",
        action="store_const",
        const="3days",
        dest="preset",
        help="Files older than 3 days",
    )
    mode.add_argument(
        "--7days",
        action="store_const",
        const="7days",
        dest="preset",
        help="Files older than 7 days (default)",
    )
    mode.add_argument(
        "--2weeks",
        action="store_const",
        const="2weeks",
        dest="preset",
        help="Files older than 14 days",
    )
    mode.add_argument(
        "--1month",
        action="store_const",
        const="1month",
        dest="preset",
        help="Files older than 30 days",
    )

    parser.add_argument(
        "--days", "-d",
        type=int,
        default=7,
        help="Max age in days (default: 7)",
    )
    parser.add_argument(
        "--older-than",
        type=parse_date,
        metavar="YYYY-MM-DD",
        help="Delete files modified before this date",
    )
    parser.add_argument(
        "--newer-than",
        type=parse_date,
        metavar="YYYY-MM-DD",
        help="Delete files modified after this date (combine with --older-than for range)",
    )
    parser.add_argument(
        "--dry-run", "-n",
        action="store_true",
        help="Show what would be deleted without deleting",
    )
    parser.add_argument(
        "--verbose", "-v",
        action="store_true",
        help="Show kept files too",
    )
    parser.add_argument(
        "--quiet", "-q",
        action="store_true",
        help="Only output summary",
    )
    parser.add_argument(
        "--json",
        action="store_true",
        help="Force JSON output to stdout",
    )
    return parser


def main() -> None:
    parser = parse_args()
    args = parser.parse_args()

    if args.prune:
        if args.older_than is not None or args.newer_than is not None:
            parser.error("--prune cannot be combined with --older-than/--newer-than")
        if args.days != 7:
            parser.error("--prune cannot be combined with --days")
        result = prune_outputs(
            dry_run=args.dry_run,
            verbose=args.verbose,
        )
    elif args.older_than is not None or args.newer_than is not None:
        result = clean_outputs(
            cutoff=args.older_than,
            newer_than=args.newer_than,
            dry_run=args.dry_run,
            verbose=args.verbose,
        )
    else:
        days = PRESETS[args.preset] if args.preset else args.days
        now = datetime.now()
        result = clean_outputs(
            cutoff=now - timedelta(days=days),
            newer_than=None,
            dry_run=args.dry_run,
            verbose=args.verbose,
        )

    if args.json:
        print(json.dumps({
            "scan_name": result.scan_name,
            "timestamp": result.timestamp,
            "filter_desc": result.filter_desc,
            "dry_run": result.dry_run,
            "summary": result.summary,
            "deleted": result.deleted,
            "kept": result.kept,
            "errors": result.errors,
        }, indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result)

    if result.errors:
        sys.exit(1)


if __name__ == "__main__":
    main()
