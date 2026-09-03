---
name: sync-docs
description: "SDLC Phase: MAINTENANCE. Comprehensive documentation sync against actual code implementation and feature specs — covering `docs/`, module docs, agent guides & skills (`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, `.agents/plans/`), AND devtools (`tools/`, `.github/workflows/`, `.github/scripts/`). Discovers patterns and rules from authoritative docs, then verifies them against code and specs."
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

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for doc navigation map.

## When to Activate

Use this skill after any implementation, refactoring, or audit to keep documentation in sync with
the actual codebase and its feature specs. Documentation is the single source of truth (see
conventions) — code, docs, and specs must agree. **This includes agent guides & skills:**
`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`, and `.agents/plans/` must stay
consistent with the specs and code they document. **This includes devtools:**
`tools/*.py` (scanners), `tools/README.md`, `.github/workflows/*.yaml`, `.github/scripts/*.sh` must
stay consistent with the code and docs they automate. A spec amendment (renamed default, new
invariant, changed path) must be mirrored in any guide, skill, or devtool that documents or
automates it.

## Workflow

Follow `AGENTS.md §Agent Workflow` for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (docs stay in sync with the **governing spec**), **Size Triage** (S/M/L session splitting —
a multi-module sync is M/L, stage by module), verification strategy, and commit format. This skill
adds the doc-sync workflow, audit scope, and verification rules found in the Skill Rules section
below — nothing else.

### Construct — Context & Scope

- Locate the governing spec, the agent guides/skills that reference it — `AGENTS.md`,
  `.agents/skills/*/SKILL.md`, `.agents/context/*.md`, `.agents/plans/` — and the devtools that
  automate it — `tools/*.py`, `tools/README.md`, `.github/workflows/*.yaml`, `.github/scripts/*.sh`
  (a spec/code change must be mirrored there too)
- Review git commits from at least the last 14 days (`git log --since="14 days ago" --oneline`,
  `git log --since="14 days ago" --stat` — minimum window; extend to full log if drift may be older)
- Verify paths, class names, signatures, scanner rules, and workflow triggers against actual code (don't trust docs blindly)

### Execute — Documentation Sync

- Identify changes: `git diff` for new/deleted/modified files (including `tools/`, `.github/workflows/`, `.github/scripts/`)
- Update reference docs (file listings, class names, schemas), conceptual docs (business rules),
  agent guides & skills (rule tables, skill scopes, context states), and devtools (scanner rules,
  workflow triggers, script flags, `tools/README.md` catalog) per the governing spec
- Verify all relative links are still valid
- History is tracked via `git log --follow -- <file>` and commit messages, not inline metadata

### Verify — Quality Gates

- **Markdown-only changes:** run `python3 tools/scan_doc_links.py` (doc changes don't need
  pint/tests)
- Cross-check against `.agents/rules/sync-verification.md` (the automated sync-verification rule asset)
- Pint only if PHP files were touched

---

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Sync workflow (git-history discovery + docs-to-update mapping) | `.agents/rules/sync-workflow.md` | Starting any doc sync or audit |
| Audit scope (verify every doc claim against code & specs, incl. agent guides & skills) | `.agents/rules/audit-scope.md` | Auditing docs against code and specs |
| Sync verification gate (metadata, links, key rules, checklist) | `.agents/rules/sync-verification.md` | Finalizing a sync before commit |

---

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_doc_links.py` | Validate all relative links in markdown files (freshness via `git log --follow -- <file>`, not inline metadata) | `python3 tools/scan_doc_links.py` |
| `scan_spec_tests.py` | Spec↔tests + browser coverage, priority scoring, module breakdown | `python3 tools/scan_spec_tests.py --module {Module}` |
| `scan_violations.py` | C1-C8/D1-D6 + SRP + Livewire/P (derived thresholds, no hardcoding) | `python3 tools/scan_violations.py --module {Module}` |
| `tools/README.md` | Catalog of all devtools, their inputs/outputs, and usage | `cat tools/README.md` |
| `.github/workflows/*.yaml` | CI/CD pipelines (release, hotfix, CI) — triggers, jobs, secrets, environments | `cat .github/workflows/*.yaml` |
| `.github/scripts/*.sh` | Deploy, lint, test, guard scripts — flags, health checks | `cat .github/scripts/*.sh` |

Output: `tools/outputs/{timestamp}-{description}.json` with findings. Devtools (`tools/`, `.github/`) are part of the sync — a new scanner rule or workflow trigger must be documented in `tools/README.md` and any guide that references it.

---

## References

| Topic                        | Doc                                          |
| ---------------------------- | -------------------------------------------- |
| Documentation-first approach | `docs/conventions.md` (§0)                   |
| Documentation structure      | `docs/conventions.md` (§Documentation Rules) |
| Full doc catalog             | `docs/index.md`                              |
| Module index                 | `docs/refs/modules/index.md`                      |
| Feature specs                | `docs/specs/index.md`                        |
| Agent guides & skills        | `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/` |
| Devtools                     | `tools/README.md`, `.github/workflows/*.yaml`, `.github/scripts/*.sh` |
| Scanner catalog              | `tools/README.md` (§ Catalog)                |
| Hotfix workflow              | `.github/workflows/hotfix.yaml`              |
