---
name: code-refactoring
description: "SDLC Phase: DESIGN / REFACTORING. Systematic refactoring patterns for all code layers — extracting Actions, Entities, thinning Livewire, fixing exception handling, and enforcing architectural patterns."
upstream:
  - audit-protocol
  - security-audit
  - feature-building
downstream:
  - code-writing
  - doc-writing
  - feature-building
  - pest-testing
  - sync-docs
---

# Code Refactoring

> **Prerequisite:** Load `context-awareness` for project orientation and architecture rules.

## When to Activate

Apply this skill when refactoring any code — extracting business logic from fat classes, eliminating
code smells, enforcing clean code principles (SOLID, DRY, SOC), or migrating toward the project's
architectural patterns. Covers ALL layers.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Code Refactoring

- Identify code smells: fat Livewire, fat Model, inline business rules, magic strings
- Choose the appropriate refactoring workflow (A-F)
- Extract business logic to Action or Entity
- Thin Livewire/Controller by moving logic out
- Fix exception handling, clean up dead code
- Output: refactored code with preserved behavior, accompanied by updated tests

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of refactoring performed
    - Files refactored (before/after structure)
    - Workflow(s) applied (A-F)
    - Test suite status (pass/fail)
- Feeds into: feature-building (integration), pest-testing (test verification), sync-docs (doc
  updates)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                                              |
| -------------- | -------------------------------------------------------------------------------------------------- |
| **Upstream**   | `audit-protocol` (issue findings), `security-audit` (security findings)                            |
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

## Verification Checklist

- [ ] Tests pass before and after refactoring
- [ ] Action: correct base class, single `execute()`, DTO for 3+ params
- [ ] Entity: `final readonly`, zero I/O, `fromModel()`, bridge on Model
- [ ] Model: no business methods, `#[Fillable]`, entity bridges only
- [ ] DTO: `final readonly`, scalars/enums/Carbon only
- [ ] No `dd/dump/ray` introduced
- [ ] `declare(strict_types=1)` in new files
- [ ] Pint clean; PHPStan passes

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `python3 scripts/scan_dead_code.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 scripts/scan_architecture.py` |

Use `--module {Name}` to scope. Output: `scripts/outputs/{timestamp}-{description}.json`.

## Quality Gate — arch-guard

After refactoring, validate against arch-guard:
- Run `python3 scripts/scan_violations.py` to verify no new violations
- Run `python3 scripts/scan_class_contracts.py` for contract compliance
- See `arch-guard` skill for full rule reference

## References

| Topic                        | Doc                                                          |
| ---------------------------- | ------------------------------------------------------------ |
| Action Triad                 | `docs/architecture/action-pattern.md`                        |
| Entity-Model separation      | `docs/architecture/entity-pattern.md`                        |
| Livewire component rules     | `docs/architecture/livewire-pattern.md`                      |
| Exception hierarchy          | `docs/architecture/exception-pattern.md`                     |
| Model conventions            | `docs/architecture/model-pattern.md`                         |
| Data / DTOs                  | `docs/architecture/data-pattern.md`                          |
| Service vs Action vs Support | `docs/architecture/service-pattern.md`, `support-pattern.md` |
| Coding conventions           | `docs/conventions.md`                                        |
