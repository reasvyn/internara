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

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when implementing any new feature, bug fix, security patch, or performance
optimization. This is the orchestrator that coordinates specialized sub-skills for each layer of the
implementation. For features classified **L** (AGENTS.md Size Triage), split the build into multiple
sessions — see Size Triage below.

## Size Triage (AGENTS.md)

| Size | Criteria | Execution | User check-in |
|------|----------|-----------|---------------|
| **S** | ≤3 files, single concern | Single pass | None |
| **M** | 4-10 files, 2-3 concerns | Single session, staged, batch verify | One checkpoint before commit |
| **L** | >10 files, multi-module, cross-cutting | **Split into sessions** | **MUST inform user first** |

**L-size:** after Construct, tell the user *"This feature is too broad for a single pass — I will
split it into N sessions"*, propose a session plan (each session = one layer or one concern with its
own verify + report), then execute session by session — never all at once.

## Agent Workflow

Using this skill follows 4 phases (mapped to AGENTS.md 9-step: Construct = Steps 1-5, Execute = 6,
Verify = 7, Report & Commit = 8-9):

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read the governing spec from `docs/specs/` — list the FR/NFR/UC IDs this feature must satisfy
  (Spec-First Doctrine: no behavior without a requirement; if the spec is missing, write it first)
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- **Classify the size (S/M/L)** and, if **L**, inform the user + propose a session plan
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Feature Building

- Follow the build order (see Implementation Flow §4 — single source of truth for ordering)
- Delegate sub-skills as needed (livewire, tailwindcss, medialibrary, pulse)
- Follow Action Triad: Command for mutations, Read for queries, Process for orchestration
- Ensure DTO for 3+ params, ActionResponse for structured returns
- For **M/L** tasks: stage the build by layer/concern; after each stage, run `git status` + `git diff`
  to confirm only intended files changed
- Output: implemented feature with tests, translations, routes, and updated docs

### 3. Verify — Quality Gates

- Run change-type-appropriate verification (see Verify Matrix below — not a fixed command set)
- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run targeted tests: `php artisan test --compact --filter={TestName}` (full suite ONCE at the end)
- Run arch-guard scripts: `scan_violations.py`, `scan_class_contracts.py`, `scan_security.py`,
  `scan_naming.py`, `scan_conventions.py`, `scan_doc_links.py`
- Verify with git: `git status` + `git diff` — confirm only intended files changed, nothing lost
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of work done
    - Files created or modified
    - Test suite status (pass/fail)
    - Deviation from original plan (if any)
    - Identified blockers or risks
    - If sessions were split: per-session summary + what remains
- Feeds into: pest-testing (test suite), sync-docs (doc updates)
- Commit using format: `type(scope): description`
- Push if requested

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
| Feature is **L** size | Split into sessions; inform user first |

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

- Use the Verify Matrix below (change-type-appropriate), then run the arch-guard scripts
- Run lint + static analysis + tests
- Check pre-commit checklist from `context-awareness`
- If refactoring was involved, load `code-refactoring` for verification

## Verify Matrix

| Change type | Verification |
|-------------|-------------|
| Config/docs/markdown | Visual inspection, no tests |
| Blade/CSS/JS | `npm run build` only |
| Translation keys | `vendor/bin/pint --dirty --test` + tinker echo |
| PHP single file | `vendor/bin/pint --dirty --test` + targeted test |
| PHP module refactor | `vendor/bin/pest --testsuite={ModuleName}` |
| New feature / business logic | Full suite ONCE after all changes batched |
| Always | `git status` + `git diff`, arch-guard scripts |

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
