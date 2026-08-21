# Sync Verification — The Final Quality Gate for Doc Sync

> **Last updated:** 2026-08-17 **Changes:** rewritten from checklist to comprehensive rule — preserves the full checklist content as prose with rationale

## Intent

After docs have been updated to match code and specs, run the final sync verification gate: update
metadata, verify every link resolves, honor the six sync key rules, and pass the verification
checklist. This is the audit that proves the sync actually happened and is complete.

## Rationale

The sync workflow's earlier steps (discovery and audit — see `rules/sync-workflow.md` and
`rules/audit-scope.md`) do the heavy lifting, but a sync is not finished until the artifacts are
left in a verifiable state. Three things go wrong at this final stage:

1. **Metadata not updated.** A doc whose `Last updated` date wasn't bumped looks older than it is —
   the freshness signal that lets `scan_doc_links.py` and future agents tell "maintained" from
   "abandoned" is corrupted.
2. **Links broken by the edit.** Renaming a section or deleting a file that other docs reference
   breaks their anchors; the edit that "fixed" one doc damages two others.
3. **Key rules silently violated.** Editing a conceptual doc to add a schema, duplicating a block
   instead of linking, or rewriting a file wholesale undoes the sync's own principles.

The gate exists to catch these before commit — a synced doc set is only as good as its weakest
artifact.

## How to Apply

### Update Metadata

Every `.md` file has metadata:

```markdown
> **Last updated:** YYYY-MM-DD **Changes:** brief description of what changed
```

Update both fields whenever content changes. Use the motion prefixes: `sync — {description}` for
auto-syncs, `feat — {description}` for new content, `fix — {description}` for corrections.

### Verify No Broken Links

- Relative paths in `[text](path)` must resolve.
- Check for renamed or deleted files referenced in docs.
- Anchor links must match existing section headings.

### Honor the Key Rules

1. Documentation is the SSOT — if code disagrees with docs, fix code (or fix docs if behavior
   changed intentionally).
2. Do NOT duplicate content — reference existing docs with relative paths.
3. Every module must have exactly one conceptual doc and one reference doc.
4. Conceptual docs contain NO implementation details (no file paths, no schemas).
5. Reference docs contain NO design rationale.
6. **Always use the `edit` tool (not `write`) when updating docs** — rewrite only the changed
   sections to minimize risk of accidentally deleting content or breaking formatting.

### Pass the Verification Checklist

- [ ] New modules/submodules have `.md` + `-reference.md` files
- [ ] Module index (`index.md`) updated with new dependencies
- [ ] Feature specs in `docs/specs/` match implementation (FR, NFR, data contracts)
- [ ] Spec index (`docs/specs/index.md`) lists all specs
- [ ] Agent guides & skills (`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/plans/`) match specs and code
- [ ] File paths in docs verified against actual codebase
- [ ] Class names and method signatures verified
- [ ] Migration schemas match actual database
- [ ] No broken relative links
- [ ] Metadata updated on all changed docs
- [ ] No stale content (removed features, renamed classes, changed signatures)

## Anti-Patterns & Pitfalls

- **Metatadata-only sync:** bumping `Last updated` on every doc "so the scanner passes" without
  verifying content. This actively hides future drift — don't touch a doc you didn't fix.
- **Deleting/renaming a file mid-sync** without fixing the docs that reference it. The scanner
  catches the broken link, but only after you created it.
- **Ignoring the agent layer checklist items.** The three checklist items about `AGENTS.md`,
  `.agents/skills/*`, `.agents/context/`, `.agents/plans/` are checked boxes in most syncs — they
  are exactly the ones that rot silently.
- **Whole-file rewrite "while we're here".** Key rule 6 exists because a `write` over an existing
  doc can silently drop unrelated content. Use targeted edits; review with `git diff`.
- **Committing before the gate.** Running the checklist mentally after commit — the whole point is
  to catch problems while fixing them is cheap.

## Verification / Detection

- `python3 scripts/scan_doc_links.py` — broken links (`BROKEN_FILE_LINK`, `BROKEN_ANCHOR`) and
  missing/stale metadata across `docs/`, `.agents/context/`, `README.md`, `AGENTS.md`.
- `git status` + `git diff` — confirm exactly the intended docs changed and nothing unrelated was
  dropped (Edit Policy).
- Grep for tier violations: conceptual docs with `app/`, `.php`, `::class`; reference docs with
  "because"/"why"/"purpose".