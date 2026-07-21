---
name: sync-docs
description: SDLC Phase: MAINTENANCE. Comprehensive markdown documentation sync against actual code implementation. Discovers patterns and rules from authoritative docs, then verifies them against code.
upstream:
  - feature-building
  - code-refactoring
  - doc-writing
  - livewire-development
  - tailwindcss-development
  - medialibrary-development
  - pulse-development
  - pest-testing
  - audit-protocol
  - security-audit
  - roadmap-planning
---

# Sync Docs

> **Prerequisite:** Load `context-awareness` for doc navigation map.

## When to Activate

Use this skill after any implementation, refactoring, or audit to keep documentation in sync with
the actual codebase. Documentation is the single source of truth (see conventions) — code and docs
must agree.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Review last 10 git commits (`git log --oneline -10`) to understand recent changes before syncing
  - Run `git log -10 --stat` to see which files were touched per commit
  - Run `git diff HEAD~10..HEAD --name-status` for a consolidated view of added/modified/deleted files
  - This context prevents re-syncing already-correct docs and focuses effort on actual changes
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Documentation Sync

- Identify changes: git diff for new/deleted/modified files
- Update reference docs: add/remove file listings, class names, schemas
- Update conceptual docs: adjust business rules, boundaries
- Verify all relative links are still valid
- Update metadata: Last updated date + Changes description
- Output: updated documentation with verified file paths, class names, schemas, and metadata

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of documentation changes
    - Files updated (conceptual and reference docs)
    - Broken links found and fixed
    - Metadata updated
- Final step in SDLC cycle — no downstream skill expected
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                       |
| -------------- | ------------------------------------------- |
| **Upstream**   | All implementation/analysis/planning skills |
| **This skill** | **MAINTENANCE** — verifies and updates docs |
| **Downstream** | None (final quality gate)                   |

## Sync Workflow

### 0. Review Recent Git History

Before making any doc changes, review what actually changed in the last 10 commits:

```bash
git log -10 --stat                          # summary per commit
git diff HEAD~10..HEAD --name-status        # consolidated file changes
git log -10 --format="%h %s"               # commit messages for context
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
| Architecture pattern | `docs/architecture.md` or `docs/architecture/{pattern}-pattern.md`   |
| Conventions          | `docs/conventions.md`                                                |
| Module dependencies  | `docs/modules/index.md`                                              |
| Database schema      | `docs/infrastructure/database.md`, `docs/foundation/erd.md`          |
| ADR                  | `docs/adr/` (if decision is notable)                                 |
| Features             | `docs/key-features.md`                                               |
| Config               | `docs/infrastructure/configuration.md`                               |

### 3. Verify Documentation Accuracy

For each doc, check:

- File paths exist and are correct
- Class names match actual code
- Method signatures match implementation
- Action listings include all execute() methods
- Enum values include all cases
- Model relationships match actual Eloquent definitions
- Migration schemas match database tables
- Dependency graphs reflect actual imports

### 4. Update Metadata

Every `.md` file has metadata:

```markdown
> **Last updated:** YYYY-MM-DD **Changes:** brief description of what changed
```

Update both fields when content changes.

### 5. Verify No Broken Links

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
| `scan_doc_links.py` | Validate all relative links in markdown files | `python3 scripts/scan_doc_links.py` |

Output: `scripts/outputs/{timestamp}-doc-links.json` with broken link details (file, line, target).

## Verification Checklist

- [ ] New modules/submodules have `.md` + `-reference.md` files
- [ ] Module index (`index.md`) updated with new dependencies
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
