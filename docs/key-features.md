# Key Features

> **Last updated:** 2026-06-10

Complete feature inventory across all 19 modules, organized by program lifecycle phase.

---

## Foundation Phase

### Core — Foundation & Infrastructure

Base classes, contracts, middleware, and cross-module utilities.

| Feature | Description |
|---------|-------------|
| BaseModel (UUID) | All models extend `BaseModel` with UUID primary keys and `HasUuids` |
| BaseAction | Every business operation extends `BaseAction` — transaction + logging |
| BaseEntity | `final readonly` business rules, zero framework dependencies |
| BasePolicy | Role and ownership authorization checks |
| BaseRecordManager | CRUD Livewire base: search, filter, sort, pagination, bulk actions |
| BaseController | Common HTTP controller utilities |
| BaseFormRequest | Core's form request (not Laravel's) with validation exception handling |
| BaseData | Readonly DTO with `fromArray()` / `toArray()` |
| SmartLogger | Dual-channel fluent logger (system + activity) with PII masking |
| Exception Hierarchy | AppException (action/infrastructure/presentation) + ModuleException |
| StatusEnum | State machine via `canTransitionTo()`, `isTerminal()`, `validTransitions()` |
| LabelEnum | All enums implement `label(): string` |
| Security Headers | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy |
| Log Context | Request tracing: request_id, method, URL, IP, user |
| System Health | 15-point check (PHP, extensions, memory, DB, migrations, storage, queue, cache, app key) |
| Activity Logging | Spatie Activity Log with query scopes |
| CSV Handler | Export, import, template download with header validation |
| Language Switcher | Livewire bilingual toggle (en/id) |
| Theme Switcher | Livewire light/dark/system theme toggle |

### Setup — Installation & Provisioning

One-time guided installation.

| Feature | Description | Access |
|---------|-------------|--------|
| 6-Step Setup Wizard | Environment Audit, Super Admin, School, Department, Finalize, Complete | Guest (token) |
| Environment Audit | PHP version, extensions, directory permissions, database, terminal | Installer |
| Setup Token | Encrypted random token gates wizard access, single-use | System |
| School Initialization | Create first school profile in settings | Installer |
| Super Admin Creation | Name always "Administrator", username "superadmin" | Installer |
| Recovery Key | 64-char random key, stored hashed in DB, saved to `storage/app/private/.recovery-key` | Installer |
| CLI Install | `php artisan setup:install` with `--check-only` and `--force` | CLI |
| Super Admin Recovery | `php artisan admin:recover` — emergency CLI recovery | CLI |

### Settings — System Configuration & Branding

Key-value configuration store with dynamic resolution.

| Feature | Description | Access |
|---------|-------------|--------|
| System Setting Manager | Key-value store with type enforcement (boolean, text, numeric, JSON, image, color) | Super Admin |
| Branding Configuration | App name, logo, favicon, colors (primary/secondary/accent), custom CSS | Super Admin |
| Feature Flags | Enable/disable features at runtime | Super Admin |
| Mail Configuration | SMTP settings with test email verification | Super Admin |
| Theme System | Color resolution into CSS custom properties (light/dark) | System |
| Locale Management | Bilingual with session preference, resolved from stored setting | System |

---

## User Management Phase

### Auth — Authentication & Authorization

Login, password management, account recovery, RBAC.

| Feature | Description | Access |
|---------|-------------|--------|
| Login via Email/Username | 4-step sequential validation, auto-lock after 10 failures | Guest |
| Forgot Password | Email-based reset (60 min expiry, single-use token) | Guest |
| Reset Password | New password via email token | Guest |
| Confirm Password | Re-authenticate before sensitive operations | Auth |
| Rate Limiting | Multi-endpoint throttling (login 5/60s, forgot 3/3600s, reset 5/300s, recovery 3/300s) | Guest + Auth |
| Recovery Slip | Admin generates 10 one-time codes, delivered offline, no expiry | Admin |
| Account Recovery | User redeems code to unlock account and set new password | Guest |
| RBAC | 5 roles + 2 functional roles (mentor, mentee) with `Role::resolvesTo()` | All |
| Super Admin Integrity | Name/username immutable (Administrator/superadmin), non-deletable | System |

### User — Identity & Profiles

User profiles, notifications, dashboards, account lifecycle.

