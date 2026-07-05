---
name: writing-issues
description: SDLC Phase: ANALYSIS / PLANNING. Structured GitHub Issues writing for bugs, features, security, refactoring, and tech debt — with clear scope, impact, recommendations, and design decisions. Produces issues that are actionable by both developers and AI agents.
downstream:
  - roadmap-planning
  - feature-building
  - code-refactoring
  - security-audit
---

# Writing Issues

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when creating GitHub Issues for any tracked work — bugs, features, security
vulnerabilities, refactoring, performance, tech debt, or documentation. Every issue must be
immediately actionable by a developer or AI agent without requiring additional context.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Understand affected modules and submodules
- Gather all relevant information (error logs, stack traces, user reports, code references)
- Identify the correct issue type (bug/feature/security/refactor/perf/docs/chore)
- Determine severity and priority

### 2. Execute — Issue Writing

- Write issue using the appropriate template type
- Ensure scope and impact are clearly defined
- Include at least 2 approaches for recommendation (if relevant)
- Document design decisions and trade-offs
- Ensure reproducible steps (for bug) or acceptance criteria (for feature)
- Output: structured GitHub Issue with title, description, scope, impact, recommendations, and
  design decisions

### 3. Verify — Quality Gates

- Review: can the issue be understood without additional context?
- Review: is the scope specific enough (not multiple issues in one)?
- Review: are all technical terms explained?
- Check: no sensitive information/credentials exposed

### 4. Report & Commit

- Deliver a report to the user:
    - Summary of issues created
    - Type, severity, priority
    - Files that will be affected
    - Recommended approach with pros/cons
- Feeds into: roadmap-planning (prioritization), feature-building or code-refactoring
  (implementation)
- Create issue on GitHub
- Add appropriate labels
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                                                                      |
| -------------- | ---------------------------------------------------------------------------------------------------------- |
| **Upstream**   | `audit-protocol` (audit findings), `security-audit` (security findings), `code-refactoring` (tech debt)    |
| **This skill** | **ANALYSIS / PLANNING** — produces GitHub Issues                                                           |
| **Downstream** | `roadmap-planning` (prioritization), `feature-building` (implementation), `code-refactoring` (refactoring) |

## Issue Types

| Type            | Label         | When to Use                                   |
| --------------- | ------------- | --------------------------------------------- |
| **Bug**         | `bug`         | Behavior doesn't match specification          |
| **Feature**     | `enhancement` | New capability                                |
| **Security**    | `security`    | Security vulnerability                        |
| **Refactor**    | `refactor`    | Structure improvement without behavior change |
| **Performance** | `perf`        | Speed/memory optimization                     |
| **Test**        | `test`        | Test addition or fixes                        |
| **Docs**        | `docs`        | Documentation update                          |
| **Chore**       | `chore`       | Tooling, dependencies, config                 |

## Unified Issue Template

Every issue FOLLOWS this template. Irrelevant sections may be removed.

