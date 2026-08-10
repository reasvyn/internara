# Project Requirements — High-Level Feature Specifications

> **Last updated:** 2026-08-10 **Changes:** full deduplication — merged duplicated feature entries (System Health, CSV Handler, Language/Theme Switchers, Compliance Monitoring, Cross-Role Proxy, Rate Limiting, Audit Trail) into authoritative sections, consolidated 7 cross-role usability indicators, and added CSV to output-format requirements

## Description

Complete high-level feature specifications for the Internara PKL management system. Lists every
feature organized by program lifecycle phase, with descriptions, access control, and business rules.
Referenced by `docs/specs/index.md` for feature specifications and `docs/modules/index.md` for module
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
| System Health       | 15-point check (PHP, extensions, memory, DB, migrations, storage, queue, cache, app key); Admin-accessible |
| Activity Logging    | Spatie Activity Log with query scopes                                                    |

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
| Theme System           | Color resolution into CSS custom properties (light/dark); Livewire light/dark/system theme toggle | System      |
| Locale Management      | Bilingual with session preference and Livewire en/id toggle, resolved from stored setting | System      |
| Cache Invalidation     | Automatic via SettingObserver on Eloquent model events (created/updated/deleted)    | System      |

#### Auth — Authentication & Authorization

Login, password management, account recovery, RBAC.

| Feature                  | Description                                                                            | Access       |
| ------------------------ | -------------------------------------------------------------------------------------- | ------------ |
| Login via Email/Username | 4-step sequential validation, auto-lock after 10 failures                              | Guest        |
| Forgot Password          | Email-based reset (60 min expiry, single-use token)                                    | Guest        |
| Reset Password           | New password via email token                                                           | Guest        |
| Confirm Password         | Re-authenticate before sensitive operations                                            | Auth         |
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
48h); admin can proxy for either role. Applies to: logbook verification, assessment grading,
supervision log verification. Proxy-graded items are tagged with metadata for audit trail. See
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

| Category                | Requirement                                                                          |
| ----------------------- | ------------------------------------------------------------------------------------ |
| Functional Completeness | Every feature — login, activity logging, and monitoring included — is complete and conforms to the design in §3 |
| Performance             | Data processing completes within expected response times and pages load efficiently  |
| Performance             | Application operates with efficient use of computing power and device memory          |
| Performance             | System stays stable under data traffic load without crashing or lagging              |
| Performance             | Consistent performance under varying internet connection speeds                       |
| Database                | SQLite WAL mode or MySQL; UUID primary keys; 55 tables (37 domain + 18 system)       |
| Cache                   | Redis for production, file cache for development                                      |
| Queue                   | Separate `default` and `documents` pipelines                                          |
| Security                | Tiered access control is accurate across Admin, Teacher, DUDI (Supervisor), and Student roles; credentials are stored and transmitted securely |
| Security                | PII masking in logs, rate limiting on all auth endpoints, CSP headers                 |
| Reliability             | Errors are handled precisely and communicated via informative, user-friendly messages |
| Compatibility           | Access remains smooth and stable across web browsers (cross-browser)                 |
| Output Quality          | Printed summaries and exported files (PDF/Excel/CSV) are neat, accurate, and precise in format |
| Maintainability         | Clear, structured architecture that simplifies maintenance and future feature development |
| Backup                  | 4-hour RPO, under 1-hour RTO                                                          |
| Localization            | Bilingual English/Indonesian, locale stored in session                                |

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

### 6.7 Visual & Usability Quality Criteria

Every page and interactive flow MUST satisfy the following quality criteria:

| Criterion                 | Requirement                                                                     |
| ------------------------- | ------------------------------------------------------------------------------- |
| **Layout**                | Neat, balanced, and consistent placement of elements on every page              |
| **Color**                 | Harmonious, aesthetic, eye-friendly color combinations                           |
| **Icons & Visuals**       | Intuitive icons and visual elements that clarify the function of each feature   |
| **Typography**            | Readable font type, size, and text contrast on various backgrounds              |
| **Responsiveness**        | Automatic layout adaptation across screen sizes (mobile & desktop)              |
| **Navigation**            | Clear, easy-to-follow navigation flow between pages and features                |
| **Feedback**              | Visual feedback (toast/notification/alert) on every user action                 |
| **Component Consistency** | Consistent form and behavior of interactive components (buttons, forms) across the system |
| **Learnability**          | New users can quickly understand and master application workflows               |
| **Form Usability**        | Forms are easy to fill without imposing excessive cognitive load                |

