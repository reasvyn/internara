# Doc Quality — Completeness & Accuracy Gate

> **Last updated:** 2026-08-19 **Changes:** clarify `**Changes:**` metadata records only the latest revision, history lives in git

## Intent

Before any doc change is committed, it must pass a single quality gate that verifies accuracy,
structure, and maintainability. This rule consolidates the checks scattered across the doc-writing
rules into one review pass, so a doc change ships clean — or not at all.

## Rationale

A doc is judged on three properties:

- **Accuracy** — does it describe the actual codebase and spec, with current metadata?
- **Structure** — does it follow the template an agent or human expects to find?
- **Maintainability** — will it still be correct after the next refactor, or is it already a trap
  (broken links, duplicated facts)?

Each individual rule file explains one slice of this (structure → `metadata-structure.md`,
separation → `two-tier-model.md`, links → `link-integrity.md`, PHPDoc → `phpdoc.md`,
SSOT/edit discipline → `documentation-first.md`). This gate is where they all get **checked
together**, because docs fail in combination: a structurally perfect doc with a broken link and a
stale date is still a bad doc. Running the combined gate catches what the slices miss and keeps
every doc the same shape, so maintenance stays cheap.

## How to Apply

Run this gate on **every** doc change (new, edited, or rewritten) before committing. A failure in
any area blocks the commit until fixed — there are no "acceptable" docs that skip a category.

### 1. Structural Completeness

- H1 title uses `# Subject — Subtitle` format.
- Metadata block present on line 3: `> **Last updated:** YYYY-MM-DD **Changes:** ...`.
- `## Description` section present (1-3 sentence summary), first H2.
- `## Quick References` footer section present (the standard footer).
- `---` horizontal rules between major sections.
- Heading hierarchy is correct (`##` → `###` → `####`), no skipped levels.

Why: these are the navigational anchors every reader relies on. A doc missing its `## Description`
has no summary; a doc missing its footer has no pointer to related material.

### 2. Two-Tier Separation

- Conceptual doc (`{module}.md`) contains NO implementation details (file paths, class names,
  schemas, Actions/Routes tables).
- Reference doc (`{module}-reference.md`) contains NO design rationale.
- Non-module docs separate "why" (conceptual) from "what" (reference).

Why: see `rules/two-tier-model.md`. The failure mode is the "kitchen-sink" doc — one file trying to
be both manual and index — which serves neither audience and rots in both dimensions.

### 3. Metadata

- `**Last updated:**` date is current (`YYYY-MM-DD` format, not `DD/MM/YYYY` or `MM-DD-YYYY`).
- `**Changes:**` describes what changed in this revision — the latest change only. Past changes are
  recorded in git history, never accumulated in the metadata line (no "Prior:" trails).

Why: stale metadata is a lie told to every future reader. The date is how anyone (including the
`scan_doc_links.py` scanner) judges whether a doc can be trusted.

### 4. Link Integrity

- All `[text](path)` resolve to existing files.
- All `[text](path#anchor)` match existing headings.
- No orphaned references (link target doesn't exist).
- Relative paths used (not absolute paths).

Why: see `rules/link-integrity.md`. Broken links are the first visible sign of doc rot, and they
erase trust in the whole document.

### 5. Content Quality

- No content duplication — cross-reference via relative links instead.
- `## Quick References` used (not `## References`, `## See Also`, `## Resources`).
- Code examples use correct syntax highlighting (```php, ```bash, etc.).
- Tables are properly formatted with aligned columns.
- Indonesian text only in `lang/id/` — all docs are English.

Why: duplicated content becomes competing truth; misnamed sections break predictability; Indonesian
prose outside `lang/id/` leaks into the English-only codebase and docs.

### 6. PHPDoc Quality

- No `@author`, `@version`, `@created`, `@package` tags.
- No redundant `@param`/`@return` when native types are present.
- `@throws` lists specific exception types (not generic `Exception`).
- One-line for simple methods, multi-line for complex.
- `@see` used for cross-references to related classes.

Why: see `rules/phpdoc.md`. PHPDoc that duplicates or contradicts native types misleads the reader
who trusts it.

## Anti-Patterns & Pitfalls

The following are the destructive patterns this gate exists to prevent. Any one of them is a
commit-blocking defect:

- Metadata date **not updated** when content changed.
- Implementation details **leaked into** conceptual docs.
- Design rationale **leaked into** reference docs.
- Content **duplicated** instead of cross-referenced.
- **Broken** relative links.
- PHPDoc **duplicating** native type hints.

**Pitfall — "it's just one line":** treating a one-line doc fix as too small for the gate. A
one-line change that forgets to bump the date or breaks an anchor is still a defect; the gate is
proportional, not skip-able.

**Pitfall — "the scanner passed":** `scan_doc_links.py` validates links and metadata only. It does
not check tier separation, section names, or PHPDoc. A clean scanner is necessary, not sufficient.

## Verification / Detection

- `python3 scripts/scan_doc_links.py` — catches broken links, missing/stale metadata, wrong
  section-name footers are NOT caught — review those manually.
- Grep for tier violations: `grep -n "app/\|::class\|.php" docs/modules/*.md` (conceptual docs
  should have no hits).
- Grep for PHPDoc violations: `grep -rn "@author\|@version\|@param \|@return " app/`.
- `git diff` on the touched doc — confirm only the intended sections changed (Edit Policy).
