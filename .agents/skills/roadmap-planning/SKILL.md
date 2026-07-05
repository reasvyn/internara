---
name: roadmap-planning
description: SDLC Phase: PLANNING. Structured roadmap planning for bug fixes, security/performance improvements, and feature development. Produces actionable implementation phases with clear priorities, dependencies, and testing strategy.
upstream:
  - audit-protocol
  - security-audit
downstream:
  - feature-building
---

# Roadmap Planning

> **Prerequisite:** Load `context-awareness` for project orientation and module context.

## When to Activate

Use this skill when planning the work roadmap — prioritizing bug fixes, security patches, features,
or performance improvements. Produces structured phases in `docs/roadmap.md`.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Planning & Prioritization

- Collect inputs: audit findings, security issues, bug reports, feature requests
- Categorize by severity and urgency
- Evaluate dependencies between modules (use index.md)
- Define phases with clear scope and acceptance criteria
- Update docs/roadmap.md with prioritized phases
- Output: updated `docs/roadmap.md` with prioritized phases, clear scope, dependencies, and
  acceptance criteria

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of planned phases
    - Priority ordering with rationale
    - Dependencies and blockers identified
- Feeds into: feature-building (implementation), code-refactoring (refactoring tasks)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                               |
| -------------- | ------------------------------------------------------------------- |
| **Upstream**   | `audit-protocol` (issues found), `security-audit` (vulnerabilities) |
| **This skill** | **PLANNING** — prioritizes and sequences work                       |
| **Downstream** | `feature-building` (implements planned work)                        |

## Planning Process

### 1. Collect Inputs

Gather findings from:

- `audit-protocol` — code quality and pattern violations
- `security-audit` — security vulnerabilities
- GitHub Issues — bug reports and feature requests
- `docs/roadmap.md` — existing planned work

### 2. Categorize

| Category        | Priority   | Examples                                              |
| --------------- | ---------- | ----------------------------------------------------- |
| **Security**    | Highest    | OWASP violations, PII exposure, auth bypass           |
| **Bug fixes**   | High       | Functional regressions, data loss, incorrect behavior |
| **Performance** | Medium     | N+1 queries, slow pages, memory leaks                 |
| **Features**    | Variable   | New capabilities per product scope                    |
| **Refactoring** | Low-Medium | Code smells, convention drift, tech debt              |
| **Docs**        | Low        | Outdated docs, missing references                     |

### 3. Evaluate Dependencies

- Identify module dependencies (use `docs/modules/index.md`)
- Sequence: Core → foundation modules → dependent modules
- Group related changes into phases
- Identify blockers and prerequisites

### 4. Define Phases

Each phase should:

- Have a clear scope (one concern or related group)
- List specific files/changes
- Define acceptance criteria
- Include testing requirements
- Note documentation updates needed

### 5. Update Roadmap

Record planned work in `docs/roadmap.md` with:

| Phase   | Scope             | Dependencies  | Status                       |
| ------- | ----------------- | ------------- | ---------------------------- |
| Phase 1 | Short description | Prerequisites | Planned / In Progress / Done |

### 6. Hand Off

For each phase, create a task specification that `feature-building` can execute. Include module
references, pattern docs to follow, and acceptance criteria.

## Key Principles

1. Security issues take precedence over all other work
2. Fix root causes, not symptoms (fix the pattern, not the instance)
3. Group related changes to minimize context switching
4. Each phase should be completable in a single work session
5. Leave the codebase better than you found it (boy scout rule)

## References

| Topic                  | Doc                                     |
| ---------------------- | --------------------------------------- |
| Module dependencies    | `docs/modules/index.md`                 |
| Feature inventory      | `docs/key-features.md`                  |
| Product scope          | `docs/foundation/product-definition.md` |
| Architecture decisions | `docs/adr/index.md`                     |
| Known issues           | GitHub Issues                           |
