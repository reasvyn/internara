---
name: roadmap-planning
description: SDLC Phase: PLANNING. Structured roadmap planning for bug fixes, security/performance improvements, and feature development. Produces actionable implementation phases in docs/roadmap.md with clear priorities, dependencies, and testing strategy.
upstream: [audit-protocol, security-audit]
downstream: [feature-building]
---

# Roadmap Planning Skill

## When to Activate

Apply this skill when planning or updating the project roadmap (`docs/roadmap.md`). Covers three
pillars:
1. **Bug fixes** — production bugs, logic errors, regression, flaky tests
2. **Security & performance** — vulnerabilities, N+1 queries, caching, rate limiting, auth gaps
3. **Feature development** — new modules, submodules, ADR-driven enhancements

Every planning session produces or updates `docs/roadmap.md` with phased implementation phases,
task-level breakdowns, dependencies, and testing strategy.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `audit-protocol` — findings feed bug/refactor pipeline |
| | `security-audit` — findings feed security pipeline |
| | `docs/known-issues.md` — known bugs and tech debt |
| **This skill** | **PLANNING** — produces `docs/roadmap.md` |
| **Downstream (output)** | `feature-building` — executes roadmap tasks |
| **Phase** | Planning → [Analysis] → [Design] → [Implementation] → [Testing] → [Maintenance] |

---

## Planning Workflow

Each phase below is a distinct planning activity. Execute in order — each phase depends on the
previous.

```
Audit Current State → Classify Pipeline → Prioritize → Phase Planning → Document → Verify
```

---

## Phase 1 — Current State Assessment

### 1.1 Load Context

Read these documents before planning (concurrent read):

| Document | Purpose |
|----------|---------|
| `docs/roadmap.md` | Existing roadmap — understand what's planned, in progress, completed |
| `docs/known-issues.md` | Known bugs, code smells, security gaps — source of bug-fix pipeline |
| `docs/architecture.md` | Architecture constraints that affect feature placement |
| `docs/modules/module-index.md` | Module boundaries — where new features belong |
| `docs/conventions.md` | Conventions that feature implementations must follow |
| `AGENTS.md` | Project invariants, quick rules |
| `docs/architecture/exception-pattern.md` | Error handling patterns for new actions |
| `docs/foundation/rbac.md` | Authorization model — affects feature design |

### 1.2 Audit Existing Roadmap

Read `docs/roadmap.md` and evaluate:

1. **Is the roadmap still accurate?**
   - Are any phases/tasks already completed?
   - Are any phases blocked by missing dependencies?
   - Are any tasks no longer relevant (superseded by other changes)?

2. **Are there gaps?**
   - Bug fixes not captured in any phase.
   - Security vulnerabilities identified but not scheduled.
   - Performance issues (N+1, slow queries, missing cache) without remediation tasks.
   - Feature requests from `key-features.md` not yet on roadmap.

3. **Are phases properly scoped?**
   - Each phase should deliver independently — no phase depends on later phases.
   - Dependencies between tasks are explicit.
   - Testing strategy defined per phase.

**Output:** Updated assessment section in `docs/roadmap.md` noting what's completed, blocked, or stale.

### 1.3 Gather Pipeline Sources

Collect actionable items from these sources:

| Source | What to extract | Priority heuristic |
|--------|----------------|-------------------|
| `docs/known-issues.md` | Bug descriptions, severity, affected files | CRITICAL/HIGH → immediate |
| `docs/architecture.md` | Deprecated patterns needing migration | Based on frequency of use |
| `audit-protocol` results | Convention violations, security holes | Severity in findings |
| `tests/` flaky tests | Tests that fail intermittently | MEDIUM (erodes trust) |
| `key-features.md` | Planned features not yet built | Business priority |
| Recent `git log` | Reverted commits, hotfixes, regressions | HIGH if repeated |
| `composer audit` / `npm audit` | Vulnerable dependencies | CRITICAL |
| PHPStan baseline | Type errors, dead code | Based on error level |

**Output:** Comprehensive list of all actionable items across all sources.

---

## Phase 2 — Pipeline Classification

### 2.1 Classify by Type

Every item falls into exactly one pipeline:

