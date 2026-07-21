---
name: feature-building
description: SDLC Phase: IMPLEMENTATION (Orchestrator). Execution phase that takes task specifications from docs/roadmap.md or docs/specs/ and implements them — coordinating specialized sub-skills for each concern.
upstream:
  - roadmap-planning
  - spec-writing
  - code-refactoring
  - writing-issues
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

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when implementing any new feature, bug fix, security patch, or performance
optimization. This is the orchestrator that coordinates specialized sub-skills for each layer of the
implementation.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Feature Building

- Read task spec from docs/roadmap.md
- Follow build sequence: Docs → Migration/Model → Enum → Entity → Action → Policy → Livewire ->
  Blade → Routes → Translations → Tests
- Delegate sub-skills as needed (livewire, tailwindcss, medialibrary, pulse)
- Follow Action Triad: Command for mutations, Read for queries, Process for orchestration
- Ensure DTO for 3+ params, ActionResponse for structured returns
- Output: implemented feature with tests, translations, routes, and updated docs

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of work done
    - Files created or modified
    - Test suite status (pass/fail)
    - Deviation from original plan (if any)
    - Identified blockers or risks
- Feeds into: pest-testing (test suite), sync-docs (doc updates)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                |
| -------------- | -------------------------------------------------------------------- |
| **Upstream**   | `roadmap-planning` (task spec), `spec-writing` (feature specs), `code-refactoring` (refactored code) |
| **This skill** | **IMPLEMENTATION (Orchestrator)** — executes the build               |
| **Downstream** | `pest-testing` (tests), `sync-docs` (doc updates), sub-skills        |

## Implementation Flow

### 1. Understand the Task

- Read the task specification from `docs/roadmap.md` or the issue description
- If a feature spec exists in `docs/specs/`, read it — it contains requirements (FR/NFR IDs),
  data contracts, design decisions, and success metrics that MUST guide implementation
- Read the relevant module docs: `docs/modules/{module}.md` (business rules) and
  `docs/modules/{module}-reference.md` (file structure)
- Read the relevant pattern doc: `docs/architecture/{pattern}-pattern.md`
- Identify which modules, submodules, and layers are affected

### 2. Design the Solution

- Follow 4-layer dependency rules — UI → Business → Data → Framework
- Follow Action Triad — Command (mutations), Read (queries), Process (orchestration)
- Use DTOs for input boundaries (3+ params), ActionResponse for output
- Delegate business rules to Entities
- Plan the file structure: Model → Entity → Action → DTO → Event → Listener → Policy → Livewire →
  Route → View

### 3. Load Relevant Sub-skills

| If the task involves... | Load this skill            |
| ----------------------- | -------------------------- |
| Livewire components     | `livewire-development`     |
| File uploads / media    | `medialibrary-development` |
| UI / styling / layout   | `tailwindcss-development`  |
| Pulse dashboards        | `pulse-development`        |

### 4. Implement (Build Order)

Recommended build order for new features:

1. **Migration** — database table
2. **Model** — extends `BaseModel`, `#[Fillable]`, relationships, entity bridge
3. **Enum** — `implements LabelEnum` (+ `StatusEnum` for state machines)
4. **Entity** — `final readonly`, `fromModel()`, business rules
5. **DTO** — `final readonly`, `BaseData`, `fromArray()`
6. **Action** — correct triad base, single `execute()`, DTO input, ActionResponse
7. **Event + Listener** — only if async side effect is needed
8. **Policy** — `BasePolicy`, CRUD methods
9. **Livewire component** — thin, delegates to Actions
10. **Blade view** — follows existing view patterns
11. **Route** — in correct `routes/web/{module}.php`
12. **Tests** — every Action gets a test file
13. **Translations** — `__()` keys in both `lang/en/` and `lang/id/`

### 5. Verify

- Run lint + static analysis + tests
- Check pre-commit checklist from `context-awareness`
- If refactoring was involved, load `code-refactoring` for verification

## Key Rules

1. New Action? Must have a test file before code review
2. New Entity? Must be `final readonly` with `fromModel()`
3. New Model? Must use `#[Fillable]` and extend `BaseModel`
4. New mutation? Must use Command Action, never direct `Model::create()` in Livewire
5. New query? Use Read Action if complex (aggregations, cross-module); Model scopes if simple
6. New user-facing string? Must exist in BOTH `lang/en/` and `lang/id/`
7. New feature? Must update relevant docs (documentation-first approach)

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
