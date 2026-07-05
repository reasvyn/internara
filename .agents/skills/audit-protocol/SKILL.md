---
name: audit-protocol
description: SDLC Phase: ANALYSIS. Systematic multi-layer codebase audit enforcing conventions, architecture patterns, security, and industry best practices. Produces structured findings in GitHub Issues with actionable fix recommendations.
downstream:
  - roadmap-planning
  - code-refactoring
  - security-audit
---

# Audit Protocol

> **Prerequisite:** Load `context-awareness` for project orientation, conventions, and architecture
> context.

## When to Activate

Use this skill when performing a systematic audit of the codebase. Audits focus on pattern
violations, code smells, security holes, and convention drift — NOT feature enhancements. Activates
during ANALYSIS phase or as a periodic quality gate.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Audit Protocol

- Audit Layer 4 (UI): Livewire, Blade, Controllers, Routes, Policies
- Audit Layer 3 (Business): Actions, Events, Listeners
- Audit Layer 2 (Data): Models, Entities, DTOs, Enums, Migrations
- Audit Layer 1 (Infra): Services, Support, Config, Core
- Record each finding as a GitHub Issue with severity, location, and fix recommendation
- Output: GitHub Issues documenting audit findings with severity, location, and fix recommendations

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of findings by layer and severity
    - Files audited
    - Number of issues created (critical/high/medium/low)
- Feeds into: roadmap-planning (prioritize fixes), code-refactoring (fix issues), security-audit
  (deep security pass)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                                                         |
| -------------- | ------------------------------------------------------------------------------------------------------------- |
| **Upstream**   | `context-awareness` (conventions, architecture rules)                                                         |
| **This skill** | **ANALYSIS** — finds issues, records them                                                                     |
| **Downstream** | `roadmap-planning` (prioritize fixes), `code-refactoring` (fix issues), `security-audit` (deep security pass) |

## Audit Layers

Audit each layer of the codebase separately. For each layer, check the relevant docs and record
violations as GitHub Issues.

### Layer 4 — Presentation/UI

Check Livewire components, Blade templates, Controllers, Policies, Routes:

- No `Model::create/update/delete/save` in Livewire components
- No `DB::transaction()` or `DB::beginTransaction()` in Livewire
- No `app()->make()`, `resolve()`, or `new Action()` — injections must come via method parameters
- `RejectedException` must be caught from Action calls (before generic `Throwable`)
- No unescaped `{!! !!}` for user content without inline justification
- Policy methods return boolean — no inline authorization in Livewire
- Routes registered in correct `routes/web/{module}.php` file
- No maryUI Toast methods (`$this->success()`, `$this->error()`) — use flasher

### Layer 3 — Business/Domain Ops

Check Actions, Events, Listeners, Notifications:

- Action extends correct base class (Command/Read/Process)
- Exactly one public `execute()` method
- Command/Process uses `$this->transaction()` for DB writes
- `$this->log()` called after mutation
- `$this->dispatchEvent()` only if listener exists (check `config/event.php`)
- Business rules delegate to Entity — throw `RejectedException`, not `RuntimeException`
- DTO used for 3+ params; raw `array` not accepted in execute()
- ActionResponse returned for structured feedback

### Layer 2 — Data/Persistent

Check Models, Entities, DTOs, Enums, Migrations:

- Entities are `final readonly` extending `BaseEntity` — zero I/O
- Entities do not import Actions, Services, Livewire, Controllers
- DTOs are `final readonly` extending `BaseData` — scalars, enums, Carbon only
- Models use `#[Fillable]` attribute (not `$fillable`/`$guarded`)
- Foreign keys use `foreignUuid()->constrained('{table}')` with explicit `onDelete()`/`onUpdate()`
- Enums implement `LabelEnum` (all) and `StatusEnum` (state machines)
- Cache keys registered in `config/cache-keys.php` — no inline strings
- No raw SQL without parameterized binding

### Layer 1 — Framework/Infrastructure

Check Services, Support, Core classes, Config:

- Services contain infrastructure logic only (not domain business rules)
- Support classes are static-only with zero side effects
- Config files follow documented schema

## Issue Format

Each finding recorded as a GitHub Issue should include:

- **Title:** `{layer}: {short description}` (e.g.,
  `livewire: Model::create() in RegistrationCenter`)
- **Location:** File path and line number
- **Violation:** Which rule/pattern is violated (reference doc and section)
- **Severity:** Critical / Major / Minor
- **Fix:** Brief recommendation of the correct approach

## Key Rules

1. Audit every module, not just the one being changed
2. Record issues even if fixing them is not in scope — prioritization happens downstream
3. Do NOT fix issues during audit — that is the refactoring phase
4. Verify findings against actual code — docs and skills may be stale

## Verification Checklist

- [ ] Layer 4 (UI) audited for direct DB mutations
- [ ] Layer 3 (Business) audited for Action Triad compliance
- [ ] Layer 2 (Data) audited for Entity purity and DTO boundaries
- [ ] Layer 1 (Infra) audited for Service/Support boundaries
- [ ] All findings recorded as GitHub Issues with severity and fix recommendation
- [ ] No fixes applied during audit (scope discipline)
- [ ] Existing issues checked for duplicates before filing

## References

| Topic                      | Doc                                      |
| -------------------------- | ---------------------------------------- |
| Architecture & layer rules | `docs/architecture.md`                   |
| Coding conventions         | `docs/conventions.md`                    |
| Action Triad patterns      | `docs/architecture/action-pattern.md`    |
| Entity-Model separation    | `docs/architecture/entity-pattern.md`    |
| Model conventions          | `docs/architecture/model-pattern.md`     |
| Livewire component rules   | `docs/architecture/livewire-pattern.md`  |
| Exception hierarchy        | `docs/architecture/exception-pattern.md` |
| Caching conventions        | `docs/architecture/cache-pattern.md`     |
| Critical invariants        | `AGENTS.md` (§Critical Invariants)       |
