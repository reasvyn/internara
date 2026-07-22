# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-07-23 **Changes:** refactor — grouped by lifecycle phase, ordered by build dependency (34 specs)

## Description

Comprehensive feature specifications for the Internara system. Each spec defines problem
statements, goals/non-goals, user stories, functional/non-functional requirements, API/data
contracts, design decisions, and success metrics.

Specs are the **authoritative source** for feature implementation. When code and spec disagree,
update the spec first, then implement.

---

## Build Order

Specs are grouped by **lifecycle phase** (mirrors `docs/foundation/product-definition.md`)
and ordered by **dependency depth** within each phase. Build phases sequentially; specs within
a phase may be built in listed order.

```
Phase 1        Phase 2         Phase 3      Phase 4        Phase 5           Phase 6              Phase 7          Phase 8
Foundation  →  Partnerships →  Programs  →  Enrollment →  Daily Ops      →  Assessment & Eval  →  Certification  →  Reporting
(install,     (companies,     (internship   (registration, (logbook,         (rubrics,            (templates,       (grade cards,
 settings,      academics,      structure)    placement,     attendance,       scoring,             handbooks,        snapshots)
 auth,          partnerships)                 user admin)    supervision)      feedback)            credentials)
 dashboard)
```

### Phase 1 — Foundation

Infrastructure, settings, auth, dashboard shell. No business-logic dependencies.

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 1 | [Core Foundation](core-foundation.md) | Core | — |
| 2 | [System Requirements](system-requirements.md) | Core | — |
| 3 | [Module Discovery](module-discovery.md) | Core | — |
| 4 | [Logging & Error Handling](logging-and-error-handling.md) | Core | — |
| 5 | [Installation](installation.md) | Setup | #1 |
| 6 | [Setup Wizard](setup-wizard.md) | Setup | #5 |
| 7 | [Settings Infrastructure](settings-infrastructure.md) | Settings | #1 |
| 8 | [Branding, Theme & Locale](branding-theme-locale.md) | Settings | #7 |
| 9 | [Authentication](authentication.md) | Auth | #1 |
| 10 | [School Profile](school-profile.md) | Academics | #7 |
| 11 | [Dashboard](dashboard.md) | User | #9 |

### Phase 2 — Partnerships

External partners and academic structure. Depends on Phase 1 (settings, auth, school profile).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 12 | [Department Management](department-management.md) | Academics | #10 |
| 13 | [Academic Year Management](academic-year-management.md) | Academics | #10 |
| 14 | [Company Management](company-management.md) | Partners | #11, #12 |
| 15 | [Partnership Management](partnership-management.md) | Partners | #14 |

### Phase 3 — Programs

Internship structure and grouping. Depends on Phase 2 (departments, academic years, companies).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 16 | [Internship Lifecycle](internship-lifecycle.md) | Program | #13, #14 |
| 17 | [Internship Groups](internship-groups.md) | Program | #16 |

### Phase 4 — Enrollment

Student intake, placement, user administration. Depends on Phase 3 (program, groups).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 18 | [Registration](registration.md) | Enrollment | #16, #17 |
| 19 | [Placement](placement.md) | Enrollment | #18, #14 |
| 20 | [Account Application](account-application.md) | Enrollment | #18 |
| 21 | [User CRUD & Status](user-crud-and-status.md) | User / SysAdmin | #9 |
| 22 | [CSV Import & Export](csv-import-export.md) | Cross-Module | #21, #14, #12 |
| 23 | [Account Slips](account-slips.md) | User / SysAdmin | #21 |

### Phase 5 — Daily Operations

Active internship period. Depends on Phase 4 (placement active).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 24 | [Daily Activity](daily-activity.md) | Journals | #19 |
| 25 | [Supervision](supervision.md) | Journals | #19, #24 |
| 26 | [Incident](incident.md) | Incident | #19 |

### Phase 6 — Assessment & Evaluation

Scoring, feedback, coursework. Depends on Phase 4 (placement active).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 27 | [Assessment](assessment.md) | Assessment | #19 |
| 28 | [Evaluation](evaluation.md) | Evaluation | #19 |
| 29 | [Assignment](assignment.md) | Assignment | #19 |

### Phase 7 — Certification

Credentials, documents, handbooks. Depends on Phases 5–6 (assessments, evaluations complete).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 30 | [Document Templates](document-templates.md) | Document | #5 |
| 31 | [Handbooks](handbooks.md) | Document | #30 |
| 32 | [Certification](certification.md) | Certification | #27, #28 |

### Phase 8 — Reporting

Grade cards, archived snapshots. Depends on Phase 7 (certification complete).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 33 | [Reports](reports.md) | Reports | #32 |

---

## How Specs Are Used

```
spec-writing → docs/specs/{feature}.md → feature-building → code-writing → pest-testing
```

1. **`spec-writing`** skill produces a spec document in this directory
2. **`feature-building`** skill reads the spec as the primary implementation guide
3. **`code-writing`** skill implements against the spec's FR/NFR IDs
4. **`pest-testing`** skill verifies implementation matches spec requirements

---

## Spec Template

Every spec follows the 10-section format defined in `.agents/skills/spec-writing/SKILL.md`:

1. Problem Statements
2. Goals & Non-Goals
3. User Stories / Use Cases
4. Functional Requirements (FR-IDs)
5. Non-Functional Requirements (NFR-IDs)
6. API / Data Contracts
7. Design Decisions (DD-IDs)
8. Success Metrics

---

## Quick References

- `.agents/skills/spec-writing/SKILL.md` — Spec writing conventions and template
- `.agents/skills/feature-building/SKILL.md` — How specs feed into implementation
- `docs/foundation/project-requirements.md` — High-level feature specs
- `docs/modules/index.md` — Module dependency graph
