#!/usr/bin/env python3
"""
_output.py — Uniform output handler for all scan tools (v1.0)

Single source of truth for tool output formatting. All tools should delegate
output via this module instead of implementing ad-hoc printing.

Usage:
    from _output import handle_output
    result = build_report(findings, scan_name, ...)
    handle_output(result, args)  # handles --format, --output, --verbose, --quiet, --json

Formats:
    json    — Full JSON report to stdout or file (machine-readable)
    text    — Human-readable findings per file (for CI logs)
    summary — One-line per-file + aggregate (default for --format summary)

The module re-uses _common.ScanResult / Finding schema so reports stay compatible
with tools/outputs/*.json consumers (CI, dashboards).
"""

from __future__ import annotations

import json
import sys
from dataclasses import asdict
from pathlib import Path
from typing import Any

try:
    from _common import OUTPUT_DIR, ScanResult
except ImportError:
    import sys
    from pathlib import Path
    sys.path.insert(0, str(Path(__file__).parent))
    from _common import OUTPUT_DIR, ScanResult


def _colorize(text: str, color: str, enabled: bool) -> str:
    if not enabled:
        return text
    colors = {"red": "\033[91m", "green": "\033[92m", "yellow": "\033[93m", "blue": "\033[94m", "reset": "\033[0m"}
    return f"{colors.get(color, '')}{text}{colors.get('reset', '')}"


def format_json(result: ScanResult) -> str:
    """Return uniform JSON string for a ScanResult."""
    data = asdict(result)
    return json.dumps(data, indent=2, ensure_ascii=False) + "\n"


def format_text(result: ScanResult, verbose: bool = False) -> str:
    """Return uniform text findings (one line per finding)."""
    lines: list[str] = []
    for f in result.findings:
        loc = f"{f['file']}:{f['line']}"
        lines.append(f"{f['id']} [{f['severity']}] {loc} — {f['message']}")
        if verbose and f.get("suggestion"):
            lines.append(f"  → {f['suggestion']}")
    if not lines:
        lines.append("(no findings)")
    return "\n".join(lines) + "\n"


def format_summary(result: ScanResult, verbose: bool = False) -> str:
    """Return uniform summary string (same as _common.print_summary but as string)."""
    # We reuse _common's color logic via string building; keep it simple and deterministic
    s = result.summary
    by_sev = s.get("by_severity", {})
    sev_str = " | ".join(f"{k}: {v}" for k, v in by_sev.items() if v > 0) if s.get("failed", 0) > 0 else "none"
    status = "PASS" if s.get("failed", 0) == 0 else "FAIL"
    lines = [
        f"Scan: {result.scan_name} ({result.scan_type}) | {status} | Checks: {s.get('total_checks', 0)} Passed: {s.get('passed', 0)} Failed: {s.get('failed', 0)} | {sev_str}",
        f"Time: {result.execution_time_ms}ms | Files: {result.metadata.get('total_files', 'N/A')}",
    ]
    if verbose and result.findings:
        lines.append("Findings:")
        for f in result.findings[:10]:
            lines.append(f"  {f['id']} [{f['severity']}] {f['file']}:{f['line']} — {f['message']}")
        if len(result.findings) > 10:
            lines.append(f"  ... and {len(result.findings) - 10} more")
    return "\n".join(lines) + "\n"


def write_json_file(result: ScanResult, output_path: Path | None = None) -> Path:
    """Atomically write JSON report to file (uniform)."""
    if output_path is None:
        from datetime import datetime
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-{result.scan_name}.json"
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    tmp = output_path.with_suffix(".tmp")
    try:
        tmp.write_text(format_json(result), encoding="utf-8")
        tmp.replace(output_path)
    except Exception as e:
        # Fallback
        try:
            output_path.write_text(format_json(result), encoding="utf-8")
        except Exception:
            raise RuntimeError(f"Failed to write report to {output_path}: {e}") from e
    finally:
        if tmp.exists():
            try:
                tmp.unlink()
            except OSError:
                pass
    return output_path


def handle_output(result: ScanResult, args: Any) -> int:
    """
    Uniform output dispatcher. Handles --format, --output, --verbose, --quiet, --json.
    Returns exit code (0 = pass, 1 = fail when --strict).
    """
    fmt = getattr(args, "format", "json")
    output = getattr(args, "output", None)
    verbose = bool(getattr(args, "verbose", False))
    quiet = bool(getattr(args, "quiet", False))
    strict = bool(getattr(args, "strict", False))
    force_json = bool(getattr(args, "json", False))

    # Always write JSON report to file (unless --format json goes to stdout)
    json_path: Path | None = None
    if output:
        json_path = write_json_file(result, Path(output))
    elif fmt != "json" or force_json:
        # For non-json formats, still persist a JSON file for CI
        json_path = write_json_file(result, None)

    # Stdout handling
    if force_json or fmt == "json":
        if not quiet:
            sys.stdout.write(format_json(result))
        if json_path and not output:
            # Also inform where JSON was saved when not explicitly requested
            sys.stderr.write(f"Report saved: {json_path}\n")
    elif fmt == "text":
        if not quiet:
            sys.stdout.write(format_text(result, verbose=verbose))
        if json_path:
            sys.stderr.write(f"Report saved: {json_path}\n")
    elif fmt == "summary":
        if not quiet:
            # Use colored summary when tty, else plain
            use_color = sys.stdout.isatty()
            # Reuse _common's colored summary for summary format to keep visual parity
            from _common import print_summary
            print_summary(result, verbose=verbose)
            # Also ensure JSON path is shown
            if json_path:
                sys.stderr.write(f"Report saved: {json_path}\n")
        else:
            # Quiet: only show where file went
            if json_path:
                sys.stderr.write(f"Report saved: {json_path}\n")
    else:
        # Fallback
        if not quiet:
            sys.stdout.write(format_json(result))

    # Exit code for --strict
    if strict and result.summary.get("failed", 0) > 0:
        return 1
    return 0
