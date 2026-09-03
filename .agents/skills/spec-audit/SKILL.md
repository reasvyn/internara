---
name: spec-audit
description: "SDLC Phase: ANALYSIS. Bidirectional spec-implementation audit — verifies specs match code and code matches specs, and that agent guides & skills stay consistent with specs. Determines which side (spec or implementation) needs fixing. Fixes the spec immediately when it lags the implementation; creates GitHub Issues for significant findings. Runs the audited spec's test suite and writes spec-traceable tests for any spec'd component that lacks them. Flexible scope: audit by spec, module, phase, audit area, or agent guides & skills."
downstream:
  - issue-writing
  - code-refactoring
  - feature-building
  - pest-testing
  - sync-docs
---

# Spec Audit — Specification ↔ Implementation Synchronization

> **Prerequisite:** Read `AGENTS.md §Context Awareness` for project orientation, module map, and conventions.

## When to Activate

Use this skill to verify that feature specifications (`docs/specs/`) and code implementation
(`app/`, `tests/`, `routes/`, `database/migrations/`) are in sync, and that **agent guides & skills**
(`AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/`) accurately reflect
the specs they reference. Detects three categories of drift:

1. **Spec → Code:** Spec promises something the code doesn't deliver (missing implementation)
2. **Code → Spec:** Code does something the spec doesn't document (unspecified behavior)
3. **Both stale:** Spec and code disagree on shared contracts (signatures, paths, names)

Agent guides & skills must stay aligned with the specs: a spec change with no matching update in the
skill/guide that documents it is a **Code → Spec** drift (guide lagging). See
[Area 8: Agent Guides & Skills](#area-8-agent-guides--skills).

> **Spec & Test Coverage Assertions (non-negotiable):**
> - **Spec coverage:** Every initiative, feature, and API — including data contracts, endpoints,
>   Livewire contracts, and DTOs — must be recorded in `docs/specs/` as an `FR`/`NFR`/`UC`. An
>   initiative/feature/API without a requirement is a **spec gap** — amend the spec first
>   (spec-first doctrine, `spec-writing` skill, `docs/specs/spec-template.md`).
> - **Test coverage:** Every requirement in the spec must be verified and tested. Testable `FR`/`NFR`/`UC`
>   require a spec-traceable test (`scan_spec_tests` must be 100% for testable; no requirement without
>   a test, no test without a requirement — orphans are removed, gaps are filled in-run).
> - **Trust nothing blindly:** Do not fully trust spec, code, or docs at face value. Every decision
>   — spec→code vs code→spec vs both — must be justified via `git log --follow -- <file>`,
>   `git blame <file>`, and code inspection against the governing spec's intent. If history is silent,
>   treat it as a finding, don't silently decide. Prefer high-impact, low-effort fixes and record the
>   rationale.

**Key distinction from `arch-guard`:** `arch-guard` checks code against conventions and architecture
rules (C1-C8, D1-D6); `spec-audit` checks code against feature specifications (FR/NFR/contracts).
`arch-guard` is code-first; `spec-audit` is spec-first.

**Key distinction from `sync-docs`:** `sync-docs` updates docs to match code (one direction:
code → docs); `spec-audit` checks bidirectional sync and determines which side needs fixing.
`sync-docs` is MAINTENANCE; `spec-audit` is ANALYSIS.

## Workflow

This skill runs a **custom pipeline** (`SCOPE → DISCOVER → AUDIT → TRIAGE → FIX/ISSUE → FINALIZE →
REPORT`) — it is an ANALYSIS skill, not a standard implementation workflow. Follow the
`AGENTS.md §Agent Workflow` for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize), **Size Triage**, and commit format;
this skill adds the audit pipeline defined in the rule assets — nothing else.

