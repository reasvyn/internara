# Project Requirements — High-Level Feature Specifications

> **Last updated:** 2026-07-21 **Changes:** sync — remove Guidance module, split SupervisionLog to Journals and Handbook to Document

## Description

Complete high-level feature specifications for the Internara PKL management system. Lists every
feature organized by program lifecycle phase, with descriptions, access control, and business rules.
Referenced by `docs/key-features.md` for the feature inventory and `docs/modules/index.md` for module
dependencies.

---

## 1. Context

Vocational schools (SMK) in Indonesia mandate PKL (_Praktik Kerja Lapangan_) for 3–6 months. A
typical medium-to-large SMK manages **500–1,000 active students** placed across **150–300 partner
companies (DUDI)** per placement period.

---

## 2. Role Model (5 Roles + 2 Functional)

| Role        | Code          | Description                                                                               |
| ----------- | ------------- | ----------------------------------------------------------------------------------------- |
| Super Admin | `super_admin` | Unrestricted system access, infrastructure management, bypasses all permission checks     |
| Admin       | `admin`       | School-level operations: user management, programs, companies, departments                |
| Teacher     | `teacher`     | Academic supervision: journal review, assignment grading, site visits, grade compilation  |
| Student     | `student`     | Program participation: attendance, logbooks, assignments, certificate download            |
| Supervisor  | `supervisor`  | Industry-side supervision: attendance verification, journal review, competency evaluation |

Each user is assigned exactly one role. Two additional **functional roles** (`mentor`, `mentee`) are
resolved at runtime via `Role::resolvesTo()` for business logic — never stored or used in
middleware.

---

## 3. Feature Specifications by Lifecycle Phase

### 3.1 Foundation Phase

#### Core — Foundation & Infrastructure

Base classes, contracts, middleware, and cross-module utilities that every other module depends on.

| Feature             | Description                                                                              |
| ------------------- | ---------------------------------------------------------------------------------------- |
| BaseModel (UUID)    | All models extend `BaseModel` with UUID primary keys and `HasUuids`                      |
| BaseAction          | Every business operation extends `BaseAction` — transaction + logging                    |
| BaseEntity          | `final readonly` business rules, zero framework dependencies                             |
| BasePolicy          | Role and ownership authorization checks                                                  |
| BaseRecordManager   | CRUD Livewire base: search, filter, sort, pagination, bulk actions                       |
| BaseController      | Common HTTP controller utilities                                                         |
| BaseFormRequest     | Core's form request (not Laravel's) with validation exception handling                   |
| BaseData            | Readonly DTO with `fromArray()` / `toArray()`                                            |
| SmartLogger         | Dual-channel fluent logger (system + activity) with PII masking                          |
| Exception Hierarchy | AppException (action/infrastructure/presentation) + ModuleException                      |
| StatusEnum          | State machine via `canTransitionTo()`, `isTerminal()`, `validTransitions()`              |
| LabelEnum           | All enums implement `label(): string`                                                    |
| Security Headers    | CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy                                |
| Log Context         | Request tracing: request_id, method, URL, IP, user                                       |
| System Health       | 15-point check (PHP, extensions, memory, DB, migrations, storage, queue, cache, app key) |
| Activity Logging    | Spatie Activity Log with query scopes                                                    |
| CSV Handler         | Export, import, template download with header validation                                 |
| Language Switcher   | Livewire bilingual toggle (en/id)                                                        |
| Theme Switcher      | Livewire light/dark/system theme toggle                                                  |

#### Setup — Installation & Provisioning

One-time guided installation.

| Feature               | Description                                                                           | Access        |
| --------------------- | ------------------------------------------------------------------------------------- | ------------- |
| 6-Step Setup Wizard   | Environment Audit, Super Admin, School, Department, Finalize, Complete                | Guest (token) |
| Environment Audit     | PHP version, extensions, directory permissions, database, terminal                    | Installer     |
| Setup Token           | Encrypted random token gates wizard access, single-use                                | System        |
| School Initialization | Create first school profile in settings                                               | Installer     |
| Super Admin Creation  | Name always "Administrator", username "superadmin"                                    | Installer     |
| Recovery Key          | 64-char random key, stored hashed in DB, saved to `storage/app/private/.recovery-key` | Installer     |
| CLI Install           | `php artisan setup:install` with `--check-only` and `--force`                         | CLI           |
| Super Admin Recovery  | `php artisan admin:recover` — emergency CLI recovery                                  | CLI           |

