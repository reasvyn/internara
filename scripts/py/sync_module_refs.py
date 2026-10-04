#!/usr/bin/env python3
"""Sync module reference docs with the real filesystem inventory.

Three repair passes, all deterministic and idempotent:

1. PATH_FIXES   — rewrite stale path prefixes left behind by domain renames
                  (e.g. `Domain/Backups/` -> `Domain/Backup/`).
2. MISSING_ROWS — append rows to an existing table for files that exist in the
                  module but are absent from the doc's table.
3. MISSING_SECTIONS — insert a whole `## <Kind>` section (with a generated
                  table) for a component directory the doc never documented.

Run with --dry-run to preview; --check to exit 1 when drift remains.
"""

from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent.parent
DOCS = ROOT / "docs" / "refs" / "modules"
MODULES = ROOT / "app" / "Modules"

# Component directories in the order they should appear in a reference doc.
# Tuple = (doc section heading, table column headers, relative path segment).
KINDS: list[tuple[str, list[str], str]] = [
    ("Actions", ["File", "Class", "Extends"], "Actions"),
    ("Models", ["File", "Class", "Extends"], "Models"),
    ("Data / DTOs", ["File", "Class", "Extends"], "Data"),
    ("Enums", ["File", "Enum", "Implements"], "Enums"),
    ("Entities", ["File", "Class", "Extends"], "Entities"),
    ("Events", ["File", "Event", "Extends"], "Events"),
    ("Listeners", ["File", "Listener", "Listens To"], "Listeners"),
    ("Livewire Components", ["File", "Component", "Extends"], "Livewire"),
    ("Policies & Permissions", ["File", "Policy", "Extends"], "Policies"),
    ("Form Requests", ["File", "Request", "Extends"], "Http/Requests"),
    ("Middleware", ["File", "Middleware", "Purpose"], "Http/Middleware"),
    ("Services", ["File", "Service", "Purpose"], "Services"),
    ("Support", ["File", "Class", "Purpose"], "Support"),
    ("Console Commands", ["File", "Command", "Signature"], "Console/Commands"),
]

# Headings that already cover a component directory under a different name.
# Without this, a doc that has a rich `Support Classes & Action Traits` table
# would get a second, thinner `## Support` section appended.
SECTION_ALIASES: dict[str, tuple[str, ...]] = {
    "Support": ("Support", "Support Classes & Action Traits", "Support Classes"),
    "Form Requests": ("Form Requests", "Requests"),
    "Middleware": ("Middleware",),
    "Console Commands": ("Console Commands", "Commands"),
    "Services": ("Services",),
}

# Domain renames: doc rows still carry the old directory name.
PATH_FIXES: list[tuple[str, str]] = [
    (r"Domain/Backups/", "Domain/Backup/"),
    (r"(?<!Domain/)Backups/", "Backup/"),
    (r"Domain/Permissions/", "Domain/Permission/"),
    (r"(?<!Domain/)Permissions/", "Permission/"),
    (r"Domain/AccessTokens/", "Domain/AccessToken/"),
    (r"(?<!Domain/)AccessTokens/", "AccessToken/"),
    # `Notifications` is also a component directory (Xxx/Notifications/), so this
    # fix is anchored to the `Domain/` prefix to stay unambiguous.
    (r"Domain/Notifications/", "Domain/Notify/"),
]

EXTENDS_RE = re.compile(
    r"^\s*(?:final\s+|abstract\s+)?(?:class|enum|interface|trait)\s+\w+[^\n]*?\bextends\s+(\w+)", re.M
)
IMPLEMENTS_RE = re.compile(
    r"^\s*(?:final\s+|abstract\s+)?(?:class|enum|interface|trait)\s+\w+[^\n]*?\bimplements\s+([^\n{]+)", re.M
)
PROPERTY_RE = re.compile(r"protected\s+string\s+\$signature\s*=\s*'([^']+)'")


def module_dirs() -> dict[str, Path]:
    return {d.name.lower(): d for d in MODULES.iterdir() if d.is_dir()}


