---
name: spec-writing
description:
    'SDLC Phase: PLANNING / DOCUMENTATION. Writing comprehensive feature specification documents —
    problem statements, goals/non-goals, user stories, functional/non-functional requirements,
    API/data contracts, design decisions, and success metrics. Produces self-contained specs that
    serve as the authoritative source for feature implementation.'
upstream:
    - context-awareness
    - doc-writing
downstream:
    - feature-building
    - code-writing
    - pest-testing
    - issue-writing
---

# Spec Writing

> **Last updated:** 2026-08-18 **Changes:** slimmed to index form — comprehensive rules (11-section
> template, requirement IDs, scoping, section content, data contracts, indexing & lifecycle) now
> live in `rules/` and are mapped by the `## Skill Rules` table

> **Prerequisite:** Load `context-awareness` for project orientation and `doc-writing` for
> documentation conventions.

## When to Activate

Use this skill when:

- Writing a new feature specification document (`docs/specs/{ID}-{feature}.md`)
- Defining requirements before implementation begins
- Documenting design decisions for complex features
- Creating acceptance criteria for features
- Defining API/data contracts before coding

**Do NOT use for:**

- Module conceptual docs (`docs/refs/modules/{module}.md`) — use `doc-writing`
- Module reference docs (`docs/refs/modules/{module}-reference.md`) — use `doc-writing`
- Architecture decision records — use `doc-writing`
- Bug reports or issue writing — use `issue-writing`

---

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting — a spec split
into multiple initiatives is multi-session work; propose a plan to the user), verification strategy,
and commit format. This skill adds the 11-section template, requirement ID conventions, scoping
rules, indexing, and spec lifecycle — nothing else.

### Execute — Write Specification

- Follow the 11-section spec template (`rules/spec-template.md`)
- Use `edit` tool for existing files, `write` tool only for new files
- Every statement must be verifiable or actionable
- Reference source code with file paths where implementation exists
- Cross-reference related docs instead of duplicating content

### Verify — Quality Gates

- Verify with git: `git status` + `git diff` — confirm only the intended spec file(s) changed and no
  content was lost (version-control verification)
- All 11 sections are present and populated
- Every functional requirement has a unique ID (`FR-{area}{number}`)
- Every non-functional requirement has a unique ID (`NFR-{category}{number}`)
- Every design decision has a unique ID (`DD-{number}`)
- §9 Roadmap has Prerequisites (with specific artifacts), Build Guide (1-2 sentences), and Next
  Steps
- All cross-references resolve to existing files
- Metadata block present with current date; `> **Spec ID:** XXXXX` line matching the filename
- No duplicate content across sections (cross-reference instead)

### Report — Deliver

- Deliver a report to the user: file created/updated, number of requirements and design decisions,
  any gaps or assumptions flagged

---

## Skill Rules

| Rule                                                                           | Asset                      | Applies when                                                  |
| ------------------------------------------------------------------------------ | -------------------------- | ------------------------------------------------------------- |
| 11-section spec template (structure, section intent, writing discipline)       | `rules/spec-template.md`   | Writing or reviewing any spec document                        |
| Requirement ID conventions (FR/NFR/UC/PS/DD prefixes + area codes)             | `rules/requirement-ids.md` | Assigning any requirement ID in a spec                        |
| Scoping rules (one initiative = one spec; when/how to split)                   | `rules/scoping-rules.md`   | Deciding the boundary of a spec or splitting an oversized one |
| Section content rules (PS, Goals, UCs, FRs, NFRs, DDs, metrics, roadmap)       | `rules/section-content.md` | Filling any spec section with verifiable content              |
| §6 Data contract writing (verifiable signatures/shapes/routes/config)          | `rules/data-contracts.md`  | Writing or verifying API/data contracts                       |
| Spec indexing & lifecycle (5-char IDs, phases, dependencies, add/split/update) | `rules/spec-indexing.md`   | Registering, adding, splitting, renaming, or updating a spec  |

## Quick References

| Topic                     | Location                                  |
| ------------------------- | ----------------------------------------- |
| Documentation conventions | `docs/conventions.md`                     |
| Doc-writing skill         | `.agents/skills/doc-writing/SKILL.md`     |
| Feature specs             | `docs/specs/index.md`                     |
| High-level specs          | `docs/specs/QLHDO-internara-project.md` |
| Product definition        | `docs/foundation/product-definition.md`   |
| Module index              | `docs/refs/modules/index.md`                   |
| Existing specs            | `docs/specs/`                             |
| Architecture overview     | `docs/architecture.md`                    |
