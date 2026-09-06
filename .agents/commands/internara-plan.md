---
name: internara-plan
description: Plan Internara work — spec-first & issue scoping (spec-writing, issue-writing). Owns docs/specs/*.md FR/NFR/UC and GitHub issues. Use when plan, spec, requirement, SRS, issue, scope, or feature planning is mentioned.
---

# Plan Command — Spec to Requirement IDs (Internara)

Delegates to the `internara-planner` agent (planning specialist) and the `spec-writing`/`issue-writing`
skills. Spec-first, never fix-first: any instruction that changes behavior must have a requirement ID.

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask for the feature/bug/change, its module, and whether a spec exists.

## Steps

1. **Locate the governing spec** via `docs/specs/index.md` (foundation/module/feature). If none exists,
   draft `docs/specs/{ID}-{feature}.md` from the `spec-writing` template.
2. **Define & scope.** List affected modules/layers/files, blockers (migrations, config, service
   registration), and reorder batched instructions by impact-to-effort.
3. **Write the spec or issue.** FR/NFR/UC IDs, data contracts, design decisions (DD-1…), build order,
   success metrics — or a `gh issue create` body via `issue-writing`.
4. **Hand off.** Leave code/tests untouched; hand to `internara-builder`/`internara-tester` with clear
   FR IDs.

## Validation

- [ ] Every behavior traces to FR/NFR/UC; no invented behavior without an ID
- [ ] Spec is SSOT — cross-links to AGENTS.md, `.agents/context/`, module docs resolve