| Pipeline | Tag | Description | Examples |
|----------|-----|-------------|----------|
| **Bug Fix** | `fix` | Production bug, logic error, regression, incorrect behavior | Login redirect loop, wrong status transition, data inconsistency |
| **Security** | `security` | Vulnerability, auth bypass, data exposure, dependency CVE | XSS vector, mass assignment hole, CSRF gap, outdated package |
| **Performance** | `perf` | Slow queries, N+1, memory leaks, cache misses | Dashboard loading >5s, N+1 in Livewire render, unbounded collection |
| **Refactor** | `refactor` | Code quality, pattern migration, tech debt | Extract inline logic to Action, rename ApiToken→AccessToken, add Entity bridge |
| **Feature** | `feat` | New capability, submodule, ADR-driven enhancement | Cross-Role Proxy, internship export, notification preferences |
| **Test** | `test` | Missing tests, flaky tests, test infrastructure | Coverage below threshold, unmaintained factory, slow test suite |
| **Docs** | `docs` | Documentation gap, stale doc, missing ADR | Module reference missing new Actions, outdated ERD |
| **Chore** | `chore` | Tooling, CI/CD, dependency updates, config | Pint rule update, PHPStan config, Dependabot alerts |

### 2.2 Classify by Module

Group items by module (from `docs/modules/module-index.md`):

```
Core       Bug: 2  Security: 0  Perf: 1  Feature: 0
Auth       Bug: 1  Security: 0  Perf: 0  Feature: 1
User       Bug: 0  Security: 1  Perf: 0  Feature: 0
...
```

This reveals which modules have the most technical debt vs. which are ready for new features.

### 2.3 Cross-Cutting Items

Items that span multiple modules get their own cross-cutting category:

- Global N+1 audit (multiple Livewire components)
- Base class migration (multiple Actions)
- Policy proxy integration (5+ modules)
- PHPStan level bump

**Output:** Classified inventory grouped by pipeline + module.

---

## Phase 3 — Prioritization

### 3.1 Severity Matrix

Score each item on two axes:

| Axis | 1 (Low) | 2 (Medium) | 3 (High) | 4 (Critical) |
|------|---------|------------|-----------|--------------|
| **Impact** | Cosmetic only | Minor inconvenience | Blocks workflow, data loss risk | Production crash, data breach |
| **Effort** | Hours, 1 file | Days, 2–5 files | Weeks, 5–15 files | Months, 15+ files |

**Priority = Impact + Effort score:**

| Score | Priority | Action |
|-------|----------|--------|
| 6–8 | **P0 – Immediate** | Next sprint, must fix |
| 4–5 | **P1 – High** | Next 1–2 sprints |
| 2–3 | **P2 – Medium** | Next quarter |
| <2 | **P3 – Low** | Backlog, icebox |

### 3.2 Dependency Graph

For items with dependencies, build a DAG:

```
A → B → C    (B blocks C, A blocks B)
D → C        (D also blocks C)
E (independent)
```

Tasks without dependencies (leaf nodes) can be parallelized. Tasks with blockers must wait.

### 3.3 Balanced Pipeline

Ensure the roadmap has a healthy mix:

| Pipeline | Target % of roadmap | Rationale |
|----------|---------------------|-----------|
| Bug Fix | 20–30% | Keep defect count low |
| Security | 5–15% | Critical but usually fewer items |
| Performance | 5–10% | Periodic optimization sprints |
| Refactor | 10–20% | Prevent architecture drift |
| Feature | 30–50% | Core value delivery |
| Test | 5–10% | Maintain quality |
| Docs | 5% | Keep docs in sync |
| Chore | 5% | Tooling maintenance |

If any pipeline dominates (>60%), the roadmap is unbalanced — shift focus.

**Output:** Prioritized, dependency-ordered item list with pipeline balance check.

---

## Phase 4 — Phase Planning

### 4.1 Group into Phases

Each phase MUST:
1. **Deliver independently** — no phase depends on a later phase.
2. **Be completable in 1–2 sprints** (2–4 weeks).
3. **Have clear entry/exit criteria.**
4. **Include testing requirements.**
5. **Identify affected files.**

Phase naming: `Phase {N}: {Short Description} (Priority: {P0–P3})`

### 4.2 Write Task Specifications

For each task within a phase:

```
### Task {N}.{M} — {Verb}{Entity}

| Field | Value |
|-------|-------|
| **Pipeline** | fix / security / perf / refactor / feat / test / docs / chore |
| **Module** | {Module} |
| **Priority** | P0–P3 |
| **Effort** | Small / Medium / Large |
| **Files** | `{file1}`, `{file2}` |
| **Depends on** | Task {X.Y} |

**Current state:** What the code does now (problematic or missing).

**Target state:** What the code should do after implementation.

**Implementation notes:**
- Step-by-step approach
- Key files to modify
- Pattern references (e.g., `docs/architecture/action-pattern.md`)

**Testing:**
- What to test (happy path, edge cases, error handling)
- Type of test (unit / feature / arch)
```

