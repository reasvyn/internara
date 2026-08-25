# Internara Project — Initial Specification

> **Spec ID:** QLHDO
> **Last updated:** 2026-08-25 **Changes:** sync — §9.1/9.5/9.6 maryUI → TallstackUI v4 (x-ts-modal, x-ts-icon/Heroicons, x-ts-* components)

## Description

This is the **initial specification** of the Internara system — a self-hosted, single-tenant web
application for managing compulsory industrial fieldwork programs (PKL — _Praktik Kerja Lapangan_)
at Indonesian vocational schools (SMA/SMK). It is the **spec-zero blanket spec**: it establishes the
product boundary, the 12-phase lifecycle, the 18-module landscape, the role model, the global
(cross-cutting) requirements every feature spec inherits, and the **high-level feature
specifications** per lifecycle phase. All other specs in `docs/specs/` derive scope and defaults
from this document.

It deliberately does **not** restate implementation-level detail — each phase's functional detail
lives in its own feature spec (indexed in [docs/specs/index.md](index.md)). This spec defines what
is true of the whole system regardless of module: roles, the high-level feature inventory per phase,
localization, security posture, UI/UX standards, module-map defaults, and the build order.

---

## 1. Problem Statements

### PS-1 — PKL Administration Is Fragmented

Indonesian vocational schools legally require PKL, yet most manage the full lifecycle — enrollment,
placement, attendance, logbook, supervision, assessment, certification, reporting — with paper
forms, Excel spreadsheets, WhatsApp messages, and ad-hoc email. A coordinator compiling final grade
cards must manually gather data from dozens of disconnected artifacts, making errors, delays, and
unfinished work inevitable.

### PS-2 — Hidden Scale

A typical medium-to-large SMK manages 500–1,000 active students across 150–300 partner companies
(DUDI) per placement period — on the order of 45,000 attendance records, 6,000 logbook entries,
1,500–2,500 submissions, and 500 evaluation forms per period. No manual process survives that volume
without data loss.

### PS-3 — No Accountability Trail

Without a unified system, verification and sign-off cannot be traced: attendance is unverifiable at
scale, logbook entries are ungraded and unsearchable, certificates are forgeable, and there is no
audit trail for administrative action. Schools need digital evidence of the educational process.

### PS-4 — No Single Source of Truth

Operational data lives in the private tooling of whoever happens to hold it (a teacher's sheet, a
supervisor's notebook, an admin's mail queue). There is no canonical, role-filtered record any
participant can query, and no consistent reporting base.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                                                                                                                                                                            |
| --- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| G1  | Digitize the complete PKL lifecycle end-to-end: foundation → configuration → identity → institutional → partnerships → programs → enrollment → daily ops → assessment → certification → reporting → maintenance |
| G2  | Provide one canonical, role-filtered source of truth for every participant (student, teacher, supervisor, admin)                                                                                                |
| G3  | Operate as a self-hosted, single-tenant, MIT-licensed application with zero recurring vendor costs and full data sovereignty                                                                                    |
| G4  | Enforce authorization and an audit trail at every layer (Secure — S1)                                                                                                                                           |
| G5  | Stay sustainable and maintainable through module colocation and clean boundaries (Sustain — S2)                                                                                                                 |
| G6  | Scale cleanly within a single tenant via spec-driven features and an Action-triad architecture (Scalable — S3)                                                                                                  |
| G7  | Support the full domain bilingually (Indonesian primary, English secondary) with `__()` on all user-facing strings                                                                                              |

### Non-Goals

| ID  | Non-Goal                                                           |
| --- | ------------------------------------------------------------------ |
| NG1 | Multi-tenant SaaS — single-tenant by design, no tenant-ID overhead |
| NG2 | HR / payroll features                                              |
| NG3 | Real-time chat                                                     |
| NG4 | Government database sync — CSV import/export only                  |
| NG5 | Mobile native apps — responsive web only                           |

---

## 3. User Stories / Use Cases

### UC-1 — School Initializes and Configures the System

**Actor:** Super Admin / Admin **Preconditions:** Server deployed, environment audit passes
**Flow:**

1. Super Admin runs the 6-step setup wizard (environment audit, super admin, school, department,
   finalize)
2. Admin configures branding, theme, locale, and school profile; creates departments and academic
   years
3. Admin registers partner companies and formal partnerships with slot quotas
4. System becomes operational **Postconditions:** School can enroll students; `superadmin` account
   exists per [setup rules](../refs/modules/setup.md)

### UC-2 — Student Completes the PKL Lifecycle

**Actor:** Student **Preconditions:** Registration open, placement slots available **Flow:**

1. Student registers (guest apply page or registration wizard), uploads required documents
2. Admin verifies registration and places the student into a DUDI slot
3. Student clocks in/out, keeps a reflective logbook, submits assignments, and acknowledges
   handbooks
4. Student downloads certificate after assessment and report sign-off **Postconditions:** Full
   digital trail of the internship exists and is auditable

### UC-3 — Teacher Supervises and Assesses

**Actor:** Teacher **Preconditions:** Students placed, program active **Flow:**

1. Teacher supervises assigned students (logbook review, supervision logs, monitoring visits)
2. Teacher or supervisor scores against competency rubrics; student submissions are graded
3. Teacher compiles and finalizes the grade card; certificate becomes issuable **Postconditions:**
   Grades are aggregated; finalized artifacts are immutable

### UC-4 — Supervisor Evaluates University-Side Performance

**Actor:** Supervisor (industry) **Preconditions:** Student active at the DUDI site **Flow:**

1. Supervisor verifies attendance and reviews logbook entries
2. Supervisor submits competency evaluations for assigned students (direct or proxy-stamped)
3. Evaluations flow into final score aggregation **Postconditions:** Industry-side scores are
   present in the final record

### UC-5 — Admin Operates and Audits the System

**Actor:** Admin / Super Admin **Preconditions:** System running **Flow:**

1. Admin manages users (CRUD, lock/unlock, role assignment, account slips) and announcements
2. Admin monitors health checks, audit logs, and job queues
3. Admin runs backups, GDPR export/erasure, and archival per policy **Postconditions:** Operation is
   observable and recoverable within RPO/RTO targets

---

## 4. Role Model (5 Roles + 2 Functional)

| Role        | Code          | Description                                                                               |
| ----------- | ------------- | ----------------------------------------------------------------------------------------- |
| Super Admin | `super_admin` | Unrestricted system access, infrastructure management, bypasses all permission checks     |
| Admin       | `admin`       | School-level operations: user management, programs, companies, departments                |
| Teacher     | `teacher`     | Academic supervision: journal review, assignment grading, site visits, grade compilation  |
| Student     | `student`     | Program participation: attendance, logbooks, assignments, certificate download            |
| Supervisor  | `supervisor`  | Industry-side supervision: attendance verification, journal review, competency evaluation |