- Choose the audit scope and run Size Triage → `.agents/rules/scope-configuration.md`
- Understand drift categories and which side is authoritative → `.agents/rules/bidirectional-audit.md`
- Execute the 6/8 audit areas → `.agents/rules/audit-areas.md` (now includes `scan_spec_tests` priority scoring + browser-test hints for UI, and `scan_violations` SRP god class/long method/many params — derived thresholds, no hardcoding)
- Run the spec's tests and fill spec-traceable test gaps in-run (Pest + `tests/Browser` for UI) → `.agents/rules/run-and-write-tests.md`
- Classify findings via the decision matrix → `.agents/rules/decision-matrix.md`
- Resolve each finding: fix-now (spec-lagging / test-gap / minor) vs GitHub Issue → `.agents/rules/fix-or-issue.md`
- Run the full pipeline, structure the report, honor scope discipline → `.agents/rules/audit-workflow.md`

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_violations.py` | C1-C8, D1-D6 + SRP (god class, long method, many params) + Livewire/P | `python3 tools/scan_violations.py` |
| `scan_class_contracts.py` | Action/Entity/DTO/Model/Enum contract compliance | `python3 tools/scan_class_contracts.py` |
| `scan_security.py` | XSS, SQL injection, auth gaps, hardcoded secrets | `python3 tools/scan_security.py` |
| `scan_naming.py` | File, class, method, variable naming conventions | `python3 tools/scan_naming.py` |
| `scan_architecture.py` | Component counts per module, submodule structure | `python3 tools/scan_architecture.py` |
| `scan_conventions.py` | strict_types, Fillable, debug calls, hardcoded strings | `python3 tools/scan_conventions.py` |
| `scan_dead_code.py` | Unregistered observers, unused DTOs, orphan events | `python3 tools/scan_dead_code.py` |
| `scan_doc_links.py` | Validate all relative links in markdown files | `python3 tools/scan_doc_links.py` |
| `scan_spec_tests.py` | Spec↔tests + browser coverage, priority scoring, module breakdown, top gaps | `python3 tools/scan_spec_tests.py` |
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | `python3 tools/scan_issues.py` |

All scripts output to `tools/outputs/{timestamp}-{description}.json`. Use `--module {Name}` to scope
to a single module. `scan_spec_tests` now scores uncovered FRs by priority (critical FR-A/L/M → high)
and suggests `tests/Browser` (puppeteer-core) for UI requirements; `scan_violations` now flags SRP
god class / long method / many params (derived thresholds, no hardcoding). See `tools/README.md`.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Bidirectional audit — drift categories & which side is authoritative | `.agents/rules/bidirectional-audit.md` | Any audit finding; deciding Spec→Code vs Code→Spec vs both |
| Scope configuration — flexible scope & work channels | `.agents/rules/scope-configuration.md` | Choosing what to audit (spec/module/phase/area/guides/full) |
| Audit areas 1-8 — paths, contracts, requirements, tests, coverage, cross-refs, deps, guides | `.agents/rules/audit-areas.md` | Executing any audit area |
| Run-the-spec's-tests & Test-Gap Fill — mandatory audit duties | `.agents/rules/run-and-write-tests.md` | Reviewing test coverage; any spec'd component without a test |
| Triage & decision matrix — classifying findings, choosing the fix side | `.agents/rules/decision-matrix.md` | Classifying any finding or deciding resolution |
| Fix-now vs Issue — auto-fix criteria, spec-lagging fix, issue standards | `.agents/rules/fix-or-issue.md` | Resolving each finding after triage |
| Audit workflow — pipeline, report structure & scope discipline | `.agents/rules/audit-workflow.md` | Running the full audit from scope to report |

---
## References

| Topic | Doc |
|-------|-----|
| Feature specs | `docs/specs/index.md` |
| Spec template | `.agents/skills/spec-writing/SKILL.md` |
| Agent guides & skills | `AGENTS.md`, `.agents/skills/*/SKILL.md`, `.agents/context/` |
| Module structure | `docs/refs/modules/index.md` |
| Architecture & layer rules | `docs/architecture.md` |
| Architecture patterns | `docs/guides/arch/{pattern}-pattern.md` |
| Action Triad patterns | `docs/guides/arch/action-pattern.md` |
| Entity-Model separation | `docs/guides/arch/entity-pattern.md` |
| Model conventions | `docs/guides/arch/model-pattern.md` |
| Livewire component rules | `docs/guides/arch/livewire-pattern.md` |
| Exception hierarchy | `docs/guides/arch/exception-pattern.md` |
| Caching conventions | `docs/guides/arch/cache-pattern.md` |
| Testing patterns | `docs/guides/arch/testing-pattern.md` |
| Coding conventions | `docs/conventions.md` |
| Security conventions | `docs/conventions.md` (§3) |
| RBAC & authorization | `docs/guides/rbac.md` |
| Critical invariants | `AGENTS.md` (§Critical Invariants) |
| Development status | GitHub Issues |
| Issue writing | `.agents/skills/issue-writing/SKILL.md` |
| Spec-driven testing | `.agents/skills/pest-testing/SKILL.md` |
| Architecture guard | `.agents/skills/arch-guard/SKILL.md` |
| Doc sync | `.agents/skills/sync-docs/SKILL.md` |
