#!/usr/bin/env python3
"""
Clean Old Outputs — Remove script output files based on age or date range.
Deletes JSON files from scripts/outputs/ based on file modification time.

Presets:  --yesterday, --3days, --7days (default), --2weeks, --1month
Custom:   --older-than YYYY-MM-DD [--newer-than YYYY-MM-DD]
Numeric:  --days N
"""

from __future__ import annotations

import argparse
import sys
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path
from typing import Any

# ─── Constants ──────────────────────────────────────────────────────────────

ROOT = Path(__file__).resolve().parent.parent
OUTPUT_DIR = Path(__file__).parent / "outputs"
SCAN_NAME = "clean-outputs"
SCAN_VERSION = "1.1.0"

PRESETS: dict[str, int] = {
    "yesterday": 1,
    "3days": 3,
    "7days": 7,
    "2weeks": 14,
    "1month": 30,
}

PRESET_LABELS: dict[str, str] = {
    "yesterday": "1 day (yesterday)",
    "3days": "3 days",
    "7days": "7 days (default)",
    "2weeks": "14 days (2 weeks)",
    "1month": "30 days (1 month)",
}


# ─── Data ───────────────────────────────────────────────────────────────────

@dataclass
class CleanupResult:
    scan_version: str
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
        scan_version=SCAN_VERSION,
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
            rel = f"scripts/outputs/{filepath.name}"
            should_delete = True

            if cutoff_ts is not None and mtime_ts >= cutoff_ts:
                should_delete = False
            if newer_ts is not None and mtime_ts <= newer_ts:
                should_delete = False

            if should_delete:
                size = filepath.stat().st_size
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

examples:
  python3 scripts/clean_outputs.py --yesterday --dry-run
  python3 scripts/clean_outputs.py --older-than 2026-07-01 --newer-than 2026-06-15
  python3 scripts/clean_outputs.py --days 3 -v
""",
    )

    preset = parser.add_mutually_exclusive_group()
    preset.add_argument(
        "--yesterday",
        action="store_const",
        const=1,
        dest="days",
        help="Files older than 1 day",
    )
    preset.add_argument(
        "--3days",
        action="store_const",
        const=3,
        dest="days",
        help="Files older than 3 days",
    )
    preset.add_argument(
        "--7days",
        action="store_const",
        const=7,
        dest="days",
        help="Files older than 7 days (default)",
    )
    preset.add_argument(
        "--2weeks",
        action="store_const",
        const=14,
        dest="days",
        help="Files older than 14 days",
    )
    preset.add_argument(
        "--1month",
        action="store_const",
        const=30,
        dest="days",
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
    return parser.parse_args()


def main() -> None:
    args = parse_args()

    # Determine cutoff from --older-than / --newer-than or --days
    if args.older_than is not None or args.newer_than is not None:
        cutoff = args.older_than
        newer_than = args.newer_than
    else:
        now = datetime.now()
        cutoff = now - timedelta(days=args.days)
        newer_than = None

    result = clean_outputs(
        cutoff=cutoff,
        newer_than=newer_than,
        dry_run=args.dry_run,
        verbose=args.verbose,
    )

    if args.json:
        import json
        print(json.dumps({
            "scan_version": result.scan_version,
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
