# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-08-15 **Changes:** add — Dummy Data spec (#56, Maintenance); add —
> Conditional Deployment spec (#55, Maintenance); Foundation specs (#1–#11) verified against the
> rewritten Core suite (266 tests); spec↔code deviations reconciled (security-headers,
> shared-utilities)

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
| 11 | [Module Manager & Service](module-manager.md) | Core | #2, #3, #5 |

### Phase 2 — Configuration

System installation, settings, and visual identity. Depends on Phase 1.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 12 | [Installation](installation.md) | Setup | #1, #2 |
| 13 | [Setup Wizard](setup-wizard.md) | Setup | #12 |
| 14 | [Recovery Ecosystem](recovery-ecosystem.md) | Setup | #1, #12 |
| 15 | [Settings Infrastructure](settings-infrastructure.md) | Settings | #2 |
| 16 | [Branding, Theme & Locale](branding-theme-locale.md) | Settings | #15 |
| 17 | [School Profile](school-profile.md) | Academics | #15 |

### Phase 3 — Identity & Auth

Authentication, password management, profile, notifications, and dashboards. Depends on Phases 1–2.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 18 | [Authentication](authentication.md) | Auth | #2, #8 |
| 19 | [Notification Infrastructure](notification-infrastructure.md) | User | #2, #7 |
| 20 | [Announcement System](announcement-system.md) | SysAdmin | #15, #19 |
| 21 | [Dashboard](dashboard.md) | User | #18, #19 |
| 22 | [Password Reset](password-reset.md) | Auth | #2, #18 |
| 23 | [Password Confirmation](password-confirmation.md) | Auth | #2, #18 |
| 24 | [Account Recovery Slips](account-recovery-slips.md) | Auth | #2, #18, #47 |
| 25 | [Profile Management](profile-management.md) | User | #2, #8, #18, #47 |

### Phase 4 — Institutional

Internal academic structure. Depends on Phase 2 (school profile).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 26 | [Department Management](department-management.md) | Academics | #17 |
| 27 | [Academic Year Management](academic-year-management.md) | Academics | #17 |

### Phase 5 — Partnerships

External partners and formal collaborations. Depends on Phase 4 (departments).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 28 | [Company Management](company-management.md) | Partners | #17, #26 |
| 29 | [Partnership Management](partnership-management.md) | Partners | #28 |

### Phase 6 — Programs

Internship structure and grouping. Depends on Phases 4–5.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 30 | [Internship Lifecycle](internship-lifecycle.md) | Program | #27, #29 |
| 31 | [Internship Groups](internship-groups.md) | Program | #30 |

### Phase 7 — Enrollment

Student intake, placement, user administration. Depends on Phase 6.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 32 | [Registration](registration.md) | Enrollment | #30, #31 |
| 33 | [Placement](placement.md) | Enrollment | #32, #28 |
| 34 | [Account Application](account-application.md) | Enrollment | #32 |
| 35 | [User CRUD & Status](user-crud-and-status.md) | User | #18 |
| 36 | [CSV Import & Export](csv-import-export.md) | Enrollment | #35, #28, #26 |
| 37 | [Account Slips](account-slips.md) | User | #35 |

### Phase 8 — Daily Operations

Active internship period. Depends on Phase 7 (placement active).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 38 | [Daily Activity](daily-activity.md) | Journals | #33 |
| 39 | [Supervision](supervision.md) | Journals | #33, #38 |
| 40 | [Incident](incident.md) | Incident | #33 |

### Phase 9 — Assessment

Scoring, feedback, coursework. Depends on Phase 7 (placement active).

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 41 | [Assessment](assessment.md) | Assessment | #33 |
| 42 | [Evaluation](evaluation.md) | Evaluation | #33 |
| 43 | [Assignment](assignment.md) | Assignment | #33 |

### Phase 10 — Certification

Credentials, documents, handbooks, media, PDF. Depends on Phases 8–9.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 44 | [Document Templates](document-templates.md) | Document | #12 |
| 45 | [Handbooks](handbooks.md) | Document | #44 |
| 46 | [Certification](certification.md) | Certification | #41, #42 |
| 47 | [File Uploads & Media](file-uploads-media.md) | Core | #2 |
| 48 | [PDF Generation](pdf-generation.md) | Core | #47 |

### Phase 11 — Reporting

Archived snapshots, grade cards, official correspondence, and final lifecycle records. End of PKL lifecycle.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 49 | [Reports](reports.md) | Reports | #46 |
| 50 | [Official Documents](official-documents.md) | Document | #32, #44, #49 |

### Phase 12 — Maintenance

Backup, compliance, job queues, archiving, system cleanup, and demo/test data provisioning. Runs continuously after Phase 11.

| #  | Spec | Module | Depends On |
| -- | ---- | ------ | ---------- |
| 51 | [Job & Queue Infrastructure](job-queue-infrastructure.md) | Core | #2, #7 |
| 52 | [Backup System](backup-system.md) | SysAdmin | #7, #8, #15, #19, #51 |
| 53 | [GDPR Compliance](gdpr-compliance.md) | SysAdmin | #15, #35 |
| 54 | [System Maintenance](system-maintenance.md) | SysAdmin | #6, #8, #51, #52 |
| 55 | [Conditional Deployment](deployment.md) | Core | #4, #12, #51, #54 |
| 56 | [Dummy Data](dummy-data.md) | Core | #8, #26, #27, #28, #29, #30, #31, #32, #33, #38, #39, #40, #41, #42, #43, #46, #49 |

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
