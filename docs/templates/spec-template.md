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

Goals and non-goals are two bullet lists (not a table), each item with its reason inline:

### Goals

- **{goal}** — {what it is}. *Why:* {reason}.

### Non-Goals

- **{excluded scope}**. *Why:* {reason}.

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). The table format matches
FR/NFR/DD; fill `Layer` / `Status` only when the UC has a verifiable, code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-{SCOPE}-{XXX} | {actor: action, outcome} | P0/P1/P2/P3 | U/F/B/A/— | Planned/Partial/Full/— |

### 3.1 {Group Title}

Group topics with `3.1`, `3.2`, …; each UC belongs to exactly one group.

#### UC-{SCOPE}-{XXX} — {short title}

{Detail sub-section — content as needed: preconditions, flow, postconditions, actor(s),
governing-spec links. No set format. Every requirement row gets one, right after the table.}

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer that will verify it (legend below),
so testers know where to look. `Status` tracks implementation of this specific requirement
(independent of the spec's overall status in `docs/specs/index.md`).

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-{SCOPE}-{XXX} | {verifiable statement} | P0/P1/P2/P3 | U/F/B/A | Planned/Partial/Full |

**Layer legend:** `U` = Unit (Entity/DTO/Enum/Policy/Support, no DB), `F` = Feature
(Action/Livewire/Console, real DB), `B` = Browser (E2E journey), `A` = Arch (structure/contracts).

**Status legend:** `Planned` = not started, `Partial` = in progress, `Full` = implemented & verified.

### 4.1 {Group Title}

Group topics with `4.1`, `4.2`, …; each FR belongs to exactly one group.

#### FR-{SCOPE}-{XXX} — {short title}

{Detail sub-section — content as needed: acceptance criteria, edge cases, cross-references.
No set format. Every requirement row gets one, right after the table.}

## 5. Non-Functional Requirements

A Non-Functional Requirement is a measurable constraint on the system (performance, security,
reliability). `Target` is the concrete number/SLO. `Priority` and `Status` behave as in §4; NFRs
have an extra `Target` column.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-{SCOPE}-{XXX} | {measurable constraint} | {number} | P0/P1/P2/P3 | U/F/B/A | Planned/Partial/Full |

### 5.1 {Group Title}

Group topics with `5.1`, `5.2`, …; each NFR belongs to exactly one group.

#### NFR-{SCOPE}-{XXX} — {short title}

{Detail sub-section — content as needed: measurement method, verification approach, exceptions.
No set format. Every requirement row gets one, right after the table.}

## 6. API / Data Contracts

{Exact schemas, signatures, payloads — precise enough to implement against without asking.}

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). Only fill the `Layer` / `Status`
columns when a specific DD has a verifiable, code-testable consequence — otherwise leave them as
`—`. The table format is identical to UC/FR/NFR for consistency.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-{SCOPE}-{XXX} | {design decision & rationale} | P0/P1/P2/P3 | U/F/B/A/— | Planned/Partial/Full/— |

### 7.1 {Group Title}

Group topics with `7.1`, `7.2`, …; each DD belongs to exactly one group.

#### DD-{SCOPE}-{XXX} — {short title}

{Detail sub-section — content as needed: the decision, rationale, and trade-off.
No set format. Every requirement row gets one, right after the table.}

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
- **Requirement IDs:** stable per spec — format `{FR|NFR|UC|DD}-{SCOPE}-{XXX}` (e.g.
  `FR-AUTH-001`, `NFR-AUTH-002`, `UC-AUTH-003`); tests and implementation reference them verbatim.
  - `{FR|NFR|UC|DD}` — the requirement/decision type (Functional / Non-Functional / Use Case /
    Design Decision)
  - `{SCOPE}` — the requirement's scope/area code, **not** the spec ID — a short functional area
    (e.g. `AUTH`, `USR`, `SEED`), uppercase; 1–8 alphanumeric chars
  - `{XXX}` — a 3-digit running number (001, 002, 003, …) identifying the order of the
    requirement within its scope, e.g. `FR-AUTH-001`, `NFR-AUTH-002`
  - Non-testable marker suffix (`*`, `~`, `!`, `-NT`, `-X`) may append, e.g. `FR-AUTH-001*`.

## How Requirements Are Verified

Verification is **inferred from the requirement rows**, not declared as a separate block in the
spec. The `Layer` column declares where each verified row is tested; the test file naming carries
the rest.

A test file's name must match the spec ID and the requirement ID it verifies. Use
`describe("{SpecID}: {spec-name}")` + `it("{SpecID}-{ReqID}: {behavior}")`. The traceability
checker (`tools/scan_spec_tests.py`) flags any FR/NFR/UC row that has no matching test, so a
missing test fails the spec gate. UC and DD rows are testable but optional — leave `Layer`/`Status`
as `—` when not code-verified.

The four test layers and where they live:

| Layer code | Directory | What it asserts | DB? |
|------------|-----------|-----------------|-----|
| `A` Arch | `tests/Arch/{Module}/` | Structure — namespace, base-class usage, no forbidden patterns. Covered also by `tools/scan_violations.py` + `tools/scan_class_contracts.py` in CI. | No |
| `U` Unit | `tests/Unit/{Module}/` | Entities, Enums, DTOs, Policies, Support. Pure logic. | No |
| `F` Feature | `tests/Feature/{Module}/` | `Action::execute()` end-to-end against a real DB (`LazilyRefreshDatabase`). Mock only framework boundary (`Http::fake()`, `Queue::fake()`, `Mail::fake()`). | Yes |
| `B` Browser | `tests/Browser/{Module}/` | A real authenticated journey through the UI. One per major flow. | Yes |

The `Layer` column in §3/§4/§5/§7 must match the tests you write — a requirement labeled `U` is
verified by a Unit test, `F` by a Feature test, etc.

Mark a requirement as non-testable only when the *property* itself is uncheckable in code (visual
contrast, latency under load, manual UX). The marker (e.g. `FR-{SCOPE}-{XXX}*`) suppresses the
traceability check for that one row — it does not exempt a logic-level rule from test coverage.

## Writing Discipline

- Every FR/NFR/UC (and optionally DD) gets a stable ID (`{FR|NFR|UC|DD}-{SCOPE}-{XXX}`) — tests
  reference these IDs verbatim. The four tables (UC/FR/NFR/DD) share the same column format
  `ID | Requirement | Priority | Layer | Status`; NFR adds a `Target` column.
- Requirements are verifiable statements, not wishes ("rejects placement when slot capacity is
  full", not "handles capacity well").
- §6 contracts are non-negotiable precision: table names, column types, method signatures.
- One initiative = one spec; split oversized specs rather than growing them unbounded.
- **Non-testable marker (short):** If a requirement cannot be code-tested (manual verification, UX, infra), mark it with a short non-testable suffix/prefix: `*` (canonical), `~`, `!`, `-X`, `-NT`, or `X-` prefix (e.g., `FR-{SCOPE}-001*`, `NFR-{SCOPE}-002~`). The marker is auditability, not a license to hide testable logic.
- **Status consistency:** `Status` is always one of `Planned` / `Partial` / `Full`. It tracks the
  *requirement itself*, independent of the spec's overall status in `docs/specs/index.md`.

## Quick References

- [`doc-template.md`](doc-template.md) — shared documentation standards (Diátaxis, principles)
- [`specs/index.md`](../specs/index.md) — spec registry and build order
