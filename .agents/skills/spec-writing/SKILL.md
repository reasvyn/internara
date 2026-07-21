---
name: spec-writing
description: SDLC Phase: PLANNING / DOCUMENTATION. Writing comprehensive feature specification documents — problem statements, goals/non-goals, user stories, functional/non-functional requirements, API/data contracts, design decisions, and success metrics. Produces self-contained specs that serve as the authoritative source for feature implementation.
upstream:
  - context-awareness
  - doc-writing
  - roadmap-planning
downstream:
  - feature-building
  - code-writing
  - pest-testing
  - writing-issues
---

# Spec Writing

> **Prerequisite:** Load `context-awareness` for project orientation and `doc-writing` for
> documentation conventions.

## When to Activate

Use this skill when:
- Writing a new feature specification document (`docs/specs/{feature}.md`)
- Defining requirements before implementation begins
- Documenting design decisions for complex features
- Creating acceptance criteria for features
- Defining API/data contracts before coding

**Do NOT use for:**
- Module conceptual docs (`docs/modules/{module}.md`) — use `doc-writing`
- Module reference docs (`docs/modules/{module}-reference.md`) — use `doc-writing`
- Architecture decision records — use `doc-writing`
- Bug reports or issue writing — use `writing-issues`

---

## Agent Workflow

### 1. Construct — Research & Scope

- Load `context-awareness` and `doc-writing` skills
- Read primary references:
  - `docs/key-features.md` — feature inventory
  - `docs/foundation/project-requirements.md` — high-level feature specs
  - `docs/foundation/product-definition.md` — scope, personas, system boundary
  - `docs/modules/{module}.md` and `docs/modules/{module}-reference.md` — if feature belongs to a module
- Read existing code if implementation exists (verify against docs)
- Read any existing specs in `docs/specs/` to follow established patterns
- Identify the feature boundary: what's in scope, what's not
- Determine the target audience: developers implementing, testers verifying, PMs reviewing

### 2. Execute — Write Specification

- Follow the 10-section spec template (see below)
- Use `edit` tool for existing files, `write` tool only for new files
- Every statement must be verifiable or actionable
- Reference source code with file paths where implementation exists
- Reference config values with exact keys and defaults
- Cross-reference related docs instead of duplicating content

### 3. Verify — Quality Gates

- All 10 sections are present and populated
- Every functional requirement has a unique ID (`FR-{area}{number}`)
- Every non-functional requirement has a unique ID (`NFR-{category}{number}`)
- Every design decision has a unique ID (`DD-{number}`)
- All cross-references resolve to existing files
- All file paths reference real files in the codebase
- Metadata block present with current date
- No duplicate content across sections (cross-reference instead)

### 4. Report — Deliver

- Deliver a report to the user:
  - File created/updated
  - Number of requirements defined
  - Number of design decisions documented
  - Any gaps or assumptions flagged

---

## 10-Section Spec Template

Every spec document follows this structure:

```markdown
# Feature Name — Subtitle/Scope

> **Last updated:** YYYY-MM-DD **Changes:** description

## Description

{1-3 sentence summary. What this spec covers and why it exists.}

---

## 1. Problem Statements

### PS-N — Short Title

{What problem does this solve? Why can't we ignore it?}

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | ...  |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | ...      |

---

## 3. User Stories / Use Cases

### UC-N — Title

**Actor:** {Who performs this}
**Preconditions:** {What must be true before this starts}
**Flow:** {Step-by-step numbered list}
**Postconditions:** {What's true after completion}

---

## 4. Functional Requirements

| ID   | Requirement |
| ---- | ----------- |
| FR-X1 | ...        |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-X1 | ...        |

---

## 6. API / Data Contracts

{Data structures, action signatures, routes, events, config values}

---

## 7. Design Decisions

### DD-N — Short Title

**Decision:** {What was decided}
**Rationale:** {Why this approach}
**Trade-off:** {What was sacrificed}

---

## 8. Success Metrics

{Measurable targets for the feature}

---

## Quick References

- `{path}` — {what's there}
```

---

## Requirement ID Conventions

Use these prefixes to make requirements scannable and cross-referenceable:

