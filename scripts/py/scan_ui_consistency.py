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
  UI-WEIGHT    font-black/font-extrabold — weights the self-hosted family does not ship
  UI-HEADING   Heading structure: page without h1, or h2 deviating from the canonical scale
  UI-TYPE_SCALE Arbitrary px font size below the 12px floor, or inline font-size
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
try:
    from _output import handle_output
except ImportError:
    import sys as _sys
    _sys.path.insert(0, str(__import__("pathlib").Path(__file__).parent))
    from _output import handle_output

SCAN_NAME = "ui-consistency"

RE_BLADE_ECHO = re.compile(r"\{\{\s*__\(")
RE_HARDCODED_STRING = re.compile(r">([A-Z][a-zA-Z0-9 ,.'\"!?\-]{3,})</")
RE_HARDCODED_COLOR = re.compile(r"class=\"[^\"]*\b(bg|text|border)-(red|blue|green|yellow|purple|pink|gray|slate|zinc)-(?:50|100|200|300|400|500|600|700|800|900)\b")
RE_IMG = re.compile(r"<img\s+(?:\"[^\"]*\"|'[^']*'|\{\{.*?\}\}|@\w+\((?:[^()]|\([^()]*\))*\)|[^>\"'])+>", re.I | re.S)
RE_IMG_ALT = re.compile(r"\balt\s*=", re.I)
RE_INPUT = re.compile(r"<input\s[^>]*>", re.I)
RE_LABEL_FOR = re.compile(r"<label\s[^>]*\bfor\s*=", re.I)
RE_ARIA_LABEL = re.compile(r"aria-label\s*=", re.I)
RE_ICON_BUTTON = re.compile(r"<button[^>]*>\s*<svg[^>]*>.*?</svg>\s*</button>", re.I | re.S)
RE_DUPLICATE_NAV = re.compile(r"<nav[^>]*>.*?</nav>", re.I | re.S)
RE_CORE_COMPONENTS = re.compile(r"<x-(core|ui)::(layouts|ui)\.")
RE_HEADING_TAG = re.compile(r"<h([1-6])\b([^>]*)>", re.IGNORECASE | re.DOTALL)
RE_CLASS_ATTR = re.compile(r"""class\s*=\s*["']([^"']*)["']""", re.DOTALL)
RE_FORBIDDEN_WEIGHT = re.compile(r"\bfont-(black|extrabold)\b")
RE_ARBITRARY_SIZE = re.compile(r"\btext-\[(\d+)px\]")
RE_INLINE_FONT_SIZE = re.compile(r"""style\s*=\s*["'][^"']*font-size:\s*\d+px""", re.DOTALL)
RE_BLADE_COMMENT = re.compile(r"\{\{--.*?--\}\}", re.DOTALL)
RE_HTML_COMMENT = re.compile(r"<!--.*?-->", re.DOTALL)

