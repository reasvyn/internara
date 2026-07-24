# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-07-23 **Changes:** feat — add infrastructure specs (event system, RBAC, middleware, security headers, jobs, media, PDF) (40 specs)

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

Infrastructure, settings, auth, dashboard shell, core infrastructure. No business-logic dependencies.

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 1 | [Core Foundation](core-foundation.md) | Core | — |
| 2 | [System Requirements](system-requirements.md) | Core | — |
| 3 | [Module Discovery](module-discovery.md) | Core | — |
| 4 | [Logging & Error Handling](logging-and-error-handling.md) | Core | — |
| 5 | [Event System](event-system.md) | Core | #1 |
| 6 | [RBAC & Authorization](rbac-and-authorization.md) | Core | #1 |
| 7 | [Middleware Pipeline](middleware-pipeline.md) | Core | #1 |
| 8 | [Security Headers](security-headers.md) | Core | #7 |
| 9 | [Job & Queue Infrastructure](job-queue-infrastructure.md) | Core | #1 |
| 10 | [Installation](installation.md) | Setup | #1 |
| 11 | [Setup Wizard](setup-wizard.md) | Setup | #10 |
| 12 | [Settings Infrastructure](settings-infrastructure.md) | Settings | #1 |
| 13 | [Branding, Theme & Locale](branding-theme-locale.md) | Settings | #12 |
| 14 | [Authentication](authentication.md) | Auth | #1 |
| 15 | [School Profile](school-profile.md) | Academics | #12 |
| 16 | [Dashboard](dashboard.md) | User | #14 |

### Phase 2 — Partnerships

External partners and academic structure. Depends on Phase 1 (settings, auth, school profile).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 17 | [Department Management](department-management.md) | Academics | #15 |
| 18 | [Academic Year Management](academic-year-management.md) | Academics | #15 |
| 19 | [Company Management](company-management.md) | Partners | #16, #17 |
| 20 | [Partnership Management](partnership-management.md) | Partners | #19 |

### Phase 3 — Programs

Internship structure and grouping. Depends on Phase 2 (departments, academic years, companies).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 21 | [Internship Lifecycle](internship-lifecycle.md) | Program | #18, #19 |
| 22 | [Internship Groups](internship-groups.md) | Program | #21 |

### Phase 4 — Enrollment

Student intake, placement, user administration. Depends on Phase 3 (program, groups).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 23 | [Registration](registration.md) | Enrollment | #21, #22 |
| 24 | [Placement](placement.md) | Enrollment | #23, #19 |
| 25 | [Account Application](account-application.md) | Enrollment | #23 |
| 26 | [User CRUD & Status](user-crud-and-status.md) | User / SysAdmin | #14 |
| 27 | [CSV Import & Export](csv-import-export.md) | Cross-Module | #26, #19, #17 |
| 28 | [Account Slips](account-slips.md) | User / SysAdmin | #26 |

### Phase 5 — Daily Operations

Active internship period. Depends on Phase 4 (placement active).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 29 | [Daily Activity](daily-activity.md) | Journals | #24 |
| 30 | [Supervision](supervision.md) | Journals | #24, #29 |
| 31 | [Incident](incident.md) | Incident | #24 |

### Phase 6 — Assessment & Evaluation

Scoring, feedback, coursework. Depends on Phase 4 (placement active).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 32 | [Assessment](assessment.md) | Assessment | #24 |
| 33 | [Evaluation](evaluation.md) | Evaluation | #24 |
| 34 | [Assignment](assignment.md) | Assignment | #24 |

### Phase 7 — Certification

Credentials, documents, handbooks, media, PDF. Depends on Phases 5–6 (assessments, evaluations complete).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 35 | [Document Templates](document-templates.md) | Document | #10 |
| 36 | [Handbooks](handbooks.md) | Document | #35 |
| 37 | [Certification](certification.md) | Certification | #32, #33 |
| 38 | [File Uploads & Media](file-uploads-media.md) | Core | #1 |
| 39 | [PDF Generation](pdf-generation.md) | Core | #38 |

### Phase 8 — Reporting

Grade cards, archived snapshots. Depends on Phase 7 (certification complete).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 40 | [Reports](reports.md) | Reports | #37 |

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