```markdown
## Title

{type}: {module}/{submodule} — {short description}

Examples:

- `bug: enrollment/registration — duplicate entry on concurrent submit`
- `feature: reports/report — add CSV export for grade cards`
- `security: auth/login — rate limit bypass via header manipulation`
- `refactor: user/profile — extract business rules to Entity`

---

## Description

{Full description of this issue. Describe the PROBLEM concretely, not the solution. For bugs: what
happened vs what should have happened. For features: user story or problem statement.}

**Bug example:**

> When two students submit the registration form simultaneously, both requests pass the quota check
> before either transaction commits, resulting in over-quota placement (1 slot filled by 2
> students).

**Feature example:**

> Coordinators need to export finalized grade cards as CSV for offline verification. Currently the
> only option is on-screen table view.

---

## Scope & Impact

| Field              | Value                                   |
| ------------------ | --------------------------------------- |
| **Module**         | {Module}                                |
| **Submodule**      | {Submodule}                             |
| **Files affected** | `{file}`, `{file}`                      |
| **Dependencies**   | {module or task that is a prerequisite} |
| **Severity**       | critical / high / medium / low          |
| **Priority**       | urgent / high / medium / low            |

**Impact description:** {Narratively describe the impact of this issue on the system, users, or
development. Example: "This affects all 500+ students during registration week. Every over-quota
placement requires manual cleanup by admin."}

---

## Reproduction (Bug Only)

### Steps to Reproduce

1. {Step 1}
2. {Step 2}
3. {Step 3}

### Expected Behavior

{What should happen}

### Actual Behavior

{What actually happens}

### Environment

- PHP version: 8.4.x
- Database: SQLite / MySQL / PostgreSQL
- Queue driver: sync / database / redis
- Browser: Chrome/Firefox/Safari (if frontend issue)

---

## Acceptance Criteria

{For feature/refactor. Checklist that must be satisfied for the issue to be closed.}

- [ ] Criterion 1
- [ ] Criterion 2
- [ ] Criterion 3

---

## Recommended Approach

{Describe the RECOMMENDED approach to resolve this issue. Include at least 2 approaches if there are
significant trade-offs.}

### Approach A: {Approach Name} (Recommended)

{Technical description of the approach — which files are changed, which pattern is used, how the new
data flow works.}

**Pros:**

- {Benefit 1}
- {Benefit 2}

**Cons:**

- {Drawback 1}
- {Drawback 2}

### Approach B: {Alternative Approach Name}

{Technical description of the alternative approach.}

**Pros:**

- {Benefit 1}

**Cons:**

- {Drawback 1}
- {Drawback 2}

---

## Design Decisions

{Design decisions that were MADE and their RATIONALE. This is important for audit trail and
preventing repeated questions during code review.}

| Decision   | Chosen          | Rationale   |
| ---------- | --------------- | ----------- |
| {Decision} | {Chosen option} | {Rationale} |
| {Decision} | {Chosen option} | {Rationale} |

**Example:** | Decision | Chosen | Rationale | |----------|--------|-----------| | Locking strategy
| Pessimistic lock via `lockForUpdate()` | Optimistic lock retry logic adds complexity; registration
volume is low (< 10/min) so pessimistic is acceptable | | Where to enforce | Command Action, not DB
constraint | Business rule (quota check) belongs in domain layer; DB constraint is defense-in-depth
|

---

## Related

- {Link to related issue}
- {Link to ADR}
- {Link to docs}

---

## Implementation Notes (for AI Agents)

{Implementation guidance that helps AI agents or new developers.}

- Pattern to follow: {link to docs/architecture/{pattern}-pattern.md}
- Module context: {link to docs/modules/{module}.md}
- Reference file: `{path/to/existing/implementation}`
- Note invariants: {relevant AGENTS.md critical rules}
```

## Key Rules

1. **One issue = one concern.** Do not combine bug + feature in a single issue
2. **Scope must be specific.** "Fix enrollment module" is too broad. "Prevent duplicate registration
   on concurrent submit" is precise
3. **Impact must be measurable.** Not "system becomes slow" but "query takes 3s instead of 200ms for
   1000 students"
4. **Recommended Approach is mandatory for technical issues.** Not just "fix this" but "how to fix
   this"
5. **Design Decisions are mandatory.** Document why Approach A was chosen over Approach B
6. **DO NOT include credentials, tokens, or sensitive data** in the issue
7. **Use relative paths** for file references within the project
8. **Label according to type** — use labels already defined in the repo

## Labels Reference

| Label              | Color     | Description                |
| ------------------ | --------- | -------------------------- |
| `bug`              | `#d73a4a` | Something isn't working    |
| `enhancement`      | `#a2eeef` | New feature or request     |
| `security`         | `#000000` | Security vulnerability     |
| `refactor`         | `#fbca04` | Code restructuring         |
| `perf`             | `#0e8a16` | Performance improvement    |
| `test`             | `#fef2c0` | Test additions or fixes    |
| `docs`             | `#0075ca` | Documentation              |
| `chore`            | `#bfdadc` | Maintenance, tooling, deps |
| `good first issue` | `#7057ff` | Good for newcomers         |
| `help wanted`      | `#008672` | Extra attention needed     |
| `duplicate`        | `#cfd3d7` | Already reported           |
| `wontfix`          | `#ffffff` | Will not be addressed      |

## Verification Checklist

- [ ] Title is clear with format `type: module/submodule — description`
- [ ] Description explains the problem, not the solution
- [ ] Scope & Impact defined with specific module and files
- [ ] Severity and priority are filled
- [ ] Recommended Approach has pros/cons
- [ ] Design Decisions are documented
- [ ] No sensitive information
- [ ] Label matches the issue type
- [ ] No duplication with existing issues (check GitHub Issues)

## References

| Topic                 | Doc                                      |
| --------------------- | ---------------------------------------- |
| GitHub Issues         | {url repo}/issues                        |
| Module structure      | `docs/modules/index.md`                  |
| Architecture patterns | `docs/architecture/{pattern}-pattern.md` |
| Critical invariants   | `AGENTS.md` (§ Critical Invariants)      |
| Coding conventions    | `docs/conventions.md`                    |
