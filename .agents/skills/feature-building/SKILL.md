---
name: feature-building
description: "SDLC Phase: IMPLEMENTATION (Orchestrator). Execution phase that takes task specifications from docs/specs/ and implements them — coordinating specialized sub-skills for each concern."
upstream:
  - spec-writing
  - code-refactoring
downstream:
  - code-writing
  - doc-writing
  - pest-testing
  - sync-docs
  - livewire-development
  - tailwindcss-development
  - medialibrary-development
  - pulse-development
---

# Feature Building

> **Last updated:** 2026-08-17 **Changes:** extracted inline rules into `rules/` rule assets (spec-driven implementation, build order, sub-skill delegation, artifact contracts, localization & docs, deliverable quality) with a `## Skill Rules` mapping section

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when implementing any new feature, bug fix, security patch, or performance
optimization. This is the orchestrator that coordinates specialized sub-skills for each layer of the
implementation.

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (read the **governing spec** from `docs/specs/`, map FR/NFR/UC IDs), **Size Triage**
(S/M/L session splitting — L-size MUST inform the user and split into sessions), verification
strategy, and commit format. This skill adds the feature build order, sub-skill delegation, and key
rules below — nothing else.

### Construct — Context & Scope

- Read the governing spec from `docs/specs/` — list the FR/NFR/UC IDs this feature must satisfy
  (Spec-First Doctrine: no behavior without a requirement; if the spec is missing, write it first)
- Read relevant module docs and pattern docs
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Feature Building

- Follow the build order (see Implementation Flow §4 — single source of truth for ordering)
- Delegate sub-skills as needed (livewire, tailwindcss, medialibrary, pulse)
- For **M/L** tasks: stage the build by layer/concern; verify each stage before the next

### 3. Report & Commit

- Deliver a comprehensive report to the user (summary, files changed, test status, deviations,
  blockers, per-session summary if split)
- Feeds into: pest-testing (test suite), sync-docs (doc updates)

## Phase Context

| Role           | Skill                                                                |
| -------------- | -------------------------------------------------------------------- |
| **Upstream**   | `spec-writing` (feature specs), `code-refactoring` (refactored code) |
| **This skill** | **IMPLEMENTATION (Orchestrator)** — executes the build               |
| **Downstream** | `pest-testing` (tests), `sync-docs` (doc updates), sub-skills        |

## Skill Handoffs (Actionable)

| Condition | Action |
|-----------|--------|
| Spec missing or incomplete | Load `spec-writing`, write/amend the spec, get user approval, then continue |
| Livewire component work | Load `livewire-development` before writing components |
| File uploads / media | Load `medialibrary-development` before upload code |
| UI / styling / layout | Load `tailwindcss-development` before Blade/CSS |
| Pulse dashboard | Load `pulse-development` before dashboard code |
| Refactoring involved | Load `code-refactoring` for verification |

## Implementation Flow

### 1. Understand the Task

- Read the task specification from `docs/specs/` (check §9 Roadmap for prerequisites and build sequence)
- If a feature spec exists in `docs/specs/`, read it — it contains requirements (FR/NFR IDs),
  data contracts, design decisions, and success metrics that MUST guide implementation
- If the spec is missing, stop and write it first (`spec-writing`) — no behavior without a requirement
- Read the relevant module docs: `docs/modules/{module}.md` (business rules) and
  `docs/modules/{module}-reference.md` (file structure)
- Read the relevant pattern doc: `docs/architecture/{pattern}-pattern.md`
- Identify which modules, submodules, and layers are affected

### 2. Design the Solution

- Follow 4-layer dependency rules — UI → Business → Data → Framework
- Follow Action Triad — Command (mutations), Read (queries), Process (orchestration)
- Use DTOs for input boundaries (3+ params), ActionResponse for output
- Delegate business rules to Entities
- Plan the file structure using the single build order below (same order as Implementation §4)

### 3. Load Relevant Sub-skills

| If the task involves... | Load this skill            |
| ----------------------- | -------------------------- |
| Livewire components     | `livewire-development`     |
| File uploads / media    | `medialibrary-development` |
| UI / styling / layout   | `tailwindcss-development`  |
| Pulse dashboards        | `pulse-development`        |

### 4. Implement (Build Order)

