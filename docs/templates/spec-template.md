# Spec Template — Feature Specification Skeleton

## Description

The fixed section skeleton every spec in `docs/specs/` follows: the 10 numbered sections.
Specs are the requirements SSOT — implementation and tests
trace back to the requirement IDs defined here. Section-by-section content rules are documented
inline in §Writing Discipline below.

## The Skeleton

Copy everything inside the fence into `docs/specs/{ID}-{feature}.md`, where `{ID}` is a fresh
5-character alphanumeric registry key, unique under `docs/specs/` and registered in
`docs/specs/index.md` (see §Spec IDs below).

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

## Spec IDs

- **Format:** `{ID}-{feature}.md` where `{ID}` is a unique 5-character `A-Z0-9` key (e.g.
  `D2FT3-architecture.md`). Allocation order is irrelevant; uniqueness and registry entry are not.
- **Registry:** every spec is registered in `docs/specs/index.md` with the same ID.
- **Requirement IDs:** stable per spec — `FR-{AREA}-NN`, `NFR-{AREA}-NN`, `UC-{AREA}-NN`; tests and
  implementation reference them verbatim.

## How Requirements Are Verified

Verification is **inferred from the requirement rows**, not declared as a separate block in the
spec. The ID prefix carries the layer; the test file naming convention carries the rest.

A test file's name must match the spec ID and the requirement ID it verifies. Use
`describe("{SpecID}: {spec-name}")` + `it("{SpecID}-{ReqID}: {behavior}")`. The traceability
checker (`tools/scan_spec_tests.py`) flags any FR/NFR/UC row that has no matching test, so a
missing test fails the spec gate.

The four test layers and where they live:

- **Architecture tests** live in `tests/Arch/{Module}/` and assert structure — namespace
  conventions, base-class usage, no forbidden patterns. Run `tools/scan_violations.py` and
  `tools/scan_class_contracts.py` in CI; they cover the same ground.
- **Unit tests** live in `tests/Unit/{Module}/` and exercise Entities, Enums, DTOs, Policies,
  and Support classes. No database, no framework. Pure-logic.
- **Feature tests** live in `tests/Feature/{Module}/` and drive Actions (`Action::execute()`)
  end-to-end against a real database (`LazilyRefreshDatabase`). Mock only the framework boundary
  (`Http::fake()`, `Queue::fake()`, `Mail::fake()`).
- **Browser tests** live in `tests/Browser/{Module}/` and walk a real authenticated journey
  through the UI. Use sparingly; one per major flow is enough.

Mark a requirement as non-testable only when the *property* itself is uncheckable in code (visual
contrast, latency under load, manual UX). The marker (e.g. `FR-AREA-01*`) suppresses the
traceability check for that one row — it does not exempt a logic-level rule from test coverage.

## Writing Discipline

- Every FR/NFR/UC gets a stable ID (`FR-{AREA}-NN`) — tests reference these IDs verbatim.
- Requirements are verifiable statements, not wishes ("rejects placement when slot capacity is
  full", not "handles capacity well").
- §6 contracts are non-negotiable precision: table names, column types, method signatures.
- One initiative = one spec; split oversized specs rather than growing them unbounded.
- **Non-testable marker (short):** If a requirement cannot be code-tested (manual verification, UX, infra), mark it with a short non-testable suffix/prefix: `*` (canonical), `~`, `!`, `-X`, `-NT`, or `X-` prefix (e.g., `FR-{AREA}-01*`, `NFR-P1~`). The marker is auditability, not a license to hide testable logic.

## Quick References

- [`doc-template.md`](doc-template.md) — shared documentation standards (Diátaxis, principles)
- [`specs/index.md`](../specs/index.md) — spec registry and build order
