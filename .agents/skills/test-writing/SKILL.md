---
name: test-writing
description: "SDLC Phase: TESTING. Comprehensive code verification, efficient test execution, and failing test diagnosis — prioritizes lightweight verification over full test suite and spec-traceable, noise-free tests."
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
  - medialibrary-development
downstream:
  - sync-docs
  - pest-testing
---

# Verify & Testing

> **Last updated:** 2026-08-17 **Changes:** extracted inline rules (Core Principles, §1-§5) into `rules/` rule assets with a `## Skill Rules` mapping section

> **Prerequisite:** Load `context-awareness` for project conventions and critical invariants.

## When to Activate

Use this skill when:
- Verifying code changes before committing (any layer)
- Deciding what verification strategy to use for a given change
- Writing new tests or fixing failing tests
- Auditing tests against their specs (spec gaps, orphan tests)
- Determining whether running the full test suite is necessary

## Workflow

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model: spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting), verification
strategy, and commit format. This skill adds the verification-strategy, test-execution, and
test-diagnosis rules found in the Skill Rules section below — nothing else.

---

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Verification strategy (change-type matrix, lightweight toolkit, full-suite fire drill) | `rules/verification-strategy.md` | Deciding what to run for a given change |
| Spec-driven testing (requirement-traceable tests, audit scope, minimalism) | `rules/spec-driven-testing.md` | Writing tests or auditing tests against their specs |
| Efficient execution (targeted commands, batching, memory budget) | `rules/efficient-execution.md` | Running tests without wasting time or resources |
| Test writing (follow existing patterns, scenario checklist, helpers) | `rules/test-writing.md` | Writing new tests |
| Test diagnosis (read failures, efficient fix workflow, pre-existing handling) | `rules/test-diagnosis.md` | Fixing failing tests |

---

## References

| Topic | Location |
|-------|----------|
| Feature specs (source of truth for tests) | `docs/specs/index.md`, `docs/specs/{ID}-{feature}.md` |
| Testing patterns | `docs/architecture/testing-pattern.md` |
| Pest testing skill | `.agents/skills/pest-testing/SKILL.md` |
| arch-guard skill | `.agents/skills/arch-guard/SKILL.md` |
| Pre-commit checklist | `AGENTS.md` (end of file) |
| Critical invariants | `AGENTS.md` (§Critical Invariants) |