def inventory(module: Path) -> dict[str, list[Path]]:
    """Map each component directory (e.g. 'Actions') to its PHP files, sorted.

    A file is assigned to the *first* matching segment so that a file inside
    `Domain/Notify/Models/` is recorded under `Models`, not under `Notify`.
    """
    found: dict[str, list[Path]] = {}
    for path in module.rglob("*.php"):
        rel = path.relative_to(module)
        parts = rel.parts[:-1]  # directory parts only
        for _heading, _headers, segment in KINDS:
            seg_parts = segment.split("/")
            if any(parts[i : i + len(seg_parts)] == tuple(seg_parts) for i in range(len(parts))):
                found.setdefault(segment, []).append(rel)
                break
    return {k: sorted(v) for k, v in found.items()}


DOCBLOCK_RE = re.compile(r"/\*\*\s*\n\s*\*\s*(?P<text>[^\n]+?)\s*(?:\n|$)")


def summarize(source: str) -> str:
    """First sentence of the class docblock, used for the Purpose column."""
    m = DOCBLOCK_RE.search(source)
    if not m:
        return "—"
    text = m.group("text").strip().rstrip(".")
    if not text or text.startswith(("@", "{")):
        return "—"
    # Keep table cells compact and pipe-safe.
    return (text[:90] + "…") if len(text) > 90 else text


def describe(rel: Path, source: str, headers: list[str] | None = None) -> list[str]:
    """Build the table cells (after the file path) for one component file."""
    name = rel.stem
    wants_purpose = bool(headers) and headers[-1] == "Purpose"
    purpose = summarize(source) if wants_purpose else "—"
    if "Console/Commands" in str(rel):
        sig = PROPERTY_RE.search(source)
        return [f"`{name}`", f"`{sig.group(1)}`" if sig else purpose]
    if rel.parts[-2] == "Enums":
        impl = IMPLEMENTS_RE.search(source)
        names = re.findall(r"(\w+)", impl.group(1)) if impl else []
        return [f"`{name}`", ", ".join(f"`{n}`" for n in names) or "—"]
    ext = EXTENDS_RE.search(source)
    if ext:
        return [f"`{name}`", f"`{ext.group(1)}`"]
    return [f"`{name}`", purpose]


def has_table(section: str) -> bool:
    """True when the section contains a real markdown table."""
    rows = re.findall(r"^\|(.+)\|\s*$", section, re.M)
    if len(rows) < 2:
        return False
    return not (set(rows[1].replace("|", "").replace(" ", "")) - {"-", ":"})


def build_section(heading: str, headers: list[str], rels: list[str], sources: dict[str, str]) -> str:
    rows = "\n".join(
        "| `{}` | {} |".format(r, " | ".join(describe(Path(r), sources[r], headers))) for r in rels
    )
    return (
        f"## {heading}\n\n"
        f"| {headers[0]} | {headers[1]} | {headers[2]} |\n"
        f"|---|---|---|\n{rows}\n\n---\n\n"
    )


# New sections go before the trailing "Architectural Integration" / "Tests"
# block so the document keeps its structural narrative order.
TAIL_HEADINGS = ("Architectural Integration", "Tests")


def insert_section(
    text: str, heading: str, headers: list[str], rels: list[str], sources: dict[str, str]
) -> str:
    block = build_section(heading, headers, rels, sources)
    for tail in TAIL_HEADINGS:
        m = re.search(rf"^## {re.escape(tail)}\s*$", text, re.M)
        if m:
            # build_section already ends with a `---` rule, so the block needs no
            # leading one — appending both would leave a doubled separator.
            prefix = text[: m.start()].rstrip()
            return f"{prefix}\n\n{block}{text[m.start():]}"
    return text.rstrip() + "\n\n" + block


def section_prefix(documented: set[str], rels: list[str]) -> bool:
    """True when the section already writes paths without the `Domain/` prefix.

    Some reference docs list domain subtrees as `Backup/Services/...` while
    others use the full `Domain/Backup/Services/...`. Appending rows in the
    other style would create near-duplicate entries, so the existing style
    wins.
    """
    slashed = [d for d in documented if "/" in d]
    domain_slashed = [d for d in slashed if d.startswith("Domain/")]
    if not domain_slashed and any(r.startswith("Domain/") for r in rels):
        return True
    return not domain_slashed and len(slashed) > len(domain_slashed) / 2