Each user is assigned exactly one role. Three additional **functional roles** (`admin-group`, `mentor`,
`mentee`) are resolved at runtime via `Role::resolvesTo()` for business logic — never stored or used
in middleware. `admin-group` is the administrative grouping (`super_admin`/`admin`). See FR-G2 for the
requirement binding and [T4B26](T4B26-rbac-and-authorization.md) for the RBAC specification.

---

## 5. Functional Requirements

### Cross-Cutting Functional Requirements

These are **global defaults every feature spec inherits**; a feature spec may tighten them but never
violate them. Where this section references `docs/guides/*` or a phase spec, the referenced
document is authoritative for that concern.

| ID    | Requirement                                                                                                                                                                                                        |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| FR-G1 | Every user-facing string MUST use the `__()` helper; all modules ship `lang/en/` and `lang/id/` (D3)                                                                                                               |
| FR-G2 | The system MUST expose exactly five stored roles — `super_admin`, `admin`, `teacher`, `student`, `supervisor` — plus three runtime-resolved functional roles (`admin-group`, `mentor`, `mentee`) via `Role::resolvesTo()` (see §4)  |
| FR-G3 | The system MUST enforce `superadmin` integrity: name is always `Super Admin`, username always `superadmin`, immutable and non-deletable                                                                            |
| FR-G4 | All administrative mutations MUST be audit-logged (activity channel) with PII masking (S1)                                                                                                                         |
| FR-G5 | Sensitive endpoints MUST be rate-limited (global 30/min/IP; login 5/60s; forgot 3/3600s; reset 5/300s; recovery 3/300s)                                                                                            |
| FR-G6 | The system MUST run a system health check covering PHP, extensions, memory, DB, migrations, storage, queue, cache, and app key                                                                                     |
| FR-G7 | All records MUST use UUID primary keys via `BaseModel`/`HasUuids`                                                                                                                                                  |
| FR-G8 | Program data MUST flow in dependency order: Foundation → Configuration → Identity & Auth → Institutional → Partnerships → Programs → Enrollment → Daily Ops → Assessment → Certification → Reporting → Maintenance |

### Lifecycle Coverage Matrix

Each row names the phase, its governing feature spec(s), and the owning module. The referenced spec
is authoritative for that phase's functional detail.

| ID     | Requirement                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| FR-L1  | Foundation — stack, base classes, utilities, events, RBAC, middleware, security headers, module manager ([D2FT3](D2FT3-architecture.md), [FB792](FB792-tech-stack.md), [ZT6VS](ZT6VS-core-infra-services.md), [SE5Q9](SE5Q9-base-classes.md), [C8F0D](C8F0D-shared-utilities.md), [J68GZ](J68GZ-system-requirements.md), [I1BCV](I1BCV-module-discovery.md), [89SRA](89SRA-logging-and-error-handling.md), [NUCY3](NUCY3-event-system.md), [T4B26](T4B26-rbac-and-authorization.md), [2CF4Y](2CF4Y-middleware-pipeline.md), [1PGM4](1PGM4-security-headers.md), [B114U](B114U-module-manager.md)) |
| FR-L2  | Configuration — installation, setup wizard, recovery ecosystem, settings, branding/theme/locale, school profile ([8NZAU](8NZAU-installation.md), [VEJCX](VEJCX-setup-wizard.md), [C9ZB6](C9ZB6-recovery-ecosystem.md), [YB22J](YB22J-settings-infrastructure.md), [52O1I](52O1I-branding-theme-locale.md), [81SMS](81SMS-school-profile.md))                                                                                                                                                                                                                                                      |
| FR-L3  | Identity & Auth — layout/UI system, authentication, notification infra, announcements, dashboard, password reset/confirmation, recovery slips, profile management ([8XMYS](8XMYS-layout-and-ui-system.md), [YB7RG](YB7RG-authentication.md), [TXR2H](TXR2H-notification-infrastructure.md), [3S55V](3S55V-announcement-system.md), [CKKZC](CKKZC-dashboard.md), [D9TKW](D9TKW-password-reset.md), [CQVSK](CQVSK-password-confirmation.md), [SHQ1J](SHQ1J-account-recovery-slips.md), [OCEMS](OCEMS-profile-management.md))                                                                        |
| FR-L4  | Institutional — department and academic year management ([4HWSB](4HWSB-department-management.md), [XW6F5](XW6F5-academic-year-management.md))                                                                                                                                                                                                                                                                                                                                                                                                                                                     |
| FR-L5  | Partnerships — company and partnership management with quota tracking ([XI3LB](XI3LB-company-management.md), [NTHQA](NTHQA-partnership-management.md))                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| FR-L6  | Programs — internship lifecycle and cohort groups ([7C5WM](7C5WM-internship-lifecycle.md), [IT0OE](IT0OE-internship-groups.md))                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| FR-L7  | Enrollment — registration, placement, account application, user CRUD/status, CSV I/O, account slips ([MBB5R](MBB5R-registration.md), [J9GBH](J9GBH-placement.md), [920SO](920SO-account-application.md), [95EVB](95EVB-user-crud-and-status.md), [O2KCR](O2KCR-csv-import-export.md), [EWCZ0](EWCZ0-account-slips.md))                                                                                                                                                                                                                                                                            |
| FR-L8  | Daily Ops — daily activity, supervision, incidents ([1KSWL](1KSWL-daily-activity.md), [2EHSE](2EHSE-supervision.md), [3RU9S](3RU9S-incident.md))                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| FR-L9  | Assessment — rubrics, assessment, evaluation, assignments ([ARDA6](ARDA6-assessment.md), [AXKZW](AXKZW-evaluation.md), [T657Z](T657Z-assignment.md))                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| FR-L10 | Certification — document templates, handbooks, certification, media, PDF ([PKYX6](PKYX6-document-templates.md), [ZUFG8](ZUFG8-handbooks.md), [J0M04](J0M04-certification.md), [WQGTP](WQGTP-file-uploads-media.md), [7UB7S](7UB7S-pdf-generation.md))                                                                                                                                                                                                                                                                                                                                             |
| FR-L11 | Reporting — reports, official documents ([R6BMW](R6BMW-reports.md), [7H5D6](7H5D6-official-documents.md))                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| FR-L12 | Maintenance — jobs/queues, backup, GDPR, system maintenance, conditional deployment, dummy data ([8FVZA](8FVZA-job-queue-infrastructure.md), [HBXCI](HBXCI-backup-system.md), [7HNCF](7HNCF-gdpr-compliance.md), [E1MSJ](E1MSJ-system-maintenance.md), [06IB6](06IB6-deployment.md), [3UOZP](3UOZP-dummy-data.md))                                                                                                                                                                                                                                                                                |

