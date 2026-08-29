# Metadata & Structure — The Documentation Contract

## Intent

Every markdown document must follow a standard structural template with standard section names. These conventions make every doc predictable to navigate (fixed heading order) and machine-checkable by scanners. Freshness and change history are tracked via git, not inline metadata.

## Rationale

Docs degrade silently: a file edited six months ago looks identical to one updated yesterday unless history is visible. Section names that vary (`## References` vs `## See Also` vs `## Where to Find It`) make navigation unpredictable for both humans and AI agents. A fixed contract means:
- **History is traceable** — `git log --follow -- <file>` shows when a doc was last touched; `git diff` shows what changed.
- **Structure is predictable** — a reader always knows where the summary, the body, and the footer are.
- **Automation is possible** — scanners (`scan_doc_links.py`) enforce link integrity and structure without inline dates.

## How to Apply

### History Discipline (Git as Source of Truth)

No inline `> **Last updated:**` metadata in markdown files. History lives in git:

```bash
git log --follow -- <file>      # history of a doc
git diff -- <file>              # what changed in this branch
git log --since="14 days ago" --oneline -- docs/
```

Write a descriptive commit message (`type(scope): desc`); do not duplicate it inside the file.

### Document Structure Template

Every markdown doc follows this **minimal** structure:

```markdown
# Title — Subtitle/Scope

## Description

{1-3 sentence summary of what this doc covers.}

---

## {Content Heading}

{Body content — explanation, rules, guidelines, etc.}

### {Sub-section}

{Deeper detail under the content heading}

---

## AI Agent Guides  *(optional)*

{Structured, machine-readable instructions optimized for AI agents — checklists,
decision tables, scan commands, quick lookups. Only add when the doc serves as a
reference that agents will consult during tasks.}

---

## Quick References

- `{path}` — {what's there}
- `{path}` — {what's there}
- [Related Doc](relative-path.md) — {why it's relevant}
```

| Level | Element | Purpose |
|-------|---------|---------|
| H1 | `# Title` | Document identity — one per file, always first |
| H2 | `## Description` | What this doc covers — mandatory on every doc |
| H2 | `## {Content}` | Main body — as many H2 sections as needed |
| H3 | `### {Sub-section}` | Deeper detail under a content H2 |
| H2 | `## AI Agent Guides` | Optional — machine-readable instructions for AI agents |
| H2 | `## Quick References` | Links to related files, always last section |

Structural rules:
- H1 title: `# Subject — Subtitle` format, exactly one per file.
- `## Description` is always the first H2 after H1.
- Content sections (`##`) are topical — name them after what they explain.
- `###` subsections group related detail under a content H2.
- `## Quick References` is the standard footer (not `## References`, not `## Where to Find It`).
- `---` horizontal rules separate major H2 sections.
- Never skip heading levels: H1 → H2 → H3 (no H4 unless truly necessary).

### Section Naming Conventions

| Purpose | Correct Name | Wrong Names |
|---------|-------------|-------------|
| File/code location pointers | `## Quick References` | `## References`, `## See Also`, `## Resources`, `## Where to Find It` |
| Module overview | `## Description` | `## Summary`, `## Overview` |
| Behavior explanation | `## How It Works` | `## Implementation`, `## Details` |

### AI Agent Guides Rules

`## AI Agent Guides` is **optional**. Add it only when the document is a reference that AI agents
will consult during coding tasks.

**What goes here:**
- Checklists agents can step through
- Decision tables (if X → do Y)
- Scan/verification commands
- Quick-lookup tables (invariant → file path, rule → line number)
- Anti-pattern → fix mapping

**What does NOT go here:**
- Explanations (those belong in the content body)
- Design rationale (that's for humans)
- Full code examples (link to source instead)

**Format principles:**
- Prefer tables over prose — agents parse tables faster.
- Prefer concrete over abstract — `python3 tools/scan_violations.py` not "run the scan".
- Prefer flat over nested — avoid deep heading trees inside this section.
- Every entry should be actionable without reading surrounding context.

## Anti-Patterns & Pitfalls

- **Wrong footer name:** `## References` or `## Where to Find It` where the convention says `## Quick References` — breaks predictability and the cross-doc search.
- **Explanatory prose inside `AI Agent Guides`:** turns the machine-readable section into prose and duplicates the body.
- **Skipping heading levels:** jumping H2 → H4 confuses navigation and TOC generation.

## Verification / Detection

- `python3 tools/scan_doc_links.py` — validates relative links (file, anchor, external); freshness via git, not inline dates.
- Visual check: H1 present and first; `## Description` first H2; `## Quick References` last.
- Grep for wrong footer names: `grep -rn "## References\|## See Also\|## Where to Find It" docs/`.
- History check: `git log --follow -- <file>` for last touch; `git diff` for branch changes.