| Prefix  | Category         | Example          |
| ------- | ---------------- | ---------------- |
| `PS-`   | Problem Statement| `PS-1`           |
| `G-`    | Goal             | `G1`             |
| `NG-`   | Non-Goal         | `NG1`            |
| `UC-`   | Use Case         | `UC-1`           |
| `FR-`   | Functional Req   | `FR-A1` (A=audit)|
| `NFR-`  | Non-Functional   | `NFR-S1` (S=security)|
| `DD-`   | Design Decision  | `DD-1`           |

For `FR-` and `NFR-`, add a single-letter area code when the feature has multiple sub-areas:

| Area Code | Category       | Example        |
| --------- | -------------- | -------------- |
| `A`       | Audit/Check    | `FR-A1`        |
| `P`       | Provisioning   | `FR-P1`        |
| `T`       | Token          | `FR-T1`        |
| `W`       | Wizard/UI      | `FR-W1`        |
| `F`       | Finalization   | `FR-F1`        |
| `AC`      | Access Control | `FR-AC1`       |
| `C`       | CLI            | `FR-C1`        |
| `S`       | Security       | `NFR-S1`       |
| `P`       | Performance    | `NFR-P1`       |
| `R`       | Reliability    | `NFR-R1`       |
| `U`       | Usability      | `NFR-U1`       |
| `M`       | Maintainability| `NFR-M1`       |

---

## Content Rules

### Problem Statements

- Each PS must describe a problem, not a solution
- PS must explain why the problem matters (consequences of not solving it)
- Group related problems — don't create separate PS for symptoms of the same root cause

### Goals & Non-Goals

- Goals must be measurable or verifiable
- Non-Goals must be explicit — they prevent scope creep during implementation
- If a Non-Goal becomes a Goal later, update the spec and add a DD explaining the change

### User Stories / Use Cases

- Use cases must cover the primary path AND important alternatives
- Preconditions must be checkable (not vague like "system is ready")
- Postconditions must be verifiable (not vague like "user is happy")
- Include error/edge cases as separate flows when important

### Functional Requirements

- Every FR must be uniquely identifiable (for test traceability)
- FR must be atomic — one requirement per line
- FR must use "must" (mandatory), "should" (strongly recommended), "may" (optional)
- Reference data contracts from §6 when FR involves specific data structures

### Non-Functional Requirements

- NFR must have a measurable target when possible (time, size, count)
- Separate categories: Security, Performance, Reliability, Usability, Maintainability
- NFR must be testable — if you can't test it, rewrite it

### API / Data Contracts

- Include exact class signatures, method signatures, config arrays
- Show data types for all properties
- List all enum cases if applicable
- Reference source files with paths
- Include route definitions with middleware

### Design Decisions

- Only document decisions that are non-obvious or have significant trade-offs
- Don't document "obvious" choices (e.g., "we used PHP because the project is PHP")
- Each DD must explain what was rejected and why
- If a DD is later overturned, update it with a note and add a new DD

### Success Metrics

- Metrics must be measurable (not "fast" — use "< 30s")
- Include both positive metrics (what should work) and negative metrics (what should NOT happen)
- Metrics should be achievable — set aspirational but realistic targets

---

## Spec Lifecycle

| Phase    | Action                                              |
| -------- | --------------------------------------------------- |
| Draft    | Write initial spec with all 10 sections             |
| Review   | Verify against code, check completeness             |
| Approve  | User confirms spec before implementation begins     |
| Implement| `feature-building` implements against spec          |
| Verify   | Tests trace back to FR/NFR IDs                      |
| Update   | If requirements change during implementation, update spec first |

**Documentation-first:** The spec is written BEFORE implementation. Code matches the spec, not the
other way around.

---

## Phase Context

| Role | Skill |
|------|-------|
| **Upstream** | `context-awareness` (project orientation), `doc-writing` (documentation conventions), `roadmap-planning` (feature identification) |
| **This skill** | **SPEC WRITING** — defines feature specifications before implementation |
| **Downstream** | `feature-building` (implementation), `code-writing` (coding), `pest-testing` (verification), `writing-issues` (if spec reveals gaps) |

---

## Quick References

| Topic | Location |
|-------|----------|
| Documentation conventions | `docs/conventions.md` |
| Doc-writing skill | `.agents/skills/doc-writing/SKILL.md` |
| Feature inventory | `docs/key-features.md` |
| High-level specs | `docs/foundation/project-requirements.md` |
| Product definition | `docs/foundation/product-definition.md` |
| Module index | `docs/modules/index.md` |
| Existing specs | `docs/specs/` |
| Architecture overview | `docs/architecture.md` |
