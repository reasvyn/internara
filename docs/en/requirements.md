# Project Requirements & User Flow

> **⚠️ WARNING: This document is a binding specification.**
> Any modification to the requirements, role matrix, or phase definitions must be approved through formal review. Unauthorized changes may cause misalignment between implementation, tests, and documentation.

---

This document defines the complete project requirements and end-to-end user flow of the Internara internship management system — from installation and setup through to evaluation, period closing, and archival. It follows industry-standard internship lifecycle phases (Plan, Execute, Monitor, Close) adapted for vocational education programs.

---

## Table of Contents

1. [Functional Requirements](#1-functional-requirements)
2. [Non-Functional Requirements](#2-non-functional-requirements)
3. [Technical Requirements](#3-technical-requirements)
4. [Lifecycle Overview](#4-lifecycle-overview)
5. [Phase 0: System Setup](#5-phase-0-system-setup)
6. [Phase 1: Foundation](#6-phase-1-foundation)
7. [Phase 2: Internship Planning](#7-phase-2-internship-planning)
8. [Phase 3: Registration & Placement](#8-phase-3-registration--placement)
9. [Phase 4: Operations](#9-phase-4-operations)
10. [Phase 5: Assessment & Evaluation](#10-phase-5-assessment--evaluation)
11. [Phase 6: Period Closing](#11-phase-6-period-closing)
12. [Phase 7: Archiving](#12-phase-7-archiving)
13. [Flow Summary](#13-flow-summary)

---

## 1. Functional Requirements

### FR-1: Multi-Tenant Institution Management
| ID | Requirement | Priority |
|---|---|---|
| FR-1.1 | System supports a single school/institution record | High |
| FR-1.2 | School profile includes name, institutional code, address, contacts, principal name, and logo | High |
| FR-1.3 | Academic years are configurable with start/end dates and active flag | High |
| FR-1.4 | Departments are configurable and scoped to the school | High |
| FR-1.5 | Only one academic year can be active at any time | Medium |

### FR-2: Role-Based Access Control
| ID | Requirement | Priority |
|---|---|---|
| FR-2.1 | System defines five user roles: super_admin, admin, teacher, student, supervisor | High |
| FR-2.2 | Functional roles (mentor, mentee) are resolved at runtime from user roles | Medium |
| FR-2.3 | Super admin bypasses all authorization checks via Gate::before | High |
| FR-2.4 | Role assignment is managed through Spatie Laravel Permission | High |

### FR-3: Internship Lifecycle Management
| ID | Requirement | Priority |
|---|---|---|
| FR-3.1 | Internships follow a state machine: DRAFT → PUBLISHED → ACTIVE → COMPLETED or CANCELLED | High |
| FR-3.2 | Internships have configurable registration windows and activity periods | High |
| FR-3.3 | Partner companies can be registered with contact and industry information | High |
| FR-3.4 | Placement slots have configurable quotas enforced by the system | High |
| FR-3.5 | Document requirements can be attached to internships (mandatory or optional) | Medium |

### FR-4: Student Registration
| ID | Requirement | Priority |
|---|---|---|
| FR-4.1 | New students can apply via public account application form | High |
| FR-4.2 | Existing students can self-register for available internships | High |
| FR-4.3 | Admins can directly place students into internships | High |
| FR-4.4 | Registration follows PENDING → ACTIVE → COMPLETED state machine | High |
| FR-4.5 | Mentor assignments (teacher + supervisor) are created during registration activation | High |

### FR-5: Daily Operations
| ID | Requirement | Priority |
|---|---|---|
| FR-5.1 | Students can write daily logbook entries with learning outcomes | High |
| FR-5.2 | Logbook follows DRAFT → SUBMITTED → VERIFIED state machine with REVISION_REQUIRED loop | High |
| FR-5.3 | Teachers can record daily attendance with six status values | High |
| FR-5.4 | Teachers can create assignments with due dates and reference documents | High |
| FR-5.5 | Students can submit assignment work with DRAFT/SUBMITTED states | High |
| FR-5.6 | Supervision logs track mentoring and guidance sessions | High |
| FR-5.7 | Students can submit absence requests in advance | Medium |
| FR-5.8 | Students must acknowledge internship handbooks | Medium |

### FR-6: Assessment & Scoring
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-6.1 | Rubrics define weighted competencies and indicators for evaluation | High | ✅ Existing |
| FR-6.2 | Competencies are scoped to evaluator roles (teacher, supervisor) | High | ✅ Existing |
| FR-6.3 | Assessment scoring is role-filtered (teacher sees their competencies only) | High | ✅ Existing |
| FR-6.4 | Final assessment is irreversible once finalized | High | ✅ Existing |
| FR-6.5 | Final score is computed using weighted normalization (0-100) | High | ✅ Existing |
| FR-6.6 | Mentor evaluations track communication, responsiveness, and guidance quality | Medium | ✅ Existing |
| FR-6.7 | Supervisor-scored competencies are optional; weights redistribute if supervisor does not score | High | ✅ Implemented in `FinalizeAssessmentAction` |

### FR-7: Partnership & MoU Management
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-7.1 | Partnerships formalise cooperation between school and host companies with agreement number, validity period, and contact person | Medium | ✅ Implemented |
| FR-7.2 | Partnership records include start date, end date, and status (ACTIVE/EXPIRED/TERMINATED) | Medium | ✅ Implemented |
| FR-7.3 | Scanned MoU documents can be attached to partnership records via media library | Medium | ✅ Implemented — Spatie Media Library `mou_document` collection with file upload in PartnershipManager |
| FR-7.4 | System warns when a partnership is nearing expiry | Low | ✅ Implemented — stats widget showing count of partnerships expiring within 30 days |
| FR-7.5 | Partnerships are informational — they never block placement or registration | High | ✅ Implemented |

### FR-8: Internship Briefing & Pre-Departure
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-8.1 | Schools can create briefing sessions with date, location, and description | Medium | ✅ Implemented |
| FR-8.2 | Student attendance at briefing sessions is recorded by teacher/admin | Medium | ✅ Implemented |
| FR-8.3 | Briefing attendance can be mandatory or optional | Medium | ✅ Implemented |
| FR-8.4 | If mandatory: student must have attended briefing before starting logbook/attendance | Medium | ✅ Implemented — gated in `CreateLogbookAction` and `ClockInAction` |
| FR-8.5 | Admin can override briefing attendance (logged in audit trail) | Medium | ✅ Implemented |
| FR-8.6 | Supervisor not involved in briefing management | High | ✅ Implemented |

### FR-9: Final Report («Laporan PKL»)
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-9.1 | Students can write a structured final internship report | High | ✅ Implemented |
| FR-9.2 | Report follows DRAFT → SUBMITTED → APPROVED state machine with REVISION_REQUIRED loop | High | ✅ Implemented |
| FR-9.3 | Teachers can approve reports with an optional numeric score (0-100) | High | ✅ Implemented |
| FR-9.4 | Revision history is preserved per round with teacher feedback | Medium | ✅ Implemented |
| FR-9.5 | Industry supervisor can optionally add notes — never required for approval | High | ✅ Implemented — `supervisor/reports/notes` route |
| FR-9.6 | Report score can be auto-imported into assessment rubric | Low | ✅ Implemented — `AutoCalculateAssessmentAction` now includes `report_score` in auto-calculation data |

### FR-10: Presentation & Seminar («Sidang PKL»)
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-10.1 | Oral presentations can be scheduled for students with a panel of examiners | Medium | ✅ Implemented |
| FR-10.2 | Examiners are teachers/admins only | High | ✅ Implemented |
| FR-10.3 | Each examiner scores independently | Medium | ✅ Implemented |
| FR-10.4 | Final presentation score is the average of all examiner scores | Medium | ✅ Implemented in `CompletePresentationAction` |
| FR-10.5 | Composite score (report + presentation) using configurable weights | Medium | ✅ Implemented (defaults: 50/50) |
| FR-10.6 | Presentation is optional per internship | Medium | ✅ Implemented — `requires_presentation`, `presentation_weight`, `report_weight` columns on `internships` table |

### FR-11: Certificate Generation («Sertifikat PKL»)
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-11.1 | Internship completion certificates can be issued as auto-generated PDFs | Medium | ✅ Implemented — dompdf via `CertificateRenderer` |
| FR-11.2 | Certificate templates are configurable with placeholders | Medium | ✅ Implemented — HTML templates, `Blade::render()` |
| FR-11.3 | Each certificate has a unique serial number | Medium | ✅ Implemented — `{PREFIX}/{YEAR}/{SEQUENTIAL}` |
| FR-11.4 | Certificates can be issued individually or in batch | Medium | ✅ Implemented — `BatchIssueCertificateAction` with filter by registration status |
| FR-11.5 | Certificates can be revoked; hidden from student portal | Low | ✅ Implemented |
| FR-11.6 | Certificate issuance is optional | High | ✅ Implemented |

### FR-12: Placement Change («Mutasi PKL»)
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-12.1 | Students can request mid-internship placement changes with reason | Medium | ✅ Implemented — `student/internships/placement-change` |
| FR-12.2 | Placement change follows PENDING → APPROVED/REJECTED state machine | Medium | ✅ Implemented |
| FR-12.3 | Capacity is re-checked on approval | Medium | ✅ `PlacementCapacity::fromModel()->hasAvailableSlots()` |
| FR-12.4 | Filled quotas are atomically decremented/incremented | Medium | ✅ Implemented in DB transaction |
| FR-12.5 | Registration stays ACTIVE throughout the change | High | ✅ Implemented |
| FR-12.6 | Only admin can approve; supervisor not involved | High | ✅ Implemented |

### FR-13: Incident Reporting
| ID | Requirement | Priority | Status |
|---|---|---|---|
| FR-13.1 | Anyone can report workplace incidents | Medium | ✅ Implemented |
| FR-13.2 | Incident type (5 categories) and severity (4 levels) | Medium | ✅ Implemented |
| FR-13.3 | Incident follows REPORTED → INVESTIGATING → RESOLVED → CLOSED | Medium | ✅ State machine implemented |
| FR-13.4 | HIGH/CRITICAL incidents trigger instant notification | Low | ✅ Implemented — `IncidentReportedNotification` sent to all admins and assigned teacher on HIGH/CRITICAL |
| FR-13.5 | Incidents never block operational activities | High | ✅ Implemented |
| FR-13.6 | Only teacher/admin can resolve/close | High | ✅ Implemented |

### FR-14: Account & Security
| ID | Requirement | Priority |
|---|---|---|
| FR-14.1 | Account status follows NIST-aligned lifecycle with 8 states: PROVISIONED, ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, SUSPENDED, INACTIVE, ARCHIVED | High |
| FR-14.2 | Login history is recorded with IP, user agent, and geo-location data | Medium |
| FR-14.3 | Suspicious login attempts are detected and flagged | Medium |
| FR-14.4 | Account recovery uses encrypted one-time codes | Medium |
| FR-14.5 | Multi-approval workflow protects sensitive account changes | Medium |
| FR-14.6 | GDPR deletion logging for compliance | Low |

### FR-15: Reporting & Closure
| ID | Requirement | Priority |
|---|---|---|
| FR-15.1 | Completed internships lock all operational activities | High |
| FR-15.2 | Batch operations support mass status transitions | Medium |
| FR-15.3 | Reports (completion summaries, performance, participation) can be generated as PDFs | Medium |
| FR-15.4 | Account archival preserves data while blocking login | Medium |
| FR-15.5 | PROTECTED status prevents super admin accounts from being archived or deleted | High |

### FR-16: Notifications
| ID | Requirement | Priority |
|---|---|---|
| FR-16.1 | Welcome notifications sent on account creation | Medium |
| FR-16.2 | Assignment published notifications sent to enrolled students | Medium |
| FR-16.3 | Submission feedback notifications sent on grading/verification | Medium |
| FR-16.4 | Account status change notifications | Medium |

### FR-17: Dynamic Branding
| ID | Requirement | Priority |
|---|---|---|
| FR-17.1 | Institution name, logo, favicon, and site title are configurable via admin panel | Medium |
| FR-17.2 | Three brand colors (primary, secondary, accent) with dark mode support | Medium |
| FR-17.3 | Color presets for one-click application | Low |
| FR-17.4 | Author attribution integrity verification | Medium |

---

## 2. Non-Functional Requirements

| ID | Requirement | Target |
|---|---|---|
| NFR-1 | Architecture must support offline-capable deployment (self-hosted) | Single-server SQLite |
| NFR-2 | Database driver abstraction supports SQLite, MySQL, MariaDB, PostgreSQL | PDO abstraction |
| NFR-3 | Primary keys use UUIDs for distributed compatibility | All business models |
| NFR-4 | Code quality enforced by static analysis | PHPStan `--level=max` |
| NFR-5 | Test coverage minimum threshold | 80% (enforced by CI) |
| NFR-6 | Code formatting enforced by automated tooling | Laravel Pint |
| NFR-7 | Auditing must track every state-changing action | spatie/laravel-activitylog |
| NFR-8 | Sensitive settings stored encrypted in database | SettingValueCast 'encrypted' type |
| NFR-9 | All enums are string-backed with label() and optional color() methods | LabelEnum / ColorableEnum |
| NFR-10 | Entities are pure PHP (no Eloquent/framework imports) | final readonly classes |
| NFR-11 | Actions have single responsibility with one execute() method | Action pattern |
| NFR-12 | Controllers and Livewire components delegate logic to Actions | Thin controller pattern |
| NFR-13 | Frontend uses TailwindCSS v4 with DaisyUI and maryUI components | CSS-first config |
| NFR-14 | Real-time features via WebSocket broadcasting | laravel/reverb |
| NFR-15 | Application monitoring via Laravel Pulse | pulse:check / pulse:work |
| NFR-16 | CSS variables for dynamic theming with light/dark mode | html[data-theme] selectors |

---

## 3. Technical Requirements

| ID | Requirement | Specification |
|---|---|---|
| TR-1 | PHP version | 8.4 or higher |
| TR-2 | Node.js version | 20 or higher |
| TR-3 | Database (default) | SQLite |
| TR-4 | Database (optional) | MySQL 8+, MariaDB, PostgreSQL 14+ |
| TR-5 | Package manager | Composer + npm |
| TR-6 | Asset bundler | Vite 7 |
| TR-7 | Testing framework | Pest 4 |
| TR-8 | Static analysis | PHPStan 2 (`--level=max`) |
| TR-9 | Code style | Laravel Pint |
| TR-10 | UI framework | DaisyUI 5 + maryUI 2 |
| TR-11 | Client-side interactivity | Alpine.js (via Livewire) |
| TR-12 | Icon set | Tabler Icons |
| TR-13 | Image cropping | Cropper.js |
| TR-14 | PDF generation | barryvdh/laravel-dompdf |
| TR-15 | QR code generation | simplesoftwareio/simple-qrcode |
| TR-16 | WebSocket server | Laravel Reverb |
| TR-17 | Docker development | Laravel Sail |
| TR-18 | MCP server (IDE) | Laravel Boost |
| TR-19 | Real-time logs | Laravel Pail |

---

## 4. Lifecycle Overview

Internara maps the internship lifecycle into seven sequential phases. Each phase has a clear entry gate, a set of activities, and an exit gate to the next phase.

```
Phase 0          Phase 1         Phase 2           Phase 3
System Setup →   Foundation →    Internship Plan → Registration

Phase 4          Phase 5         Phase 6           Phase 7
Operations   →   Assessment →    Period Close →    Archive
```

| Phase | Name | Purpose |
|---|---|---|---|
| 0 | System Setup | Install and configure the application |
| 1 | Foundation | Define school structure, users, and roles |
| 2 | Internship Planning | Create programs, register companies, set quotas, manage partnerships |
| 3 | Registration & Placement | Enroll students, assign placements, attach mentors, pre-departure briefing |
| 4 | Operations | Daily activities: logbook, attendance, assignments, supervision, report writing, incident reporting, placement changes |
| 5 | Assessment & Evaluation | Grade performance, finalize assessments, evaluate mentors, oral presentations |
| 6 | Period Closing | Conclude the internship cycle, issue certificates, generate reports |
| 7 | Archiving | Preserve records, lock down completed periods |

---

## 5. Phase 0: System Setup

**Goal:** Get the application running and ready for institutional use.

### Flow

```
Install → Set up Wizard → Super Admin → Settings
```

See [System Lifecycle: System Bootstrap](system-lifecycle.md#3-phase-0-system-bootstrap) for the detailed installation flow, wizard steps, and post-installation commands.

---

## 6. Phase 1: Foundation

**Goal:** Configure the school's academic structure and user roles.

### Flow

```
School Profile → Academic Years → Departments → Users → Roles
```

See [System Lifecycle: Foundation](system-lifecycle.md#4-phase-1-foundation) for the detailed school configuration, academic years, departments, user roles, and account creation flow.

---

## 7. Phase 2: Internship Planning

**Goal:** Create internship programs, onboard partner companies, define placement slots, and manage formal partnership agreements.

### Flow

```
Internship → Companies → Partnerships → Placements → Document Requirements
```

See [System Lifecycle: Internship Planning](system-lifecycle.md#5-phase-2-internship-planning) for the detailed internship creation, company registration, partnership management, placement slot definition, and document requirement flow.

Partnership management (MoU) is documented in [MoU & Partnership Management](lifecycles/mou-partnership.md).

---

## 8. Phase 3: Registration & Placement

**Goal:** Enroll students into the internship, assign them to placements with mentors, and conduct pre-departure preparation.

### Flow

Three paths exist depending on the student's situation:

```
New Student (no account) → Path A: Account Application
Existing Student         → Path B: Self-Registration → Path C: Direct Placement
```

All three paths produce an ACTIVE registration.

After registration is active, students may attend a pre-departure briefing before beginning operations (see [Internship Briefing](lifecycles/internship-briefing.md)).

See [System Lifecycle: Registration Engine](system-lifecycle.md#6-phase-3-registration-engine) for the detailed registration flow with action orchestration and system guards.

---

## 9. Phase 4: Operations

**Goal:** Run the daily activities during the active internship period.

### Flow

Seven parallel activity streams, all gated by Registration:ACTIVE.

```
Logbook → Attendance → Assignments → Supervision → Final Report → Placement Change → Incident Reporting
```

See [System Lifecycle: Operations Engine](system-lifecycle.md#7-phase-4-operations-engine) for the detailed operations flow including state machines for logbook, attendance, assignments, and supervision.

Supporting sub-processes:
- [Final Report](lifecycles/final-report.md) — structured report writing and grading (overlaps Phase 5)
- [Placement Change](lifecycles/placement-change.md) — mid-internship company mutation
- [Incident Reporting](lifecycles/incident-reporting.md) — workplace incident documentation

---

## 10. Phase 5: Assessment & Evaluation

**Goal:** Measure student performance, finalize grades, evaluate mentors, and conduct oral presentations.

### Flow

```
Rubrics → Indicators → Score → Finalize → Presentation → Mentor Evaluation
```

See [System Lifecycle: Assessment Engine](system-lifecycle.md#8-phase-5-assessment-engine) for the detailed assessment flow including rubric structure, scoring visibility, finalization guards, and mentor evaluation.

**Supervisor dependency:** Competencies assigned to `supervisor` role are fully optional — if the supervisor does not score them, weights are redistributed to scored competencies. The teacher can always finalize without supervisor input.

Supporting sub-processes:
- [Presentation & Seminar](lifecycles/presentation-seminar.md) — oral defense of internship results
- [Final Report](lifecycles/final-report.md) — report grading feeds into assessment (if configured)

---

## 11. Phase 6: Period Closing

**Goal:** Conclude the internship cycle, lock all activities, issue certificates, and prepare institutional reports.

### Flow

```
Complete Internship → Issue Certificates → Reports → Data Lock
```

See [System Lifecycle: Closure Engine](system-lifecycle.md#9-phase-6-closure-engine) for the detailed closure flow including internship transition, batch operations, report generation, and pre-close integrity checks.

Certificate issuance is documented in [Certificate Generation](lifecycles/certificate-generation.md).

---

## 12. Phase 7: Archiving

**Goal:** Preserve records for compliance, lock data against modifications, and free up system capacity for new periods.

### Flow

```
Archive Accounts → Lock Period → Retention
```

See [System Lifecycle: Archival Engine](system-lifecycle.md#10-phase-7-archival-engine) for the detailed archival flow including account archival, period lock, GDPR compliance, and preparing for a new cycle.

---

## 13. Flow Summary

### Complete Lifecycle Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                      SYSTEM SETUP (Phase 0)                      │
│  Install → Wizard → Super Admin → Settings                       │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                       FOUNDATION (Phase 1)                       │
│  School → Academic Years → Departments → Users → Roles           │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                   INTERNSHIP PLANNING (Phase 2)                  │
│  Create Internship → Companies → Partnerships → Placements → Doc │
│  [Draft → Published → Active]  ┌──────────────────────────┐     │
│                                │ MoU/Partnership          │     │
│                                │ (ACTIVE → EXPIRED →      │     │
│                                │  TERMINATED)              │     │
│                                └──────────────────────────┘     │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                 REGISTRATION & PLACEMENT (Phase 3)                │
│                                                                   │
│  ┌──────────────────┐  ┌────────────────┐  ┌──────────────────┐  │
│  │ Path A: Apply    │  │ Path B: Self   │  │ Path C: Direct   │  │
│  │ (new student)    │  │ (existing)     │  │ (admin)          │  │
│  │                  │  │                │  │                  │  │
│  │ Application →    │  │ Registration → │  │ → Active         │  │
│  │ Approved →       │  │ Verified →     │  │   Registration   │  │
│  │ Active           │  │ Active         │  │                  │  │
│  └──────────────────┘  └────────────────┘  └──────────────────┘  │
│                    All paths produce Active Registration         │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                      OPERATIONS (Phase 4)                        │
│                                                                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │
│  │ Logbook  │  │Attendance│  │Assignments│  │ Supervision      │ │
│  │          │  │          │  │           │  │                  │ │
│  │ Draft →  │  │ Present  │  │ Draft →   │  │ Create log →     │ │
│  │ Submit → │  │ Late     │  │ Publish → │  │ Verify (teacher) │ │
│  │ Verify   │  │ Absent   │  │ Submit →  │  │                  │ │
│  │          │  │ etc.     │  │ Grade     │  │                  │ │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────────┘ │
│                                                                   │
│  ┌────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │ Final Report   │  │ Placement Change │  │ Incident Reports │  │
│  │                │  │                  │  │                  │  │
│  │ Draft → Submit→│  │ Request →        │  │ Report → Investi-│  │
│  │ Approve        │  │ Approve/Reject   │  │ gate → Resolve   │  │
│  └────────────────┘  └──────────────────┘  └──────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                ASSESSMENT & EVALUATION (Phase 5)                 │
│  Score Indicators → Finalize Assessment → Preséntation →        │
│  → Evaluate Mentors                                             │
│                                                                   │
│  Supervisor competencies are OPTIONAL — weights redistribute     │
│  if supervisor does not participate.                             │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                     PERIOD CLOSING (Phase 6)                     │
│  Complete Internship → Issue Certificates → Reports → Data Lock │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                       ARCHIVING (Phase 7)                        │
│  Archive Accounts → Lock Period → Prepare for Next Cycle         │
└──────────────────────────────────────────────────────────────────┘
```

### State Transition Summary

See the [Complete State Transition Map](system-lifecycle.md#12-complete-state-transition-map) in System Lifecycle for the authoritative state machine definitions for all entities.

### Who Does What: Role Matrix

| Activity | Super Admin | Admin | Teacher | Student | Supervisor |
|---|---|---|---|---|---|---|
| System setup | ✓ | — | — | — | — |
| School config | ✓ | ✓ | — | — | — |
| Manage users | ✓ | ✓ | — | — | — |
| Create internships | ✓ | ✓ | ✓ | — | — |
| Register companies | ✓ | ✓ | ✓ | — | — |
| Manage partnerships/MoU | ✓ | ✓ | — | — | — |
| Set placement quotas | ✓ | ✓ | ✓ | — | — |
| Apply for internship | — | — | — | ✓ | — |
| Verify registration | ✓ | ✓ | ✓ | — | — |
| Place students directly | ✓ | ✓ | ✓ | — | — |
| Record briefing attendance | ✓ | ✓ | ✓ | — | — |
| Write logbook | — | — | — | ✓ | — |
| Verify logbook | — | — | ✓ | — | — |
| Record attendance | — | — | ✓ | — | — |
| Submit assignments | — | — | — | ✓ | — |
| Grade submissions | ✓ | ✓ | ✓ | — | ✓ |
| Create supervision log | — | — | ✓ | — | ✓ |
| Verify supervision | — | — | ✓ | — | — |
| Score assessment (all competencies) | ✓ | ✓ | — | — | — |
| Score assessment (own role) | ✓ | ✓ | ✓ | — | ✓ |
| Finalize assessment | — | — | ✓ | — | — |
| Write final report | — | — | — | ✓ | — |
| Grade final report | ✓ | ✓ | ✓ | — | —¹ |
| Schedule presentations | ✓ | ✓ | — | — | — |
| Examine presentations | ✓ | ✓ | ✓ | — | — |
| Issue certificates | ✓ | ✓ | — | — | — |
| Request placement change | ✓ | ✓ | ✓ | ✓ | — |
| Approve placement change | ✓ | ✓ | — | — | — |
| Report incident | ✓ | ✓ | ✓ | ✓ | ✓ |
| Resolve incident | ✓ | ✓ | ✓ | — | — |
| Evaluate mentors | ✓ | ✓ | ✓ | ✓ | — |
| Close internship period | ✓ | ✓ | ✓ | — | — |
| Archive accounts | ✓ | ✓ | — | — | — |
| Generate reports | ✓ | ✓ | — | — | — |

> ¹ Supervisor can optionally add notes to the final report, but does not grade it.
