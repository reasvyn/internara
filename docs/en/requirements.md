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
| ID | Requirement | Priority |
|---|---|---|
| FR-6.1 | Rubrics define weighted competencies and indicators for evaluation | High |
| FR-6.2 | Competencies are scoped to evaluator roles (teacher, supervisor) | High |
| FR-6.3 | Assessment scoring is role-filtered (teacher sees their competencies only) | High |
| FR-6.4 | Final assessment is irreversible once finalized | High |
| FR-6.5 | Final score is computed using weighted normalization (0-100) | High |
| FR-6.6 | Mentor evaluations track communication, responsiveness, and guidance quality | Medium |

### FR-7: Account & Security
| ID | Requirement | Priority |
|---|---|---|
| FR-7.1 | Account status follows NIST-aligned lifecycle with 8 states: PROVISIONED, ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, SUSPENDED, INACTIVE, ARCHIVED | High |
| FR-7.2 | Login history is recorded with IP, user agent, and geo-location data | Medium |
| FR-7.3 | Suspicious login attempts are detected and flagged | Medium |
| FR-7.4 | Account recovery uses encrypted one-time codes | Medium |
| FR-7.5 | Multi-approval workflow protects sensitive account changes | Medium |
| FR-7.6 | GDPR deletion logging for compliance | Low |

### FR-8: Reporting & Closure
| ID | Requirement | Priority |
|---|---|---|
| FR-8.1 | Completed internships lock all operational activities | High |
| FR-8.2 | Batch operations support mass status transitions | Medium |
| FR-8.3 | Reports (completion summaries, performance, participation) can be generated as PDFs | Medium |
| FR-8.4 | Account archival preserves data while blocking login | Medium |
| FR-8.5 | PROTECTED status prevents super admin accounts from being archived or deleted | High |

### FR-9: Notifications
| ID | Requirement | Priority |
|---|---|---|
| FR-9.1 | Welcome notifications sent on account creation | Medium |
| FR-9.2 | Assignment published notifications sent to enrolled students | Medium |
| FR-9.3 | Submission feedback notifications sent on grading/verification | Medium |
| FR-9.4 | Account status change notifications | Medium |

### FR-10: Dynamic Branding
| ID | Requirement | Priority |
|---|---|---|
| FR-10.1 | Institution name, logo, favicon, and site title are configurable via admin panel | Medium |
| FR-10.2 | Three brand colors (primary, secondary, accent) with dark mode support | Medium |
| FR-10.3 | Color presets for one-click application | Low |
| FR-10.4 | Author attribution integrity verification | Medium |

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
|---|---|---|
| 0 | System Setup | Install and configure the application |
| 1 | Foundation | Define school structure, users, and roles |
| 2 | Internship Planning | Create programs, register companies, set quotas |
| 3 | Registration & Placement | Enroll students, assign placements, attach mentors |
| 4 | Operations | Daily activities: attendance, logbook, assignments, supervision |
| 5 | Assessment & Evaluation | Grade performance, finalize assessments, evaluate mentors |
| 6 | Period Closing | Conclude the internship cycle, generate reports |
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

**Goal:** Create internship programs, onboard partner companies, and define placement slots.

### Flow

```
Internship → Companies → Placements → Document Requirements
```

See [System Lifecycle: Internship Planning](system-lifecycle.md#5-phase-2-internship-planning) for the detailed internship creation, company registration, placement slot definition, and document requirement flow.

---

## 8. Phase 3: Registration & Placement

**Goal:** Enroll students into the internship and assign them to placements with mentors.

### Flow

Three paths exist depending on the student's situation:

```
New Student (no account) → Path A: Account Application
Existing Student         → Path B: Self-Registration → Path C: Direct Placement
```

All three paths produce an ACTIVE registration.

See [System Lifecycle: Registration Engine](system-lifecycle.md#6-phase-3-registration-engine) for the detailed registration flow with action orchestration and system guards.

---

## 9. Phase 4: Operations

**Goal:** Run the daily activities during the active internship period.

### Flow

Four parallel activity streams, all gated by Registration:ACTIVE.

```
Logbook → Attendance → Assignments → Supervision
```

See [System Lifecycle: Operations Engine](system-lifecycle.md#7-phase-4-operations-engine) for the detailed operations flow including state machines for logbook, attendance, assignments, and supervision.

---

## 10. Phase 5: Assessment & Evaluation

**Goal:** Measure student performance, finalize grades, and evaluate mentors.

### Flow

```
Rubrics → Indicators → Score → Finalize → Mentor Evaluation
```

See [System Lifecycle: Assessment Engine](system-lifecycle.md#8-phase-5-assessment-engine) for the detailed assessment flow including rubric structure, scoring visibility, finalization guards, and mentor evaluation.

---

## 11. Phase 6: Period Closing

**Goal:** Conclude the internship cycle, lock all activities, and prepare institutional reports.

### Flow

```
Complete Internship → Reports → Data Lock
```

See [System Lifecycle: Closure Engine](system-lifecycle.md#9-phase-6-closure-engine) for the detailed closure flow including internship transition, batch operations, report generation, and pre-close integrity checks.

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
│  Create Internship → Companies → Placements → Documents          │
│  [Draft → Published → Active]                                    │
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
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                ASSESSMENT & EVALUATION (Phase 5)                 │
│  Score Indicators → Finalize Assessment → Evaluate Mentors       │
└──────────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                     PERIOD CLOSING (Phase 6)                     │
│  Complete Internship → Generate Reports → Handle Pending Items   │
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
|---|---|---|---|---|---|
| System setup | ✓ | — | — | — | — |
| School config | ✓ | ✓ | — | — | — |
| Manage users | ✓ | ✓ | — | — | — |
| Create internships | ✓ | ✓ | ✓ | — | — |
| Register companies | ✓ | ✓ | ✓ | — | — |
| Set placement quotas | ✓ | ✓ | ✓ | — | — |
| Apply for internship | — | — | — | ✓ | — |
| Verify registration | ✓ | ✓ | ✓ | — | — |
| Place students directly | ✓ | ✓ | ✓ | — | — |
| Write logbook | — | — | — | ✓ | — |
| Verify logbook | — | — | ✓ | — | — |
| Record attendance | — | — | ✓ | — | — |
| Submit assignments | — | — | — | ✓ | — |
| Grade submissions | ✓ | ✓ | ✓ | — | ✓ |
| Create supervision log | — | — | ✓ | — | ✓ |
| Verify supervision | — | — | ✓ | — | — |
| Score assessment | ✓ | ✓ | ✓ | — | ✓ |
| Finalize assessment | — | — | ✓ | — | — |
| Evaluate mentors | ✓ | ✓ | ✓ | ✓ | — |
| Close internship period | ✓ | ✓ | ✓ | — | — |
| Archive accounts | ✓ | ✓ | — | — | — |
| Generate reports | ✓ | ✓ | — | — | — |
