# Requirement ID Conventions — FR/NFR/UC and Area Codes

Requirement IDs are the traceability spine of the whole project: tests carry them, `spec-audit`
verifies implementation against them, and issue-writing files findings against them. A requirement
without a stable ID is untraceable; an ID colliding with another requirement creates false
test/audit matches. The conventions below keep IDs scannable, unique, and cross-referenceable.

---

## Prefix Conventions

| Prefix | Category | Example |
| ------ | ------- | ------- |
| `PS-` | Problem Statement | `PS-1` |
| `G-` | Goal | `G1` |
| `NG-` | Non-Goal | `NG1` |
| `UC-` | Use Case | `UC-1` |
| `FR-` | Functional Req | `FR-A1` (A=audit) |
| `NFR-` | Non-Functional | `NFR-S1` (S=security) |
| `DD-` | Design Decision | `DD-1` |

**Why each prefix is distinct:** each maps to a section and a test/audit behavior. `FR-*` is asserted
by feature tests; `NFR-*` only when testable at the code level; `UC-*` maps to end-to-end flows. If a
spec used `R1` for a requirement, nothing distinguishes functional from non-functional nor holds a
stable handle.

---

## Area Codes for FR-/NFR-

When a feature has multiple sub-areas, add a single-letter area code after the prefix:

| Area Code | Category | Example |
| --------- | -------- | ------- |
| `A` | Audit/Check | `FR-A1` |
| `P` | Provisioning | `FR-P1` |
| `T` | Token | `FR-T1` |
| `W` | Wizard/UI | `FR-W1` |
| `F` | Finalization | `FR-F1` |
| `AC` | Access Control | `FR-AC1` |
| `C` | CLI | `FR-C1` |
| `S` | Security | `NFR-S1` |
| `P` | Performance | `NFR-P1` |
| `R` | Reliability | `NFR-R1` |
| `U` | Usability | `NFR-U1` |
| `M` | Maintainability | `NFR-M1` |

**Why area codes exist:** they make requirements scannable by concern and cross-referenceable —
`FR-AC1` instantly reads "the first access-control functional requirement". Without them, `FR-1`,
`FR-2`, ... gives the reader no semantic signal and collisions across sub-areas confuse audit
grouping.

**How to apply:**

- Only use an area code when the feature genuinely has multiple sub-areas; a single-area spec uses
  `FR-1`, `FR-2`, ...
- The `AC` code covers access control / RBAC; don't reuse a bare letter that is ambiguous with an
  existing code.
- NFR codes follow the standard NFR categories (Security, Performance, Reliability, Usability,
  Maintainability) so the category is visible at a glance.

**Anti-patterns to avoid:** reusing the same `FR-A1` in two different specs (IDs are unique *within*
a spec; the spec ID prefixes the requirement in tests — see Spec IDs below); inventing non-listed area
codes without documenting them; mixing `FR-` and `NFR-` under the same code semantics.

---

## Requirement ID vs Spec ID

Requirement IDs (`FR-*`, `NFR-*`, `UC-*`, `DD-*`) are **internal to the spec** and remain unchanged
throughout the spec lifecycle. The **spec ID** (the 5-character alphanumeric `XXXXX` in the filename
and metadata) is the external identity. Tests combine both:

- Test description format: `{SpecID}-{ReqID}: Test description...` — e.g.
  `3UOZP-FR-C10: refuses to seed in production`
- Grouped under: `describe("{SpecID}: Test description...")`

This gives every test a globally-unique spec+requirement handle.

---

## Verification / Detection

- Every functional requirement has a unique ID (`FR-{area}{number}`)
- Every non-functional requirement has a unique ID (`NFR-{category}{number}`)
- Every design decision has a unique ID (`DD-{number}`)
- No duplicate IDs within a spec; area codes are used consistently and documented
- Tests reference requirements via `{SpecID}-{ReqID}:` — `spec-audit` Area 4 cross-checks test names
  against FR/NFR IDs; an ID mismatch is a drift finding
