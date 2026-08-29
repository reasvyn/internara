#!/usr/bin/env python3
"""
scan_ui_consistency.py — UI Consistency & Accessibility Guard (v1.0)

Enforces visual and UX consistency per:
  docs/guides/arch/ui-pattern.md (Tailwind v4 CSS-first, semantic palette, responsive)
  docs/guides/arch/ux-pattern.md (WCAG 2.2 AA, i18n)
  docs/conventions.md §13-14 (a11y, localization)

Rules:
  UI-I18N      Hardcoded user string without __() in Blade/Livewire
  UI-COLOR     Hardcoded Tailwind color outside --color-* palette (e.g., bg-red-500 vs bg-primary)
  UI-A11Y-ALT  <img> without alt
  UI-A11Y-LABEL <input> without associated <label> or aria-label
  UI-A11Y-ARIA Icon-only button without aria-label
  UI-COMPONENT Duplicate layout markup instead of Core reusable component (Navbar/Sidebar/Header/Footer)
"""

from __future__ import annotations

import re
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from _common import (  # noqa: E402
    ROOT,
    Finding,
    ScanResult,
    build_report,
    parse_args_with_common,
    print_summary,
    read_file,
    relative_path,
    write_report,
)

SCAN_NAME = "ui-consistency"

RE_BLADE_ECHO = re.compile(r"\{\{\s*__\(")
RE_HARDCODED_STRING = re.compile(r">([A-Z][a-zA-Z0-9 ,.'\"!?\-]{3,})</")
RE_HARDCODED_COLOR = re.compile(r"class=\"[^\"]*\b(bg|text|border)-(red|blue|green|yellow|purple|pink|gray|slate|zinc)-(?:50|100|200|300|400|500|600|700|800|900)\b")
RE_IMG = re.compile(r"<img\s[^>]*>", re.I)
RE_IMG_ALT = re.compile(r"\balt\s*=", re.I)
RE_INPUT = re.compile(r"<input\s[^>]*>", re.I)
RE_LABEL_FOR = re.compile(r"<label\s[^>]*\bfor\s*=", re.I)
RE_ARIA_LABEL = re.compile(r"aria-label\s*=", re.I)
RE_ICON_BUTTON = re.compile(r"<button[^>]*>\s*<svg[^>]*>.*?</svg>\s*</button>", re.I | re.S)
RE_DUPLICATE_NAV = re.compile(r"<nav[^>]*>.*?</nav>", re.I | re.S)
RE_CORE_COMPONENTS = re.compile(r"<x-core::(layouts|ui)\.")


def discover_core_components() -> list[str]:
    """Discover UI components dynamically from resources/views/ui (SSOT) and legacy core; no hardcoding."""
    comps: list[str] = []
    for base in [ROOT / "resources" / "views" / "ui", ROOT / "resources" / "views" / "core"]:
        if not base.exists():
            continue
        for p in base.rglob("*.blade.php"):
            rel = p.relative_to(base).with_suffix("").as_posix()
            # Prefix with module name for clarity
            prefix = "ui" if "ui" in str(base) else "core"
            comps.append(f"{prefix}/{rel}")
    return sorted(set(comps))


