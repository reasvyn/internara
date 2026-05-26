# Key Features

> Every feature in Internara belongs to one of **24 business domains**. Each domain owns its
> complete vertical slice: persistence, business rules, UI components, authorization, and HTTP
> interface.

## Contents

- [1. Auth — Keamanan & Identitas](#1-auth--keamanan--identitas)
- [2. User — Identitas & Profil](#2-user--identitas--profil)
- [3. School — Institusi](#3-school--institusi)
- [4. Settings — Konfigurasi Runtime](#4-settings--konfigurasi-runtime)
- [5. Setup — Instalasi](#5-setup--instalasi)
- [6. Registration — Pendaftaran Magang](#6-registration--pendaftaran-magang)
- [7. Internship — Program Magang](#7-internship--program-magang)
- [8. Placement — Penempatan](#8-placement--penempatan)
- [9. Partnership — Kemitraan](#9-partnership--kemitraan)
- [10. Mentee — Siswa](#10-mentee--siswa)
- [11. Mentor — Supervisi](#11-mentor--supervisi)
- [12. Attendance — Kehadiran](#12-attendance--kehadiran)
- [13. Logbook — Jurnal Harian](#13-logbook--jurnal-harian)
- [14. Schedule — Jadwal](#14-schedule--jadwal)
- [15. Assignment — Tugas](#15-assignment--tugas)
- [16. Guidance — Panduan](#16-guidance--panduan)
- [17. Incident — Insiden](#17-incident--insiden)
- [18. Assessment — Penilaian Kompetensi](#18-assessment--penilaian-kompetensi)
- [19. Evaluation — Evaluasi Mentor/Program](#19-evaluation--evaluasi-mentorprogram)
- [20. Document — Dokumen & Rendering](#20-document--dokumen--rendering)
- [21. Certificate — Sertifikat](#21-certificate--sertifikat)
- [22. Admin — Administrasi Sistem](#22-admin--administrasi-sistem)
- [23. Core — Fondasi Arsitektural](#23-core--fondasi-arsitektural)
- [24. Shared — Utilitas Lintas Domain](#24-shared--utilitas-lintas-domain)
- [Role Access Matrix](#role-access-matrix)

---

## 1. Auth — Keamanan & Identitas

Security boundary — login, autentikasi, RBAC, account lifecycle, recovery.

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

## 2. User — Identitas & Profil

Identity persistence — user profiles, dashboard routing, avatar, notifications.

| Feature | Description | Access |
|---|---|---|
| User & Profile Models | UUID-based user identity with extended profile (phone, address, gender, blood type, emergency contact, national ID, school/department FK) | All |
| Profile Editor | Self-service personal data update (name, email, phone, address, bio) | Auth |
| Avatar Upload | Single image via media library, 200x200 WebP thumbnail | Auth |
| Role-based Dashboard | Automatic routing to admin/teacher/supervisor/student dashboard by role priority | Auth |
| Admin Dashboard | System overview: user stats, readiness checklist, quick links | Admin |
| Teacher Dashboard | Supervision view: supervised students, pending journals, active companies | Teacher |
| Supervisor Dashboard | Industry view: active interns, pending evaluations, verified journals | Supervisor |
| Student Dashboard | Registration status, journal progress, quick actions (write journal, clock in, etc.) | Student |
| Notification Center | Full-page with search, filter (unread/read), sorting, bulk mark-read/delete | Auth |
| Notification Bell | Navbar indicator with unread count | Auth |
| Recent Activity Feed | Chronological user activity log | Auth |
| Username Generation | Unique username generation with collision avoidance | System |

---

## 3. School — Institusi

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

## 4. Settings — Konfigurasi Runtime

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

## 5. Setup — Instalasi

First-run wizard — prepares the application from empty database to fully operational.

| Feature | Description | Access |
|---|---|---|
| 7-Step Setup Wizard | Guided: Environment Check, School, Department, Admin Account, Internship (optional), Finalize, Complete | Guest (token) |
| Environment Audit | PHP version, extensions, directory permissions, database, terminal | Installer |
| Setup Token | Encrypted random token gates wizard access | System |
| School Initialization | Create first school profile | Installer |
| Super Admin Creation | Create first super_admin account (name always "Administrator", username "superadmin") | Installer |
| Recovery Key Generation | 64-char random key (shown once, hashed in storage) | Installer |
| CLI Install | `php artisan setup:install` with `--check-only` and `--force` flags | CLI |
| Super Admin Recovery | Emergency CLI recovery when all super admins are lost | CLI |

---

## 6. Registration — Pendaftaran Magang

Gateway domain — student enrollment into internship programs.

| Feature | Description | Access |
|---|---|---|
| Apply Page (Guest) | Submit application without login (personal data, school, internship preferences) | Guest |
| Registration Center | Browse programs currently accepting registrations | Auth |
| Registration Wizard | Multi-step: select program, choose placement, review, submit | Student |
| Document Upload | Upload required documents per program requirements | Student |
| Registration Verification | Admin review pending registrations, assign placement and mentors, activate | Admin |
| Application Review | Admin approves guest applications (auto-creates User+Mentee+Registration) or rejects | Admin |

---

## 7. Internship — Program Magang

Core operational domain — program definitions, requirements, reports, groups, phases.

| Feature | Description | Access |
|---|---|---|
| Internship Manager | CRUD programs: name, dates, academic year, department, type | Admin |
| Program Lifecycle | DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED with transition gates | Admin |
| Requirement Manager | Document requirements per program (DOCUMENT, SKILL, TEXT) | Admin |
| Group Manager | Internship groups with member roles | Admin |
| Phase Manager | Program phases/timeline stages | Admin |
| Report Writer | Student writes and submits reports | Student |
| Report Review | Admin/teacher review submitted reports | Admin |
| Supervisor Notes | Supervisor adds notes to student reports | Supervisor |

---

## 8. Placement — Penempatan

Bridge between supply (company slots) and demand (students needing host organizations).

| Feature | Description | Access |
|---|---|---|
| Placement Index | CRUD slots per company per program with quota tracking | Admin |
| Direct Placement | Assign student directly to slot (auto-creates Mentee+Registration) | Admin |
| Placement Change Request | Student requests slot change with reason | Student |
| Change Request Management | Admin reviews, approves, or rejects placement changes | Admin |
| Capacity Enforcement | Atomic quota increment/decrement, never exceeds limit | System |

---

## 9. Partnership — Kemitraan

External relationships — companies and partnership agreements.

| Feature | Description | Access |
|---|---|---|
| Company Manager | CRUD company profiles (name, address, industry, website, contact) | Admin |
| Partnership Manager | CRUD agreements (number, title, dates, scope, contact person, signing parties) | Admin |
| Partnership Lifecycle | ACTIVE, EXPIRED, TERMINATED with transition rules | System |
| MOU Document Upload | Upload agreement documents via media library | Admin |
| Expiry Detection | Warns when partnership is expiring (default 30 days) | System |

---

## 10. Mentee — Siswa

Student's lens — dashboard, progress tracking, self-service access.

| Feature | Description | Access |
|---|---|---|
| Progress Tracking | Real-time: assignments, attendance %, logbook entries, evaluations, guidance docs | Student |
| Mentor Visibility | See assigned mentors with contact info, photo | Student |
| Quick Actions | Write logbook, clock in, submit assignments, view evaluations | Student |

---

## 11. Mentor — Supervisi

Supervision toolkit for teachers and company supervisors.

| Feature | Description | Access |
|---|---|---|
| Supervision Logs | Private notes: observations, concerns, action items | Mentor |
| Supervision Log Manager | Manage logs with search and filter | Mentor |
| Report Review | View mentee submitted reports | Mentor |
| Assess Internship | Evaluate student internship performance | Teacher |
| Submissions Grading | Grade student submissions | Teacher |
| Evaluate Mentor | Admin evaluates mentor performance | Admin |

---

## 12. Attendance — Kehadiran

Presence tracking during internship.

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

## 13. Logbook — Jurnal Harian

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

## 14. Schedule — Jadwal

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

## 15. Assignment — Tugas

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

## 16. Guidance — Panduan

Handbooks and documents that users must read and acknowledge.

| Feature | Description | Access |
|---|---|---|
| Handbook Manager | CRUD handbooks: title, slug, content (Markdown), version, active/inactive | Admin |
| Student Handbook View | Browse and read handbooks by role | Student/Teacher/Supervisor |
| Acknowledgement System | Immutable acknowledgement with user, timestamp, IP | User |
| Target Audience | Role-filtered: all, student, teacher, supervisor | System |

---

## 17. Incident — Insiden

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

## 18. Assessment — Penilaian Kompetensi

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

## 19. Evaluation — Evaluasi Mentor/Program

Structured feedback collection about the internship experience.

| Feature | Description | Access |
|---|---|---|
| Mentor Evaluation | Rate communication, responsiveness, guidance quality | Student |
| Program Evaluation | Rate curriculum relevance, administration, facility support | Student |
| Company Evaluation | Rate workplace safety, task relevance, mentoring | Student |
| Overall Satisfaction | Independent overall satisfaction rating | Student |
| Score Bands | EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39) | System |
| Admin View | Filter by type, aggregate scores, trends | Admin |

---

## 20. Document — Dokumen & Rendering

Rendering engine for generated PDF, spreadsheet, and other output files.

| Feature | Description | Access |
|---|---|---|
| Template Manager | Upload and manage document templates (Blade, CSS, XLSX) | Admin |
| Rendering Pipeline | 6-step: resolve template → discover renderer → gather data → inject → invoke driver → store | System |
| Reports Manager | Generate, view, and download reports | Admin |
| Download Endpoints | Authorized PDF and document downloads | Auth |
| Template Versioning | Every document records exact template version used | System |

---

## 21. Certificate — Sertifikat

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

## 22. Admin — Administrasi Sistem

System-level management across all domains.

| Feature | Description | Access |
|---|---|---|
| User Manager | CRUD all roles: create, update, lock/unlock, archive | Admin |
| Admin Manager | Manage admin accounts | Super Admin |
| Student Manager | Manage student accounts | Admin |
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

---

## 23. Core — Fondasi Arsitektural

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

## 24. Shared — Utilitas Lintas Domain

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

## Role Access Matrix

| Role | Domain Access |
|---|---|
| **SUPER_ADMIN** | Unrestricted — bypasses all permission checks |
| **ADMIN** | School, Settings, Admin, Partnership, Placement, Registration, Internship, Assessment, Certificate, Document, Schedule, Attendance, Logbook (read), Incident, Guidance, User management |
| **TEACHER** | Mentor (supervision), Assignment (grading), Assessment (grading), Logbook (review), Attendance (approve absence) |
| **SUPERVISOR** | Mentor (supervision), Report (notes), Logbook (review), Attendance (approve absence) |
| **STUDENT** | Mentee (dashboard), Registration, Logbook, Attendance (clock-in), Assignment (submit), Assessment (view), Evaluation (submit), Incident (report), Placement (change request), Certificate (download), Guidance (view), Schedule (view) |
| **GUEST** | Setup wizard, Registration (apply), Login, Forgot/Reset Password |

---

> **Total: 24 business domains with 150+ features identified.**
> See [Architecture](architecture.md) for the domain structure and [Domain Index](domain/domain-index.md)
> for the complete domain reference.