---

## 7. Security & Compliance

- **PII Redaction:** Email, phone, NISN, password, address masked in logs per PDP law (UU No.
  27/2022)
- **Rate Limiting:** Multi-layer: global (30/min/IP), per-endpoint (login 5/60s, forgot 3/3600s,
  reset 5/300s, recovery 3/300s)
- **Account Locking:** Auto-lock after 10 failed attempts
- **GDPR:** Deletion logging, data erasure workflows

---

## 8. Domain, Curriculum & Regulatory Compliance

The system MUST align with the applicable vocational education regulations, curriculum structure,
and PKL implementation standards (SOP) at the school.

| Category                  | Requirement                                                                              |
| ------------------------- | ---------------------------------------------------------------------------------------- |
| **Curriculum Alignment**  | PKL operational stages in the system align with applicable regulations and vocational curriculum structure |
| **Curriculum Alignment**  | Logbook (daily journal) components align with Learning Outcomes (CP) and the student's competency skill profile |
| **Curriculum Alignment**  | Mentoring reporting flow aligns with PKL implementation standards in vocational schools  |
| **Curriculum Alignment**  | DUDI work-role mapping (plotting) matches the student's competency skill profile          |
| **Curriculum Alignment**  | Competency unit management is configurable independently by the school                    |
| **Curriculum Alignment**  | Framework accommodates separation of technical competencies (hard skills) and work ethic (soft skills) |
| **Assessment Flexibility**| Rubric customization facilitates adjusting assessment criteria to school/department-specific needs |
| **Assessment Flexibility**| Score weighting accommodates varying proportions of DUDI vs. school score combination     |
| **Assessment Accuracy**   | Dynamic weighting schemes are accurately computed into valid final grade predicates/conversions |
| **SOP Compliance**        | Application workflow complies with the PKL implementation SOP at the school               |
| **Administrative Instruments** | Digital administrative instruments (assignment letters, approval sheets, certificates) are complete and structurally correct |
| **Attendance Verification** | Attendance verification and daily presence monitoring at the internship site use valid criteria |
| **Mentoring Evidence**    | Digital mentoring trail serves as an authentic record of the educational process          |
| **Reporting Format**      | Final report recapitulation format conforms to school administrative accountability standards |
| **Digital Approval**      | Digital document approval mechanism conforms to school administrative legality principles |
| **Terminology**           | Vocational education terms/terminology are used accurately and correctly across the system |
| **Form Instructions**     | Clear, substantive instructions guide all actors when filling forms                       |
| **Information Completeness** | DUDI profile and student placement data are presented completely and relevantly         |
| **Feedback Support**      | Feedback features facilitate correction and revision of student work reports              |
| **Mentoring Support**     | System effectively supports mentoring communication and handling of student field issues  |

---

## 9. Usability & User Experience Indicators (Per-Role)

Role-specific practicality indicators that evaluate how easy the system is for each actor to use.

**Deduplication policy:** an indicator that applies to more than one role is written **once**, with
all applicable roles listed in the **Roles** column. Role-specific indicators are written as
separate rows. Rows will be merged (not duplicated) as additional roles are added.

