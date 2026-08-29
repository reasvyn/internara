# Spec Template — The 11-Section Structure

Every spec document in `docs/specs/` follows the same 11-section template. The template is the
contract that makes specs comparable, machine-scannable (`spec-audit` checks sections by number), and
implementation-ready. A spec missing a section — or with a section in the wrong order — fails the
spec-audit completeness gate and leaves implementers guessing.

---

## Why a fixed template exists

- **Comparability:** any two specs can be diffed section-by-section.
- **Auditability:** `spec-audit` Area 6 checks §1-§9 + metadata by number; a spec that shuffles
  sections breaks the auditor's mapping.
- **Implementation-readiness:** the template forces the author to think through problems, goals,
  stories, requirements, contracts, decisions, metrics, and roadmap — none can be silently omitted.
- **Traceability:** requirement IDs live in fixed sections, so tests can reference `FR-*` in §4 and
  auditors can verify them.

---

## The 11-Section Template

```markdown
# Feature Name — Subtitle/Scope

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

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [{name}](link.md) | {specific artifact, class, or state} |

{If no prerequisites: "No prerequisites — this is a foundational spec."}

### Build Guide
{1-2 sentence narrative: what was built by this spec, and what the developer
should build next. Use active voice. Name specific classes, methods, or config
keys that connect to the next spec.}

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [{name}](link.md) | {what artifact flows and how} |

---

## Quick References

- `{path}` — {what's there}
```

---

## Section-by-Section Intent

| Section | Purpose | Minimum content |
|---------|---------|-----------------|
| Description | Scope and existence in 1-3 sentences | Why this spec exists |
| §1 Problem Statements | The pain to solve, and why ignoring it is costly | ≥1 `PS-N` with title + consequences |
| §2 Goals & Non-Goals | Measurable intent + explicit out-of-scope | `G1..`, `NG1..` tables |
| §3 User Stories / Use Cases | Who does what and the flows | `UC-N` with Actor/Preconditions/Flow/Postconditions |
| §4 Functional Requirements | Testable behavior, unique IDs | `FR-*` table, atomic, must/should/may |
| §5 Non-Functional Requirements | Performance/security/reliability/usability/maintainability | `NFR-*` table (even "none applicable") |
| §6 API / Data Contracts | Exact signatures, shapes, routes, config | Class/method signatures, DTOs, routes, config arrays |
| §7 Design Decisions | Non-obvious choices and their rationale | ≥1 `DD-N` with Decision/Rationale/Trade-off |
| §8 Success Metrics | Measurable targets, positive and negative | Numbers with units, achievement criteria |
| §9 Roadmap | Dependency graph position | Prerequisites table, Build Guide, Next Steps |
| Quick References | Navigation to related artifacts | File paths + related specs |

---

## Writing Discipline

- **Use `edit` for existing files, `write` only for new files** — surgery over rewrite (Edit Policy).
- **Every statement must be verifiable or actionable** — no prose that cannot be tested or acted on.
- **Reference source code with file paths where implementation exists** — a spec that cites an actual
  class gives `spec-audit` a path to verify.
- **Cross-reference related docs instead of duplicating content** — never copy a contract into two
  specs; link it.

---

## Verification / Detection

A spec is complete when:

- All sections are present and populated (1-9 + Quick References)
- Every FR/NFR/DD has a unique ID; §9 has Prerequisites (with specific artifacts), Build Guide
  (1-2 sentences), and Next Steps
- All cross-references resolve to existing files
- Metadata `> **Spec ID:** XXXXX` matches the filename (`{ID}-{feature}.md`)
- No duplicate content across sections (cross-reference instead)

`spec-audit` Area 6 (§6.5 Checklist in the spec-audit skill) automates this completeness check per
spec — run it after writing any spec.
