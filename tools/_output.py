#!/usr/bin/env python3
"""
_output.py — Uniform output handler for all scan tools (v2.0)

Single source of truth for tool output formatting. All tools should delegate
output via this module instead of implementing ad-hoc printing.

Usage:
    from _output import handle_output
    result = build_report(findings, scan_name, ...)
    handle_output(result, args)  # handles --format, --output, --verbose, --quiet, --json

Formats:
    json      — Full JSON report to stdout or file (machine-readable)
    text      — Human-readable findings per file (for CI logs)
    summary   — One-line per-file + aggregate (default for --format summary)
    html      — HTML report with styling
    markdown  — Markdown report for GitHub/docs
    diff      — Compare two JSON reports and show changes
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
    """Return uniform summary string."""
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


def format_html(result: ScanResult) -> str:
    """Return HTML report for a ScanResult."""
    sev_colors = {
        "critical": "#dc2626",
        "high": "#ea580c",
        "high": "#d97706",
        "medium": "#65a30d",
        "low": "#6b7280",
    }
    
    findings_html = ""
    for f in result.findings:
        color = sev_colors.get(f["severity"], "#374151")
        findings_html += f"""
        <tr style="border-bottom: 1px solid #e5e7eb;">
            <td style="padding: 12px; font-family: monospace; color: #6b7280;">{f['id']}</td>
            <td style="padding: 12px;"><span style="background: {color}20; color: {color}; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">{f['severity'].upper()}</span></td>
            <td style="padding: 12px; font-family: monospace; font-size: 13px;">{f['file']}:{f['line']}</td>
            <td style="padding: 12px;">{f['message']}</td>
            <td style="padding: 12px; color: #6b7280; font-size: 13px;">{f.get('suggestion', '')}</td>
        </tr>
        """
    
    by_sev = result.summary.get("by_severity", {})
    status = "PASS" if result.summary.get("failed", 0) == 0 else "FAIL"
    status_color = "#16a34a" if status == "PASS" else "#dc2626"
    
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Report: {result.scan_name}</title>
    <style>
        body {{ font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 24px; background: #f9fafb; color: #111827; }}
        .container {{ max-width: 1400px; margin: 0 auto; }}
        .header {{ background: white; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }}
        .header h1 {{ margin: 0 0 8px 0; font-size: 24px; }}
        .meta {{ color: #6b7280; font-size: 14px; }}
        .summary {{ display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }}
        .card {{ background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }}
        .card-value {{ font-size: 32px; font-weight: 700; margin: 8px 0; }}
        .card-label {{ color: #6b7280; font-size: 14px; }}
        .findings {{ background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }}
        .findings h2 {{ margin: 0 0 16px 0; font-size: 18px; }}
        table {{ width: 100%; border-collapse: collapse; }}
        th {{ text-align: left; padding: 12px; background: #f3f4f6; font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Scan Report: {result.scan_name}</h1>
            <div class="meta">
                Scan type: {result.scan_type} | Module: {result.module or 'all'} | 
                Time: {result.execution_time_ms}ms | Generated: {result.timestamp}
            </div>
        </div>
        
        <div class="summary">
            <div class="card">
                <div class="card-label">Status</div>
                <div class="card-value" style="color: {status_color};">{status}</div>
            </div>
            <div class="card">
                <div class="card-label">Total Checks</div>
                <div class="card-value">{result.summary.get('total_checks', 0)}</div>
            </div>
            <div class="card">
                <div class="card-label">Failed</div>
                <div class="card-value" style="color: #dc2626;">{result.summary.get('failed', 0)}</div>
            </div>
            <div class="card">
                <div class="card-label">Critical</div>
                <div class="card-value" style="color: #dc2626;">{by_sev.get('critical', 0)}</div>
            </div>
            <div class="card">
                <div class="card-label">High</div>
                <div class="card-value" style="color: #ea580c;">{by_sev.get('high', 0)}</div>
            </div>
            <div class="card">
                <div class="card-label">Medium</div>
                <div class="card-value" style="color: #d97706;">{by_sev.get('medium', 0)}</div>
            </div>
            <div class="card">
                <div class="card-label">Low</div>
                <div class="card-value" style="color: #6b7280;">{by_sev.get('low', 0)}</div>
            </div>
        </div>
        
        <div class="findings">
            <h2>Findings ({result.summary.get('failed', 0)})</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Severity</th>
                        <th>Location</th>
                        <th>Message</th>
                        <th>Suggestion</th>
                    </tr>
                </thead>
                <tbody>
                    {findings_html if findings_html else '<tr><td colspan="5" style="padding: 24px; text-align: center; color: #6b7280;">No findings — all checks passed!</td></tr>'}
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
"""