#### Settings — System Configuration & Branding

Key-value configuration store with dynamic resolution.

| Feature                | Description                                                                        | Access      |
| ---------------------- | ---------------------------------------------------------------------------------- | ----------- |
| System Setting Manager | Key-value store with type enforcement (boolean, text, numeric, JSON, image, color) | Super Admin |
| Branding Configuration | App name, logo, favicon, colors (primary/secondary/accent), custom CSS             | Super Admin |
| Feature Flags          | Enable/disable features at runtime                                                 | Super Admin |
| Mail Configuration     | SMTP settings with test email verification                                         | Super Admin |
| Theme System           | Color resolution into CSS custom properties (light/dark)                           | System      |
| Locale Management      | Bilingual with session preference, resolved from stored setting                    | System      |
| Cache Invalidation     | Automatic via SettingObserver on Eloquent model events (created/updated/deleted)    | System      |

#### Auth — Authentication & Authorization

Login, password management, account recovery, RBAC.

| Feature                  | Description                                                                            | Access       |
| ------------------------ | -------------------------------------------------------------------------------------- | ------------ |
| Login via Email/Username | 4-step sequential validation, auto-lock after 10 failures                              | Guest        |
| Forgot Password          | Email-based reset (60 min expiry, single-use token)                                    | Guest        |
| Reset Password           | New password via email token                                                           | Guest        |
| Confirm Password         | Re-authenticate before sensitive operations                                            | Auth         |
| Rate Limiting            | Multi-endpoint throttling (login 5/60s, forgot 3/3600s, reset 5/300s, recovery 3/300s) | Guest + Auth |
| Recovery Slip            | Admin generates 10 one-time codes, delivered offline, no expiry                        | Admin        |
| Account Recovery         | User redeems code to unlock account and set new password                               | Guest        |
| RBAC                     | 5 roles + 2 functional roles (mentor, mentee) with `Role::resolvesTo()`                | All          |
| Super Admin Integrity    | Name/username immutable (Administrator/superadmin), non-deletable                      | System       |

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
| System Health        | 15-point check: PHP, extensions, DB, cache, storage, queue   | Admin       |

---

### 3.2 Academic Setup Phase

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

---

### 3.3 Program Management Phase

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

---

### 3.4 Enrollment Phase

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

---

### 3.5 Daily Operations Phase

#### Journals — Logbook, Attendance & Scheduling

Daily activity tracking.

| Feature               | Description                                                              | Access           |
| --------------------- | ------------------------------------------------------------------------ | ---------------- |
| Logbook Entry         | Daily entry: date, activities, learnings, challenges, plans, attachments | Student          |
| Logbook Workflow      | DRAFT → SUBMITTED → VERIFIED/FINALIZED, 48h teacher bypass               | Student + Mentor |
| One Entry Per Day     | Maximum one entry per calendar day per student                           | System           |
| Compliance Monitoring | Auto-notify mentor if N days without entry                               | System           |
| Student Clock In/Out  | Timestamp-based, optional GPS data                                       | Student          |
| Absence Request       | Submit planned/unplanned absence with reason                             | Student          |
| Absence Approval      | Mentor approves single-day, extended requires admin                      | Mentor + Admin   |
| Attendance Manager    | CRUD records, filter, sort, reports                                      | Admin            |

#### Journals — Supervision Logs & Monitoring Visits

Mentor relationships and supervision log management within the Journals module.

| Feature                     | Description                                              | Access                         |
| --------------------------- | -------------------------------------------------------- | ------------------------------ |
| Supervision Logs            | Private notes: site visits, online, phone supervision    | Mentor                         |
| Supervision Log Workflow    | DRAFT → SUBMITTED → REVIEWED → ACKNOWLEDGED               | Mentor                         |
| Mentoring Assignments       | Maps teachers and supervisors to student registrations   | Admin                          |
| Cross-Role Proxy            | Teacher proxies supervisor log verification after 48h    | Teacher                        |

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

---

### 3.6 Assessment Phase

#### Assessment — Competency Evaluation

Rubric-based evaluation framework.

