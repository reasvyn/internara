# Key Features
> Last updated: 2026-06-04
> Changes: Restructured all feature sections from 25 old-domain groups into 16 current domains; pruned non-existent features (Mentor/Mentee Manager, Company/Overall/Program Quality Evaluation, Evaluation Aggregation, closure pipeline beyond readiness check); corrected User dashboard features (Journal Progress, Dashboard Quick Actions)

> Every feature in Internara belongs to one of **16 domains**. Each domain owns its
> complete vertical slice: persistence, business rules, UI components, authorization, and HTTP
> interface.
>
> Domains below are listed in **program lifecycle order** — from system foundation through
> installation, daily operations, assessment, certification, and finally program closure and
> archival.

## Contents

- [1. Core — Foundation & Infrastructure](#1-core--foundation--infrastructure)
- [2. User — Identity, Auth & Profiles](#2-user--identity-auth--profiles)
- [3. SysAdmin — System Administration](#3-sysadmin--system-administration)
- [4. Academics — School & Departments](#4-academics--school--departments)
- [5. Program — Internship Lifecycle](#5-program--internship-lifecycle)
- [6. Enrollment — Registration & Placement](#6-enrollment--registration--placement)
- [7. Assessment — Competency Evaluation](#7-assessment--competency-evaluation)
- [8. Evaluation — Feedback Collection](#8-evaluation--feedback-collection)
- [9. Assignment — Tasks & Submissions](#9-assignment--tasks--submissions)
- [10. Journals — Logbook, Attendance & Scheduling](#10-journals--logbook-attendance--scheduling)
- [11. Guidance — Mentoring & Handbooks](#11-guidance--mentoring--handbooks)
- [12. Incident — Issue Reporting](#12-incident--issue-reporting)
- [13. Partners — Companies & Agreements](#13-partners--companies--agreements)
- [14. Certification — Credentialing](#14-certification--credentialing)
- [15. Reports — Student Final Reports](#15-reports--student-final-reports)
- [16. Document — Templates & Rendering](#16-document--templates--rendering)
- [Role Access Matrix](#role-access-matrix)

---

## 1. Core — Foundation & Infrastructure

Base classes, contracts, infrastructure, and cross-domain utilities used by every domain.

| Feature | Description |
|---|---|
| Base Model (UUID) | All models (except User) extend `BaseModel` with UUID primary keys |
| Base Action | Every business operation extends `BaseAction` — transaction + logging |
| Base Entity | `final readonly` business rules with zero framework dependencies |
| Base Policy | Authorization with role and ownership checks |
| Base Record Manager | CRUD Livewire base with search, filter, sort, pagination, bulk actions |
| Base Controller | Common controller utilities for HTTP endpoints |
| FormRequest | Validation exception handling without redirect |
| Data (DTO) | Abstract readonly data transfer objects with `fromArray()` / `toArray()` |
| SmartLogger | Dual-channel fluent logger (system + activity) with PII masking |
| Exception Hierarchy | AppException (action/infrastructure/presentation) + DomainException (separate tree) |
| StatusEnum Contract | State machine via `canTransitionTo()`, `isTerminal()`, `validTransitions()` |
| ColorableEnum Contract | Status enums with CSS color variant support |
| LabelEnum Contract | All enums implement `label(): string` for UI display |
| Security Headers Middleware | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| Log Context Middleware | Request tracing (request_id, method, URL, IP, user) |
| System Health | 15-point check: environment, PHP, extensions, memory, DB, migrations, storage, disk, queue, cache, app key |
| System Cleanup | Prune expired resets, stale cache, failed jobs, old logs |
| Activity Logging | Spatie Activity Log with query scopes |
| HandlesActionErrors Trait | Consistent try-catch-log-rethrow pattern |
| Environment Detection | Centralized environment checks (debug, development, production) |
| Locale Management | Bilingual English/Indonesian with session preference |
| Theme System | Color resolution from settings into CSS custom properties (light/dark) |
| CSV Handler | Export, import, and template download with optional header validation |
| Language Switcher | Livewire bilingual toggle |
| Theme Switcher | Livewire light/dark/system theme toggle |
| Lang Checker | Development helper: logs warnings for missing translation keys |

---

## 2. User — Identity, Auth & Profiles

Authentication, user profiles, role-based dashboards, notifications, account lifecycle, and recovery.

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
| User & Profile Models | UUID-based user identity with extended profile (phone, address, gender, blood type, emergency contact, national ID, school/department FK) | All |
| Profile Editor | Self-service personal data update (name, email, phone, address, bio) | Auth |
| Avatar Upload | Single image via media library, 200x200 WebP thumbnail | Auth |
| Role-based Dashboard | Automatic routing to admin/teacher/supervisor/student dashboard by role priority | Auth |
| Admin Dashboard | System overview: user stats, readiness checklist, quick links | Admin |
| Teacher Dashboard | Supervision view: supervised students, pending journals, active companies | Teacher |
| Supervisor Dashboard | Industry view: active participants, pending evaluations, verified journals | Supervisor |
| Student Dashboard | Registration status, journal progress, quick action buttons (write journal, request absence, documents, handbooks) | Student |
| Notification Center | Full-page with search, filter (unread/read), sorting, bulk mark-read/delete | Auth |
| Notification Bell | Navbar indicator with unread count | Auth |
| Recent Activity Feed | Chronological user activity log | Auth |
| Username Generation | Unique username generation with collision avoidance | System |
| Journal Progress | Journal verification progress (verified/total) on dashboard | Student |
| Mentor Visibility | See assigned mentors with contact info, photo | Student |
| Dashboard Quick Actions | Write journal, request absence, view documents, browse handbooks | Student |

---

## 3. SysAdmin — System Administration

Setup wizard, runtime configuration, user CRUD, announcements, audit logging, health monitoring, and GDPR compliance.

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
| System Setting Manager | Key-value store with type enforcement (boolean, text, numeric, JSON, image, color) | Super Admin |
| Branding Configuration | App name, logo, favicon, colors (primary/secondary/accent), custom CSS | Super Admin |
| Feature Flags | Enable/disable features at runtime | Super Admin |
| Mail Configuration | SMTP settings with test email verification | Super Admin |
| Cache Invalidation | Every setting change immediately invalidates cache | System |
| Settings Audit Trail | Every change logged with before/after values, user, timestamp | System |
| User Manager | CRUD all roles: create, update, lock/unlock, mark as alumni | Admin |
| Admin Manager | Manage admin accounts | Super Admin |
| Student Manager | Manage student accounts; bulk archive completed students | Admin |
| Teacher Manager | Manage teacher accounts | Admin |
| Supervisor Manager | Manage supervisor accounts | Admin |
| Announcement Manager | Broadcast system with DRAFT/SCHEDULED/PUBLISHED lifecycle, Markdown, role-targeted | Admin |
| Scheduled Announcements | Auto-publish via scheduler every minute | System |
| Audit Log Manager | Centralized read-only audit log with filters | Admin |
| Account Clone Detector | Detect potential duplicate accounts | Admin |
| GDPR Deletion Logs | View GDPR erasure request history | Admin |
| Application Review | Review guest applications, approve (auto-create user) or reject | Admin |
| Bulk Operations | Mass user creation with result summaries | Admin |
| Archived Record Access | Read-only access to data from closed/archived programs | Admin |

---

## 4. Academics — School & Departments

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

## 5. Program — Internship Lifecycle

Program definitions, requirements, groups, phases, lifecycle management, and closure readiness.

| Feature | Description | Access |
|---|---|---|
| Program Manager | CRUD programs: name, dates, academic year, department, type | Admin |
| Program Lifecycle | DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED with transition gates | Admin |
| Requirement Manager | Document requirements per program (DOCUMENT, SKILL, TEXT) | Admin |
| Group Manager | Groups with member roles | Admin |
| Phase Manager | Program phases/timeline stages | Admin |
| Program Lifecycle Extension | Extend program dates when necessary, with audit trail | Admin |
| Closure Readiness Check | Automated verification: all assessments finalized, all submissions graded, all attendance verified, all supervision logs signed, all certificates issued | Admin |

---

## 6. Enrollment — Registration & Placement

Student registration, placement slot assignment, and change requests.

| Feature | Description | Access |
|---|---|---|
| Apply Page (Guest) | Submit application without login (personal data, school, program preferences) | Guest |
| Registration Center | Browse programs currently accepting registrations | Auth |
| Registration Wizard | Multi-step: select program, choose placement, review, submit | Student |
| Document Upload | Upload required documents per program requirements | Student |
| Registration Verification | Admin review pending registrations, assign placement and mentors, activate | Admin |
| Application Review | Admin approves guest applications (auto-creates User+Mentee+Registration) or rejects | Admin |
| Placement Index | CRUD slots per company per program with quota tracking | Admin |
| Direct Placement | Assign student directly to slot (auto-creates Mentee+Registration) | Admin |
| Placement Change Request | Student requests slot change with reason | Student |
| Change Request Management | Admin reviews, approves, or rejects placement changes | Admin |
| Capacity Enforcement | Atomic quota increment/decrement, never exceeds limit | System |

---

## 7. Assessment — Competency Evaluation

Rubric-based competency evaluation framework with presentations.

| Feature | Description | Access |
|---|---|---|
| Rubric Manager | CRUD weighted criteria, performance levels, descriptive anchors | Admin |
| Competency Manager | Manage competencies and indicators within rubrics | Admin |
| Assessment Grading | Score against rubric, auto-calculate weighted total | Teacher |
| Presentation Schedule | Panel-based evaluation scheduling | Admin |
| Presentation Lifecycle | SCHEDULED → COMPLETED / CANCELLED | Admin |
| Finalization | Finalized assessments immutable — corrections require new round | System |

---

## 8. Evaluation — Feedback Collection

Structured mentor evaluation with score bands and admin oversight.

| Feature | Description | Access |
|---|---|---|
| Mentor Evaluation | Student rates mentor communication, responsiveness, guidance quality | Student |
| Score Bands | EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39) | System |
| Admin View | Filter by type, aggregate scores, trends | Admin |

---

## 9. Assignment — Tasks & Submissions

Task creation, student submissions, grading workflow, and revision loop.

| Feature | Description | Access |
|---|---|---|
| Assignment Manager | CRUD tasks: title, description, due dates, resources, points, rubric | Admin |
| Submit Assignment | Text, file uploads, or both with draft workflow | Student |
| Submission Grading | Numeric score, rubric-referenced, written feedback | Teacher |
| Submission Lifecycle | DRAFT → SUBMITTED → VERIFIED → GRADED, optional REVISION_REQUIRED → DRAFT | System |
| Deadline Management | Due dates, late flagging, extension support | Teacher |
| Version History | Every save and submit versioned for audit | System |

---

## 10. Journals — Logbook, Attendance & Scheduling

Daily activity tracking: logbook entries, attendance with clock-in/out, absence requests, and schedule management.

| Feature | Description | Access |
|---|---|---|
| Logbook Entry | Daily entry: date, activities, learnings, challenges, plans, attachments | Student |
| Logbook Draft Workflow | DRAFT → SUBMITTED → VERIFIED, optional REVISION_REQUIRED → DRAFT | Student + Mentor |
| Mentor Review | View, acknowledge, comment, return for revision | Mentor |
| Calendar View | Color-coded: green (verified), yellow (submitted), blue (draft), gray (no entry) | Student + Mentor |
| Compliance Monitoring | Auto-notify if N days without entry (default 3 to mentor, 5+ to coordinator) | System |
| One Entry Per Day | Maximum one entry per calendar day per student | System |
| Student Clock In | Timestamp-based, optional GPS data | Student |
| Student Clock Out | Auto-compute duration | Student |
| Absence Request | Submit planned or unplanned absence with reason and optional docs | Student |
| Absence Approval | Mentor approves single-day, extended requires additional approval | Mentor + Admin |
| Attendance Manager | CRUD records, filter, sort, reports | Admin |
| Attendance Compliance | Notify mentor when attendance drops below threshold | System |
| Immutable Attendance Records | Records immutable after configurable window (default 24h) | System |
| Schedule Index | CRUD events: title, description, times, location, category, program | Admin |
| Recurring Events | Daily, weekly, biweekly, monthly with end condition | Admin |
| Calendar Views | Day, week, month, agenda | Student + Mentor + Admin |
| Event Reminders | Configurable in-app + email reminders | System |
| Conflict Detection | Detect overlapping events with warning | System |
| Past Event Immutability | Past events immutable — corrections require cancellation + recreation | System |

---

## 11. Guidance — Mentoring & Handbooks

Mentoring relationships, supervision logs, handbooks, and acknowledgement tracking.

| Feature | Description | Access |
|---|---|---|
| Supervision Logs | Private notes: observations, concerns, action items | Mentor |
| Supervision Log Manager | Manage logs with search and filter | Mentor |
| Report Review | View mentee submitted reports | Mentor |
| Assess Student Performance | Evaluate student against program competencies | Teacher |
| Submissions Grading | Grade student submissions | Teacher |
| Handbook Manager | CRUD handbooks: title, slug, content (Markdown), version, active/inactive | Admin |
| Student Handbook View | Browse and read handbooks by role | Student/Teacher/Supervisor |
| Acknowledgement System | Immutable acknowledgement with user, timestamp, IP | User |
| Target Audience | Role-filtered: all, student, teacher, supervisor | System |

---

## 12. Incident — Issue Reporting

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

## 13. Partners — Companies & Agreements

External relationship management — company profiles and partnership agreements.

| Feature | Description | Access |
|---|---|---|
| Company Manager | CRUD company profiles (name, address, industry, website, contact) | Admin |
| Partnership Manager | CRUD agreements (number, title, dates, scope, contact person, signing parties) | Admin |
| Partnership Lifecycle | ACTIVE, EXPIRED, TERMINATED with transition rules | System |
| MOU Document Upload | Upload agreement documents via media library | Admin |
| Expiry Detection | Warns when partnership is expiring (default 30 days) | System |

---

## 14. Certification — Credentialing

Certificate templates, issuance, revocation, and verification.

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

## 15. Reports — Student Final Reports

Student-authored final reports with revision workflow and supervisor review.

| Feature | Description | Access |
|---|---|---|
| Report Writer | Student writes and submits final internship reports | Student |
| Report Review | Admin/teacher review submitted reports with revision workflow | Admin |
| Supervisor Notes | Supervisor adds notes to student reports | Supervisor |

---

## 16. Document — Templates & Rendering

Rendering engine for official documents, PDF generation, and template management.

| Feature | Description | Access |
|---|---|---|
| Template Manager | Upload and manage document templates (Blade, CSS, XLSX) | Admin |
| Rendering Pipeline | 6-step: resolve template → discover renderer → gather data → inject → invoke driver → store | System |
| Reports Manager | Generate, view, and download reports | Admin |
| Download Endpoints | Authorized PDF and document downloads | Auth |
| Template Versioning | Every document records exact template version used | System |

---

## Role Access Matrix

| Role | Domain Access |
|---|---|
| **SUPER_ADMIN** | Unrestricted — bypasses all permission checks |
| **ADMIN** | Academics, SysAdmin, Partners, Program, Enrollment, Assessment, Certificate, Document, Journals (read), Incident, Guidance, User management, Evaluation (admin view) |
| **TEACHER** | Guidance (supervision), Assignment (grading), Assessment (grading), Journals (logbook review, attendance approve), Evaluation (admin view) |
| **SUPERVISOR** | Guidance (supervision), Reports (notes), Journals (logbook review, attendance approve) |
| **STUDENT** | User (dashboard), Enrollment (registration, placement request), Journals (logbook, attendance), Assignment (submit), Assessment (view), Evaluation (submit), Incident (report), Certification (download), Guidance (view) |
| **GUEST** | SysAdmin (setup wizard), Enrollment (apply), User (login, forgot/reset password) |

---

> **Total: 16 domains with 150+ features covering the complete program lifecycle:**
> Foundation → Identity → System Administration → Institution → Partnerships → Program Setup
> → Enrollment → Daily Operations → Assessment → Evaluation → Certification → **Closure**
>
> See [Architecture](architecture.md) for the domain structure and [Product Definition](product-definition.md)
> for the product vision and scope.
