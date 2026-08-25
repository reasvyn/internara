---
name: sync-docs
description: "SDLC Phase: MAINTENANCE. Comprehensive documentation sync against actual code implementation and feature specs — covering `docs/`, module docs, AND agent guides & skills (`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/plans/`). Discovers patterns and rules from authoritative docs, then verifies them against code and specs."
upstream:
  - feature-building
  - code-refactoring
  - doc-writing
  - livewire-development
  - tailwindcss-development
  - medialibrary-development
  - pulse-development
  - pest-testing
  - security-audit
---

# Sync Docs

> **Last updated:** 2026-08-25 **Changes:** scanner freshness + git-history window changed to minimum 14 days (was 7)

> **Prerequisite:** Load `context-awareness` for doc navigation map.

## When to Activate

Use this skill after any implementation, refactoring, or audit to keep documentation in sync with
the actual codebase and its feature specs. Documentation is the single source of truth (see
conventions) — code, docs, and specs must agree. **This includes agent guides & skills:**
`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, and `.agents/plans/` must stay
consistent with the specs and code they document. A spec amendment (renamed default, new invariant,
changed path) must be mirrored in any guide or skill that documents it.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (docs stay in sync with the **governing spec**), **Size Triage** (S/M/L session splitting —
a multi-module sync is M/L, stage by module), verification strategy, and commit format. This skill
adds the doc-sync workflow, audit scope, and verification rules found in the Skill Rules section
below — nothing else.

### Construct — Context & Scope

- Locate the governing spec and the agent guides/skills that reference it — `AGENTS.md`,
  `.agents/skills/*/SKILL.md`, `.agents/context/*.md`, `.agents/plans/` (a spec/code change must be
  mirrored there too)
- Review git commits from at least the last 14 days (`git log --since="14 days ago" --oneline`,
  `git log --since="14 days ago" --stat` — minimum window; extend to full log if drift may be older)
- Verify paths, class names, signatures against actual code (don't trust docs blindly)

### Execute — Documentation Sync

- Identify changes: `git diff` for new/deleted/modified files
- Update reference docs (file listings, class names, schemas), conceptual docs (business rules),
  and agent guides & skills (rule tables, skill scopes, context states) per the governing spec
- Verify all relative links are still valid
- Update metadata: `Last updated` date + `Changes` description

### Verify — Quality Gates

- **Markdown-only changes:** run `python3 scripts/scan_doc_links.py` (doc changes don't need
  pint/phpstan/tests)
- Cross-check against `rules/sync-verification.md` (the automated sync-verification rule asset)
- PHPStan/Pint only if PHP files were touched

---

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Sync workflow (git-history discovery + docs-to-update mapping) | `rules/sync-workflow.md` | Starting any doc sync or audit |
| Audit scope (verify every doc claim against code & specs, incl. agent guides & skills) | `rules/audit-scope.md` | Auditing docs against code and specs |
| Sync verification gate (metadata, links, key rules, checklist) | `rules/sync-verification.md` | Finalizing a sync before commit |

---

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_doc_links.py` | Validate all relative links in markdown files and flag ALL markdown files whose `Last updated` metadata is missing or older than 14 days, with suggestion to verify and synchronize against codebase | `python3 scripts/scan_doc_links.py` |

Output: `scripts/outputs/{timestamp}-doc-links.json` with broken link details (file, line, target).

---

## References

| Topic                        | Doc                                          |
| ---------------------------- | -------------------------------------------- |
| Documentation-first approach | `docs/conventions.md` (§0)                   |
| Documentation structure      | `docs/conventions.md` (§Documentation Rules) |
| Full doc catalog             | `docs/index.md`                              |
| Module index                 | `docs/modules/index.md`                      |
| Feature specs                | `docs/specs/index.md`                        |
| Agent guides & skills        | `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/` |