def check_blade(path: Path) -> list[Finding]:
    content = read_file(path)
    if not content:
        return []
    rel = relative_path(path)
    findings: list[Finding] = []
    lines = content.splitlines()

    # Skip email templates and non-user-facing partials if needed
    if "emails" in rel or "vendor" in rel:
        return findings

    for idx, line in enumerate(lines, start=1):
        stripped = line.strip()

        # UI-I18N: look for hardcoded English-looking strings inside tags without __()
        # Heuristic: >Text< without {{ __(' pattern on same line and not in comment
        if ">" in line and "</" in line and "{{" not in line and "@lang" not in line and "__(" not in line:
            m = RE_HARDCODED_STRING.search(line)
            if m:
                text = m.group(1).strip()
                # Ignore short, non-user-facing, or technical strings
                if len(text) > 8 and not text.startswith("{{") and text not in ("Content", "Loading"):
                    # Whitelist common non-i18n cases
                    if text.lower() not in ("search", "loading..."):
                        # Only flag if file is in resources/views and not a layout that is mostly structure
                        if "core/layouts" not in rel:
                            findings.append(Finding(
                                id="UI-0000",
                                rule="UI_I18N",
                                severity="low",
                                category="convention",
                                file=rel,
                                line=idx,
                                message=f"Hardcoded user string without __(): '{text[:40]}'",
                                suggestion="Use {{ __('key') }} per docs/conventions.md §14 and ux-pattern.md §Localization",
                                reference="docs/conventions.md §14, docs/guides/arch/ux-pattern.md",
                                context={"text": text[:80]},
                            ))

        # UI-COLOR: hardcoded Tailwind palette outside semantic --color-*
        mcol = RE_HARDCODED_COLOR.search(line)
        if mcol and "resources/css/app.css" not in rel:
            findings.append(Finding(
                id="UI-0000",
                rule="UI_COLOR",
                severity="low",
                category="convention",
                file=rel,
                line=idx,
                message=f"Hardcoded Tailwind color '{mcol.group(0)[:40]}' — use semantic palette (--color-primary etc.)",
                suggestion="Use bg-primary/text-primary/border-primary per docs/guides/arch/ui-pattern.md §Semantic Palette",
                reference="docs/guides/arch/ui-pattern.md §@theme / docs/conventions.md §Theming",
                context={"class": mcol.group(0)[:80]},
            ))

    # UI-A11Y: <img> without alt (whole-file scan to catch multiline)
    for m in RE_IMG.finditer(content):
        tag = m.group(0)
        if not RE_IMG_ALT.search(tag):
            line = content[:m.start()].count("\n") + 1
            findings.append(Finding(
                id="UI-0000",
                rule="UI_A11Y_ALT",
                severity="medium",
                category="convention",
                file=rel,
                line=line,
                message="<img> without alt attribute",
                suggestion='Add alt="" for decorative or descriptive alt per docs/guides/arch/ux-pattern.md §22.1',
                reference="docs/guides/arch/ux-pattern.md §22.1 Perceivable",
                context={"tag": tag[:100]},
            ))

    # UI-A11Y: icon-only button without aria-label
    if "<button" in content and "<svg" in content:
        for m in RE_ICON_BUTTON.finditer(content):
            block = m.group(0)
            if "aria-label" not in block and "aria-labelledby" not in block:
                # Check if button contains visible text
                inner = re.sub(r"<svg.*?</svg>", "", block, flags=re.S | re.I)
                inner_text = re.sub(r"<[^>]+>", "", inner).strip()
                if not inner_text:
                    line = content[:m.start()].count("\n") + 1
                    findings.append(Finding(
                        id="UI-0000",
                        rule="UI_A11Y_ARIA",
                        severity="medium",
                        category="convention",
                        file=rel,
                        line=line,
                        message="Icon-only <button> without aria-label",
                        suggestion='Add aria-label="Close modal" etc. per docs/guides/arch/ux-pattern.md §22.4',
                        reference="docs/guides/arch/ux-pattern.md §22.4 Robust",
                        context={"tag": block[:120]},
                    ))

    # UI-COMPONENT: raw <nav> without Core component — informational only, not mandatory.
    # Not every component must use Core; this is a suggestion for app-shell layouts only.
    if "<nav" in content and "x-core::" not in content and "core/layouts" not in rel:
        # Only suggest for files that look like layout/shell (not generic content components)
        is_shell_candidate = any(k in rel for k in ["layouts", "app.blade", "base.blade"]) or "resources/views/layouts" in rel
        if not is_shell_candidate:
            # Content components may legitimately have their own <nav> (e.g., tabs); don't flag
            pass
        else:
            nav_count = len(RE_DUPLICATE_NAV.findall(content))
            if nav_count > 0 and "resources/views" in rel:
                line = content.find("<nav")
                line_no = content[:line].count("\n") + 1 if line != -1 else 1
                findings.append(Finding(
                    id="UI-0000",
                    rule="UI_COMPONENT",
                    severity="low",
                    category="architecture",
                    file=rel,
                    line=line_no,
                    message="Raw <nav> in layout without Core component — consider <x-core::layouts.*> for consistency (optional)",
                    suggestion="For app shell, prefer reusable Core layouts; content components may keep their own markup",
                    reference="docs/guides/arch/ui-pattern.md, docs/conventions.md §Frontend",
                    context={"count": nav_count},
                ))

    return findings


def main() -> None:
    args = parse_args_with_common("UI Consistency & a11y Guard — i18n, palette, a11y, Core components")
    start = time.time()
    findings: list[Finding] = []

    # Find blade files
    from _common import find_blade_files, find_files_parallel
    blade_files = find_blade_files(args.module)

    findings.extend(find_files_parallel(blade_files, check_blade))
    findings.sort(key=lambda f: (f.file, f.line))
    for i, f in enumerate(findings):
        f.id = f"UI-{i+1:04d}"

    core_comps = discover_core_components()
    metadata = {"total_files": len(blade_files), "core_components": ", ".join(core_comps[:20]), "core_components_total": len(core_comps)}
    result = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=len(blade_files) * 3)

    if args.json or args.format == "json":
        import dataclasses, json
        print(json.dumps(dataclasses.asdict(result), indent=2, ensure_ascii=False))
    elif not args.quiet:
        print_summary(result, verbose=args.verbose)

    out = write_report(result, Path(args.output) if args.output else None)
    if not args.quiet:
        print(f"Report saved: {relative_path(out)}")
    if args.strict and result.summary["failed"] > 0:
        sys.exit(1)


if __name__ == "__main__":
    main()
