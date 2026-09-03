---
name: code-refactoring
description: "SDLC Phase: DESIGN / REFACTORING. Systematic refactoring patterns for all code layers — extracting Actions, Entities, thinning Livewire, fixing exception handling, and enforcing architectural patterns."
upstream:
  - arch-guard
  - security-audit
  - spec-audit
  - feature-building
downstream:
  - code-writing
  - doc-writing
  - feature-building
  - pest-testing
  - sync-docs
---

# Code Refactoring

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for project orientation and architecture rules.

## When to Activate

Apply this skill when refactoring any code — extracting business logic from fat classes, eliminating
code smells, enforcing clean code principles (SOLID, DRY, SOC), or migrating toward the project's
architectural patterns. Covers ALL layers.

## Workflow

Follow `AGENTS.md §Agent Workflow` for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (the refactor must keep satisfying the **governing spec**'s FR/NFR/UC IDs — never change
spec-defined behavior), **Size Triage** (S/M/L session splitting), verification strategy, and commit
format. This skill adds the refactoring principles, workflows A-F, and verification checklist
below — nothing else.

### Execute — Refactoring

- Identify code smells: fat Livewire, fat Model, inline business rules, magic strings
- Choose the appropriate refactoring workflow (A-F)
- Extract business logic to Action or Entity
- Thin Livewire/Controller by moving logic out
- Fix exception handling, clean up dead code
- Behavior preservation: tests pass before and after; write characterization tests first if none
  exist (see Core Principles)

## Phase Context

| Role           | Skill                                                                                              |
| -------------- | -------------------------------------------------------------------------------------------------- |
| **Upstream**   | `arch-guard` (code quality), `security-audit` (security findings), `spec-audit` (spec drift)   |
| **This skill** | **DESIGN / REFACTORING** — restructures code                                                       |
| **Downstream** | `feature-building` (integrates refactored code), `pest-testing` (tests), `sync-docs` (doc updates) |

## Core Principles

### Behavior Preservation

Refactoring changes structure, not behavior. Tests must pass before and after. If tests don't exist
for the code being refactored, write characterization tests first.

### Scope Discipline

One concern per change — never mix refactoring with feature work or bug fixes. Each commit is a
single, verifiable transformation.

### Strangler Pattern

New code alongside old, verify equivalence, route traffic gradually, remove old when safe.

## Refactoring Workflows

### Workflow A — Extract Business Logic to Action

**When:** Livewire, Controller, or Middleware contains `Model::create/update/delete`,
`DB::transaction()`, or inline business logic.

1. Determine the Action type: Command (mutations) / Read (queries) / Process (orchestration)
2. Create the Action class in `{Module}/{SubModule}/Actions/` extending the correct base
3. Move the mutation logic into `execute()` — single public method
4. Wrap DB writes in `$this->transaction()`, add `$this->log()`
5. Accept DTO for 3+ params; return ActionResponse for structured feedback
6. Only dispatch an event if a listener exists (`config/event.php`)
7. Inject the Action into the caller via method parameter (not constructor)

### Workflow B — Extract Business Rules to Entity

**When:** Model accumulates business methods (`canX()`, `isY()`, `hasZ()`), or Actions contain
inline conditionals on record state.

1. Identify conditionals — each distinct group is a candidate for an Entity
2. Create Entity in `{Module}/{SubModule}/Entities/` — `final readonly`, `fromModel()`
3. Add bridge method on Model: `as{Role}(): {Role}Entity`
4. Replace inline conditionals with `$entity->ensureCan{Action}()` which throws `RejectedException`
5. Clean up business methods from Model (keep scopes, remove domain logic)

### Workflow C — Thin Livewire Component

**When:** Livewire component has inline DB calls, business rules, side effects, or exceeds 300
lines.

1. Extract `Model::create/update/delete` → Command Action (Workflow A)
2. Extract inline business rules → Entity methods (Workflow B)
3. Extract repeated UI patterns → Blade components
4. Extract complex forms (5+ fields) → Form Object (`Livewire\Form`)
5. Result: component contains only UI state, validation, authorization, and Action delegation

### Workflow D — Fix Exception Handling

1. Replace `throw new RuntimeException(...)` for business rules → `throw new RejectedException(...)`
2. Narrow catch blocks: catch `RejectedException` before `Throwable`
3. Match exception type to scenario (see exception pattern docs)

### Workflow E — Clean Code Smells

- **Dead code:** Remove unused private methods, unused imports, dead assignments
- **Magic strings to enums:** Consolidate repeated string literals
- **Magic numbers to constants:** Extract named constants
- **Flatten conditionals:** Early returns over nested `if` blocks

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Refactoring safety gates | `.agents/rules/refactoring-safety.md` | Before, during, and after any refactor |
| Verification checklist | `.agents/rules/verification-checklist.md` | Before declaring a refactor complete |

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `python3 tools/scan_dead_code.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 tools/scan_architecture.py` |

Use `--module {Name}` to scope. Output: `tools/outputs/{timestamp}-{description}.json`.

## References

| Topic                        | Doc                                                          |
| ---------------------------- | ------------------------------------------------------------ |
| Action Triad                 | `docs/guides/arch/action-pattern.md`                        |
| Entity-Model separation      | `docs/guides/arch/entity-pattern.md`                        |
| Livewire component rules     | `docs/guides/arch/livewire-pattern.md`                      |
| Exception hierarchy          | `docs/guides/arch/exception-pattern.md`                     |
| Model conventions            | `docs/guides/arch/model-pattern.md`                         |
| Data / DTOs                  | `docs/guides/arch/data-pattern.md`                          |
| Service vs Action vs Support | `docs/guides/arch/service-pattern.md`, `support-pattern.md` |
| Coding conventions           | `docs/conventions.md`                                        |