### 4.3 Dependency Chain Documentation

Use a markdown table:

```
| # | Phase | Task | Priority | Files | Depends On |
|---|-------|------|----------|-------|------------|
| 1 | 1 | BasePolicy proxy helper | P0 | `BasePolicy.php` | — |
| 2 | 1 | Fix SupervisorDashboard 403 | P0 | `SupervisorDashboard.php` | — |
```

### 4.4 No-Change Zones

Document what is **explicitly excluded** from the roadmap with rationale:

```
| Feature / Area | Reason for Exclusion |
|----------------|---------------------|
| {Module}/{Submodule} | {Why it's kept as-is} |
```

Prevents scope creep and preserves focus.

**Output:** Fully specified phases with task-level detail, ready for implementation.

---

## Phase 5 — Documentation

### 5.1 Update docs/roadmap.md

The roadmap document must contain:

```markdown
# Roadmap — {Title}

> **Last updated:** {YYYY-MM-DD}
> **Changes:** {summary of changes since last update}

> **Status:** {Design approved / In progress / On hold}
> **Target:** {Architectural scope}
> **Dependencies:** {prerequisites}

---

## 1. Overview

Brief description of what this roadmap covers. Why it matters, what problem it solves.

## 2. Current State (Gaps)

### 2.{N} {Severity} — {Category}

| # | File | Issue |
|---|------|-------|
| {ID} | `{file}:{line}` | {description} |

Organize by severity: Critical → High → Medium → Low.

## 3. Implementation Phases

### Phase {N}: {Title} (Priority: {P0–P3})

#### Task {N}.{M} — {Verb}{Entity}

| Field | Value |
|-------|-------|
| **Pipeline** | fix / security / perf / refactor / feat |
| **Module** | {Module} |
| **Effort** | Small / Medium / Large |
| **Files** | `{file}` |
| **Depends on** | Task {X.Y} |

**Current → Target:** One-line diff of the change.

**Implementation notes:** ...
**Testing:** ...

## 4. Testing Strategy

| Test | Type | What It Verifies |
|------|------|------------------|
| `{TestName}` | Unit/Feature | {verification description} |

## 5. Integration Order

| # | Phase | Task | Files | Depends On |
|---|-------|------|-------|------------|
| 1 | 1 | {description} | `{file}` | — |

## 6. No-Change Zones

| Feature / Area | Reason |
|----------------|--------|
| {Module} | {Rationale} |
```

### 5.2 Cross-Reference Known Issues

- Every bug fix task in roadmap should reference the corresponding `docs/known-issues.md` entry.
- Every security task should reference the audit finding or CVE.
- Every feature task should reference the ADR or `key-features.md` section.

### 5.3 Roadmap Hygiene Rules

1. **One roadamap at a time.** The document covers one major initiative (e.g., Cross-Role Proxy,
   Performance Sprint). Completed roadmaps are archived under `docs/roadmap/archive/`.
2. **Phases are irreversible.** Once a phase is completed, mark it as `[COMPLETED]` but keep its
   content — it's an audit trail.
3. **No duplicates.** A task appears exactly once — reference it by ID if it's needed by multiple
   phases.
4. **Dates are accurate.** The `last updated` header reflects the most recent content change.
   Version history is tracked in `git log`.

**Output:** Updated `docs/roadmap.md` with all phases, tasks, dependencies, and testing strategy.

---

## Phase 6 — Verification

### 6.1 Sanity Checks

- [ ] Each phase can be completed independently (no forward dependencies).
- [ ] Every task has a clear current → target description.
- [ ] Every task specifies which files to modify.
- [ ] Testing strategy covers happy path + edge cases + error handling.
- [ ] No pipeline dominates >60% of total effort.
- [ ] All known critical/high bugs from `docs/known-issues.md` are scheduled.
- [ ] No-change zones are documented and justified.
- [ ] Dependencies between tasks are accurate and complete.

### 6.2 Cross-Check Against Conventions

- [ ] All feature tasks reference a pattern doc: `docs/architecture/{pattern}-pattern.md`.
- [ ] All new Actions follow Action Triad (Command/Read/Process).
- [ ] All new Livewire components follow Thin Component Rule.
- [ ] All new Entities are `final readonly` extending `BaseEntity`.
- [ ] All new DTOs are `final readonly` extending `BaseData`.
- [ ] All new events extend `BaseEvent` and are registered in `config/event.php`.

