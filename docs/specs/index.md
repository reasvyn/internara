# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-07-24 **Changes:** feat — add 4 Phase 3 specs (password-reset,
> password-confirmation, account-recovery-slips, profile-management); renumber all specs

## Description

Comprehensive feature specifications for the Internara system. Each spec defines problem
statements, goals/non-goals, user stories, functional/non-functional requirements, API/data
contracts, design decisions, and success metrics.

Specs are the **authoritative source** for feature implementation. When code and spec disagree,
update the spec first, then implement.

---

## Build Order

Specs are grouped by **lifecycle phase** and ordered by **dependency depth** within each phase.
Build phases sequentially; specs within a phase may be built in listed order.

```
Phase 1         Phase 2           Phase 3            Phase 4         Phase 5
Foundation   →  Configuration  →  Identity & Auth →  Institutional →  Partnerships
(PHP/Laravel,   (install,         (auth, notify,      (departments,   (companies,
 base classes,   settings,         dashboard)          academic yrs)   partnerships)
 utilities)     branding)

Phase 6         Phase 7           Phase 8            Phase 9          Phase 10
Programs      →  Enrollment    →  Daily Ops       →  Assessment   →  Certification
(internship      (registration,    (logbook,           (rubrics,        (templates,
 structure)       placement)       attendance)         scoring)         credentials)

Phase 11         Phase 12
Reporting      → Maintenance
(grade cards,     (backup, GDPR, job queues,
 snapshots)        archiving, cleanup)
```

### Phase 1 — Foundation

Core technology, architectural base classes, and shared utilities. Everything else depends on these.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 1  | [Tech Stack](tech-stack.md) | Core | — |
| 2  | [Base Classes](base-classes.md) | Core | #1 |
| 3  | [Shared Utilities](shared-utilities.md) | Core | #1, #2 |
| 4  | [System Requirements](system-requirements.md) | Core | #1 |
| 5  | [Module Discovery](module-discovery.md) | Core | #1, #2 |
| 6  | [Logging & Error Handling](logging-and-error-handling.md) | Core | #1, #2 |
| 7  | [Event System](event-system.md) | Core | #2 |
| 8  | [RBAC & Authorization](rbac-and-authorization.md) | Core | #2 |
| 9  | [Middleware Pipeline](middleware-pipeline.md) | Core | #2 |
| 10 | [Security Headers](security-headers.md) | Core | #9 |

### Phase 2 — Configuration

System installation, settings, and visual identity. Depends on Phase 1.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 11 | [Installation](installation.md) | Setup | #1, #2 |
| 12 | [Setup Wizard](setup-wizard.md) | Setup | #11 |
| 13 | [Recovery Ecosystem](recovery-ecosystem.md) | Setup | #1, #11 |
| 14 | [Settings Infrastructure](settings-infrastructure.md) | Settings | #2 |
| 15 | [Branding, Theme & Locale](branding-theme-locale.md) | Settings | #14 |
| 16 | [School Profile](school-profile.md) | Academics | #14 |

### Phase 3 — Identity & Auth

Authentication, password management, profile, notifications, and dashboards. Depends on Phases 1–2.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 17 | [Authentication](authentication.md) | Auth | #2, #8 |
| 18 | [Notification Infrastructure](notification-infrastructure.md) | User | #2, #7 |
| 19 | [Announcement System](announcement-system.md) | SysAdmin | #14, #18 |
| 20 | [Dashboard](dashboard.md) | User | #17, #18 |
| 21 | [Password Reset](password-reset.md) | Auth | #2, #17 |
| 22 | [Password Confirmation](password-confirmation.md) | Auth | #2, #17 |
| 23 | [Account Recovery Slips](account-recovery-slips.md) | Auth | #2, #17, #42 |
| 24 | [Profile Management](profile-management.md) | User | #2, #8, #17, #42 |

### Phase 4 — Institutional

Internal academic structure. Depends on Phase 2 (school profile).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 25 | [Department Management](department-management.md) | Academics | #16 |
| 26 | [Academic Year Management](academic-year-management.md) | Academics | #16 |

### Phase 5 — Partnerships

External partners and formal collaborations. Depends on Phase 4 (departments).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 27 | [Company Management](company-management.md) | Partners | #16, #25 |
| 28 | [Partnership Management](partnership-management.md) | Partners | #27 |

### Phase 6 — Programs

Internship structure and grouping. Depends on Phases 4–5.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 29 | [Internship Lifecycle](internship-lifecycle.md) | Program | #26, #28 |
| 30 | [Internship Groups](internship-groups.md) | Program | #29 |

### Phase 7 — Enrollment

Student intake, placement, user administration. Depends on Phase 6.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 31 | [Registration](registration.md) | Enrollment | #29, #30 |
| 32 | [Placement](placement.md) | Enrollment | #31, #27 |
| 33 | [Account Application](account-application.md) | Enrollment | #31 |
| 34 | [User CRUD & Status](user-crud-and-status.md) | User | #17 |
| 35 | [CSV Import & Export](csv-import-export.md) | Enrollment | #34, #27, #25 |
| 36 | [Account Slips](account-slips.md) | User | #34 |

### Phase 8 — Daily Operations

Active internship period. Depends on Phase 7 (placement active).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 37 | [Daily Activity](daily-activity.md) | Journals | #32 |
| 38 | [Supervision](supervision.md) | Journals | #32, #37 |
| 39 | [Incident](incident.md) | Incident | #32 |

### Phase 9 — Assessment

Scoring, feedback, coursework. Depends on Phase 7 (placement active).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 40 | [Assessment](assessment.md) | Assessment | #32 |
| 41 | [Evaluation](evaluation.md) | Evaluation | #32 |
| 42 | [Assignment](assignment.md) | Assignment | #32 |

### Phase 10 — Certification

Credentials, documents, handbooks, media, PDF. Depends on Phases 8–9.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 43 | [Document Templates](document-templates.md) | Document | #11 |
| 44 | [Handbooks](handbooks.md) | Document | #43 |
| 45 | [Certification](certification.md) | Certification | #40, #41 |
| 46 | [File Uploads & Media](file-uploads-media.md) | Core | #2 |
| 47 | [PDF Generation](pdf-generation.md) | Core | #46 |

### Phase 11 — Reporting

Archived snapshots, grade cards, official correspondence, and final lifecycle records. End of PKL lifecycle.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 48 | [Reports](reports.md) | Reports | #45 |
| 49 | [Official Documents](official-documents.md) | Document | #31, #43, #48 |

### Phase 12 — Maintenance

Backup, compliance, job queues, archiving, and system cleanup. Runs continuously after Phase 11.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 50 | [Job & Queue Infrastructure](job-queue-infrastructure.md) | Core | #2, #7 |
| 51 | [Backup System](backup-system.md) | SysAdmin | #7, #8, #14, #18, #50 |
| 52 | [GDPR Compliance](gdpr-compliance.md) | SysAdmin | #14, #34 |
| 53 | [System Maintenance](system-maintenance.md) | SysAdmin | #6, #8, #50, #51 |

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
10. (Quick References)

---

## Quick References

- `.agents/skills/spec-writing/SKILL.md` — Spec writing conventions and template
- `.agents/skills/feature-building/SKILL.md` — How specs feed into implementation
- `docs/foundation/project-requirements.md` — High-level feature specs
- `docs/modules/index.md` — Module dependency graph
