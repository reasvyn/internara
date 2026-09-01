---
name: doc-writing
description: "SDLC Phase: DOCUMENTATION. Writing and maintaining project documentation — PHPDoc blocks, markdown docs, module conceptual/reference docs, metadata format, cross-references, and the documentation-first (SSOT) principle."
upstream:
  - feature-building
  - code-refactoring
  - pest-testing
  - test-writing
downstream:
  - sync-docs
---

# Doc Writing

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for project orientation and documentation map.

## When to Activate

Use this skill when:
- Writing new documentation files (markdown, PHPDoc)
- Updating existing docs to reflect code changes
- Adding or editing PHPDoc blocks on classes and methods
- Syncing documentation with implementation
- Adding module conceptual or reference docs

## Workflow

Follow `AGENTS.md §Agent Workflow` for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (docs reflect the **governing spec**; a behavior change with no requirement is raised via
`spec-writing` first), **Size Triage** (S/M/L session splitting — a multi-module doc update is M/L,
stage by module), verification strategy, and commit format. This skill adds the documentation
conventions found in the Skill Rules section below — two-tier model, metadata, structure template,
PHPDoc, link integrity, and the doc-quality gate — nothing else.

### Execute — Write Documentation

- Follow the two-tier model (conceptual vs reference)
- Follow metadata format on every file
- Follow document structure template
- Apply PHPDoc conventions for PHP files
- Ensure all cross-references resolve to real files

### Verify — Quality Gates

- **Markdown-only changes:** run `python3 tools/scan_doc_links/cli.py` (doc changes don't need
  pint/phpstan/tests)
- All relative links resolve to existing files; anchor links match actual headings
- Metadata block present with current date; `## Description` section present
- No implementation details in conceptual docs; no design rationale in reference docs
- PHPStan/Pint only if PHP/PHPDoc files were touched
- Run the doc-quality gate in `.agents/rules/doc-quality.md` before committing

---

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Documentation-First (SSOT) & edit discipline | `.agents/rules/documentation-first.md` | Any doc change; docs-vs-code disagreement |
| Two-tier model (conceptual vs reference) | `.agents/rules/two-tier-model.md` | Creating or editing module/architecture docs |
| Metadata & document structure | `.agents/rules/metadata-structure.md` | Every markdown file |
| PHPDoc conventions | `.agents/rules/phpdoc.md` | Writing or editing PHPDoc on PHP classes |
| Link integrity & content duplication | `.agents/rules/link-integrity.md` | Any cross-reference in a doc |
| Doc quality gate | `.agents/rules/doc-quality.md` | Pre-commit review of any doc change |

---

## Quick References

| Topic | Location |
|-------|----------|
| Full conventions | `docs/conventions.md` |
| Architecture overview | `docs/architecture.md` |
| Module index | `docs/refs/modules/index.md` |
| Pattern deep-dives | `docs/guides/arch/{pattern}-pattern.md` |
| Sync-docs workflow | `.agents/skills/sync-docs/SKILL.md` |
| Sync verification rules | `.agents/rules/sync-verification.md` |
| Documentation map | `docs/index.md` |
