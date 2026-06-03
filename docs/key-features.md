# Key Features
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


> Every feature in Internara belongs to one of **23 domains**. Each domain owns its
> complete vertical slice: persistence, business rules, UI components, authorization, and HTTP
> interface.
>
> Domains below are listed in **program lifecycle order** — from system foundation through
> installation, daily operations, assessment, certification, and finally program closure and
> archival.

## Contents

- [1. Core — Architectural Foundation](#1-core--architectural-foundation)
- [2. Core — Cross-domain Utilities](#2-core--cross-domain-utilities)
- [3. Setup — Installation](#3-setup--installation)
- [4. Auth — Security & Identity](#4-auth--security--identity)
- [5. User — Identity & Profile](#5-user--identity--profile)
- [6. School — Institution](#6-school--institution)
- [7. Settings — Runtime Configuration](#7-settings--runtime-configuration)
- [8. Partnership — External Relations](#8-partnership--external-relations)
- [9. Internship — Program Management](#9-internship--program-management)
- [10. Placement — Slot Assignment](#10-placement--slot-assignment)
- [11. Registration — Student Enrollment](#11-registration--student-enrollment)
- [12. Mentee — Student Role](#12-mentee--student-role)
- [13. Attendance — Presence Tracking](#13-attendance--presence-tracking)
- [14. Logbook — Daily Journal](#14-logbook--daily-journal)
- [15. Assignment — Tasks & Submissions](#15-assignment--tasks--submissions)
- [16. Mentor — Supervision](#16-mentor--supervision)
- [17. Schedule — Calendar Events](#17-schedule--calendar-events)
- [18. Guidance — Handbooks](#18-guidance--handbooks)
- [19. Incident — Issue Reporting](#19-incident--issue-reporting)
- [20. Assessment — Competency Evaluation](#20-assessment--competency-evaluation)
- [21. Evaluation — Feedback Collection](#21-evaluation--feedback-collection)
- [22. Document — Templates & Rendering](#22-document--templates--rendering)
- [23. Certificate — Credentialing](#23-certificate--credentialing)
- [24. Admin — System Administration](#24-admin--system-administration)
- [25. Program Closure & Archival](#25-program-closure--archival)
- [Role Access Matrix](#role-access-matrix)

---

## 1. Core — Architectural Foundation

Base classes, contracts, and infrastructure used across all domains.

| Feature | Description |
|---|---|
| Base Model (UUID) | All models (except User) extend `BaseModel` with UUID primary keys |
| Base Action | Every business operation extends `BaseAction` — transaction + logging |
| Base Entity | `final readonly` business rules with zero framework dependencies |
| Base Policy | Authorization with role and ownership checks |
| Base Record Manager | CRUD Livewire base with search, filter, sort, pagination, bulk actions |
| SmartLogger | Dual-channel fluent logger (system + activity) with PII masking |
| Exception Hierarchy | AppException (action/infrastructure/presentation) + DomainException (separate tree) |
| StatusEnum Contract | State machine via `canTransitionTo()`, `isTerminal()`, `validTransitions()` |
| ColorableEnum Contract | Status enums with CSS color variant support |
| FormRequest | Validation exception handling without redirect |
| Data (DTO) | Abstract readonly data transfer objects |
| Security Headers Middleware | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| Log Context Middleware | Request tracing (request_id, method, URL, IP, user) |
| System Health | 15-point check: environment, PHP, extensions, memory, DB, migrations, storage, disk, queue, cache, app key |
| System Cleanup | Prune expired resets, stale cache, failed jobs, old logs |
| Activity Logging | Spatie Activity Log with query scopes |
| HandlesActionErrors Trait | Consistent try-catch-log-rethrow pattern |

---

## 2. Core (Cross-domain Utilities)

Cross-domain utilities without business logic.

| Feature | Description |
|---|---|
| Environment Detection | Centralized environment checks (debug, development, production) |
| Locale Management | Bilingual Indonesian/English with session preference |
| Theme System | Color resolution from settings into CSS custom properties (light/dark) |
| CSV Handler | Export, import, and template download with optional header validation |
| Language Switcher | Livewire bilingual toggle |
| Theme Switcher | Livewire light/dark/system theme toggle |
| Lang Checker | Development helper: logs warnings for missing translation keys |

---

## 3. Setup — Installation

First-run wizard — prepares the application from empty database to fully operational.

| Feature | Description | Access |
|---|---|---|
| 7-Step Setup Wizard | Guided: Environment Check, School, Department, Admin Account, Program (optional), Finalize, Complete | Guest (token) |
| Environment Audit | PHP version, extensions, directory permissions, database, terminal | Installer |
| Setup Token | Encrypted random token gates wizard access | System |
| School Initialization | Create first school profile | Installer |
| Super Admin Creation | Create first super_admin account (name always "Administrator", username "superadmin") | Installer |
| Recovery Key Generation | 64-char random key (shown once, hashed in storage) | Installer |
| CLI Install | `php artisan setup:install` with `--check-only` and `--force` flags | CLI |
| Super Admin Recovery | Emergency CLI recovery when all super admins are lost | CLI |

---

## 4. Auth — Security & Identity

Security boundary — login, authentication, RBAC, account lifecycle, recovery.

| Feature | Description | Access |
|---|---|---|
| Login via Email/Username | Authenticate with email or username, 4-step sequential validation, auto-lock after 10 failed attempts | Guest |
| Forgot Password | Self-service email-based reset (60 min expiry, single-use) | Guest |
| Reset Password | Set new password via email token | Guest |
| Confirm Password | Re-authenticate before sensitive operations | Auth |
| Rate Limiting | Per-endpoint: Login (5/60s), Forgot Password (3/3600s), Reset Password (5/300s), Confirm Password (5/300s), Account Recovery (3/300s) | Guest + Auth |
| Recovery Slip | Admin generates one-time codes for locked-out users (offline delivery, single-use, no expiry) | Admin |
| Account Recovery | User redeems recovery slip to unlock account and set new password | Guest |
| RBAC | 5 user roles (super_admin, admin, teacher, student, supervisor) + 2 functional roles (mentor, mentee) | All layers |
| Account State Machine | 8 states (provisioned, activated, verified, restricted, suspended, inactive, archived, protected) with strict guards | System |
| Clone Account Detection | Detect duplicates by email, phone, or identifier | Admin |
| Account Lifecycle Manager | View and manage user account lifecycle states | Admin |
| Recovery Code Manager | Generate and display recovery codes for user access | Auth + Admin |

---

## 5. User — Identity & Profile

Identity persistence — user profiles, dashboard routing, avatar, notifications.

| Feature | Description | Access |
|---|---|---|
| User & Profile Models | UUID-based user identity with extended profile (phone, address, gender, blood type, emergency contact, national ID, school/department FK) | All |
| Profile Editor | Self-service personal data update (name, email, phone, address, bio) | Auth |
| Avatar Upload | Single image via media library, 200x200 WebP thumbnail | Auth |
| Role-based Dashboard | Automatic routing to admin/teacher/supervisor/student dashboard by role priority | Auth |
| Admin Dashboard | System overview: user stats, readiness checklist, quick links | Admin |
| Teacher Dashboard | Supervision view: supervised students, pending journals, active companies | Teacher |
| Supervisor Dashboard | Industry view: active participants, pending evaluations, verified journals | Supervisor |
| Student Dashboard | Registration status, journal progress, quick actions (write logbook, clock in, etc.) | Student |
| Notification Center | Full-page with search, filter (unread/read), sorting, bulk mark-read/delete | Auth |
| Notification Bell | Navbar indicator with unread count | Auth |
| Recent Activity Feed | Chronological user activity log | Auth |
| Username Generation | Unique username generation with collision avoidance | System |

---

## 6. School — Institution

Institutional foundation — school profile, departments, academic years.

| Feature | Description | Access |
|---|---|---|
| School Profile Editor | Institutional data: legal name, code, address, contact, logo | Admin |
| Department Manager | CRUD departments with search, sort, paginate, bulk selection | Admin |
| Academic Year Manager | CRUD academic years with single-active constraint | Admin |
| Activate Academic Year | Selecting a year in System Settings activates it and deactivates others | Admin |
| Bulk Delete | Mass delete inactive academic years | Admin |
| Department Deletion Guard | Blocks deletion if department has active profiles | System |

---

## 7. Settings — Runtime Configuration

Key-value configuration that changes without deployment.

| Feature | Description | Access |
|---|---|---|
| System Setting Manager | Key-value store with type enforcement (boolean, text, numeric, JSON, image, color) | Super Admin |
| Branding Configuration | App name, logo, favicon, colors (primary/secondary/accent), custom CSS | Super Admin |
| Feature Flags | Enable/disable features at runtime | Super Admin |
| Mail Configuration | SMTP settings with test email verification | Super Admin |
| Cache Invalidation | Every setting change immediately invalidates cache | System |
| Audit Trail | Every change logged with before/after values, user, timestamp | System |

---

## 8. Partnership — External Relations

External relationships — companies and partnership agreements.

| Feature | Description | Access |
|---|---|---|
| Company Manager | CRUD company profiles (name, address, industry, website, contact) | Admin |
| Partnership Manager | CRUD agreements (number, title, dates, scope, contact person, signing parties) | Admin |
| Partnership Lifecycle | ACTIVE, EXPIRED, TERMINATED with transition rules | System |
| MOU Document Upload | Upload agreement documents via media library | Admin |
| Expiry Detection | Warns when partnership is expiring (default 30 days) | System |

---

## 9. Internship — Program Management

Core operational domain — program definitions, requirements, reports, groups, phases.

| Feature | Description | Access |
|---|---|---|
| Program Manager | CRUD programs: name, dates, academic year, department, type | Admin |
| Program Lifecycle | DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED with transition gates | Admin |
| Requirement Manager | Document requirements per program (DOCUMENT, SKILL, TEXT) | Admin |
| Group Manager | Groups with member roles | Admin |
| Phase Manager | Program phases/timeline stages | Admin |
| Report Writer | Student writes and submits final reports | Student |
| Report Review | Admin/teacher review submitted reports with revision workflow | Admin |
| Supervisor Notes | Supervisor adds notes to student reports | Supervisor |
| Program Lifecycle Extension | Extend program dates when necessary, with audit trail | Admin |

---

## 10. Placement — Slot Assignment

Bridge between supply (company slots) and demand (students needing host organizations).

| Feature | Description | Access |
|---|---|---|
| Placement Index | CRUD slots per company per program with quota tracking | Admin |
| Direct Placement | Assign student directly to slot (auto-creates Mentee+Registration) | Admin |
| Placement Change Request | Student requests slot change with reason | Student |
| Change Request Management | Admin reviews, approves, or rejects placement changes | Admin |
| Capacity Enforcement | Atomic quota increment/decrement, never exceeds limit | System |

---

## 11. Registration — Student Enrollment

Gateway domain — student enrollment into programs.

| Feature | Description | Access |
|---|---|---|
| Apply Page (Guest) | Submit application without login (personal data, school, program preferences) | Guest |
| Registration Center | Browse programs currently accepting registrations | Auth |
| Registration Wizard | Multi-step: select program, choose placement, review, submit | Student |
| Document Upload | Upload required documents per program requirements | Student |
| Registration Verification | Admin review pending registrations, assign placement and mentors, activate | Admin |
| Application Review | Admin approves guest applications (auto-creates User+Mentee+Registration) or rejects | Admin |

---

## 12. Mentee — Student Role

Student's lens — dashboard, progress tracking, self-service access.

| Feature | Description | Access |
|---|---|---|
| Progress Tracking | Real-time: assignments, attendance %, logbook entries, evaluations, guidance docs | Student |
| Mentor Visibility | See assigned mentors with contact info, photo | Student |
| Quick Actions | Write logbook, clock in, submit assignments, view evaluations | Student |

---

## 13. Attendance — Presence Tracking

Presence tracking during the placement program.

| Feature | Description | Access |
|---|---|---|
| Student Clock In | Timestamp-based, optional GPS data | Student |
| Student Clock Out | Auto-compute duration | Student |
| Absence Request | Submit planned or unplanned absence with reason and optional docs | Student |
| Absence Approval | Mentor approves single-day, extended requires additional approval | Mentor + Admin |
| Attendance Manager | CRUD records, filter, sort, reports | Admin |
| Compliance Monitoring | Notify mentor when attendance drops below threshold | System |
| Immutable Records | Records immutable after configurable window (default 24h) | System |

---

## 14. Logbook — Daily Journal

Daily journaling — students record activities, learnings, and plans.

| Feature | Description | Access |
|---|---|---|
| Logbook Entry | Daily entry: date, activities, learnings, challenges, plans, attachments | Student |
| Draft Workflow | DRAFT → SUBMITTED → VERIFIED, optional REVISION_REQUIRED → DRAFT | Student + Mentor |
| Mentor Review | View, acknowledge, comment, return for revision | Mentor |
| Calendar View | Color-coded: green (acknowledged), yellow (submitted), blue (draft), gray (no entry) | Student + Mentor |
| Compliance Monitoring | Auto-notify if N days without entry (default 3 to mentor, 5+ to coordinator) | System |
| One Entry Per Day | Maximum one entry per calendar day per student | System |

---

## 15. Assignment — Tasks & Submissions

Task-based learning — teachers create tasks, students submit, teachers grade.

| Feature | Description | Access |
|---|---|---|
| Assignment Manager | CRUD tasks: title, description, due dates, resources, points, rubric | Admin |
| Submit Assignment | Text, file uploads, or both with draft workflow | Student |
| Submission Grading | Numeric score, rubric-referenced, written feedback | Teacher |
| Submission Lifecycle | DRAFT → SUBMITTED → VERIFIED → GRADED, optional REVISION_REQUIRED → DRAFT | System |
| Deadline Management | Due dates, late flagging, extension support | Teacher |
| Version History | Every save and submit versioned for audit | System |

---

## 16. Mentor — Supervision

Supervision toolkit for teachers and company supervisors.

| Feature | Description | Access |
|---|---|---|
| Supervision Logs | Private notes: observations, concerns, action items | Mentor |
| Supervision Log Manager | Manage logs with search and filter | Mentor |
| Report Review | View mentee submitted reports | Mentor |
| Assess Student Performance | Evaluate student against program competencies | Teacher |
| Submissions Grading | Grade student submissions | Teacher |

---

## 17. Schedule — Calendar Events

Event calendar management.

| Feature | Description | Access |
|---|---|---|
| Schedule Index | CRUD events: title, description, times, location, category, program | Admin |
| Recurring Events | Daily, weekly, biweekly, monthly with end condition | Admin |
| Calendar Views | Day, week, month, agenda | Student + Mentor + Admin |
| Event Reminders | Configurable in-app + email reminders | System |
| Conflict Detection | Detect overlapping events with warning | System |
| Past Event Immutability | Past events immutable — corrections require cancellation + recreation | System |

---

## 18. Guidance — Handbooks

Handbooks and documents that users must read and acknowledge.

| Feature | Description | Access |
|---|---|---|
| Handbook Manager | CRUD handbooks: title, slug, content (Markdown), version, active/inactive | Admin |
| Student Handbook View | Browse and read handbooks by role | Student/Teacher/Supervisor |
| Acknowledgement System | Immutable acknowledgement with user, timestamp, IP | User |
| Target Audience | Role-filtered: all, student, teacher, supervisor | System |

---

## 19. Incident — Issue Reporting

Structured incident reporting, investigation, and resolution.

| Feature | Description | Access |
|---|---|---|
| Incident Form | Report: date/time, location, description, category, severity, evidence uploads | All users |
| Severity Classification | LOW, MEDIUM, HIGH, CRITICAL with escalation behavior | System |
| Investigation Workflow | REPORTED → INVESTIGATING → RESOLVED → CLOSED | Admin |
| Immutable Timeline | Every action recorded: timestamp, actor, action type, details | System |
| Resolution Outcomes | CONFIRMED_ACTION_TAKEN, CONFIRMED_NO_ACTION, UNFOUNDED, REFERRED | Admin |
| CRITICAL Notifications | Out-of-band alerts to all admins for HIGH/CRITICAL severity | System |

---

## 20. Assessment — Competency Evaluation

Rubric-based competency evaluation framework.

| Feature | Description | Access |
|---|---|---|
| Rubric Manager | CRUD weighted criteria, performance levels, descriptive anchors | Admin |
| Competency Manager | Manage competencies and indicators within rubrics | Admin |
| Assessment Grading | Score against rubric, auto-calculate weighted total | Teacher |
| Presentation Schedule | Panel-based evaluation scheduling | Admin |
| Presentation Lifecycle | SCHEDULED → COMPLETED / CANCELLED | Admin |
| Finalization | Finalized assessments immutable — corrections require new round | System |

---

## 21. Evaluation — Feedback Collection

Structured feedback collection about the placement experience and program quality. Evaluations are collected from multiple perspectives throughout the program lifecycle.

| Feature | Description | Access |
|---|---|---|
| Mentor Evaluation | Student rates mentor communication, responsiveness, guidance quality | Student |
| Company Evaluation | Student rates workplace safety, task relevance, mentoring | Student |
| Overall Satisfaction | Independent overall satisfaction rating | Student |
| Score Bands | EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39) | System |
| Admin View | Filter by type, aggregate scores, trends | Admin |
| Program Quality Evaluation | Admin/teacher evaluates program outcomes: curriculum alignment, completion rates, partner satisfaction, areas for improvement. Triggered during program closure. | Admin |
| Evaluation Aggregation | Automated scoring and trend reporting across all evaluation types | System |

---

## 22. Document — Templates & Rendering

Rendering engine for generated PDF, spreadsheet, and other output files.

| Feature | Description | Access |
|---|---|---|
| Template Manager | Upload and manage document templates (Blade, CSS, XLSX) | Admin |
| Rendering Pipeline | 6-step: resolve template → discover renderer → gather data → inject → invoke driver → store | System |
| Reports Manager | Generate, view, and download reports | Admin |
| Download Endpoints | Authorized PDF and document downloads | Auth |
| Template Versioning | Every document records exact template version used | System |
| Archive Report Generation | Generate comprehensive program archive reports (grade summaries, attendance records, completion status) during closure | Admin |

---

## 23. Certificate — Credentialing

Credentialing — templates, issuance, revocation, and verification.

| Feature | Description | Access |
|---|---|---|
| Certificate Template Manager | CRUD templates: layout, branding, field mapping, versioning | Admin |
| Issue Certificate | Single issuance with unique serial number | Admin |
| Batch Issue | Cohort batch issuance (one failure does not block batch) | Admin |
| Revoke Certificate | Revoke with reason category | Admin |
| Serial Number Management | Strictly sequential, unique, permanently retired after revocation | System |
| Certificate Lifecycle | ISSUED → REVOKED (terminal, irreversible) | System |
| Student Certificates | View and download own certificates | Student |

---

## 24. Admin — System Administration

System-level management across all domains.

| Feature | Description | Access |
|---|---|---|
| User Manager | CRUD all roles: create, update, lock/unlock, mark as alumni | Admin |
| Admin Manager | Manage admin accounts | Super Admin |
| Student Manager | Manage student accounts; bulk archive completed students | Admin |
| Teacher Manager | Manage teacher accounts | Admin |
| Supervisor Manager | Manage supervisor accounts | Admin |
| Mentor Manager | Manage mentor records | Admin |
| Mentee Manager | Manage mentee records | Admin |
| Announcement Manager | Broadcast system with DRAFT/SCHEDULED/PUBLISHED lifecycle, Markdown, role-targeted | Admin |
| Scheduled Announcements | Auto-publish via scheduler every minute | System |
| Audit Log Manager | Centralized read-only audit log with filters | Admin |
| Account Clone Detector | Detect potential duplicate accounts | Admin |
| GDPR Deletion Logs | View GDPR erasure request history | Admin |
| Application Review | Review guest applications, approve (auto-create user) or reject | Admin |
| Bulk Operations | Mass user creation with result summaries | Admin |
| Archived Record Access | Read-only access to data from closed/archived programs | Admin |

---

## 25. Program Closure & Archival

The final stage of the program lifecycle. Once all students have completed their placements, all assessments are finalized, all certificates are issued, and all evaluations are collected, the program enters closure. This stage ensures that program data is preserved as an immutable school archive before the program is marked complete.

These features are implemented within the **Internship** domain (closure logic and data snapshot) and the **User** domain (alumni status), coordinated by a Process Action.

| Feature | Description | Access |
|---|---|---|
| Closure Readiness Check | Automated verification: all assessments finalized, all submissions graded, all attendance verified, all supervision logs signed, all certificates issued | Admin |
| Program Finalization | Compute final grade aggregates, lock all mutable records, freeze assessment scores | Admin |
| Student Alumni Marking | All active students in the program are automatically marked as alumni (AccountStatus → ARCHIVED). Their accounts remain accessible (read-only dashboard, certificate download) but cannot participate in new programs. | System |
| Data Snapshot | Immutable archive of all program records: registrations, attendance, logbooks, assignments, assessments, evaluations, certificates. Stored as a versioned snapshot. | System |
| Program Status Transition | Program moves from COMPLETED → ARCHIVED once the snapshot is confirmed. Archived programs are read-only everywhere. | Admin |
| Archived Program View | Read-only access to browse archived programs. All data preserved in its final state: grades, attendance summaries, journal archives, certificate records. | Admin |
| Program Evaluation Trigger | When a program is archived, admin/teachers are prompted to complete a Program Quality Evaluation before the closure is finalized. | Admin |
| Archive Report | Generated summary document containing: student rosters, final grade sheets, attendance summaries, certificate logs, evaluation aggregates. Suitable for school records and accreditation. | Admin |
| Archive Restoration (exceptional) | In rare cases where an archived program must be reopened (e.g., data correction), a controlled un-archive process with full audit trail. Requires super_admin authorization. | Super Admin |

---

## Role Access Matrix

| Role | Domain Access |
|---|---|
| **SUPER_ADMIN** | Unrestricted — bypasses all permission checks |
| **ADMIN** | School, Settings, Admin, Partnership, Placement, Registration, Internship (including closure & archival), Assessment, Certificate, Document, Schedule, Attendance, Logbook (read), Incident, Guidance, User management, Evaluation (admin view) |
| **TEACHER** | Mentor (supervision), Assignment (grading), Assessment (grading), Logbook (review), Attendance (approve absence), Evaluation (program quality) |
| **SUPERVISOR** | Mentor (supervision), Report (notes), Logbook (review), Attendance (approve absence) |
| **STUDENT** | Mentee (dashboard), Registration, Logbook, Attendance (clock-in), Assignment (submit), Assessment (view), Evaluation (submit), Incident (report), Placement (change request), Certificate (download), Guidance (view), Schedule (view) |
| **GUEST** | Setup wizard, Registration (apply), Login, Forgot/Reset Password |

---

> **Total: 23 domains with 160+ features covering the complete program lifecycle:**
> Foundation → Installation → Identity → Institution → Partnerships → Program Setup → Enrollment
> → Daily Operations → Assessment & Reporting → Evaluation → Certification → **Closure & Archival**
>
> See [Architecture](architecture.md) for the domain structure and [Product Definition](product-definition.md)
> for the product vision and scope.