---

## 6. Feature Specifications by Lifecycle Phase

High-level feature inventory per program lifecycle phase, with descriptions and access control. Each
subsection's implementation-level detail lives in the governing feature spec (indexed in
[docs/specs/index.md](index.md)).

### 6.1 Foundation Phase

#### Core — Foundation & Infrastructure

Base classes, contracts, middleware, and cross-module utilities that every other module depends on.

| Feature             | Description                                                                                                |
| ------------------- | ---------------------------------------------------------------------------------------------------------- |
| BaseModel (UUID)    | All models extend `BaseModel` with UUID primary keys and `HasUuids`                                        |
| BaseAction          | Every business operation extends `BaseAction` — transaction + logging                                      |
| BaseEntity          | `final readonly` business rules, zero framework dependencies                                               |
| BasePolicy          | Role and ownership authorization checks                                                                    |
| BaseRecordManager   | CRUD Livewire base: search, filter, sort, pagination, bulk actions                                         |
| BaseController      | Common HTTP controller utilities                                                                           |
| BaseFormRequest     | Core's form request (not Laravel's) with validation exception handling                                     |
| BaseData            | Readonly DTO with `fromArray()` / `toArray()`                                                              |
| SmartLogger         | Dual-channel fluent logger (system + activity) with PII masking                                            |
| Exception Hierarchy | AppException (action/infrastructure/presentation) + ModuleException                                        |
| StatusEnum          | State machine via `canTransitionTo()`, `isTerminal()`, `validTransitions()`                                |
| LabelEnum           | All enums implement `label(): string`                                                                      |
| Security Headers    | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy                                                  |
| Log Context         | Request tracing: request_id, method, URL, IP, user                                                         |
| System Health       | 15-point check (PHP, extensions, memory, DB, migrations, storage, queue, cache, app key); Admin-accessible |
| Activity Logging    | Spatie Activity Log with query scopes                                                                      |

#### Setup — Installation & Provisioning

One-time guided installation.

| Feature               | Description                                                                           | Access        |
| --------------------- | ------------------------------------------------------------------------------------- | ------------- |
| 6-Step Setup Wizard   | Environment Audit, Super Admin, School, Department, Finalize, Complete                | Guest (token) |
| Environment Audit     | PHP version, extensions, directory permissions, database, terminal                    | Installer     |
| Setup Token           | Encrypted random token gates wizard access, single-use                                | System        |
| School Initialization | Create first school profile in settings                                               | Installer     |
| Super Admin Creation  | Name always "Super Admin", username "superadmin"                                      | Installer     |
| Recovery Key          | 64-char random key, stored hashed in DB, saved to `storage/app/private/.recovery-key` | Installer     |
| CLI Install           | `php artisan setup:install` with `--check-only` and `--force`                         | CLI           |
| Super Admin Recovery  | `php artisan admin:recover` — emergency CLI recovery                                  | CLI           |

#### Settings — System Configuration & Branding

Key-value configuration store with dynamic resolution.

| Feature                | Description                                                                                       | Access      |
| ---------------------- | ------------------------------------------------------------------------------------------------- | ----------- |
| System Setting Manager | Key-value store with type enforcement (boolean, text, numeric, JSON, image, color)                | Super Admin |
| Branding Configuration | App name, logo, favicon, colors (primary/secondary/accent), custom CSS                            | Super Admin |
| Feature Flags          | Enable/disable features at runtime                                                                | Super Admin |
| Mail Configuration     | SMTP settings with test email verification                                                        | Super Admin |
| Theme System           | Color resolution into CSS custom properties (light/dark); Livewire light/dark/system theme toggle | System      |
| Locale Management      | Bilingual with session preference and Livewire en/id toggle, resolved from stored setting         | System      |
| Cache Invalidation     | Automatic via SettingObserver on Eloquent model events (created/updated/deleted)                  | System      |

#### Auth — Authentication & Authorization

Login, password management, account recovery, RBAC.

| Feature                  | Description                                                             | Access |
| ------------------------ | ----------------------------------------------------------------------- | ------ |
| Login via Email/Username | 4-step sequential validation, auto-lock after 10 failures               | Guest  |
| Forgot Password          | Email-based reset (60 min expiry, single-use token)                     | Guest  |
| Reset Password           | New password via email token                                            | Guest  |
| Confirm Password         | Re-authenticate before sensitive operations                             | Auth   |
| Recovery Slip            | Admin generates 10 one-time codes, delivered offline, no expiry         | Admin  |
| Account Recovery         | User redeems code to unlock account and set new password                | Guest  |
| RBAC                     | 5 roles + 3 functional roles (admin-group, mentor, mentee) with `Role::resolvesTo()` | All    |
| Super Admin Integrity    | Name/username immutable (Super Admin/superadmin), non-deletable         | System |

#### User — Identity & Profiles

User profiles, notifications, dashboards, account lifecycle.

| Feature               | Description                                                                                                 | Access     |
| --------------------- | ----------------------------------------------------------------------------------------------------------- | ---------- |
| User & Profile Models | UUID-based identity with extended profile (phone, address, gender, blood type, emergency contact, NISN/NIP) | All        |
| Profile Editor        | Self-service data update (name, email, phone, address, bio)                                                 | Auth       |
| Avatar Upload         | Single image via media library, 200x200 WebP thumbnail                                                      | Auth       |
| Role-based Dashboard  | Auto-routing to admin/teacher/supervisor/student dashboard                                                  | Auth       |
| Admin Dashboard       | User stats, readiness checklist, quick links                                                                | Admin      |
| Teacher Dashboard     | Supervised students, pending journals, active companies                                                     | Teacher    |
| Supervisor Dashboard  | Active participants, pending evaluations, verified journals                                                 | Supervisor |
| Student Dashboard     | Registration status, journal progress, quick actions                                                        | Student    |
| Notification Center   | Full-page with search, filter, bulk mark-read/delete                                                        | Auth       |
| Notification Bell     | Navbar indicator with unread count                                                                          | Auth       |
| Account State Machine | 8 states with strict transition guards                                                                      | System     |

#### SysAdmin — System Administration

User CRUD, announcements, audit logs, health monitoring.

