# Sync Verification — Documentation Accuracy Checklist

Checklist to ensure documentation is in sync with actual code.

## File Existence

- [ ] Every file path in docs points to an existing file
- [ ] New files (Actions, Models, Entities, Enums) listed in reference docs
- [ ] Deleted files no longer listed in docs
- [ ] Renamed class names updated in docs

## Link Verification

- [ ] All relative links: `[text](path)` — path resolves to existing file
- [ ] Anchor links: `#section` — section heading exists
- [ ] No broken links to renamed or deleted files

## Schema & Implementation

- [ ] Migration schemas match actual database (check `database/migrations/`)
- [ ] Model relationships match actual Eloquent definitions
- [ ] Action listings include all `execute()` methods
- [ ] Enum values include all cases

## Conceptual Docs

- [ ] Business rules match actual implementation
- [ ] Module boundaries align with code
- [ ] No removed features still documented
- [ ] No unimplemented feature promises

## Reference Docs

- [ ] No implementation details (file paths, schemas) in conceptual docs
- [ ] No design rationale in reference docs
- [ ] Every module has exactly one `.md` + one `-reference.md`

## Metadata

- [ ] `> **Last updated:**` updated
- [ ] `> **Changes:**` populated with change description
