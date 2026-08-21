---
name: issue-writing
description: "SDLC Phase: ANALYSIS / PLANNING. Structured GitHub Issues writing for bugs, features, security, refactoring, and tech debt — with clear scope, impact, recommendations, and design decisions. Produces issues that are actionable by both developers and AI agents."
downstream:
  - feature-building
  - code-refactoring
  - security-audit
---

# Issue Writing

> **Last updated:** 2026-08-18 **Changes:** slim SKILL.md to index form — issue quality, template usage and types/labels prose live in `rules/`; unified template kept inline (per `rules/issue-template.md`)

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this skill when creating GitHub Issues for any tracked work — bugs, features, security
vulnerabilities, refactoring, performance, tech debt, or documentation. Every issue must be
immediately actionable by a developer or AI agent without requiring additional context.

Activation triggers include:

- **Pre-existing defects** (AGENTS.md Pre-existing Defects — Fix or File): a warning/error noticed
  during other work that cannot be safely fixed in-session (needs design decisions, significant
  effort, or is out of scope) **must become a GitHub issue immediately** — a defect noticed is a
  defect tracked, never silently tolerated.
- **Audit findings** (spec-audit, qa-protocol, security-audit, arch-guard): each finding that
  requires code, spec, or doc changes is filed as an issue with the finding's severity and evidence.
- **Feature requests / bug reports / refactors / tech debt** that arrive as instructions or emerge
  from code review.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (cite the **governing spec** FR/NFR/UC IDs an issue refers to), **Size Triage** (S/M/L
session splitting — an L-size issue notes the session-split plan in its body), verification
strategy, and commit format. This skill adds the issue types, unified template, key rules, and
labels reference below — nothing else.

- **Check for duplicate issues first** (Dedup-Align Doctrine): run `python3 scripts/scan_issues.py`
  and search existing open issues for the same concern/module; link to the existing one instead.
- Write the issue using the **Unified Issue Template** below (every issue follows it; irrelevant
  sections may be removed).
- Apply the key rules for issue quality (`rules/issue-quality.md`), use the template usage guidance
  (`rules/issue-template.md`), and classify type/labels correctly (`rules/issue-types-and-labels.md`).

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

For section-by-section filling guidance, the key rules, and the label set, load
`rules/issue-quality.md`, `rules/issue-template.md`, and `rules/issue-types-and-labels.md`.

## Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `scan_issues.py` | Fetch GitHub issues, summarize by module/severity | `python3 scripts/scan_issues.py` |

Output: `scripts/outputs/{timestamp}-issues.json`.

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Issue quality (completeness, actionability, quality gates, destructive patterns) | `rules/issue-quality.md` | Every issue before submission |
| Issue template (section-by-section guidance for the unified template) | `rules/issue-template.md` | Filling in each section of the unified template |
| Issue types & labels (classification, severity/priority, label set) | `rules/issue-types-and-labels.md` | Selecting type and labels for an issue |

## References

| Topic                 | Doc                                      |
| --------------------- | ---------------------------------------- |
| GitHub Issues         | {url repo}/issues                        |
| Issue workflow/rules  | `rules/issue-quality.md` (this skill)    |
| Issue template rules  | `rules/issue-template.md` (this skill)   |
| Issue types & labels  | `rules/issue-types-and-labels.md` (this skill) |
| Pre-existing defects  | `AGENTS.md` (§ Pre-existing Defects)     |
| Dedup & alignment     | `AGENTS.md` (§ Clean Code & Dedup-Align Doctrine) |
| Module structure      | `docs/modules/index.md`                  |
| Architecture patterns | `docs/architecture/{pattern}-pattern.md` |
| Critical invariants   | `AGENTS.md` (§ Critical Invariants)      |
| Coding conventions    | `docs/conventions.md`                    |