| Feature            | Description                                                    | Access                 |
| ------------------ | -------------------------------------------------------------- | ---------------------- |
| Rubric Manager     | CRUD weighted evaluation sheets with nested JSON structures    | Admin                  |
| Assessment Grading | Score against rubric indicators, auto-calculate weighted total | Teacher / Supervisor   |
| Finalization       | Finalized assessments immutable                                | System                 |
| Cross-Role Proxy   | Teacher acts as proxy for supervisor; admin proxies both       | Teacher / Admin        |
| Supervisor Grading | Industry supervisor submits scores via dedicated interface     | Supervisor             |
| Proxy Stamping     | Proxy-graded assessments tagged with metadata for audit trail  | System                 |

#### Assignment — Tasks & Submissions

Task creation, submission, grading.

| Feature              | Description                                                  | Access  |
| -------------------- | ------------------------------------------------------------ | ------- |
| Assignment Manager   | CRUD tasks: title, description, due dates, resources, points | Admin   |
| Submit Assignment    | Text, file uploads, both, with draft workflow                | Student |
| Submission Grading   | Numeric score, rubric-referenced, written feedback           | Teacher |
| Submission Lifecycle | DRAFT → SUBMITTED → VERIFIED → GRADED, optional revision     | System  |
| Deadline Management  | Due dates, late flagging, extension support                  | Teacher |

---

### 3.7 Evaluation Phase

#### Evaluation — Generic Feedback Forms

Google Forms-like feedback collection across all PKL aspects.

| Feature               | Description                                                                                    | Access |
| --------------------- | ---------------------------------------------------------------------------------------------- | ------ |
| Evaluation Forms      | Reusable form templates with weighted questions and sections                                   | Admin  |
| Polymorphic Targeting | Forms target mentors, programs, companies, or overall satisfaction via `target_type`           | System |
| Question Types        | Rating scales (1-5, 1-10), yes/no, multiple choice, agreement Likert, free text                | Admin  |
| Weighted Scoring      | Auto-calculated overall score from weighted question responses                                 | System |
| Score Bands           | EXCELLENT (85-100), GOOD (70-84), SATISFACTORY (55-69), NEEDS_IMPROVEMENT (40-54), POOR (0-39) | System |

---

### 3.8 Certification Phase

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

---

### 3.9 Reporting Phase

#### Reports — Final Grade Card

Score aggregation and sign-off.

| Feature               | Description                                                | Access          |
| --------------------- | ---------------------------------------------------------- | --------------- |
| Grade Aggregation     | Auto-calculate composite score from program weights        | System          |
| Grade Card Management | Review, override, finalize student grade card              | Teacher / Admin |
| Grade Card Lock       | Once finalized, immutable — unlocks certificate generation | System          |

---

### 3.10 Closure Phase

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

## 4. Cross-Cutting Features

These features span multiple modules and are not owned by a single module.

### 4.1 Cross-Role Proxy

Teachers can act as proxy for inactive industry supervisors after a configurable window (default
48h). Applies to: logbook verification, assessment grading, supervision log verification.
Proxy-graded items are tagged with metadata for audit trail. See
[ADR-014: Cross-Role Proxy](../adr/adr-cross-role-proxy.md).

### 4.2 Compliance Monitoring

Automated monitoring of student activity compliance. Triggers notifications when students miss
required activities (logbook entries, attendance). Configurable thresholds per program. CLI command
`journals:check-compliance` for on-demand or scheduled checks.

### 4.3 Activity Logging & Audit Trail

All administrative actions dual-logged via SmartLogger to both system channel (detailed debug) and
activity channel (immutable, PII-masked audit records). GDPR deletion logs are append-only.

### 4.4 Global Helpers

- `setting($key, $default, $skipCache)` — Runtime configuration access
- `brand($key, $default)` — Dynamic branding values (name, title, logo, favicon, colors)
- `app_info()` — Static application metadata from composer.json

### 4.5 CSV Import/Export

Template-based CSV import/export with header validation, row-by-row error reporting, and download
templates. Available on all Record Manager components.

---

## 5. Non-Functional Requirements

| Category     | Requirement                                                                    |
| ------------ | ------------------------------------------------------------------------------ |
| Performance  | Peak 1,000 concurrent clock-in writes (07:00–08:30)                            |
| Database     | SQLite WAL mode or MySQL; UUID primary keys; 55 tables (37 domain + 18 system) |
| Cache        | Redis for production, file cache for development                               |
| Queue        | Separate `default` and `documents` pipelines                                   |
| Security     | PII masking in logs, rate limiting on all auth endpoints, CSP headers          |
| Backup       | 4-hour RPO, under 1-hour RTO                                                   |
| Localization | Bilingual English/Indonesian, locale stored in session                         |

