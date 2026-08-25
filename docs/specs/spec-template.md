# Spec Template — Feature Specification Skeleton

> **Last updated:** 2026-08-25 **Changes:** docs→.agents skill path reference replaced with named-skill mention per documentation-split rule

## Description

The fixed 11-section structure every spec in `docs/specs/` follows. Specs are the requirements
SSOT — implementation and tests trace back to the requirement IDs defined here. Section-by-section
content rules live in the `spec-writing` skill.

## The Skeleton

Copy everything inside the fence into `docs/specs/{ID}-{feature}.md`, where `{ID}` is a fresh
5-character alphanumeric registry key (see the spec-indexing rule for allocation).

```markdown
# {ID} — {Feature Name}

> **Last updated:** YYYY-MM-DD **Changes:** {latest change}

## Description

{2–3 sentences: what this feature is and why it exists.}

## 1. Problem Statements

{Numbered PS-* statements — the concrete pain being solved.}

## 2. Goals & Non-Goals

| Goal | Why |
|------|-----|
| Non-goal | Explicitly excluded |

## 3. User Stories / Use Cases

{UC-* table: actor, action, outcome.}

## 4. Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-{AREA}-01 | {verifiable statement} | P0/P1/P2 |

## 5. Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-{AREA}-01 | {measurable constraint} | {number} |

## 6. API / Data Contracts

{Exact schemas, signatures, payloads — precise enough to implement against without asking.}

## 7. Design Decisions

| DD | Decision | Rationale | Alternatives rejected |
|----|----------|-----------|-----------------------|

## 8. Success Metrics

{How adoption/correctness will be measured after ship.}

## 9. Roadmap

{Phasing, dependencies on other specs.}

## Quick References

- [specs/index.md](index.md) — registry entry
```

## Writing Discipline

- Every FR/NFR/UC gets a stable ID (`FR-{AREA}-NN`) — tests reference these IDs verbatim.
- Requirements are verifiable statements, not wishes ("rejects placement when slot capacity is
  full", not "handles capacity well").
- §6 contracts are non-negotiable precision: table names, column types, method signatures.
- One initiative = one spec; split oversized specs rather than growing them unbounded.

## Quick References

- [`doc-template.md`](../doc-template.md) — shared documentation standards (Diátaxis, principles)
- `spec-writing` skill rules — section-by-section intent for each template section (agent-facing)
- [`index.md`](index.md) — spec registry and build order