def format_markdown(result: ScanResult, verbose: bool = False) -> str:
    """Return Markdown report for a ScanResult."""
    by_sev = result.summary.get("by_severity", {})
    status = "✅ PASS" if result.summary.get("failed", 0) == 0 else "❌ FAIL"
    
    lines = [
        f"# Scan Report: {result.scan_name}",
        f"",
        f"**Status:** {status}",
        f"**Scan type:** {result.scan_type}",
        f"**Module:** {result.module or 'all'}",
        f"**Time:** {result.execution_time_ms}ms",
        f"**Generated:** {result.timestamp}",
        f"",
        f"## Summary",
        f"",
        f"| Metric | Value |",
        f"|--------|-------|",
        f"| Total checks | {result.summary.get('total_checks', 0)} |",
        f"| Passed | {result.summary.get('passed', 0)} |",
        f"| Failed | {result.summary.get('failed', 0)} |",
        f"| Critical | {by_sev.get('critical', 0)} |",
        f"| High | {by_sev.get('high', 0)} |",
        f"| Medium | {by_sev.get('medium', 0)} |",
        f"| Low | {by_sev.get('low', 0)} |",
        f"",
        f"## Findings",
        f"",
    ]
    
    if not result.findings:
        lines.append("No findings — all checks passed! ✅")
    else:
        for f in result.findings[:50]:  # Limit to 50 for markdown
            lines.append(f"### {f['id']} [{f['severity'].upper()}]")
            lines.append(f"")
            lines.append(f"- **File:** `{f['file']}:{f['line']}`")
            lines.append(f"- **Message:** {f['message']}")
            if f.get("suggestion"):
                lines.append(f"- **Suggestion:** {f['suggestion']}")
            lines.append(f"")
        
        if len(result.findings) > 50:
            lines.append(f"... and {len(result.findings) - 50} more findings (see JSON report)")
    
    return "\n".join(lines) + "\n"


def format_diff(old_result: ScanResult, new_result: ScanResult) -> str:
    """Compare two scan results and show differences."""
    old_findings = {(f["file"], f["rule"], f["line"]): f for f in old_result.findings}
    new_findings = {(f["file"], f["rule"], f["line"]): f for f in new_result.findings}
    
    added = [f for key, f in new_findings.items() if key not in old_findings]
    removed = [f for key, f in old_findings.items() if key not in new_findings]
    unchanged = [f for key, f in new_findings.items() if key in old_findings]
    
    lines = [
        f"# Scan Diff: {old_result.scan_name}",
        f"",
        f"**Old scan:** {old_result.timestamp}",
        f"**New scan:** {new_result.timestamp}",
        f"",
        f"## Summary",
        f"",
        f"| Metric | Old | New | Change |",
        f"|--------|-----|-----|--------|",
        f"| Failed | {old_result.summary.get('failed', 0)} | {new_result.summary.get('failed', 0)} | {'+' if new_result.summary.get('failed', 0) > old_result.summary.get('failed', 0) else '-' if new_result.summary.get('failed', 0) < old_result.summary.get('failed', 0) else '='} |",
        f"| Added | | {len(added)} | +{len(added)} |",
        f"| Removed | | {len(removed)} | -{len(removed)} |",
        f"| Unchanged | | {len(unchanged)} | ={len(unchanged)} |",
        f"",
        f"## Added Findings ({len(added)})",
        f"",
    ]
    
    for f in added[:20]:
        lines.append(f"- **{f['id']}** [{f['severity'].upper()}] `{f['file']}:{f['line']}` — {f['message']}")
    
    if len(added) > 20:
        lines.append(f"... and {len(added) - 20} more")
    
    lines.extend([
        f"",
        f"## Removed Findings ({len(removed)})",
        f"",
    ])
    
    for f in removed[:20]:
        lines.append(f"- **{f['id']}** [{f['severity'].upper()}] `{f['file']}:{f['line']}` — {f['message']}")
    
    if len(removed) > 20:
        lines.append(f"... and {len(removed) - 20} more")
    
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


def write_html_file(result: ScanResult, output_path: Path | None = None) -> Path:
    """Write HTML report to file."""
    if output_path is None:
        from datetime import datetime
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-{result.scan_name}.html"
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(format_html(result), encoding="utf-8")
    return output_path


def write_markdown_file(result: ScanResult, output_path: Path | None = None) -> Path:
    """Write Markdown report to file."""
    if output_path is None:
        from datetime import datetime
        timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
        output_path = OUTPUT_DIR / f"{timestamp}-{result.scan_name}.md"
    output_path = Path(output_path)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(format_markdown(result), encoding="utf-8")
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
        json_path = write_json_file(result, None)

    # Stdout handling
    if force_json or fmt == "json":
        if not quiet:
            sys.stdout.write(format_json(result))
        if json_path and not output:
            sys.stderr.write(f"Report saved: {json_path}\n")
    elif fmt == "text":
        if not quiet:
            sys.stdout.write(format_text(result, verbose=verbose))
        if json_path:
            sys.stderr.write(f"Report saved: {json_path}\n")
    elif fmt == "summary":
        if not quiet:
            use_color = sys.stdout.isatty()
            from _common import print_summary
            print_summary(result, verbose=verbose)
            if json_path:
                sys.stderr.write(f"Report saved: {json_path}\n")
        else:
            if json_path:
                sys.stderr.write(f"Report saved: {json_path}\n")
    elif fmt == "html":
        html_path = write_html_file(result, output)
        if not quiet:
            sys.stdout.write(f"HTML report saved: {html_path}\n")
        if json_path:
            sys.stderr.write(f"JSON report saved: {json_path}\n")
    elif fmt == "markdown":
        md_path = write_markdown_file(result, output)
        if not quiet:
            sys.stdout.write(format_markdown(result, verbose=verbose))
        if json_path:
            sys.stderr.write(f"JSON report saved: {json_path}\n")
    else:
        if not quiet:
            sys.stdout.write(format_json(result))

    # Exit code for --strict
    if strict and result.summary.get("failed", 0) > 0:
        return 1
    return 0