Single source of truth for ordering — used by Agent Workflow §2 and Design §2:

1. **Docs** — documentation-first: draft/update module docs alongside the build
2. **Migration** — database table
3. **Model** — extends `BaseModel`, `#[Fillable]`, relationships, entity bridge
4. **Enum** — `implements LabelEnum` (+ `StatusEnum` for state machines)
5. **Entity** — `final readonly`, `fromModel()`, business rules
6. **DTO** — `final readonly`, `BaseData`, `fromArray()`
7. **Action** — correct triad base, single `execute()`, DTO input, ActionResponse
8. **Event + Listener** — only if async side effect is needed
9. **Policy** — `BasePolicy`, CRUD methods
10. **Livewire component** — thin, delegates to Actions
11. **Blade view** — follows existing view patterns
12. **Route** — in correct `routes/web/{module}.php` (or `{submodule}.php` for split submodules)
13. **Tests** — a test for each spec requirement (FR/NFR/UC ID) this feature introduces; tests are
    traced to their requirement IDs, never padded
14. **Translations** — `__()` keys in both `lang/en/` and `lang/id/`

For **M/L** features, treat each build-order slice as a stage: verify (`git diff` + targeted check)
before moving to the next slice. If the feature spans multiple modules or >10 files, split the
slices across sessions per Size Triage.

### 5. Verify

- Use the change-type verification matrix in `agent-workflow` (translation keys, config/docs,
  Blade/CSS/JS, PHP refactor, new feature), then run the arch-guard scanners on touched code
- Check pre-commit checklist from `agent-workflow`
- If refactoring was involved, load `code-refactoring` for verification

## Key Rules

1. New spec requirement? Must have a spec-traced test (FR/NFR/UC ID) before code review — no orphan
   tests, no padding. Write **only** the tests the spec requires (spec-driven minimalism: faster
   verification, lower resource use, less cognitive load) — see `pest-testing` core doctrine
2. New Entity? Must be `final readonly` with `fromModel()`
3. New Model? Must use `#[Fillable]` and extend `BaseModel`
4. New mutation? Must use Command Action, never direct `Model::create()` in Livewire
5. New query? Use Read Action if complex (aggregations, cross-module); Model scopes if simple
6. New user-facing string? Must exist in BOTH `lang/en/` and `lang/id/`
7. New feature? Must update relevant docs (documentation-first approach)

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Spec-driven implementation (spec-first, requirement IDs, missing-spec handling, approach comparison, path verification) | `rules/spec-driven-implementation.md` | Any feature build starts; spec is read or written |
| Build order (14-step sequence, design alignment, staged verification for M/L) | `rules/build-order.md` | Planning the file structure and implementing slices |
| Sub-skill delegation (orchestrator handoffs, per-concern skill loading) | `rules/sub-skill-delegation.md` | Any specialized concern (Livewire, media, UI, Pulse, refactor) is written |
| Artifact contracts (Entity/Model/Action/Query non-negotiable shapes) | `rules/artifact-contracts.md` | Creating or modifying an Entity, Model, or Action |
| Localization & docs (both languages, documentation-first) | `rules/localization-and-docs.md` | Any user-facing string or any new feature |
| Deliverable quality (completion criteria and quality gates) | `rules/deliverable-quality.md` | Before review/merge of any deliverable |

## References

| Topic                | Doc                                        |
| -------------------- | ------------------------------------------ |
| Feature specs        | `docs/specs/index.md`                      |
| Spec template        | `.agents/skills/spec-writing/SKILL.md`     |
| Module structure     | `docs/modules/index.md`                    |
| Action patterns      | `docs/architecture/action-pattern.md`      |
| Entity patterns      | `docs/architecture/entity-pattern.md`      |
| Model conventions    | `docs/architecture/model-pattern.md`       |
| Data / DTOs          | `docs/architecture/data-pattern.md`        |
| Livewire conventions | `docs/architecture/livewire-pattern.md`    |
| Policy conventions   | `docs/architecture/policy-pattern.md`      |
| Event conventions    | `docs/architecture/event-pattern.md`       |
| Exception hierarchy  | `docs/architecture/exception-pattern.md`   |
| Enum conventions     | `docs/architecture/enum-pattern.md`        |
| Testing patterns     | `docs/architecture/testing-pattern.md`     |