| Feature              | Description                                                  | Access      |
| -------------------- | ------------------------------------------------------------ | ----------- |
| User Manager         | CRUD all roles: create, update, lock/unlock, mark alumni     | Admin       |
| Admin Manager        | Manage admin accounts                                        | Super Admin |
| Student Manager      | Manage students; bulk archive completed                      | Admin       |
| Teacher Manager      | Manage teacher accounts                                      | Admin       |
| Supervisor Manager   | Manage supervisor accounts                                   | Admin       |
| Announcement Manager | DRAFT/SCHEDULED/PUBLISHED lifecycle, Markdown, role-targeted | Admin       |
| Audit Log Manager    | Centralized read-only audit log with filters                 | Admin       |
| Bulk Operations      | Mass user creation with result summaries                     | Admin       |
| Pulse Monitoring     | Laravel Pulse: queue throughput, slow jobs, failed jobs      | Admin       |

### 6.2 Academic Setup Phase

#### Academics — School Profile, Departments & Academic Years

Institutional foundation.

| Feature                   | Description                                                  | Access |
| ------------------------- | ------------------------------------------------------------ | ------ |
| School Profile Editor     | Institutional data: legal name, code, address, contact, logo | Admin  |
| Department Manager        | CRUD departments with search, sort, paginate                 | Admin  |
| Academic Year Manager     | CRUD with single-active constraint                           | Admin  |
| Department Deletion Guard | Blocks deletion if active profiles reference it              | System |

#### Partners — Companies & Agreements

External relationship management.

| Feature               | Description                                                   | Access |
| --------------------- | ------------------------------------------------------------- | ------ |
| Company Manager       | CRUD company profiles (name, address, industry, contact)      | Admin  |
| Partnership Manager   | CRUD agreements (number, title, dates, scope, contact person) | Admin  |
| Partnership Lifecycle | ACTIVE, EXPIRED, TERMINATED with transition rules             | System |
| MoU Document Upload   | Upload agreement documents via media library                  | Admin  |
| Expiry Detection      | Warns 30 days before partnership expiry                       | System |

### 6.3 Program Management Phase

#### Program — Internship Lifecycle

Program definitions, requirements, groups, phases.

| Feature                 | Description                                                          | Access |
| ----------------------- | -------------------------------------------------------------------- | ------ |
| Program Manager         | CRUD programs: name, dates, academic year, department, type          | Admin  |
| Program Lifecycle       | DRAFT, PUBLISHED, ACTIVE, COMPLETED, CANCELLED with transition gates | Admin  |
| Requirement Manager     | Document requirements per program (DOCUMENT, SKILL, TEXT)            | Admin  |
| Group Manager           | Groups with member roles                                             | Admin  |
| Phase Manager           | Program phases/timeline stages                                       | Admin  |
| Closure Readiness Check | Automated verification pipeline                                      | Admin  |

### 6.4 Enrollment Phase

#### Enrollment — Registration & Placement

Student registration, placement, and change requests.

| Feature                   | Description                                                  | Access  |
| ------------------------- | ------------------------------------------------------------ | ------- |
| Apply Page (Guest)        | Submit application without login                             | Guest   |
| Registration Center       | Browse programs accepting registrations                      | Auth    |
| Registration Wizard       | Multi-step: select program, choose placement, review, submit | Student |
| Document Upload           | Upload required documents per program requirements           | Student |
| Registration Verification | Admin review pending registrations, assign mentors, activate | Admin   |
| Placement Index           | CRUD slots per company per program with quota tracking       | Admin   |
| Direct Placement          | Assign student directly to slot                              | Admin   |
| Placement Change Request  | Student requests slot change                                 | Student |
| Capacity Enforcement      | Atomic quota increment/decrement                             | System  |

### 6.5 Daily Operations Phase

#### Journals — Logbook, Attendance & Scheduling

Daily activity tracking.

| Feature              | Description                                                              | Access           |
| -------------------- | ------------------------------------------------------------------------ | ---------------- |
| Logbook Entry        | Daily entry: date, activities, learnings, challenges, plans, attachments | Student          |
| Logbook Workflow     | DRAFT → SUBMITTED → VERIFIED/FINALIZED, 48h teacher bypass               | Student + Mentor |
| One Entry Per Day    | Maximum one entry per calendar day per student                           | System           |
| Student Clock In/Out | Timestamp-based, optional GPS data                                       | Student          |
| Absence Request      | Submit planned/unplanned absence with reason                             | Student          |
| Absence Approval     | Mentor approves single-day, extended requires admin                      | Mentor + Admin   |
| Attendance Manager   | CRUD records, filter, sort, reports                                      | Admin            |

#### Journals — Supervision Logs & Monitoring Visits

Mentor relationships and supervision log management within the Journals module.

| Feature                  | Description                                            | Access |
| ------------------------ | ------------------------------------------------------ | ------ |
| Supervision Logs         | Private notes: site visits, online, phone supervision  | Mentor |
| Supervision Log Workflow | DRAFT → SUBMITTED → REVIEWED → ACKNOWLEDGED            | Mentor |
| Mentoring Assignments    | Maps teachers and supervisors to student registrations | Admin  |

#### Document — Handbooks & Templates

Policy handbook storage and compliance acknowledgement tracking within the Document module.

| Feature                     | Description                                              | Access                         |
| --------------------------- | -------------------------------------------------------- | ------------------------------ |
| Handbook Manager            | Upload and manage PDF handbooks by target role           | Admin                          |
| Handbook List & Acknowledge | View, download, and acknowledge handbooks                | Student / Teacher / Supervisor |
| Role-Targeted Visibility    | Handbooks scoped to student, teacher, supervisor, or all | System                         |

#### Incident — Issue Reporting

Structured reporting and investigation.

| Feature                | Description                                                    | Access    |
| ---------------------- | -------------------------------------------------------------- | --------- |
| Incident Form          | Date/time, location, description, category, severity, evidence | All users |
| Severity               | LOW, MEDIUM, HIGH, CRITICAL with escalation                    | System    |
| Investigation Workflow | REPORTED → INVESTIGATING → RESOLVED → CLOSED                   | Admin     |
| CRITICAL Notifications | Out-of-band alerts to all admins for HIGH/CRITICAL             | System    |

### 6.6 Assessment Phase

#### Assessment — Competency Evaluation

Rubric-based evaluation framework.

| Feature            | Description                                                    | Access               |
| ------------------ | -------------------------------------------------------------- | -------------------- |
| Rubric Manager     | CRUD weighted evaluation sheets with nested JSON structures    | Admin                |
| Assessment Grading | Score against rubric indicators, auto-calculate weighted total | Teacher / Supervisor |
| Finalization       | Finalized assessments immutable                                | System               |
| Supervisor Grading | Industry supervisor submits scores via dedicated interface     | Supervisor           |
| Proxy Stamping     | Proxy-graded assessments tagged with metadata for audit trail  | System               |

#### Assignment — Tasks & Submissions

Task creation, submission, grading.