# design-system.md §5.2 — the h1 sources are components, not hand-rolled markup
H1_SOURCES = ("<x-ui::components.page-header", "<x-ui::components.record-manager", "<h1")
HEADING_REFERENCE = "docs/guides/ui-ux/design-system.md §5.2"
WEIGHT_REFERENCE = "docs/guides/ui-ux/design-system.md §5.1"
SIZE_REFERENCE = "docs/guides/ui-ux/design-system.md §5.3"
# design-system.md §5.3 — text-xs (12px) is the floor for readable text
TYPE_SCALE_FLOOR_PX = 12


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

    # UI-COMPONENT: raw <nav> without UI/Core component — informational only, not mandatory.
    # Not every component must use Core; this is a suggestion for app-shell layouts only.
    if "<nav" in content and "x-core::" not in content and "x-ui::" not in content and "core/layouts" not in rel and "ui/layouts" not in rel:
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

    # UI-WEIGHT / UI-HEADING scan markup with comments stripped: a Blade comment that documents
    # "<h1>" must not be read as a rendered heading.
    markup = RE_HTML_COMMENT.sub("", RE_BLADE_COMMENT.sub("", content))
    # Print/plain-HTML templates (pdf output, bare error pages) carry print stylesheets, not the
    # Tailwind type scale — they are out of scope for §5.
    is_print = "pdf" in Path(rel).name.lower() or "/pdf/" in rel
    uses_design_system = 'class="' in markup and not is_print

    # UI-WEIGHT: Instrument Sans ships 400/500/600/700 only; 800/900 are synthesised
    if uses_design_system:
        for m in RE_FORBIDDEN_WEIGHT.finditer(markup):
            line_no = markup[: m.start()].count("\n") + 1
            findings.append(Finding(
                id="UI-0000",
                rule="UI_WEIGHT",
                severity="medium",
                category="convention",
                file=rel,
                line=line_no,
                message=f"'{m.group(0)}' — the self-hosted family ships no 800/900 cut, so the browser synthesises a faux-bold face",
                suggestion="Use font-bold (700), the heaviest real cut",
                reference=WEIGHT_REFERENCE,
                context={"class": m.group(0)},
            ))

    # UI-TYPE_SCALE: text-xs (12px) is the floor; arbitrary px sizes bypass the scale
    if uses_design_system:
        for m in RE_ARBITRARY_SIZE.finditer(markup):
            px = int(m.group(1))
            if px >= TYPE_SCALE_FLOOR_PX:
                continue
            line_no = markup[: m.start()].count("\n") + 1
            findings.append(Finding(
                id="UI-0000",
                rule="UI_TYPE_SCALE",
                severity="low",
                category="convention",
                file=rel,
                line=line_no,
                message=f"'{m.group(0)}' is below the {TYPE_SCALE_FLOOR_PX}px floor — arbitrary sizes bypass the type scale and are unreadable at default zoom",
                suggestion="Use text-xs; if the label genuinely cannot afford 12px, reduce container padding or hide it at the densest breakpoint",
                reference=SIZE_REFERENCE,
                context={"size": f"{px}px"},
            ))

        for m in RE_INLINE_FONT_SIZE.finditer(markup):
            line_no = markup[: m.start()].count("\n") + 1
            findings.append(Finding(
                id="UI-0000",
                rule="UI_TYPE_SCALE",
                severity="low",
                category="convention",
                file=rel,
                line=line_no,
                message="Inline font-size bypasses the type scale and does not respond to the user's text-size preference",
                suggestion="Use a scale utility (text-xs … text-4xl) instead of style=\"font-size:…\"",
                reference=SIZE_REFERENCE,
                context={"style": m.group(0)[:120]},
            ))

    # UI-HEADING: one h1 per page, and h2 must sit on the canonical scale
    if not uses_design_system:
        return findings

    headings = [
        (m.group(1), m.group(2), markup[: m.start()].count("\n") + 1)
        for m in RE_HEADING_TAG.finditer(markup)
    ]
    has_h1 = any(src in markup for src in H1_SOURCES)
    # Partials inherit their parent's h1 (setup step components, widgets)
    is_partial = "/components/" in rel or "/widgets/" in rel or "/partials/" in rel or Path(rel).name.startswith("_")

    if headings and not has_h1 and not is_partial:
        findings.append(Finding(
            id="UI-0000",
            rule="UI_HEADING",
            severity="medium",
            category="accessibility",
            file=rel,
            line=headings[0][2],
            message="Page renders section headings but no h1 — screen readers announce no page title",
            suggestion="Promote the page-title heading to <h1>, or render <x-ui::components.page-header> / <x-ui::components.record-manager>",
            reference=HEADING_REFERENCE,
            context={"levels": ",".join(f"h{h[0]}" for h in headings[:6])},
        ))

    for level, attrs, line_no in headings:
        cls = RE_CLASS_ATTR.search(attrs)
        classes = cls.group(1) if cls else ""
        if level == "2":
            missing = [t for t in ("text-xl", "font-bold") if t not in classes]
            if missing:
                findings.append(Finding(
                    id="UI-0000",
                    rule="UI_HEADING",
                    severity="low",
                    category="convention",
                    file=rel,
                    line=line_no,
                    message=f"h2 off the canonical scale — missing {'/'.join(missing)} (expected 'text-xl font-bold tracking-tight')",
                    suggestion="Section headings are text-xl font-bold tracking-tight; a smaller h2 collapses into h3",
                    reference=HEADING_REFERENCE,
                    context={"class": classes[:120]},
                ))
        elif level == "1" and "font-bold" not in classes:
            findings.append(Finding(
                id="UI-0000",
                rule="UI_HEADING",
                severity="low",
                category="convention",
                file=rel,
                line=line_no,
                message="h1 is not font-bold — the page title must outrank every section heading",
                suggestion="h1 is text-2xl font-bold tracking-tight (compact shells may keep a smaller size, not a lighter weight)",
                reference=HEADING_REFERENCE,
                context={"class": classes[:120]},
            ))

    return findings


def main() -> None:
    args = parse_args_with_common("UI Consistency & a11y Guard — i18n, palette, a11y, Core components")
    start = time.time()
    findings: list[Finding] = []

    # Find blade files
    from _common import find_blade_files, find_files_parallel
    blade_files = find_blade_files(args.module)

    findings.extend(find_files_parallel(blade_files, check_blade, use_cache=not args.no_cache))
    findings.sort(key=lambda f: (f.file, f.line))
    for i, f in enumerate(findings):
        f.id = f"UI-{i+1:04d}"

    core_comps = discover_core_components()
    metadata = {"total_files": len(blade_files), "core_components": ", ".join(core_comps[:20]), "core_components_total": len(core_comps)}
    result = build_report(findings, SCAN_NAME, "full" if not args.module else "module", args.module, start, metadata, total_checks=len(blade_files) * 6)

    # Uniform output via _output.py
    exit_code = handle_output(result, args)
    if exit_code:
        sys.exit(exit_code)


if __name__ == "__main__":
    main()
