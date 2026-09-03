# Spec Template — Feature Specification Skeleton

## Description

The fixed 12-section structure every spec in `docs/specs/` follows. Specs are the requirements
SSOT — implementation and tests trace back to the requirement IDs defined here. Section-by-section
content rules live in the `spec-writing` skill.

## The Skeleton

Copy everything inside the fence into `docs/specs/{ID}-{feature}.md`, where `{ID}` is a fresh
5-character alphanumeric registry key (see the spec-indexing rule for allocation).

```markdown
# {ID} — {Feature Name}

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

## 10. Risks & Assumptions

Items that are **not yet decided, explicitly deferred, or unverified** at the time of writing.
The `GH Issue` column links to the GitHub Issue that tracks the resolution; the `Status` column
is updated when the issue closes. This section is the **spec-side counterpart** of GitHub Issues
— keep the table lean, link out for detail.

| ID    | Risk / Assumption / Open Question                                                            | Status   | Owner      | GH Issue                                                                                |
| ----- | ------------------------------------------------------------------------------------------- | -------- | ---------- | --------------------------------------------------------------------------------------- |
| R-1   | {statement of the risk or assumption — frame as a question if undecided}                   | Open     | Maintainer | [#NNN](https://github.com/{owner}/{repo}/issues/NNN) (link or "—")                      |
| A-1   | {stated assumption — "We assume X" or "Until Y is verified, we proceed as Z"}               | Accepted | Maintainer | — (or link to issue if raised)                                                          |

**How to fill:**
- **R (Risk):** something that could go wrong, with mitigation. Format as "If X, then Y, mitigated by Z".
- **A (Assumption):** something believed to be true but not formally verified. Format as "We assume X" or "Until Y, Z is the case".
- **OQ (Open Question):** a decision the maintainer must make. Link to GH Issue.
- **Status:** `Open` (GH issue active) · `Accepted` (assumption ratified, no action needed) · `Deferred` (postponed to a later phase) · `Resolved` (linked issue closed; spec updated).
- **No row = no known risks/assumptions.** Empty section is fine — it means the spec author has
  thought through it and there is nothing pending. Do not invent filler.

**Where to read this in audits:** `spec-audit` Sessions 4-6 cross-reference this table against
open GitHub Issues for the spec's owning module. If a row says "Open" but the linked issue is
closed, the row is stale — update or remove it.

## Quick References

- [specs/index.md](index.md) — registry entry
```

## Writing Discipline

- Every FR/NFR/UC gets a stable ID (`FR-{AREA}-NN`) — tests reference these IDs verbatim.
- Requirements are verifiable statements, not wishes ("rejects placement when slot capacity is
  full", not "handles capacity well").
- §6 contracts are non-negotiable precision: table names, column types, method signatures.
- One initiative = one spec; split oversized specs rather than growing them unbounded.
- **Non-testable marker (short):** If a requirement cannot be code-tested (manual verification, UX, infra), mark it with a short non-testable suffix/prefix so `scan_spec_tests` does not flag it as a gap: `*` (canonical, 1 char), `~`, `!`, `-X`, `-NT`, or `X-` prefix (e.g., `FR-{AREA}-01*`, `NFR-P1~`, `FR-X-001`). Example: `NFR-U1*` for a visual UX requirement verified manually. The marker is auditability, not a license to hide testable logic.

## Quick References

- [`doc-template.md`](../doc-template.md) — shared documentation standards (Diátaxis, principles)
- `spec-writing` skill rules — section-by-section intent for each template section (agent-facing)
- [`index.md`](index.md) — spec registry and build order
