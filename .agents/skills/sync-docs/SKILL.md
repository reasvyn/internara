---
name: sync-docs
description: "SDLC Phase: MAINTENANCE. Comprehensive documentation sync against actual code implementation and feature specs — covering `docs/`, module docs, AND agent guides & skills (`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/contexts/`, `.agents/plans/`). Discovers patterns and rules from authoritative docs, then verifies them against code and specs."
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

> **Prerequisite:** Load `context-awareness` for doc navigation map.

## When to Activate

Use this skill after any implementation, refactoring, or audit to keep documentation in sync with
the actual codebase and its feature specs. Documentation is the single source of truth (see
conventions) — code, docs, and specs must agree. **This includes agent guides & skills:**
`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/contexts/`, and `.agents/plans/` must stay
consistent with the specs and code they document. A spec amendment (renamed default, new invariant,
changed path) must be mirrored in any guide or skill that documents it.

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (docs stay in sync with the **governing spec**), **Size Triage** (S/M/L session splitting —
a multi-module sync is M/L, stage by module), verification strategy, and commit format. This skill
adds the doc-sync workflow, audit scope, key rules, and verification checklist below — nothing else.

### Construct — Context & Scope

- Locate the governing spec and the agent guides/skills that reference it — `AGENTS.md`,
  `.agents/skills/*/SKILL.md`, `.agents/contexts/*.md`, `.agents/plans/` (a spec/code change must be
  mirrored there too)
- Review git commits from the last 7 days (`git log --since="7 days ago" --oneline`,
  `git log --since="7 days ago" --stat`) to focus on actual changes
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

## Sync Workflow

### 0. Review Recent Git History

Before making any doc changes, review what actually changed in the last 7 days:

```bash
git log --since="7 days ago" --stat              # summary per commit
git log --since="7 days ago" --name-status       # consolidated file changes
git log --since="7 days ago" --format="%h %s"    # commit messages for context
```

- Note which modules, layers, and files were affected
- Identify commits that already updated docs (skip those)
- Identify commits that introduced new code without doc updates (focus here)

### 1. Identify What Changed

- Check `git diff` for new files, deleted files, and modified files
- Identify which modules, submodules, and layers were affected
- Note new Models, Actions, Entities, Enums, DTOs, Events, Policies, Livewire components

### 2. Determine Which Docs Need Updates

| If you changed...    | Update these docs                                                    |
| -------------------- | -------------------------------------------------------------------- |
| Module structure     | `docs/modules/{module}-reference.md` (file listing, actions, models) |
| Business rules       | `docs/modules/{module}.md` (business context)                        |
| Feature requirements | `docs/specs/{ID}-{feature}.md` (FR, NFR, user stories, data contracts)       |
| Architecture pattern | `docs/architecture.md` or `docs/architecture/{pattern}-pattern.md`   |
| Conventions          | `docs/conventions.md`                                                |
| Module dependencies  | `docs/modules/index.md`                                              |
| Database schema      | `docs/infrastructure/database.md`, `docs/specs/J68GZ-system-requirements.md` (§4.4, §7.3) |
| ADR                  | `docs/adr/` (if decision is notable)                                 |
| Feature specs        | `docs/specs/index.md`                                                |
| Config               | `docs/infrastructure/configuration.md`                               |
| Agent guides         | `AGENTS.md` (module map, invariants, rule pointers)                  |
| Agent skills         | `.agents/skills/{skill}/SKILL.md` (skill scope, rules, references)   |
| Agent contexts       | `.agents/contexts/*.md` (intentional states, deploy caveats, pins)   |
| Agent plans          | `.agents/plans/` (session plans, decisions)                          |

### 3. Documentation Audit Scope

When auditing documentation against code and specs, verify these items:

- File paths in docs point to existing files
- Class names and method signatures match actual code
- Action listings include all `execute()` methods
- Enum values include all cases
- No broken relative links
- Metadata (`Last updated`, `Changes`) present on every `.md` file
- Module structure docs match actual `app/` directory layout
- **Agent guides & skills match specs and code** — spec IDs referenced in `AGENTS.md` and
  `.agents/skills/*/SKILL.md` exist in `docs/specs/index.md`; invariant values (names, config
  defaults, convention IDs C1-C8 / D1-D6) match the governing spec; "where to find" tables point to
  sections that actually exist; skill scope covers what the governing spec promises

### 4. Verify Documentation Accuracy

For each doc, check:

- File paths exist and are correct
- Class names match actual code
- Method signatures match implementation
- Action listings include all execute() methods
- Enum values include all cases
- Model relationships match actual Eloquent definitions
- Migration schemas match database tables
- Dependency graphs reflect actual imports

### 5. Update Metadata

Every `.md` file has metadata:

```markdown
> **Last updated:** YYYY-MM-DD **Changes:** brief description of what changed
```

Update both fields when content changes.

### 6. Verify No Broken Links

- Relative paths in `[text](path)` must resolve
- Check for renamed or deleted files referenced in docs
- Anchor links must match existing section headings

## Key Rules

1. Documentation is the SSOT — if code disagrees with docs, fix code (or fix docs if behavior
   changed intentionally)
2. Do NOT duplicate content — reference existing docs with relative paths
3. Every module must have exactly one conceptual doc and one reference doc
4. Conceptual docs contain NO implementation details (no file paths, no schemas)
5. Reference docs contain NO design rationale
6. **Always use `edit` tool (not `write`) when updating docs** — rewrite only the changed
   sections to minimize risk of accidentally deleting content or breaking formatting

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_doc_links.py` | Validate all relative links in markdown files and flag docs whose `Last updated` metadata is missing or older than 7 days | `python3 scripts/scan_doc_links.py` |

Output: `scripts/outputs/{timestamp}-doc-links.json` with broken link details (file, line, target).

## Verification Checklist

- [ ] New modules/submodules have `.md` + `-reference.md` files
- [ ] Module index (`index.md`) updated with new dependencies
- [ ] Feature specs in `docs/specs/` match implementation (FR, NFR, data contracts)
- [ ] Spec index (`docs/specs/index.md`) lists all specs
- [ ] Agent guides & skills (`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/contexts/`, `.agents/plans/`) match specs and code
- [ ] File paths in docs verified against actual codebase
- [ ] Class names and method signatures verified
- [ ] Migration schemas match actual database
- [ ] No broken relative links
- [ ] Metadata updated on all changed docs
- [ ] No stale content (removed features, renamed classes, changed signatures)

## References

| Topic                        | Doc                                          |
| ---------------------------- | -------------------------------------------- |
| Documentation-first approach | `docs/conventions.md` (§0)                   |
| Documentation structure      | `docs/conventions.md` (§Documentation Rules) |
| Full doc catalog             | `docs/index.md`                              |
| Module index                 | `docs/modules/index.md`                      |
| Feature specs                | `docs/specs/index.md`                        |
| Agent guides & skills        | `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/contexts/` |
