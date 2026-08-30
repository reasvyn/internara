# Skill Rules & References

> **Curated mandatory known context** — skill map index and reference navigation. Read at start of every session.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Key rules (non-negotiable) | `.agents/rules/key-rules.md` | Every instruction |
| Instruction ordering (impact-to-effort) | `.agents/rules/instruction-ordering.md` | Batched/multi-instruction messages |

## References

| Topic | Doc |
|-------|-----|
| Full workflow & module map | `AGENTS.md` |
| Verification matrix | `AGENTS.md` §Verification Strategy |
| Pre-commit checklist | `AGENTS.md` §Pre-commit Checklist |
| Skill map | `AGENTS.md` §Skill Map |
| Conventions & invariants | `docs/conventions.md` |
| Instruction ordering rule | `.agents/rules/instruction-ordering.md` (this skill) |

## Rules Index — Load on Demand

> All rule bodies live in `.agents/rules/` (150+ rules consolidated from skill `rules/` directories).
> The table below indexes the most-referenced rules; load any other rule file by name when a task
> reaches its concern.

| Rule file | Governs | Load when |
|-----------|---------|-----------|
| [`spec-first-doctrine`](../rules/spec-first-doctrine.md) | Governing spec is SSOT; no behavior without a requirement ID | Every task — consult before planning |
| [`clean-code-dedup-align`](../rules/clean-code-dedup-align.md) | DRY default, spec↔code↔docs↔tests alignment, surfacing structural decisions | Every task — during implement & review |
| [`computational-thinking`](../rules/computational-thinking.md) | Four decision pillars + predict→act→verify→adjust loop | Ambiguous or multi-step instructions |
| [`documentation-split`](../rules/documentation-split.md) | Human docs in `docs/`, AI assets in `.agents/`; directional referencing | Any documentation change |
| [`automation-first`](../rules/automation-first.md) | Script batch work; reuse scanners; `/tmp` for throwaway scripts | Repetitive/batch operations; writing scripts |
| [`impact-to-effort`](../rules/impact-to-effort.md) | Order all work: dependency chains → business importance/urgency bands → impact-to-effort ratio | Multiple instructions, backlog triage, multi-stage planning |
| [`edit-policy`](../rules/edit-policy.md) | Read-before-edit, surgical diffs, git lossless proof | Every code/doc edit |
| [`pre-existing-defects`](../rules/pre-existing-defects.md) | Fix or file noticed warnings/errors; never silent tolerance | Warnings/errors encountered mid-task |
| [`commit-as-checkpoint`](../rules/commit-as-checkpoint.md) | Commit at every session end AND every verified milestone; never leave verified work uncommitted | End of every session; each stage of multi-stage work |
| [`verification-strategy`](../rules/verification-strategy.md) | Batched verification, change-type matrix, scanner commands | Before running tests or quality gates |
| [`pre-commit-checklist`](../rules/pre-commit-checklist.md) | Final gate before every commit | Immediately before each commit |
| [`key-rules`](../rules/key-rules.md) | Non-negotiable workflow rules (load order, no restate, spec-first, narration, batch verify, impact-to-effort) | Every instruction — governs workflow |
| [`instruction-ordering`](../rules/instruction-ordering.md) | Impact-to-effort scoring for batched instructions | Multi-instruction messages |
| [`architecture-rules`](../rules/architecture-rules.md) | Layer boundaries & Action Triad checks | Classifying/reviewing code against 4-layer model |
| [`domain-boundary`](../rules/domain-boundary.md) | One business domain = one Domain; when a domain earns its own Domain | Decomposing/relocating a domain into its own Domain |
| [`coding-rules`](../rules/coding-rules.md) | Practical coding application guide (before writing any class) | Creating/reviewing Actions, Entities, DTOs, Models, Enums |
| [`testing-rules`](../rules/testing-rules.md) | What to verify when testing (spec-driven minimalism) | Writing/reviewing tests |
| [`invariants`](../rules/invariants.md) | Non-negotiable invariants C1-C8, D1-D6 | Every class written or touched |
| [`class-contracts`](../rules/class-contracts.md) | Action/Entity/DTO/Model/Enum/Livewire/Service contracts | Creating/modifying a component type |
| [`naming-conventions`](../rules/naming-conventions.md) | File/class/method/variable naming | Naming files, classes, routes, tests |
| [`performance`](../rules/performance.md) | N+1, queries, caching | Query-heavy or list/dashboard code |
| [`security`](../rules/security.md) | XSS, SQLi, mass assignment, CSRF | Any user input, output, or form |

---
*Source: AGENTS.md §Skill Rules, §References & §Rules Index. For detailed rule bodies, see `.agents/rules/`.*