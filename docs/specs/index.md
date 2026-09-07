# Feature Specifications — `docs/specs/`

## Description

Comprehensive feature specifications for the Internara system. Each spec defines problem
statements, goals/non-goals, user stories, functional/non-functional requirements, API/data
contracts, design decisions, and success metrics.

Specs are the **authoritative source** for feature implementation. When code and spec disagree,
update the spec first, then implement.

---

## Status Legend

Simplified implementation status for quick reference:

| Status | Meaning |
| ------ | ------ |
| [**Planned**](#status-legend) | Not yet started; spec exists, no implementation code |
| [**Partial**](#status-legend) | Work in progress; some requirements implemented |
| [**Full**](#status-legend) | Implemented and verified; ready for production |

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

### Spec-Zero — Initial Specification

The initial project specification. A blanket spec-zero over all phase specs: product scope,
lifecycle, module landscape, role model, global (cross-cutting) requirements every feature spec
inherits, and the project-level roadmap. No dependency — everything depends on it.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| QLHDO | [Internara Project (Initial Specification)](QLHDO-project-initialization.md) | Core | — | Partial |

### Phase 1 — Foundation

Core technology, architectural base classes, and shared utilities. Everything else depends on these.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| D2FT3 | [Architecture Design](D2FT3-architecture.md) | Core | — | Full |
| FB792 | [Tech Stack](FB792-tech-stack.md) | Core | D2FT3 | Full |
| ZT6VS | [Core & Infrastructure Services](ZT6VS-core-infra-services.md) | Core | FB792 | Full |
| SE5Q9 | [Base Classes](SE5Q9-base-classes.md) | Core | FB792, ZT6VS | Full |
| C8F0D | [Shared Utilities](C8F0D-shared-utilities.md) | Core | FB792, SE5Q9 | Full |
| J68GZ | [System Requirements](J68GZ-system-requirements.md) | Core | FB792 | Full |
| I1BCV | [Module Discovery](I1BCV-module-discovery.md) | Core | FB792, SE5Q9 | Full |
| 89SRA | [Logging & Error Handling](89SRA-logging-and-error-handling.md) | Core | FB792, SE5Q9 | Full |
| NUCY3 | [Event System](NUCY3-event-system.md) | Core | SE5Q9 | Full |
| T4B26 | [RBAC & Authorization](T4B26-rbac-and-authorization.md) | Core | SE5Q9 | Full |
| 2CF4Y | [Middleware Pipeline](2CF4Y-middleware-pipeline.md) | Core | SE5Q9 | Full |
| 1PGM4 | [Security Headers](1PGM4-security-headers.md) | Core | 2CF4Y | Full |
| B114U | [Module Manager & Service](B114U-module-manager.md) | Core | SE5Q9, C8F0D, I1BCV | Full |

### Phase 2 — Configuration

System installation, settings, and visual identity. Depends on Phase 1.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| 8NZAU | [Installation](8NZAU-installation.md) | Setup | FB792, SE5Q9 | Full |
| VEJCX | [Setup Wizard](VEJCX-setup-wizard.md) | Setup | 8NZAU | Full |
| C9ZB6 | [Recovery Ecosystem](C9ZB6-recovery-ecosystem.md) | Setup | FB792, 8NZAU | Full |
| YB22J | [Settings Infrastructure](YB22J-settings-infrastructure.md) | Settings | SE5Q9 | Full |
| 52O1I | [Branding, Theme & Locale](52O1I-branding-theme-locale.md) | Settings | YB22J | Full |
| 81SMS | [School Profile](81SMS-school-profile.md) | Academics | YB22J | Full |

### Phase 3 — Identity & Auth

Authentication, password management, profile, notifications, and dashboards. Depends on Phases 1–2.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| K8HP1 | [Public Landing Page](K8HP1-public-landing-page.md) | User | 52O1I, 8XMYS, MBB5R | Full |
| 8XMYS | [Layout & UI System](8XMYS-layout-and-ui-system.md) | Core | SE5Q9, I1BCV, YB22J, 52O1I | Partial |
| YB7RG | [Authentication](YB7RG-authentication.md) | Auth | SE5Q9, T4B26 | Full |
| TXR2H | [Notification Infrastructure](TXR2H-notification-infrastructure.md) | User | SE5Q9, NUCY3 | Full |
| 3S55V | [Announcement System](3S55V-announcement-system.md) | SysAdmin | YB22J, TXR2H | Full |
| CKKZC | [Dashboard](CKKZC-dashboard.md) | User | YB7RG, TXR2H | Full |
| D9TKW | [Password Reset](D9TKW-password-reset.md) | Auth | SE5Q9, YB7RG | Full |
| CQVSK | [Password Confirmation](CQVSK-password-confirmation.md) | Auth | SE5Q9, YB7RG | Full |
| SHQ1J | [Account Recovery Slips](SHQ1J-account-recovery-slips.md) | Auth | SE5Q9, YB7RG, WQGTP | Full |
| OCEMS | [Profile Management](OCEMS-profile-management.md) | User | SE5Q9, T4B26, YB7RG, WQGTP | Full |

### Phase 4 — Institutional

Internal academic structure. Depends on Phase 2 (school profile).

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| 4HWSB | [Department Management](4HWSB-department-management.md) | Academics | 81SMS | Full |
| XW6F5 | [Academic Year Management](XW6F5-academic-year-management.md) | Academics | 81SMS | Full |

### Phase 5 — Partnerships

External partners and formal collaborations. Depends on Phase 4 (departments).

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| XI3LB | [Company Management](XI3LB-company-management.md) | Partners | 81SMS, 4HWSB | Full |
| NTHQA | [Partnership Management](NTHQA-partnership-management.md) | Partners | XI3LB | Full |

### Phase 6 — Programs

Internship structure and grouping. Depends on Phases 4–5.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| 7C5WM | [Internship Lifecycle](7C5WM-internship-lifecycle.md) | Program | XW6F5, NTHQA | Full |
| IT0OE | [Internship Groups](IT0OE-internship-groups.md) | Program | 7C5WM | Full |

### Phase 7 — Enrollment

Student intake, placement, user administration. Depends on Phase 6.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| MBB5R | [Registration](MBB5R-registration.md) | Enrollment | 7C5WM, IT0OE | Full |
| J9GBH | [Placement](J9GBH-placement.md) | Enrollment | MBB5R, XI3LB | Full |
| 920SO | [Account Application](920SO-account-application.md) | Enrollment | MBB5R | Full |
| 95EVB | [User CRUD & Status](95EVB-user-crud-and-status.md) | User | YB7RG | Full |
| O2KCR | [CSV Import & Export](O2KCR-csv-import-export.md) | Enrollment | 95EVB, XI3LB, 4HWSB | Full |
| EWCZ0 | [Account Slips](EWCZ0-account-slips.md) | User | 95EVB | Full |

### Phase 8 — Daily Operations

Active internship period. Depends on Phase 7 (placement active).

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| 1KSWL | [Daily Activity](1KSWL-daily-activity.md) | Journals | J9GBH | Full |
| 2EHSE | [Supervision](2EHSE-supervision.md) | Journals | J9GBH, 1KSWL | Full |
| 3RU9S | [Incident](3RU9S-incident.md) | Incident | J9GBH | Full |

### Phase 9 — Assessment

Scoring, feedback, coursework. Depends on Phase 7 (placement active).

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| ARDA6 | [Assessment](ARDA6-assessment.md) | Assessment | J9GBH | Full |
| AXKZW | [Evaluation](AXKZW-evaluation.md) | Evaluation | J9GBH | Full |
| T657Z | [Assignment](T657Z-assignment.md) | Assignment | J9GBH | Full |

### Phase 10 — Certification

Credentials, documents, handbooks, media, PDF. Depends on Phases 8–9.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| PKYX6 | [Document Templates](PKYX6-document-templates.md) | Document | 8NZAU | Full |
| ZUFG8 | [Handbooks](ZUFG8-handbooks.md) | Document | PKYX6 | Full |
| J0M04 | [Certification](J0M04-certification.md) | Certification | ARDA6, AXKZW | Full |
| WQGTP | [File Uploads & Media](WQGTP-file-uploads-media.md) | Core | SE5Q9 | Full |
| 7UB7S | [PDF Generation](7UB7S-pdf-generation.md) | Core | WQGTP | Full |

### Phase 11 — Reporting

Archived snapshots, grade cards, official correspondence, and final lifecycle records. End of PKL lifecycle.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| R6BMW | [Reports](R6BMW-reports.md) | Reports | J0M04 | Full |
| 7H5D6 | [Official Documents](7H5D6-official-documents.md) | Document | MBB5R, PKYX6, R6BMW | Full |

### Phase 12 — Maintenance

Backup, compliance, job queues, archiving, system cleanup, and demo/test data provisioning. Runs continuously after Phase 11.

| ID | Spec | Module | Depends On | Status |
| -- | ---- | ------ | ---------- | ------ |
| 8FVZA | [Job & Queue Infrastructure](8FVZA-job-queue-infrastructure.md) | Core | SE5Q9, NUCY3 | Full |
| HBXCI | [Backup System](HBXCI-backup-system.md) | SysAdmin | NUCY3, T4B26, YB22J, TXR2H, 8FVZA | Full |
| 7HNCF | [GDPR Compliance](7HNCF-gdpr-compliance.md) | SysAdmin | YB22J, 95EVB | Full |
| E1MSJ | [System Maintenance](E1MSJ-system-maintenance.md) | SysAdmin | 89SRA, T4B26, 8FVZA, HBXCI | Full |
| 9YUUK | [Data Archiving & Retention](9YUUK-data-archiving.md) | SysAdmin | E1MSJ, HBXCI, 7HNCF, 8FVZA, YB22J, R6BMW | Planned |
| 06IB6 | [Conditional Deployment](06IB6-deployment.md) | Core | J68GZ, 8NZAU, 8FVZA, E1MSJ | Full |
| 3UOZP | [Dummy Data](3UOZP-dummy-data.md) | Core | T4B26, 4HWSB, XW6F5, XI3LB, NTHQA, 7C5WM, IT0OE, MBB5R, J9GBH, 1KSWL, 2EHSE, 3RU9S, ARDA6, AXKZW, T657Z, J0M04, R6BMW | Full |

---

## Spec Status

Implementation status is shown per phase as a **Status Legend** (Planned / Full) above
each phase table in this file. The legacy `implementation-matrix.md` (priority-ordered matrix with a
detailed status-usage legend) was removed — per-spec status and coverage live in the phase tables
here plus the spec files themselves.

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

Every spec follows the 12-section format defined by the `spec-writing` skill
(see [`spec-template.md`](../templates/spec-template.md) for the human-facing skeleton):

1. Description
2. Problem Statements
3. Goals & Non-Goals
4. User Stories / Use Cases
5. Functional Requirements (FR-IDs)
6. Non-Functional Requirements (NFR-IDs)
7. API / Data Contracts
8. Design Decisions (DD-IDs)
9. Success Metrics
10. Risks & Assumptions (R-/A-/OQ-IDs; links to GitHub Issues)
11. Roadmap (prerequisites, build guide, next steps)
12. Quick References

---

## Quick References

- `spec-writing` skill — Spec writing conventions and template
- `feature-building` skill — How specs feed into implementation
- §Status Legend above each phase table in this file — Implementation status (Planned / Full)
- `docs/specs/QLHDO-project-initialization.md` — High-level feature specs
- `docs/refs/modules/index.md` — Module dependency graph