### 6.3 Balance Check

```
Pipeline distribution:
  Bug Fix:     {N} tasks ({P}%)
  Security:    {N} tasks ({P}%)
  Performance: {N} tasks ({P}%)
  Refactor:    {N} tasks ({P}%)
  Feature:     {N} tasks ({P}%)
  Test:        {N} tasks ({P}%)
  Docs:        {N} tasks ({P}%)
  Chore:       {N} tasks ({P}%)

Target:
  Bug Fix:     20–30%  |  Security:   5–15%  |  Performance: 5–10%
  Refactor:    10–20%  |  Feature:    30–50%  |  Test:        5–10%
  Docs:        5%      |  Chore:      5%

Assessment: {Balanced / Unbalanced — note deviations}
```

---

## Phase 7 — Archive (Optional)

When a roadmap initiative is complete:

1. Move the completed roadmap content to `docs/roadmap/archive/{initiative}-{YYYY-MM}.md`.
2. Remove the content from `docs/roadmap.md` (keep a stub or start new initiative).
3. Update `docs/doc-index.md` to reference the archive file.
4. Update `docs/known-issues.md` — mark all resolved entries as `[RESOLVED in {initiative}]`.

---

## References

| Document | Purpose |
|----------|---------|
| `docs/roadmap.md` | Roadmap document (target file for this skill) |
| `docs/known-issues.md` | Bug and tech debt inventory (input to bug-fix pipeline) |
| `docs/architecture.md` | Architecture constraints for feature design |
| `docs/conventions.md` | Implementation standards |
| `docs/modules/module-index.md` | Module boundaries |
| `docs/key-features.md` | Feature inventory (input to feature pipeline) |
| `docs/doc-index.md` | Documentation catalog |
| `AGENTS.md` | Project invariants |
| `docs/infrastructure/testing.md` | Testing infrastructure |
| `.agents/skills/audit-protocol/SKILL.md` | Audit findings (input to bug/security/perf pipelines) |
| `.agents/skills/security-audit/SKILL.md` | Security audit findings |
| `.agents/skills/feature-building/SKILL.md` | Feature implementation workflow (downstream) |
| `.agents/skills/code-refactoring/SKILL.md` | Code refactoring (downstream for refactor tasks) |
| `.agents/skills/pest-testing/SKILL.md` | Testing (downstream) |
| `.agents/skills/sync-docs/SKILL.md` | Documentation sync (downstream) |

---

## SDLC Workflow Diagram

```
                          ┌──────────────────────────┐
                          │     docs/known-issues.md  │
                          └──────────┬───────────────┘
                                     │
              ┌──────────────────────┼──────────────────────┐
              │                      │                      │
              ▼                      ▼                      ▼
    ┌─────────────────┐  ┌─────────────────────┐  ┌─────────────────┐
    │ audit-protocol  │  │   security-audit    │  │  Other sources  │
    │ (ANALYSIS)      │  │ (ANALYSIS: Security)│  │  (git log, etc) │
    └────────┬────────┘  └──────────┬──────────┘  └────────┬────────┘
             │                      │                      │
             └──────────────────────┼──────────────────────┘
                                    ▼
                         ┌────────────────────┐
                         │  roadmap-planning  │
                         │  (PLANNING)        │
                         │  → docs/roadmap.md │
                         └────────┬───────────┘
                                  │
                    ┌─────────────┼─────────────┐
                    │             │             │
                    ▼             ▼             ▼
          ┌──────────────┐ ┌──────────┐ ┌──────────────┐
          │code-refactor │ │ feature- │ │  security    │
          │(DESIGN)      │ │ building │ │  patches     │
          └──────┬───────┘ │(IMPL)    │ │(direct feat) │
                 │         └────┬─────┘ └──────────────┘
                 │              │
                 │     ┌────────┼──────────────────┐
                 │     │        │                  │
                 ▼     ▼        ▼                  ▼
          ┌──────────┐ ┌──────────┐ ┌────────────┐ ┌──────────────┐
          │ livewire │ │tailwind  │ │medialibrary│ │   pulse     │
          │-dev      │ │-dev      │ │-dev        │ │  -dev       │
          └──────────┘ └──────────┘ └────────────┘ └──────────────┘
                 │              │
                 ▼              ▼
          ┌──────────┐  ┌──────────────┐
          │pest-test │  │   sync-docs  │
          │(TESTING) │  │(MAINTENANCE) │
          └──────────┘  └──────────────┘
```