| Feature | Description | Access |
|---------|-------------|--------|
| User & Profile Models | UUID-based identity with extended profile (phone, address, gender, blood type, emergency contact, NISN/NIP) | All |
| Profile Editor | Self-service data update (name, email, phone, address, bio) | Auth |
| Avatar Upload | Single image via media library, 200x200 WebP thumbnail | Auth |
| Role-based Dashboard | Auto-routing to admin/teacher/supervisor/student dashboard | Auth |
| Admin Dashboard | User stats, readiness checklist, quick links | Admin |
| Teacher Dashboard | Supervised students, pending journals, active companies | Teacher |
| Supervisor Dashboard | Active participants, pending evaluations, verified journals | Supervisor |
| Student Dashboard | Registration status, journal progress, quick actions | Student |
| Notification Center | Full-page with search, filter, bulk mark-read/delete | Auth |
| Notification Bell | Navbar indicator with unread count | Auth |
| Account State Machine | 8 states with strict transition guards | System |

### SysAdmin — System Administration

User CRUD, announcements, audit logs, health monitoring.

| Feature | Description | Access |
|---------|-------------|--------|
| User Manager | CRUD all roles: create, update, lock/unlock, mark alumni | Admin |
| Admin Manager | Manage admin accounts | Super Admin |
| Student Manager | Manage students; bulk archive completed | Admin |
| Teacher Manager | Manage teacher accounts | Admin |
| Supervisor Manager | Manage supervisor accounts | Admin |
| Announcement Manager | DRAFT/SCHEDULED/PUBLISHED lifecycle, Markdown, role-targeted | Admin |
| Audit Log Manager | Centralized read-only audit log with filters | Admin |
| Bulk Operations | Mass user creation with result summaries | Admin |

---

## Academic Setup Phase

### Academics — School & Departments

Institutional foundation.

| Feature | Description | Access |
|---------|-------------|--------|
| School Profile Editor | Institutional data: legal name, code, address, contact, logo | Admin |
| Department Manager | CRUD departments with search, sort, paginate | Admin |
| Academic Year Manager | CRUD with single-active constraint | Admin |
| Department Deletion Guard | Blocks deletion if active profiles reference it | System |

### Partners — Companies & Agreements

External relationship management.

| Feature | Description | Access |
|---------|-------------|--------|
| Company Manager | CRUD company profiles (name, address, industry, contact) | Admin |
| Partnership Manager | CRUD agreements (number, title, dates, scope, contact person) | Admin |
| Partnership Lifecycle | ACTIVE, EXPIRED, TERMINATED with transition rules | System |
| MoU Document Upload | Upload agreement documents via media library | Admin |
| Expiry Detection | Warns 30 days before partnership expiry | System |

---

## Program Management Phase

### Program — Internship Lifecycle

Program definitions, requirements, groups, phases.

| Feature | Description | Access |
|---------|-------------|--------|
| Program Manager | CRUD programs: name, dates, academic year, department, type | Admin |
| Program Lifecycle | DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED with transition gates | Admin |
| Requirement Manager | Document requirements per program (DOCUMENT, SKILL, TEXT) | Admin |
| Group Manager | Groups with member roles | Admin |
| Phase Manager | Program phases/timeline stages | Admin |
| Closure Readiness Check | Automated verification pipeline | Admin |

---

## Enrollment Phase

### Enrollment — Registration & Placement

Student registration, placement, and change requests.

| Feature | Description | Access |
|---------|-------------|--------|
| Apply Page (Guest) | Submit application without login | Guest |
| Registration Center | Browse programs accepting registrations | Auth |
| Registration Wizard | Multi-step: select program, choose placement, review, submit | Student |
| Document Upload | Upload required documents per program requirements | Student |
| Registration Verification | Admin review pending registrations, assign mentors, activate | Admin |
| Placement Index | CRUD slots per company per program with quota tracking | Admin |
| Direct Placement | Assign student directly to slot | Admin |
| Placement Change Request | Student requests slot change | Student |
| Capacity Enforcement | Atomic quota increment/decrement | System |

---

## Daily Operations Phase

### Journals — Logbook, Attendance & Scheduling

Daily activity tracking.

| Feature | Description | Access |
|---------|-------------|--------|
| Logbook Entry | Daily entry: date, activities, learnings, challenges, plans, attachments | Student |
| Logbook Workflow | DRAFT → SUBMITTED → VERIFIED/FINALIZED, 48h teacher bypass | Student + Mentor |
| One Entry Per Day | Maximum one entry per calendar day per student | System |
| Compliance Monitoring | Auto-notify mentor if N days without entry | System |
| Student Clock In/Out | Timestamp-based, optional GPS data | Student |
| Absence Request | Submit planned/unplanned absence with reason | Student |
| Absence Approval | Mentor approves single-day, extended requires admin | Mentor + Admin |
| Attendance Manager | CRUD records, filter, sort, reports | Admin |
| Schedule Index | CRUD events: title, description, times, location, category | Admin |
| Calendar Views | Day, week, month, agenda | Student + Mentor + Admin |
| Conflict Detection | Detect overlapping events with warning | System |