| Feature              | Description                                                  | Access  |
| -------------------- | ------------------------------------------------------------ | ------- |
| Assignment Manager   | CRUD tasks: title, description, due dates, resources, points | Admin   |
| Submit Assignment    | Text, file uploads, both, with draft workflow                | Student |
| Submission Grading   | Numeric score, rubric-referenced, written feedback           | Teacher |
| Submission Lifecycle | DRAFT → SUBMITTED → VERIFIED → GRADED, optional revision     | System  |
| Deadline Management  | Due dates, late flagging, extension support                  | Teacher |

### 6.7 Evaluation Phase

#### Evaluation — Generic Feedback Forms

Google Forms-like feedback collection across all PKL aspects.

| Feature               | Description                                                                                    | Access |
| --------------------- | ---------------------------------------------------------------------------------------------- | ------ |
| Evaluation Forms      | Reusable form templates with weighted questions and sections                                   | Admin  |
| Polymorphic Targeting | Forms target mentors, programs, companies, or overall satisfaction via `target_type`           | System |
| Question Types        | Rating scales (1-5, 1-10), yes/no, multiple choice, agreement Likert, free text                | Admin  |
| Weighted Scoring      | Auto-calculated overall score from weighted question responses                                 | System |
| Score Bands           | EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39) | System |

### 6.8 Certification Phase

#### Certification — Credentialing

Certificate templates, issuance, revocation.

| Feature                  | Description                                                 | Access  |
| ------------------------ | ----------------------------------------------------------- | ------- |
| Template Manager         | CRUD templates: layout, branding, field mapping, versioning | Admin   |
| Issue Certificate        | Single issuance with unique serial number                   | Admin   |
| Batch Issue              | Cohort batch issuance (one failure does not block batch)    | Admin   |
| Revoke Certificate       | Revoke with reason category (terminal)                      | Admin   |
| Serial Number Management | Strictly sequential, unique, permanently retired            | System  |
| Student Certificates     | View and download own certificates                          | Student |
| QR Verification          | SHA-256 hash for public certificate authenticity check      | Public  |

### 6.9 Reporting Phase

#### Reports — Final Grade Card

Score aggregation and sign-off.

| Feature               | Description                                                | Access          |
| --------------------- | ---------------------------------------------------------- | --------------- |
| Grade Aggregation     | Auto-calculate composite score from program weights        | System          |
| Grade Card Management | Review, override, finalize student grade card              | Teacher / Admin |
| Grade Card Lock       | Once finalized, immutable — unlocks certificate generation | System          |

### 6.10 Closure Phase

#### Document — Templates & Handbooks

Rendering engine for official documents (unified in `documents` table with `type` discriminator).
Handbooks managed by Document module.

| Feature                | Description                                                                                 | Access |
| ---------------------- | ------------------------------------------------------------------------------------------- | ------ |
| Document Manager       | Upload and manage document templates (Blade, CSS, XLSX)                                     | Admin  |
| Acknowledgement System | Immutable acknowledgement log (user, timestamp, IP, browser)                                | User   |
| Rendering Pipeline     | 6-step: resolve template → discover renderer → gather data → inject → invoke driver → store | System |
| Template Versioning    | Every document records exact template version used                                          | System |

---

## 7. Cross-Cutting Features

These features span multiple modules and are not owned by a single module.

### 7.1 Cross-Role Proxy

Teachers can act as proxy for inactive industry supervisors after a configurable window (default
48h); admin can proxy for either role. Applies to: logbook verification, assessment grading,
supervision log verification. Proxy-graded items are tagged with metadata for audit trail. See
[ADR-014: Cross-Role Proxy](../adr/adr-cross-role-proxy.md). The canonical contract is specified in
[T4B26-rbac-and-authorization](T4B26-rbac-and-authorization.md) §4.2.

### 7.2 Compliance Monitoring

Automated monitoring of student activity compliance. Triggers notifications when students miss
required activities (logbook entries, attendance). Configurable thresholds per program. CLI command
`journals:check-compliance` for on-demand or scheduled checks.

### 7.3 Activity Logging & Audit Trail

All administrative actions dual-logged via SmartLogger to both system channel (detailed debug) and
activity channel (immutable, PII-masked audit records). GDPR deletion logs are append-only. See
FR-G4.

### 7.4 Global Helpers

- `setting($key, $default, $skipCache)` — Runtime configuration access
- `brand($key, $default)` — Dynamic branding values (name, title, logo, favicon, colors)
- `app_info()` — Static application metadata from composer.json

The helper contracts are specified in §13 API / Data Contracts and detailed in
[C8F0D-shared-utilities](C8F0D-shared-utilities.md) (FR-SUP11) and
[YB22J-settings-infrastructure](YB22J-settings-infrastructure.md).

### 7.5 CSV Import/Export

Template-based CSV import/export with header validation, row-by-row error reporting, and download
templates. Available on all Record Manager components. Mechanics specified in
[O2KCR-csv-import-export](O2KCR-csv-import-export.md).

---

## 8. Non-Functional Requirements

| ID     | Requirement                                                                                                                                                                                              |
| ------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| NFR-S1 | Security: authorization at every layer (Policy + Action/Entity business authorization via `RejectedException`); PII masked in logs per PDP law (UU No. 27/2022); CSP + security headers on all responses |
| NFR-S2 | Input: no raw SQL without bindings (C3); no raw `Request` into create/update (D5); all user input validated server-side                                                                                  |
| NFR-P1 | Performance: pages respond within target budgets; Actions eager-load relations (no N+1); expensive queries cached with registered cache keys                                                             |
| NFR-R1 | Reliability: 4-hour RPO / under 1-hour RTO backup target; graceful degradation; job queues for heavy work (mail, PDF, reports)                                                                           |
| NFR-U1 | Usability: every page with a non-trivial workflow has a `*-guide.blade.php` (see §9.1); WCAG AA contrast; keyboard navigable; mobile-first responsive                                                    |
| NFR-M1 | Maintainability: 4-layer module-first architecture enforced by `scripts/` scans (C1–C8, D1–D6, contracts, naming, security); DRY — shared logic in Core                                                  |
| NFR-L1 | Localization: English + Indonesian, locale stored in session, togglable at runtime (see §9.3)                                                                                                            |
| NFR-C1 | Compatibility: renders consistently across modern browsers; printed/exported artifacts (PDF/Excel/CSV) are precise and stable                                                                            |
| NFR-D1 | Database: SQLite WAL mode or MySQL; UUID primary keys; 55 tables (37 domain + 18 system)                                                                                                                 |
| NFR-Q1 | Queue: separate `default` and `documents` pipelines                                                                                                                                                      |
| NFR-F1 | Functional completeness: every feature — login, activity logging, and monitoring included — is complete and conforms to the high-level design in §6                                                      |
| NFR-G1 | GDPR: deletion logging and data erasure workflows (see §10)                                                                                                                                              |

