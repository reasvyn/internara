# User Flow: The Complete Internship Lifecycle

This document describes the end-to-end flow of the Internara internship management system — from installation and setup through to evaluation, period closing, and archival. It follows industry-standard internship lifecycle phases (Plan, Execute, Monitor, Close) adapted for vocational education programs.

---

## Table of Contents

1. [Lifecycle Overview](#1-lifecycle-overview)
2. [Phase 0: System Setup](#2-phase-0-system-setup)
3. [Phase 1: Foundation](#3-phase-1-foundation)
4. [Phase 2: Internship Planning](#4-phase-2-internship-planning)
5. [Phase 3: Registration & Placement](#5-phase-3-registration--placement)
6. [Phase 4: Operations](#6-phase-4-operations)
7. [Phase 5: Assessment & Evaluation](#7-phase-5-assessment--evaluation)
8. [Phase 6: Period Closing](#8-phase-6-period-closing)
9. [Phase 7: Archiving](#9-phase-7-archiving)
10. [Flow Summary](#10-flow-summary)

---

## 1. Lifecycle Overview

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

## 2. Phase 0: System Setup

**Goal:** Get the application running and ready for institutional use.

### Flow

```
Install → Set up Wizard → Super Admin → Settings
```

### Steps

#### 2.1 Installation

The application is installed via the command line:

```bash
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

The `setup:install` command checks the server environment, creates the database, runs migrations, and seeds default settings. It outputs a one-time URL that opens the setup wizard.

#### 2.2 Setup Wizard

The setup wizard walks through:

1. **Environment check** — verifies PHP version, extensions, database connection, writable directories
2. **Database configuration** — confirms or configures the database connection
3. **School profile** — enter institution name, address, contact details, and logo
4. **Department setup** — create initial academic departments
5. **Admin account** — set up the first super administrator

After completion, the system:
- Writes a `.installed` lock file
- Generates a 64-character encrypted recovery key (save this securely)
- Invalidates the one-time setup URL
- Dispatches a `SetupFinalized` event

#### 2.3 Post-Installation

Create additional administrators or recover access:

```bash
php artisan setup:super-admin              # Create additional super admin
php artisan setup:recover-admin            # Recover lost admin access
php artisan setup:health                   # Check system readiness
```

Apply core settings through the admin panel:
- Brand name, site title, logo, favicon
- Primary/secondary/accent colors
- Default locale
- SMTP mail configuration

---

## 3. Phase 1: Foundation

**Goal:** Configure the school's academic structure and user roles.

### Flow

```
School Profile → Academic Years → Departments → Users → Roles
```

### Steps

#### 3.1 School Profile

Configure the institution's identity:

- **Name**, institutional code, address, phone, email, website, fax
- **Logo** — uploaded via media library, displayed site-wide
- **Principal name** — for official documents

Only one school record can exist (enforced by `SchoolState` entity).

#### 3.2 Academic Years

Define the academic calendar. Each year has:
- **Name** — e.g., "2025/2026"
- **Start date** and **end date**
- **Active flag** — only one academic year can be active at a time

The active academic year is used as the default for new internships.

#### 3.3 Departments

Create academic departments (e.g., Computer Science, Accounting, Engineering). Departments group students and are referenced in profile data.

#### 3.4 User Roles

Five system roles control access:

| Role | Purpose |
|---|---|
| **Super Admin** | System infrastructure, global settings, user lifecycle management |
| **Admin** | School-level management, internship oversight |
| **Teacher** | Academic supervision, assessment, grading, verification |
| **Student** | Internship participants — log journal, attendance, assignments |
| **Supervisor** | Industry-side evaluation and mentoring |

Roles are managed through the admin panel using Spatie Laravel Permission.

#### 3.5 User Accounts

Users can be created:
- **By admin** — through the user management panel (assign any role)
- **By self-registration** — through the account application flow (student role)
- **Through setup wizard** — super admin during installation

New accounts follow a status lifecycle defined in the [Account Lifecycle](lifecycles/account-lifecycle.md) state machine, from PROVISIONED through ACTIVATED and VERIFIED, with possible transitions to SUSPENDED, RESTRICTED, INACTIVE, or ARCHIVED.

---

## 4. Phase 2: Internship Planning

**Goal:** Create internship programs, onboard partner companies, and define placement slots.

### Flow

```
Internship → Companies → Placements → Document Requirements
```

### Steps

#### 4.1 Create Internship Program

An internship is a time-bounded program with:

- **Name** — e.g., "PKL 2025/2026 - Computer Science"
- **Period** — start date and end date
- **Registration window** — when students can register
- **Description** — program details
- **Status** — managed through the internship state machine defined in [System Lifecycle](system-lifecycle.md#5-phase-2-internship-planning), with states: DRAFT, PUBLISHED, ACTIVE, COMPLETED (terminal), and CANCELLED (terminal).

When creating, the system automatically assigns the active academic year.

#### 4.2 Partner Companies

Register companies that will host students. Each company record holds:
- Company name, address, contact person, phone, email
- Industry type or sector

#### 4.3 Placement Slots

Define how many students each company can host:

- **Company** — which company
- **Quota** — maximum number of students
- **Start date** and **end date** (may differ from the internship period)
- **Requirements** — special skills or prerequisites

Capacity is enforced: once the quota is filled, no more students can be assigned.

#### 4.4 Document Requirements

Specify what documents students must submit (e.g., application letter, insurance proof, parental consent). Each requirement can be mandatory or optional, and documents are verified during registration.

---

## 5. Phase 3: Registration & Placement

**Goal:** Enroll students into the internship and assign them to placements with mentors.

### Flow

Three paths exist depending on the student's situation:

```
                ┌──────────────────────────────────┐
                │     How does the student join?    │
                └──────────────────────────────────┘
                         │              │
               ┌─────────┘              └─────────┐
               ▼                                    ▼
        New Student (no account)         Existing Student
               │                                    │
               ▼                                    ▼
    Path A: Account Application         Path B: Self-Registration
                                               │
                                               │ (or Admin
                                               │  skips)
                                               ▼
                                        Path C: Direct Placement
```

### Path A: Account Application (New Students)

A prospective student who does not yet have an account:

1. **Apply** — fills out a public application form with personal data, school/department, internship preferences, and proposed company
2. **Pending review** — application is queued for admin review
3. **Admin approves** — the system:
   - Creates a User account with student role (`setup_required = true`)
   - Creates a Profile with personal data
   - Creates a Mentee record linked to the user
   - Creates an active Registration linked to the chosen internship and placement
   - Attaches mentors (school teacher + industry supervisor)
   - Sends a welcome notification
4. **Student logs in** — claims the account, completes setup, changes password

If rejected, the application is marked with a reason.

### Path B: Self-Registration (Existing Students)

A student with an existing account:

1. **Browse internships** — views published/open internships
2. **Register** — selects an internship, submits registration
3. **Pending** — registration awaits admin verification
4. **Admin verifies** — checks the registration, assigns a placement with available capacity, attaches mentors (one school teacher, one industry supervisor)
5. **Active** — registration becomes active, student can begin internship activities

### Path C: Direct Placement (Admin-Initiated)

An admin directly places a student:

1. **Admin selects** — student, internship, placement
2. **System creates** — Mentee record, active Registration (skips pending), increments placement quota
3. **Mentors attached** — based on the registration
4. **Ready** — student can begin immediately

This path is used for bulk placements or special arrangements.

### Registration States

Registration follows PENDING → ACTIVE → COMPLETED transitions (see [System Lifecycle](system-lifecycle.md#6-phase-3-registration-engine)). Paths A and C skip PENDING and create registrations directly as ACTIVE.

---

## 6. Phase 4: Operations

**Goal:** Run the daily activities during the active internship period.

### Flow

```
                    ┌── Logbook ──┐
                    │ Attendance  │
Daily Activities ───┤ Assignments │
                    │ Supervision │
                    └─────────────┘
```

### 6.1 Logbook (Daily Journal)

Students record their daily activities:

1. **Student writes** — creates a daily log entry with description and learning outcomes
2. **Saves as draft** — can edit and continue later
3. **Submits** — finalizes the entry for the day (one submitted entry per day)
4. **Teacher reviews** — school teacher can verify or request revision

The logbook state machine (DRAFT → SUBMITTED → VERIFIED/REVISION_REQUIRED) is defined in [System Lifecycle](system-lifecycle.md#7-phase-4-operations-engine).

### 6.2 Attendance

Teachers record daily attendance:

- **Present** — attended on time
- **Late** — arrived after the threshold time
- **Early Out** — left before the end time
- **Absent** — did not attend
- **Permission** — excused with notice
- **Sick** — excused with medical reason

Students can also submit absence requests in advance.

### 6.3 Assignments

Teachers create and manage assignments for internship students:

1. **Teacher creates** — defines assignment title, description, due date, and attaches reference documents
2. **Publishes** — transitions from DRAFT to PUBLISHED, notifying all enrolled students
3. **Student submits** — uploads work, adds notes; can save as DRAFT or submit as SUBMITTED
4. **Teacher reviews** — can verify, request revision, or grade
5. **Finalized** — once VERIFIED or GRADED, the submission is closed

Assignment and submission state machines are defined in [System Lifecycle](system-lifecycle.md#7-phase-4-operations-engine).

### 6.4 Supervision

Mentors (teachers and industry supervisors) conduct supervision sessions:

1. **Create log** — records date, topic, discussion notes
2. **Type** — determined automatically:
   - `Guidance` — when the school teacher supervises their own student
   - `Mentoring` — when the industry supervisor provides mentoring
3. **Verify** — school teacher verifies supervision logs created by industry supervisors

---

## 7. Phase 5: Assessment & Evaluation

**Goal:** Measure student performance, finalize grades, and evaluate mentors.

### Flow

```
Rubrics → Indicators → Score → Finalize → Mentor Evaluation
```

### 7.1 Assessment Rubrics

Rubrics define the criteria for evaluating student performance:

1. **Create rubric** — admin or teacher defines a rubric with a name and description
2. **Add competencies** — each competency has a weight and an evaluator role (admin, teacher, supervisor, or system)
3. **Add indicators** — each indicator has a name, max score, and weight within its competency
4. **Activate** — rubrics can be toggled active/inactive

### 7.2 Scoring

When a teacher or supervisor opens the assessment for a student registration:

1. **Assessment auto-created** — the system finds the active rubric for the internship and creates an assessment record
2. **Score indicators** — each evaluator scores the indicators they are authorized for (role-based filtering)
3. **Auto-import** — optionally import scores from submitted assignments and logbooks
4. **Live updates** — scores are saved as they are entered

### 7.3 Finalization

Only a school teacher can finalize an assessment:

1. Validates that a rubric exists and at least one competency has been scored
2. Calculates weighted totals for each indicator and competency
3. Saves the final numeric score, evaluator ID, and timestamp
4. Finalization is irreversible — once finalized, scores cannot be changed

**Score calculation:**
```
For each indicator:
  normalized = (student_score / max_score) × 100
  competency_score += normalized × (indicator.weight / 100)

Total score:
  total = sum of all competency_weighted_scores
```

### 7.4 Mentor Evaluation

Students and admins can evaluate mentors based on criteria:

- Communication
- Responsiveness
- Guidance quality

Each criterion is scored 0-100, with an optional overall score and feedback.

---

## 8. Phase 6: Period Closing

**Goal:** Conclude the internship cycle, lock all activities, and prepare institutional reports.

### Flow

```
Complete Internship → Reports → Data Lock
```

### Steps

#### 8.1 Transition Internship to Completed

An admin or teacher transitions the internship from `Active` to `Completed`:

- This is a **terminal** status — students can no longer submit logbooks, assignments, or attendance
- All pending verifications should be completed beforehand
- The system validates the transition against allowed status changes

#### 8.2 Batch Operations

For bulk actions across filtered records:
- **Close all filtered** — transitions a filtered set of internships to `Completed`
- **Archive filtered accounts** — batch archive selected student accounts

#### 8.3 Reports

Generate institutional reports through the Reports Manager:

- Internship completion summaries
- Student performance reports
- Company participation records
- Mentor evaluation summaries

Reports can be downloaded as PDFs.

#### 8.4 Handle Incomplete Items

Before closing a period, ensure:
- All submissions are graded or returned for revision
- Assessments are finalized
- Supervision logs are verified
- Outstanding absence requests are resolved

---

## 9. Phase 7: Archiving

**Goal:** Preserve records for compliance, lock data against modifications, and free up system capacity for new periods.

### Flow

```
Archive Accounts → Lock Period → Retention
```

### Steps

#### 9.1 Account Archival

User accounts can be moved to the `Archived` status. This is a **terminal** state:

- **Login blocked** — archived users cannot log in
- **Data preserved** — all related records (logbooks, submissions, attendance) remain in the database
- **Immutable** — no further transitions are possible from `Archived`
- **Compliance** — records are retained as required by institutional policy

The system supports mass archival via bulk actions in the user manager.

#### 9.2 Internship Period Lock

Completed or cancelled internships are terminal states:
- No new registrations or placements can be added
- Existing records are read-only for reporting
- The `.installed` lock file prevents re-running the setup wizard

#### 9.3 GDPR Compliance

The system includes a `gdpr_deletion_logs` table for tracking data deletion requests. Account archival complies with data retention policies — data is logically deleted (archived) rather than physically removed, unless a formal deletion request is processed.

#### 9.4 Preparing for a New Cycle

To start a new internship cycle:

1. Create a new **Academic Year** (and set it as active)
2. Create new **Internship Programs** (draft)
3. Update **Company partnerships** if needed
4. Define new **Placement Slots** with updated quotas
5. Repeat from Phase 2

---

## 10. Flow Summary

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