def repair(text: str, mod: Path) -> tuple[str, list[str]]:
    """Apply the three repair passes; return the new text plus a change log."""
    notes: list[str] = []
    inv = inventory(mod)

    # Pass 1 — stale domain-rename path prefixes.
    for pattern, repl in PATH_FIXES:
        text, n = re.subn(pattern, repl, text)
        if n:
            notes.append(f"path-fix {pattern} -> {repl} ({n})")

    # Passes 2 & 3 — reconcile each component table / section.
    for heading, headers, key in KINDS:
        files = inv.get(key, [])
        if not files:
            continue
        rels = [str(f) for f in files]
        sources = {str(f): (mod / f).read_text(encoding="utf-8") for f in files}
        m = None
        for candidate in SECTION_ALIASES.get(heading, (heading,)):
            m = re.search(rf"^## {re.escape(candidate)}\s*$(.*?)(?=^## |\Z)", text, re.M | re.S)
            if m:
                break
        if not m:
            text = insert_section(text, heading, headers, rels, sources)
            notes.append(f"section+ {heading} ({len(rels)} rows)")
            continue

        section = m.group(1)
        if not has_table(section):
            continue
        tokens = set(re.findall(r"`([^`\n]+)`", section))
        # A file counts as documented when its path is listed, or when its class
        # name alone is (tables keyed by command signature carry no path column).
        documented = {t for t in tokens if t.endswith(".php")}
        names = {t for t in tokens if not t.endswith(".php")}
        rels = [r for r in rels if r not in documented and Path(r).stem not in names]
        if not rels:
            continue
        absent = rels
        if stale_paths := [
            d for d in sorted(documented) if "/" in d and Path(d).name.endswith(".php") and d not in rels
        ]:
            notes.append(f"{heading}: {len(stale_paths)} stale path(s) e.g. {stale_paths[:2]}")
        if not absent:
            continue

        # Append after the final table row so trailing prose is preserved.
        strip_domain = section_prefix(documented, rels)

        def render(rel: str) -> str:
            shown = rel[len("Domain/") :] if strip_domain and rel.startswith("Domain/") else rel
            return "| `{}` | {} |".format(
                shown, " | ".join(describe(Path(rel), sources[rel], headers))
            )

        lines = section.split("\n")
        last_row = max(i for i, ln in enumerate(lines) if re.match(r"^\|.*\|\s*$", ln))
        lines[last_row + 1 : last_row + 1] = [render(r) for r in absent]
        section = "\n".join(lines)
        notes.extend(f"row+ {heading}: {r}" for r in absent)
        text = text[: m.start(1)] + section + text[m.end(1) :]

    return text, notes


def main() -> int:
    ap = argparse.ArgumentParser(description="Sync module reference docs with the codebase.")
    ap.add_argument("--dry-run", action="store_true", help="print planned repairs only")
    ap.add_argument("--check", action="store_true", help="exit 1 if repairs are pending")
    ap.add_argument("--module", help="limit to one module key (e.g. sysadmin)")
    args = ap.parse_args()

    dirs = module_dirs()
    total = 0
    for name in sorted(dirs):
        if args.module and name != args.module.lower():
            continue
        doc = DOCS / f"{name}-reference.md"
        if not doc.is_file():
            print(f"skip {name}: no reference doc", file=sys.stderr)
            continue
        original = doc.read_text(encoding="utf-8")
        updated, notes = repair(original, dirs[name])
        if not notes:
            continue
        total += len(notes)
        print(f"\n== {name} ({len(notes)} repair(s))")
        for note in notes:
            print(f"   {note}")
        if not args.dry_run:
            doc.write_text(updated, encoding="utf-8")

    print(f"\ntotal repairs: {total}")
    return 1 if (args.check and total) else 0


if __name__ == "__main__":
    raise SystemExit(main())

