# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-07-24 **Changes:** feat — split Phase 2 into Institutional + Partnerships,
> add §9 Roadmap sections (41 specs, 9 phases)

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
Phase 1        Phase 2          Phase 3         Phase 4      Phase 5        Phase 6           Phase 7          Phase 8           Phase 9
Foundation  →  Institutional →  Partnerships →  Programs  →  Enrollment →  Daily Ops     →  Assessment   →  Certification  →  Reporting
(install,     (departments,    (companies,      (internship  (registration, (logbook,        (rubrics,        (templates,        (grade cards,
 settings,      academic yrs)   partnerships)    structure)    placement,     attendance,      scoring,         handbooks,         snapshots)
 auth,                                                     user admin)    supervision)     feedback)        credentials)
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
| 12 | [Recovery Ecosystem](recovery-ecosystem.md) | Auth / Setup | #1, #10 |
| 13 | [Settings Infrastructure](settings-infrastructure.md) | Settings | #1 |
| 14 | [Branding, Theme & Locale](branding-theme-locale.md) | Settings | #13 |
| 15 | [Authentication](authentication.md) | Auth | #1 |
| 16 | [School Profile](school-profile.md) | Academics | #13 |
| 17 | [Dashboard](dashboard.md) | User | #15 |

### Phase 2 — Institutional

Internal academic structure. Depends on Phase 1 (school profile).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 18 | [Department Management](department-management.md) | Academics | #16 |
| 19 | [Academic Year Management](academic-year-management.md) | Academics | #16 |

### Phase 3 — Partnerships

External partners and formal collaborations. Depends on Phase 1 (school profile) and Phase 2 (departments).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 20 | [Company Management](company-management.md) | Partners | #16, #18 |
| 21 | [Partnership Management](partnership-management.md) | Partners | #20 |

### Phase 4 — Programs

Internship structure and grouping. Depends on Phase 2 (academic years) and Phase 3 (partnerships).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 22 | [Internship Lifecycle](internship-lifecycle.md) | Program | #19, #21 |
| 23 | [Internship Groups](internship-groups.md) | Program | #22 |

### Phase 5 — Enrollment

Student intake, placement, user administration. Depends on Phase 4 (program, groups).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 24 | [Registration](registration.md) | Enrollment | #22, #23 |
| 25 | [Placement](placement.md) | Enrollment | #24, #20 |
| 26 | [Account Application](account-application.md) | Enrollment | #24 |
| 27 | [User CRUD & Status](user-crud-and-status.md) | User / SysAdmin | #15 |
| 28 | [CSV Import & Export](csv-import-export.md) | Cross-Module | #27, #20, #18 |
| 29 | [Account Slips](account-slips.md) | User / SysAdmin | #27 |

### Phase 6 — Daily Operations

Active internship period. Depends on Phase 5 (placement active).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 30 | [Daily Activity](daily-activity.md) | Journals | #25 |
| 31 | [Supervision](supervision.md) | Journals | #25, #30 |
| 32 | [Incident](incident.md) | Incident | #25 |

### Phase 7 — Assessment

Scoring, feedback, coursework. Depends on Phase 5 (placement active).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 33 | [Assessment](assessment.md) | Assessment | #25 |
| 34 | [Evaluation](evaluation.md) | Evaluation | #25 |
| 35 | [Assignment](assignment.md) | Assignment | #25 |

### Phase 8 — Certification

Credentials, documents, handbooks, media, PDF. Depends on Phases 6–7 (assessments, evaluations complete).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 36 | [Document Templates](document-templates.md) | Document | #10 |
| 37 | [Handbooks](handbooks.md) | Document | #36 |
| 38 | [Certification](certification.md) | Certification | #33, #34 |
| 39 | [File Uploads & Media](file-uploads-media.md) | Core | #1 |
| 40 | [PDF Generation](pdf-generation.md) | Core | #39 |

### Phase 9 — Reporting

Grade cards, archived snapshots. Depends on Phase 8 (certification complete).

| # | Spec | Module | Depends On |
| - | ---- | ------ | ---------- |
| 41 | [Reports](reports.md) | Reports | #38 |

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

Every spec follows the 11-section format defined in `.agents/skills/spec-writing/SKILL.md`:

1. Problem Statements
2. Goals & Non-Goals
3. User Stories / Use Cases
4. Functional Requirements (FR-IDs)
5. Non-Functional Requirements (NFR-IDs)
6. API / Data Contracts
7. Design Decisions (DD-IDs)
8. Success Metrics
9. Roadmap (prerequisites, build guide, next steps)

---

## Quick References

- `.agents/skills/spec-writing/SKILL.md` — Spec writing conventions and template
- `.agents/skills/feature-building/SKILL.md` — How specs feed into implementation
- `docs/foundation/project-requirements.md` — High-level feature specs
- `docs/modules/index.md` — Module dependency graph
