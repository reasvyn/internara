---
name: arch-guard
description: >
    SDLC Phase: QUALITY GATE. Comprehensive architecture, convention, and pattern enforcement. Scans
    PHP/Blade code for violations of C1-C8, D1-D6, class contracts, naming conventions, security
    anti-patterns, and performance issues. Produces structured JSON reports for issue creation. Use
    after any code change, before commit, or as a periodic audit. All other code skills
    (code-writing, code-refactoring, livewire-development, pest-testing) delegate quality checks to
    this skill.
---

# Architecture Guard

> **Last updated:** 2026-08-18 **Changes:** slim SKILL.md to index form — rule prose lives in
> `rules/` (invariant enforcement, class contracts, naming, security, performance, layer audit,
> output & integration)

Comprehensive enforcement of Internara's architecture, conventions, and patterns. This is the
**single source of truth** for all quality rules — every other skill defers here.

## When to Use

Follow the `agent-workflow` skill for the canonical 9-step pipeline / 4-phase model — this skill is
the **Verify-phase quality gate**, not an SDLC phase of its own. It delegates to the canonical
**Size Triage** for scan scoping.

Use this skill when performing a systematic audit of the codebase. Audits focus on pattern
violations, code smells, security holes, and convention drift — NOT feature enhancements. Activates
during ANALYSIS phase or as a periodic quality gate.

| Scenario                     | Action                               |
| ---------------------------- | ------------------------------------ |
| Before any commit            | Run targeted checks on changed files |
| After feature implementation | Run full module scan                 |
| Periodic audit               | Run full codebase scan               |
| Onboarding new code          | Validate against all contracts       |
| CI/CD gate                   | Run automated checks in pipeline     |

**Size-aware:** if the audit spans multiple modules or the codebase is **L** size (per the
`agent-workflow` Size Triage), split the scan by module into sessions — inform the user and propose
a plan first, then run module-scoped scans (`--module {Name}`) session by session. Never run all
scripts blindly on a full-module set without batching.

## Workflow

This skill is the **Verify-phase quality gate**. Load the rule asset only when the audit reaches
that concern: invariants, class contracts, naming, security, performance, the four-layer procedure,
or output/integration. Every check resolves against `docs/conventions.md` and
`docs/architecture/{pattern}-pattern.md` as the ground truth.

**Spec-first:** this skill is a quality gate, not a source of intent. It only verifies that code
conforms to the governing spec's requirements (FR/NFR/UC IDs). If an audit surfaces a behavior
change with no requirement, the finding is a spec gap — report it via `spec-audit` / `issue-writing`
rather than changing code.

## Phase Context

| Role           | Skill                                                                                                                      |
| -------------- | -------------------------------------------------------------------------------------------------------------------------- |
| **Upstream**   | all implementation skills (`code-writing`, `code-refactoring`, `livewire-development`, `pest-testing`, `feature-building`) |
| **This skill** | **QUALITY GATE** — verifies architecture/convention/security conformance                                                   |
| **Downstream** | `spec-audit` / `issue-writing` (report spec gaps), `sync-docs` (doc drift)                                                 |

## Skill Handoffs (Actionable)

| Condition                                            | Action                                                                                      |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| Audit surfaces a behavior change with no requirement | Report as a spec gap via `spec-audit` / `issue-writing` — do not change code                |
| Finding needs a fix to code / tests                  | Pass the finding to the owning skill (`code-writing`, `pest-testing`) for a spec-driven fix |
| Doc link or metadata drift surfaced                  | Hand to `sync-docs` for baseline sync                                                       |
| Committing validated changes                         | Run targeted checks on changed files as the pre-commit quality gate                         |

## Quality Gate Commands

```bash
# Full violation scan (C1-C8, D1-D6, security, performance)
python3 scripts/scan_violations.py

# Class contract compliance
python3 scripts/scan_class_contracts.py

# Security patterns
python3 scripts/scan_security.py

# Naming conventions
python3 scripts/scan_naming.py

# Combined architecture audit (component counts, submodule structure)
python3 scripts/scan_architecture.py

# Conventions check (strict_types, Fillable, debug calls, hardcoded strings)
python3 scripts/scan_conventions.py

# Dead code detection (unregistered observers, unused DTOs, orphan events)
python3 scripts/scan_dead_code.py

# Doc link integrity
python3 scripts/scan_doc_links.py

# Test suite runner (per-module results)
python3 scripts/scan_tests.py

# Spec↔code gap analysis
python3 scripts/scan_issues.py
```

All scripts output to `scripts/outputs/{timestamp}-{description}.json`. Use `--module {Name}` to
scope to a single module. See `scripts/README.md` for full documentation.

## Skill Rules

| Rule                                                                                          | Asset                             | Applies when                                                          |
| --------------------------------------------------------------------------------------------- | --------------------------------- | --------------------------------------------------------------------- |
| Critical invariants C1-C8 / D1-D6 enforcement & rule priority hierarchy                       | `rules/invariant-enforcement.md`  | Checking any code against the non-negotiable invariants               |
| Class contract checks (Action/Entity/DTO/Model/Enum/Event/Policy/Livewire/Service)            | `rules/class-contracts.md`        | Verifying any component's structural contract                         |
| Naming convention checks (file/class/method/variable)                                         | `rules/naming-conventions.md`     | Validating naming or reviewing any named element                      |
| Security rules S1-S10 (XSS/SQLi/mass-assignment/CSRF/auth/rate-limit/secrets/uploads/headers) | `rules/security-rules.md`         | Scanning for security anti-patterns                                   |
| Performance rules P1-P5 (N+1, select, chunk, cache, exists)                                   | `rules/performance-rules.md`      | Auditing query & data-shape performance                               |
| Four-layer audit procedure & severity classification                                          | `rules/layer-audit-procedure.md`  | Auditing code by layer or classifying finding severity                |
| JSON report structure, automation mapping & skill delegation                                  | `rules/output-and-integration.md` | Consuming/filing findings or integrating arch-guard with other skills |
