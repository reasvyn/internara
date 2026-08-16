---
name: roadmap-planning
description: "SDLC Phase: PLANNING. Structured roadmap planning for bug fixes, security/performance improvements, and feature development. Produces actionable implementation phases with clear priorities, dependencies, and testing strategy."
upstream:
  - arch-guard
  - security-audit
  - spec-audit
downstream:
  - feature-building
---

# Roadmap Planning

> **Prerequisite:** Load `context-awareness` for project orientation and module context.

## When to Activate

Use this skill when planning the work roadmap — prioritizing bug fixes, security patches, features,
or performance improvements. Produces structured phases in `docs/roadmap.md`.

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (each planned phase must trace to a **governing spec** FR/NFR/UC ID), **Size Triage**
(S/M/L session splitting — each planned phase should be completable in one session), verification
strategy, and commit format. This skill adds the planning process, prioritization categories, and
roadmap table below — nothing else.

### Execute — Planning & Prioritization

- Collect inputs: audit findings, security issues, bug reports, feature requests (run
  `python3 scripts/scan_issues.py` for a summary)
- Categorize by severity and urgency
- Evaluate dependencies between modules (use `docs/modules/index.md`)
- Define phases with clear scope and acceptance criteria
- **Size each phase (S/M/L)** — flag L-size phases for session-splitting
- Update `docs/roadmap.md` with prioritized phases

### Verify — Quality Gates

- **Markdown-only changes:** run `python3 scripts/scan_doc_links.py` (roadmap edits don't need
  pint/phpstan/tests)
- PHPStan/Pint only if PHP files were touched

## Planning Process

### 1. Collect Inputs

Gather findings from:

- `arch-guard` — code quality and pattern violations
- `spec-audit` — spec-implementation drift
- `security-audit` — security vulnerabilities
- GitHub Issues — `python3 scripts/scan_issues.py` (Automation-First) for a summary
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
- **Classify the size (S/M/L)** — an L phase (>10 files / multi-module) is flagged for
  session-splitting so the implementing agent informs the user and stages it

### 5. Update Roadmap

Record planned work in `docs/roadmap.md` with:

| Phase   | Scope             | Size | Dependencies  | Status                       |
| ------- | ----------------- | ---- | ------------- | ---------------------------- |
| Phase 1 | Short description | S/M/L | Prerequisites | Planned / In Progress / Done |

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
| Feature specs          | `docs/specs/index.md`                   |
| Product scope          | `docs/foundation/product-definition.md` |
| Architecture decisions | `docs/adr/index.md`                     |
| Known issues           | GitHub Issues                           |