| #  | Indicator                                                                                                 | Roles              |
| -- | --------------------------------------------------------------------------------------------------------- | ------------------ |
| 1  | Ease and smoothness of the login process into the account or dedicated dashboard                          | Student, Teacher, Admin, Supervisor |
| 2  | Clear navigation structure for finding and moving between the main menus                                   | Student, Teacher, Supervisor |
| 3  | Clear navigation and ease of monitoring the list and activity history of supervised students               | Teacher, Supervisor |
| 4  | Clear filling instructions and available action buttons on every feature page / dashboard                  | Student, Teacher, Supervisor |
| 5  | Visual confirmation messages (notification/alert) upon successful data action (upload, update, verify, grade input) | Student, Teacher, Supervisor |
| 6  | Ease of inputting data and uploading files in digital forms                                                | Student            |
| 7  | Practical daily logbook entry without disrupting work activities at DUDI                                   | Student            |
| 8  | Ease and smoothness of daily check-in / presence recording                                                 | Student            |
| 9  | Ease of re-checking the history of submitted daily activity entries                                        | Student            |
| 10 | Ease of monitoring the approval status of daily journals by the supervisor                                 | Student            |
| 11 | Practicality of monitoring and verifying students' daily attendance (remotely and during DUDI work hours)  | Teacher, Supervisor |
| 12 | Ease of checking and approving daily journals submitted by students                                        | Teacher, Supervisor |
| 13 | Clear, useful presentation of stored attendance and daily journal recapitulation for viewing discipline and activity levels | Teacher, Supervisor |
| 14 | Practicality of uploading the final PKL report draft into the system                                       | Student            |
| 15 | Ease of viewing and reading revision notes / feedback from the DUDI supervisor and teacher                  | Student            |
| 16 | Practicality of giving revision notes, guidance, or feedback directly on students' logbook entries and report manuscripts | Teacher, Supervisor |
| 17 | Smoothness of reviewing and monitoring the progress of students' final PKL reports                         | Teacher            |
| 18 | Clear presentation and ease of checking grade recapitulations (own achievements and DUDI-entered scores)   | Student, Teacher   |
| 19 | Ease and simplicity of inputting and processing grades (school-side final grades and industry performance scores) | Teacher, Supervisor |
| 20 | Lighter, simpler mentoring and activity-checking flow compared to manual paper-based procedures           | Student, Teacher, Supervisor |
| 21 | Lighter administrative burden in managing, monitoring, assessing, and recapitulating records              | Teacher, Admin, Supervisor |
| 22 | Clear visual presentation of the DUDI location map and overall student PKL progress                        | Teacher, Admin    |
| 23 | Smooth and stable app access when operated on a desktop, laptop, or smartphone                           | Student, Teacher, Admin, Supervisor |
| 24 | Responsive, neat, and proportional interface on phone screens                                             | Student, Supervisor |
| 25 | Neat, balanced, readable visual layout (text, tables, action buttons) on dashboards and pages             | Student, Teacher, Supervisor |
| 26 | Learnability — all features are easy to understand and master on first use                                 | Student, Teacher, Admin, Supervisor |
| 27 | Real benefit of Internara in simplifying the role's PKL workflow and administration                        | Student, Teacher   |
| 28 | Accessible technical support channel when facing system issues                                             | Student            |
| 29 | Overall satisfaction and comfort while operating Internara for PKL activities                              | Student, Teacher, Admin, Supervisor |
| 30 | Clear flow for creating, activating, and managing user accounts (student, teacher, DUDI supervisor)        | Admin            |
| 31 | Simple flow for configuring and restricting tiered access rights for each user role                        | Admin            |
| 32 | Ease of inputting, uploading, and updating master data for students and DUDI partner companies             | Admin            |
| 33 | Practical placement mapping of students and assignment of supervising teachers to DUDI locations            | Admin            |
| 34 | Practical automated issuance of administrative documents (assignment letters, approval sheets)             | Admin            |
| 35 | Smooth and practical collective printing or download of grade recapitulations, certificates, and report data into digital file formats (PDF/Excel/CSV) | Admin |
| 36 | Lighter flow for creating official school documents compared to manual typing/printing procedures          | Admin            |
| 37 | Ease of configuring rubric components and grade weighting per school policy                                | Admin            |
| 38 | Clear flow for determining the PKL schedule and cycle deadlines                                            | Admin            |
| 39 | Usefulness of system settings in adapting to the Pokja PKL's administrative needs                          | Admin            |
| 40 | Ease of monitoring the completeness of administrative files and grade recaps from DUDI and teachers        | Admin            |
| 41 | Smooth and easy search and filtering of specific data                                                      | Admin            |
| 42 | Clear and simple initial view of the dedicated industry supervisor dashboard                               | Supervisor       |
| 43 | Clear presentation of the assessment criteria and rubric to be filled by the industry supervisor           | Supervisor       |

> Indicators 5 and 24–26 restate the Visual & Usability Quality Criteria (§6.7) from the roles'
> experiential perspective and are recorded here for role-based evaluation only.

---

## Quick References

- `docs/specs/index.md` — Feature specifications index
- `docs/modules/index.md` — Module dependency graph
- `docs/foundation/product-definition.md` — Scope, personas, system boundary
- `docs/architecture.md` — 4-layer architecture, Action Triad
- `docs/conventions.md` — Coding conventions, invariants C1-C8, D1-D6