---

## 9. UI/UX & Interaction Requirements

### 9.1 User Guide Components

Every page with a non-trivial workflow MUST include a `*-guide.blade.php` component providing
contextual help. The pattern follows the setup wizard's guide at
`resources/views/setup/components/setup-guide.blade.php`.

- **Placement:** `resources/views/{module}/components/{page-name}-guide.blade.php`
- **Trigger:** Fixed floating button (bottom-right, `z-50`) with question mark icon
- **Modal:** `<x-ts-modal>` (TallstackUI) with step-by-step instructions
- **Content:** Introductory sentence, numbered steps (1 through N), tip section for best practices
- **Integration:** `$showGuide` boolean state + `@include` in parent Blade view

### 9.2 Record Manager Capabilities

Every record manager component (extending `BaseRecordManager`) MUST provide:

| Capability        | Description                                                                   |
| ----------------- | ----------------------------------------------------------------------------- |
| **Search**        | Full-text search across relevant columns                                      |
| **Sort**          | Column-based sorting with visual indicators                                   |
| **Filters**       | Dropdown/checkbox filters for status, date ranges, categories                 |
| **Batch Actions** | Bulk operations on selected records (delete, status change, export selection) |
| **Extra Menu**    | Download template, import (CSV/Excel), export (CSV/Excel/PDF)                 |

### 9.3 Localization

All user-facing strings MUST use `__()` helper with bilingual support:

- **Minimum:** English (`lang/en/`) and Indonesian (`lang/id/`)
- **No hardcoded strings:** Every visible text, flash message, validation message, and UI label
- **Translation keys:** Follow `{module}.{context}.{key}` convention
- **Parameters:** Use `:param` syntax for dynamic values
- **Shared labels:** Use `common.php` for global terms (yes, no, save, cancel, etc.)

### 9.4 Theming System

Every Livewire component MUST implement the theming system from the Settings/Theme module:

- **CSS variables:** Use `var(--color-primary)`, `var(--color-secondary)`, etc. for brand colors
- **Dark/light mode:** Respect `theme.dark_mode` setting via CSS class or attribute
- **Dynamic colors:** Never hardcode hex colors — use `brand()` helper or CSS variables
- **Consistency:** All components must render correctly in both light and dark modes
- **Accessibility:** Maintain sufficient contrast ratios (WCAG AA minimum)

### 9.5 Form Field Icons

Every form field MUST include an icon for visual clarity:

- **Input fields:** Icon on the left side (e.g., `user`, `envelope`, `calendar`)
- **Buttons:** Optional icon (recommended for primary actions)
- **Icons:** Use Heroicons via TallstackUI `x-ts-icon`
- **Consistency:** Same icon for same field type across all modules
- **Accessibility:** Icons must not be the sole indicator — pair with labels

### 9.6 UI Design Principles

The interface MUST maintain a clean, modern, minimalist aesthetic with strong accessibility:

- **Layout:** Consistent spacing, clear hierarchy, white space utilization
- **Typography:** Readable fonts, appropriate sizes, clear contrast
- **Components:** Use TallstackUI v4 component library (`x-ts-*`) for consistency
- **Accessibility:** ARIA labels, keyboard navigation, screen reader support
- **Responsive:** Mobile-first design, works on all device sizes
- **Feedback:** Clear loading states, success/error messages, progress indicators

### 9.7 Visual & Usability Quality Criteria

Every page and interactive flow MUST satisfy the following quality criteria:

| Criterion                 | Requirement                                                                               |
| ------------------------- | ----------------------------------------------------------------------------------------- |
| **Layout**                | Neat, balanced, and consistent placement of elements on every page                        |
| **Color**                 | Harmonious, aesthetic, eye-friendly color combinations                                    |
| **Icons & Visuals**       | Intuitive icons and visual elements that clarify the function of each feature             |
| **Typography**            | Readable font type, size, and text contrast on various backgrounds                        |
| **Responsiveness**        | Automatic layout adaptation across screen sizes (mobile & desktop)                        |
| **Navigation**            | Clear, easy-to-follow navigation flow between pages and features                          |
| **Feedback**              | Visual feedback (toast/notification/alert) on every user action                           |
| **Component Consistency** | Consistent form and behavior of interactive components (buttons, forms) across the system |
| **Learnability**          | New users can quickly understand and master application workflows                         |
| **Form Usability**        | Forms are easy to fill without imposing excessive cognitive load                          |

---

## 10. Security & Compliance

- **PII Redaction:** Email, phone, NISN, password, address masked in logs per PDP law (UU No.
  27/2022) — see NFR-S1
- **Rate Limiting:** Multi-layer: global (30/min/IP), per-endpoint (login 5/60s, forgot 3/3600s,
  reset 5/300s, recovery 3/300s) — see FR-G5
- **Account Locking:** Auto-lock after 10 failed attempts
- **GDPR:** Deletion logging, data erasure workflows — see NFR-G1 and
  [7HNCF-gdpr-compliance](7HNCF-gdpr-compliance.md)

---

## 11. Domain, Curriculum & Regulatory Compliance

The system MUST align with the applicable vocational education regulations, curriculum structure,
and PKL implementation standards (SOP) at the school.

| Category                       | Requirement                                                                                                                  |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------- |
| **Curriculum Alignment**       | PKL operational stages in the system align with applicable regulations and vocational curriculum structure                   |
| **Curriculum Alignment**       | Logbook (daily journal) components align with Learning Outcomes (CP) and the student's competency skill profile              |
| **Curriculum Alignment**       | Mentoring reporting flow aligns with PKL implementation standards in vocational schools                                      |
| **Curriculum Alignment**       | DUDI work-role mapping (plotting) matches the student's competency skill profile                                             |
| **Curriculum Alignment**       | Competency unit management is configurable independently by the school                                                       |
| **Curriculum Alignment**       | Framework accommodates separation of technical competencies (hard skills) and work ethic (soft skills)                       |
| **Assessment Flexibility**     | Rubric customization facilitates adjusting assessment criteria to school/department-specific needs                           |
| **Assessment Flexibility**     | Score weighting accommodates varying proportions of DUDI vs. school score combination                                        |
| **Assessment Accuracy**        | Dynamic weighting schemes are accurately computed into valid final grade predicates/conversions                              |
| **SOP Compliance**             | Application workflow complies with the PKL implementation SOP at the school                                                  |
| **Administrative Instruments** | Digital administrative instruments (assignment letters, approval sheets, certificates) are complete and structurally correct |
| **Attendance Verification**    | Attendance verification and daily presence monitoring at the internship site use valid criteria                              |
| **Mentoring Evidence**         | Digital mentoring trail serves as an authentic record of the educational process                                             |
| **Reporting Format**           | Final report recapitulation format conforms to school administrative accountability standards                                |
| **Digital Approval**           | Digital document approval mechanism conforms to school administrative legality principles                                    |
| **Terminology**                | Vocational education terms/terminology are used accurately and correctly across the system                                   |
| **Form Instructions**          | Clear, substantive instructions guide all actors when filling forms                                                          |
| **Information Completeness**   | DUDI profile and student placement data are presented completely and relevantly                                              |
| **Feedback Support**           | Feedback features facilitate correction and revision of student work reports                                                 |
| **Mentoring Support**          | System effectively supports mentoring communication and handling of student field issues                                     |

