---
name: internara-write
description: Write and maintain Internara documentation — docs/, module refs, AGENTS.md, skills, link integrity. Use when document, write docs, update docs, sync docs, PHPDoc, doc drift, or docblock is mentioned.
---

# Write Command — Documentation, Content & Docs Sync (Internara)

Delegates to the `internara-writer` agent (documentation specialist) and the `doc-writing`/`sync-docs`
skills. Docs are SSOT: keeps docs ↔ specs ↔ code ↔ skills ↔ agent guides aligned, two-tier (conceptual
vs reference), with link integrity enforced. See `.agents/skills/doc-writing/SKILL.md`.

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask which artifact to write/update and what changed (code, spec, or convention).

## Steps

1. **Understand — Artifact affected.** Identify affected docs (reference/conceptual), agent guides &
   skills, module refs, or PHPDoc; locate the governing spec. On code/doc mismatch, check
   `git log --follow -- <file>` before deciding which is stale.
2. **Plan — Tier & mapping.** Choose conceptual vs reference tier; map every changed file to the doc
   that must change; decide additions vs cross-referenced updates (never duplicate a fact).
3. **Build — Write/update surgically.** Apply `doc-writing` shapes (PHPDoc, markdown, cross-refs, area
   tables); ensure every `[label](path)` resolves; keep existing voice.
4. **Verify — Quality gates.** `python3 tools/scan_doc_links.py` (broken 0, outdated explained);
   `vendor/bin/pint --dirty --test` only if PHP touched; history via `git log` (no inline
   "Last updated").
5. **Summarize — Report.** Files written/updated, tier decisions, drift resolved, gaps raised.

## Validation

- [ ] `scan_doc_links.py` broken 0; no duplicated facts (cross-referenced instead)
- [ ] Two-tier model respected; no invented content; `## Where to Find It` footer where standard