---

## 6. UI/UX & Interaction Requirements

### 6.1 User Guide Components

Every page with a non-trivial workflow MUST include a `*-guide.blade.php` component providing
contextual help. The pattern follows the setup wizard's guide at
`resources/views/setup/components/setup-guide.blade.php`.

- **Placement:** `resources/views/{module}/components/{page-name}-guide.blade.php`
- **Trigger:** Fixed floating button (bottom-right, `z-50`) with question mark icon
- **Modal:** `<x-mary-modal>` with step-by-step instructions
- **Content:** Introductory sentence, numbered steps (1 through N), tip section for best practices
- **Integration:** `$showGuide` boolean state + `@include` in parent Blade view

### 6.2 Record Manager Capabilities

Every record manager component (extending `BaseRecordManager`) MUST provide:

| Capability       | Description                                                                 |
| ---------------- | --------------------------------------------------------------------------- |
| **Search**       | Full-text search across relevant columns                                     |
| **Sort**         | Column-based sorting with visual indicators                                  |
| **Filters**      | Dropdown/checkbox filters for status, date ranges, categories                |
| **Batch Actions**| Bulk operations on selected records (delete, status change, export selection)|
| **Extra Menu**   | Download template, import (CSV/Excel), export (CSV/Excel/PDF)               |

### 6.3 Localization

All user-facing strings MUST use `__()` helper with bilingual support:

- **Minimum:** English (`lang/en/`) and Indonesian (`lang/id/`)
- **No hardcoded strings:** Every visible text, flash message, validation message, and UI label
- **Translation keys:** Follow `{module}.{context}.{key}` convention
- **Parameters:** Use `:param` syntax for dynamic values
- **Shared labels:** Use `common.php` for global terms (yes, no, save, cancel, etc.)

### 6.4 Theming System

Every Livewire component MUST implement the theming system from the Settings/Theme module:

- **CSS variables:** Use `var(--color-primary)`, `var(--color-secondary)`, etc. for brand colors
- **Dark/light mode:** Respect `theme.dark_mode` setting via CSS class or attribute
- **Dynamic colors:** Never hardcode hex colors — use `brand()` helper or CSS variables
- **Consistency:** All components must render correctly in both light and dark modes
- **Accessibility:** Maintain sufficient contrast ratios (WCAG AA minimum)

### 6.5 Form Field Icons

Every form field MUST include an icon for visual clarity:

- **Input fields:** Icon on the left side (e.g., `user`, `envelope`, `calendar`)
- **Buttons:** Optional icon (recommended for primary actions)
- **Icons:** Use Heroicons via maryUI icon system
- **Consistency:** Same icon for same field type across all modules
- **Accessibility:** Icons must not be the sole indicator — pair with labels

### 6.6 UI Design Principles

The interface MUST maintain a clean, modern, minimalist aesthetic with strong accessibility:

- **Layout:** Consistent spacing, clear hierarchy, white space utilization
- **Typography:** Readable fonts, appropriate sizes, clear contrast
- **Components:** Use maryUI component library for consistency
- **Accessibility:** ARIA labels, keyboard navigation, screen reader support
- **Responsive:** Mobile-first design, works on all device sizes
- **Feedback:** Clear loading states, success/error messages, progress indicators

---

## 7. Security & Compliance

- **PII Redaction:** Email, phone, NISN, password, address masked in logs per PDP law (UU No.
  27/2022)
- **Rate Limiting:** Multi-layer: global (30/min/IP), per-endpoint (login 5/60s, forgot 3/3600s,
  recovery 3/300s)
- **Account Locking:** Auto-lock after 10 failed attempts
- **Audit Trail:** All mutations logged via SmartLogger (system + activity dual channel)
- **GDPR:** Deletion logging, data erasure workflows

---

## Quick References

- `docs/key-features.md` — Feature inventory (same data, different presentation)
- `docs/modules/index.md` — Module dependency graph
- `docs/foundation/product-definition.md` — Scope, personas, system boundary
- `docs/architecture.md` — 4-layer architecture, Action Triad
- `docs/conventions.md` — Coding conventions, invariants C1-C8, D1-D6