### Guidance — Mentoring & Supervision

Mentor relationships and supervision logs.

| Feature | Description | Access |
|---------|-------------|--------|
| Supervision Logs | Private notes: site visits, online, phone supervision | Mentor |
| Mentoring Assignments | Maps teachers and supervisors to student registrations | Admin |

### Incident — Issue Reporting

Structured reporting and investigation.

| Feature | Description | Access |
|---------|-------------|--------|
| Incident Form | Date/time, location, description, category, severity, evidence | All users |
| Severity | LOW, MEDIUM, HIGH, CRITICAL with escalation | System |
| Investigation Workflow | REPORTED → INVESTIGATING → RESOLVED → CLOSED | Admin |
| CRITICAL Notifications | Out-of-band alerts to all admins for HIGH/CRITICAL | System |

---

## Assessment Phase

### Assessment — Competency Evaluation

Rubric-based evaluation framework.

| Feature | Description | Access |
|---------|-------------|--------|
| Rubric Manager | CRUD weighted evaluation sheets with nested JSON structures | Admin |
| Assessment Grading | Score against rubric indicators, auto-calculate weighted total | Teacher / Supervisor |
| Finalization | Finalized assessments immutable | System |
| Dual Mentor Fallback | Proxy evaluation or weight redistribution if supervisor inactive | Teacher / Admin |

### Assignment — Tasks & Submissions

Task creation, submission, grading.

| Feature | Description | Access |
|---------|-------------|--------|
| Assignment Manager | CRUD tasks: title, description, due dates, resources, points | Admin |
| Submit Assignment | Text, file uploads, both, with draft workflow | Student |
| Submission Grading | Numeric score, rubric-referenced, written feedback | Teacher |
| Submission Lifecycle | DRAFT → SUBMITTED → VERIFIED → GRADED, optional revision | System |
| Deadline Management | Due dates, late flagging, extension support | Teacher |

---

## Evaluation Phase

### Evaluation — Feedback Collection

Mentor and program feedback.

| Feature | Description | Access |
|---------|-------------|--------|
| Mentor Evaluation | Student rates mentor communication, responsiveness, guidance | Student |
| Score Bands | EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39) | System |
| Admin View | Filter by type, submodule scores, trends | Admin |

---

## Certification Phase

### Certification — Credentialing

Certificate templates, issuance, revocation.

| Feature | Description | Access |
|---------|-------------|--------|
| Template Manager | CRUD templates: layout, branding, field mapping, versioning | Admin |
| Issue Certificate | Single issuance with unique serial number | Admin |
| Batch Issue | Cohort batch issuance (one failure does not block batch) | Admin |
| Revoke Certificate | Revoke with reason category (terminal) | Admin |
| Serial Number Management | Strictly sequential, unique, permanently retired | System |
| Student Certificates | View and download own certificates | Student |

---

## Reporting Phase

### Reports — Final Grade Card

Score aggregation and sign-off.

| Feature | Description | Access |
|---------|-------------|--------|
| Grade Aggregation | Auto-calculate composite score from program weights | System |
| Grade Card Management | Review, override, finalize student grade card | Teacher / Admin |
| Grade Card Lock | Once finalized, immutable — unlocks certificate generation | System |

---

## Closure Phase

### Document — Templates & Handbooks

Rendering engine for official documents and policy handbooks.

| Feature | Description | Access |
|---------|-------------|--------|
| Template Manager | Upload and manage document templates (Blade, CSS, XLSX) | Admin |
| Handbook Manager | CRUD policy handbooks: title, slug, content (Markdown) | Admin |
| Acknowledgement System | Immutable acknowledgement log (user, timestamp, IP, browser) | User |
| Rendering Pipeline | 6-step: resolve template → discover renderer → gather data → inject → invoke driver → store | System |
| Template Versioning | Every document records exact template version used | System |

---

## Summary

**19 modules with 150+ features** covering the complete program lifecycle: Foundation → User
Management → Academic Setup → Program Management → Enrollment → Daily Operations → Assessment →
Evaluation → Certification → Reporting → Closure.