---

## 12. Usability & User Experience Indicators (Per-Role)

Role-specific practicality indicators that evaluate how easy the system is for each actor to use.

**Deduplication policy:** an indicator that applies to more than one role is written **once**, with
all applicable roles listed in the **Roles** column. Role-specific indicators are written as
separate rows. Rows will be merged (not duplicated) as additional roles are added.

| #   | Indicator                                                                                                                                              | Roles                               |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------- |
| 1   | Ease and smoothness of the login process into the account or dedicated dashboard                                                                       | Student, Teacher, Admin, Supervisor |
| 2   | Clear navigation structure for finding and moving between the main menus                                                                               | Student, Teacher, Supervisor        |
| 3   | Clear navigation and ease of monitoring the list and activity history of supervised students                                                           | Teacher, Supervisor                 |
| 4   | Clear filling instructions and available action buttons on every feature page / dashboard                                                              | Student, Teacher, Supervisor        |
| 5   | Visual confirmation messages (notification/alert) upon successful data action (upload, update, verify, grade input)                                    | Student, Teacher, Supervisor        |
| 6   | Ease of inputting data and uploading files in digital forms                                                                                            | Student                             |
| 7   | Practical daily logbook entry without disrupting work activities at DUDI                                                                               | Student                             |
| 8   | Ease and smoothness of daily check-in / presence recording                                                                                             | Student                             |
| 9   | Ease of re-checking the history of submitted daily activity entries                                                                                    | Student                             |
| 10  | Ease of monitoring the approval status of daily journals by the supervisor                                                                             | Student                             |
| 11  | Practicality of monitoring and verifying students' daily attendance (remotely and during DUDI work hours)                                              | Teacher, Supervisor                 |
| 12  | Ease of checking and approving daily journals submitted by students                                                                                    | Teacher, Supervisor                 |
| 13  | Clear, useful presentation of stored attendance and daily journal recapitulation for viewing discipline and activity levels                            | Teacher, Supervisor                 |
| 14  | Practicality of uploading the final PKL report draft into the system                                                                                   | Student                             |
| 15  | Ease of viewing and reading revision notes / feedback from the DUDI supervisor and teacher                                                             | Student                             |
| 16  | Practicality of giving revision notes, guidance, or feedback directly on students' logbook entries and report manuscripts                              | Teacher, Supervisor                 |
| 17  | Smoothness of reviewing and monitoring the progress of students' final PKL reports                                                                     | Teacher                             |
| 18  | Clear presentation and ease of checking grade recapitulations (own achievements and DUDI-entered scores)                                               | Student, Teacher                    |
| 19  | Ease and simplicity of inputting and processing grades (school-side final grades and industry performance scores)                                      | Teacher, Supervisor                 |
| 20  | Lighter, simpler mentoring and activity-checking flow compared to manual paper-based procedures                                                        | Student, Teacher, Supervisor        |
| 21  | Lighter administrative burden in managing, monitoring, assessing, and recapitulating records                                                           | Teacher, Admin, Supervisor          |
| 22  | Clear visual presentation of the DUDI location map and overall student PKL progress                                                                    | Teacher, Admin                      |
| 23  | Smooth and stable app access when operated on a desktop, laptop, or smartphone                                                                         | Student, Teacher, Admin, Supervisor |
| 24  | Responsive, neat, and proportional interface on phone screens                                                                                          | Student, Supervisor                 |
| 25  | Neat, balanced, readable visual layout (text, tables, action buttons) on dashboards and pages                                                          | Student, Teacher, Supervisor        |
| 26  | Learnability — all features are easy to understand and master on first use                                                                             | Student, Teacher, Admin, Supervisor |
| 27  | Real benefit of Internara in simplifying the role's PKL workflow and administration                                                                    | Student, Teacher                    |
| 28  | Accessible technical support channel when facing system issues                                                                                         | Student                             |
| 29  | Overall satisfaction and comfort while operating Internara for PKL activities                                                                          | Student, Teacher, Admin, Supervisor |
| 30  | Clear flow for creating, activating, and managing user accounts (student, teacher, DUDI supervisor)                                                    | Admin                               |
| 31  | Simple flow for configuring and restricting tiered access rights for each user role                                                                    | Admin                               |
| 32  | Ease of inputting, uploading, and updating master data for students and DUDI partner companies                                                         | Admin                               |
| 33  | Practical placement mapping of students and assignment of supervising teachers to DUDI locations                                                       | Admin                               |
| 34  | Practical automated issuance of administrative documents (assignment letters, approval sheets)                                                         | Admin                               |
| 35  | Smooth and practical collective printing or download of grade recapitulations, certificates, and report data into digital file formats (PDF/Excel/CSV) | Admin                               |
| 36  | Lighter flow for creating official school documents compared to manual typing/printing procedures                                                      | Admin                               |
| 37  | Ease of configuring rubric components and grade weighting per school policy                                                                            | Admin                               |
| 38  | Clear flow for determining the PKL schedule and cycle deadlines                                                                                        | Admin                               |
| 39  | Usefulness of system settings in adapting to the Pokja PKL's administrative needs                                                                      | Admin                               |
| 40  | Ease of monitoring the completeness of administrative files and grade recaps from DUDI and teachers                                                    | Admin                               |
| 41  | Smooth and easy search and filtering of specific data                                                                                                  | Admin                               |
| 42  | Clear and simple initial view of the dedicated industry supervisor dashboard                                                                           | Supervisor                          |
| 43  | Clear presentation of the assessment criteria and rubric to be filled by the industry supervisor                                                       | Supervisor                          |

> Indicators 5 and 24–26 restate the Visual & Usability Quality Criteria (§9.7) from the roles'
> experiential perspective and are recorded here for role-based evaluation only.

---

## 13. API / Data Contracts

### Identity

- `users` table via `BaseModel` + `HasUuids` (UUID PK); one row per person; role column references
  the `Role` enum.
- `Role` enum cases: `super_admin`, `admin`, `teacher`, `student`, `supervisor`, with
  `Role::resolvesTo()` mapping runtime functional roles `admin-group`/`mentor`/`mentee`.

### Global Helpers

```php
setting(string|array|null $key = null, mixed $default = null, bool $skipCache = false): mixed
brand(string $key, mixed $default = null): mixed
app_info(?string $key = null, mixed $default = null): mixed
```

Full contracts in [C8F0D-shared-utilities](C8F0D-shared-utilities.md) (FR-SUP11) and
[YB22J-settings-infrastructure](YB22J-settings-infrastructure.md).

### Module Landscape (18 modules)

`app/` hosts zero top-level business directories; all code lives in modules. Each module owns its
vertical slice: `Models/`, `Entities/`, `Enums/`, `Data/`, `Actions/`, `Events/`, `Listeners/`,
`Notifications/`, `Policies/`, `Livewire/`, `Services/`, `Support/`, routes, and `lang/`. The full
module dependency graph and registration order live in `config/module.php` and
`docs/refs/modules/index.md`.

### Architecture Contracts (authoritative references)

- 4-layer model and Action Triad — [D2FT3-architecture](D2FT3-architecture.md)
- Base classes — [SE5Q9-base-classes](SE5Q9-base-classes.md)
- RBAC & authorization — [T4B26-rbac-and-authorization](T4B26-rbac-and-authorization.md)
- Module discovery & registration — [I1BCV-module-discovery](I1BCV-module-discovery.md)

---

## 14. Design Decisions

### DD-1 — Single-Tenant, Self-Hosted, MIT

**Decision:** Distribute as a self-packaged Laravel codebase running on school-owned infrastructure.
**Rationale:** Guarantees data sovereignty, offline robustness, zero recurring cost, and no vendor
lock-in for under-resourced schools. **Trade-off:** No SaaS economics; every deployment is
per-school (install cost accepted).

### DD-2 — Module-First Vertical Slicing

**Decision:** Organize code by business module rather than a flat `app/Models` +
`app/Http/Controllers` + `app/Services` structure. **Rationale:** A business concept lives in one
directory — findable, independently testable, safe to change; prevents silent cross-module coupling
(S2). See [D2FT3](D2FT3-architecture.md) DD-1. **Trade-off:** Shared infrastructure must be
deliberately extracted to Core (FR-G8 flow).

### DD-3 — Primary Indonesian, Secondary English

**Decision:** Ship full translations in both `lang/id/` (primary) and `lang/en/` (secondary) with a
runtime toggle. **Rationale:** PKL is an Indonesian curriculum mandate; school staff and students
are native speakers. English supports bilingual schools and developers. **Trade-off:** Every
user-facing string has a translation cost — enforced by D3 and scan.

### DD-4 — Spec-Driven Build Order

**Decision:** The project is built phase-by-phase; each feature traces to a governed spec with
FR/NFR/UC IDs, and tests trace back to those IDs. **Rationale:** No behavior without a requirement;
verification is spec-gap/orphan scoring rather than line coverage (maintains the S3 doctrine).
**Trade-off:** Writing the spec precedes coding — documentation-first discipline required.

### DD-5 — Bounded Non-Goals Enforcement

**Decision:** Out-of-scope areas (multi-tenant, HR, chat, gov sync, native apps) are explicit
non-goals rather than accidental omissions. **Rationale:** Prevents scope creep and keeps the
single-tenant PKL focus crisp at scale. **Trade-off:** Schools needing government sync or payroll
run those systems externally.

---

## 15. Success Metrics

| Metric                                 | Target                                     | Measurement                                        |
| -------------------------------------- | ------------------------------------------ | -------------------------------------------------- |
| Lifecycle coverage                     | All 12 phases fully spec'd and implemented | `docs/specs/implementation-matrix.md` (green/verified rows) |
| Module colocation                      | 100% of `app/` under modules + Core        | `scan_naming.py`, directory audit                  |
| Architecture invariants (C1–C8, D1–D6) | 0 violations                               | `scripts/scan_violations.py`                       |
| Spec↔code alignment                    | 0 spec gaps, 0 orphan tests                | per-module spec audits, `scan_issues.py`           |
| Localization coverage                  | 0 hardcoded user strings in Blade/UI       | `scan_conventions.py` (D3)                         |
| Full suite                             | green                                      | `php artisan test --compact`                       |
| Backup                                 | 4h RPO / <1h RTO                           | drill + monitoring                                 |
| Security posture                       | no critical/high external-audit findings   | `qa-protocol` audits                               |

---

## 16. Roadmap

### Prerequisites

None — this is the foundational, **spec-zero** initial specification. Every other spec (and the
architecture-first build order) operates inside its scope.

### Build Guide

Implement the lifecycle in dependency order — the Foundation phase specs first
([D2FT3](D2FT3-architecture.md) architecture, then [FB792](FB792-tech-stack.md) tech stack,
[ZT6VS](ZT6VS-core-infra-services.md) infra services, [SE5Q9](SE5Q9-base-classes.md) base classes),
then each subsequent phase in build order as listed in [docs/specs/index.md](index.md). Each phase's
feature spec drives its own implementation; this spec remains the spec-zero reference for global
cross-cutting requirements (roles, localization, security, audit).

### Next Steps

| Order | Spec                                                           | Connection                                                                     |
| ----- | -------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| 1     | [Architecture Design](D2FT3-architecture.md)                   | Defines the 4-layer architecture the whole codebase must satisfy (FR-G8, DD-2) |
| 2     | [Tech Stack](FB792-tech-stack.md)                              | Pins dependency versions the build executes on                                 |
| 3     | [Core & Infrastructure Services](ZT6VS-core-infra-services.md) | Runtime services (cache, session, DB, queue, mail, storage)                    |
| 4     | [Base Classes](SE5Q9-base-classes.md)                          | BaseModel/BaseAction/BaseEntity/BaseData contracts                             |
| 5     | [System Requirements](J68GZ-system-requirements.md)            | Domain table schema for all 12 phases                                          |

---

## Quick References

- `docs/guides/product-definition.md` — scope, personas, 3S doctrine, system boundary
- `docs/specs/index.md` — full spec index and build order
- `docs/specs/implementation-matrix.md` — implementation status matrix (priority-ordered)
- `docs/refs/modules/index.md` — module dependency graph and registration
- `config/module.php` — module bootstrap order
- `docs/architecture.md` — 4-layer model, Action Triad
- **Related specs:** every spec in this directory — each derives scope from this spec-zero and/or is
  indexed under [index.md](index.md)
