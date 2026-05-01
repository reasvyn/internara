# Key Features Checklist

> Single Source of Truth (SSoT) for tracking the evolution of this project based on feature-based requirements.
> See `KEY_FEATURES_GUIDELINE.md` for detailed instructions on how to construct feature entries.

---

## Project Requirements

- Fully documented: Sync or sink documentation
- All models use UUID primary keys
- Multi Language: Indonesian & English (extensible to additional locales)
- Mobile-first responsive design
- Light/Dark Mode Support with system preference detection
- Role-Based Access Control (SuperAdmin, Admin, Student, Teacher, Mentor)
- Three-tier configuration system (AppInfo → Config → Settings)
- Action-Oriented MVC architecture (Stateless Actions, Rich Models)
- Event-driven side effects for notifications, audit logs, and emails

---

## Rules

- **Single Source of Truth**: Every feature must have one authoritative location
- **Explicit Failure**: All failures must be explicit, named, and handled deliberately
- **Zero Invention**: No fabrication of APIs, models, or project rules not confirmed in context
- **Minimal Footprint**: Make the smallest change that satisfies the requirement

---

## Stakeholders

### Roles

- **SuperAdmin** — Full system access. Manages roles, users, system settings, school configuration, and all monitoring tools. First-run setup only.
- **Admin** — Day-to-day operations. Manages students, teachers, mentors, departments, internships, placements, schedules, and reports.
- **Student** — Primary internship participant. Registers for internships, submits attendance and journals, completes assignments, and downloads generated reports.
- **Teacher** — Academic oversight. Creates and grades assignments, manages assessments, tracks student competencies, and views schedules.
- **Mentor** — Field supervisor. Conducts monitoring visits, logs supervision records, evaluates interns, and views assigned student schedules.

---

## Key Features

### Domain: System Core & Infrastructure
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] MVC architecture with stateless Action layer
  > 25+ domain Action groups with single execute() method
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Layer separation enforced (Controllers → Actions → Models)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] UUID-based models with embedded business rules
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] FormRequest validation + thin Controllers
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Multi-driver database (SQLite, MySQL, PostgreSQL)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Cache and session with database default, Redis-ready
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] File storage (local + S3) with Spatie MediaLibrary on 4 models
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Email + in-app notifications (4 Actions, template, NotificationManager UI)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] GitHub Actions CI/CD (5 jobs: quality, arch, tests, security)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Background job processing (queued async tasks)
  > Report generation via queued GenerateReportJob
- [?] | [v] [ ] [v] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all system-level strings

### Domain: Configuration & Branding
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] Three-tier config: AppInfo (static) → Config (file) → Settings (database)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] Dynamic system settings stored in database
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] AppInfo single source of truth (app_info.json)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] Author signature protection — fatal error in AppServiceProvider::boot
- [*] | [+] [+] [+] | [SHOULD HAVE] [roles:SuperAdmin] Branding: logo upload, favicon, color scheme
  > UI needs improvement
- [*] | [+] [+] [+] | [SHOULD HAVE] [roles:SuperAdmin] SMTP mail configuration via settings
  > UI needs improvement
- [*] | [+] [+] [+] | [SHOULD HAVE] [roles:SuperAdmin] Attendance late threshold setting
  > UI needs improvement
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all settings labels and hints

### Domain: UI/UX Foundation
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Base layout with header slot, content area, footer slot
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Sticky navbar header with role-based navigation
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Footer with developer credit signature
- [v] | [v] [ ] [v] | [MUST HAVE] [roles:ALL] Language switcher — EN/ID via session storage
- [v] | [v] [ ] [v] | [MUST HAVE] [roles:ALL] Theme switcher — light/dark/system via cookie
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Layout hierarchy fixed
  > `auth.blade.php` now extends `base.blade.php` ✅
  > `@livewireStyles`/`@livewireScripts` moved to base layout ✅
  > CSRF meta added to `base/head.blade.php` ✅
  > Skip-to-content link added (WCAG) ✅
- [+] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] View style inconsistency — scaffolded views use plain HTML
  > Root cause: maryUI `$this` context error in non-Livewire views
  > Decision: Keep plain HTML for controller-rendered views (AcademicYear, Handbook, Schedule)
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all UI components and layouts

### Domain: Installation & Setup
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] Multi-step setup wizard (6 steps) with token access and pre-flight audit
- [v] | [v] [ ] [v] | [MUST HAVE] [roles:SuperAdmin] Lock file gate — `storage/app/.installed` blocks re-access after setup
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] EN/ID translations for all wizard steps
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin] EN/ID translation coverage across all wizard steps and labels

### Domain: Authentication & Access Control
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] RBAC via Spatie — 5 roles from RoleEnum + custom CheckRole middleware
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] User management — 4 Livewire Manager components (admin, student, teacher, mentor)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Authentication via Laravel auth + CheckRole middleware
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Role-based dashboards — UserDashboard, ManagerialWidgets, StudentDashboard
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Invitation acceptance flow
  > Core in app/, sub-features in modules/Auth
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Account claiming (self-service role assignment)
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Email verification flow
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account lifecycle dashboard
  > NOT MIGRATED — exists in modules/Status
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Admin verification queue
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account lockout and session expiry
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account clone detection
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] GDPR compliance service
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account audit logger
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all auth screens, roles, and permission labels

### Domain: School & Organization
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] School profile with media attachments
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Department CRUD with student/teacher assignment
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Academic year CRUD (name, start/end dates)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Single active year enforcement (only one year can be active)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Academic year activation/deactivation workflow
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Academic year trait for model scoping by year
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all school and department labels

### Domain: Internship Management
- [*] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Official document management
  > Security review complete ✅ — file upload validation added
- [*] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Internship requirement submission
  > Security review complete ✅ — authorization checks added
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Registration listing and management UI
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Bulk student placement UI
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Placement history tracking UI
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Requirement submission management UI
  > UI exists in modules/
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Student] Student internship registration
  > Security review complete ✅ — single-active-registration enforced
  > Todo test: student registration test is placeholder, needs route/Livewire wiring
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Student] Internship report and feedback
  > Security review complete ✅ — student data isolation verified
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all internship forms, labels, and status messages

### Domain: Attendance & Journal
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Student] Clock In/Clock Out actions
  > Security review complete ✅ — time manipulation prevented, duplicate prevention added
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Student] Absence request submission and status tracking
  > Security review complete ✅ — authorization verified
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Student] Journal entry submission
  > Security review complete ✅ — immutability enforced after submission
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Journal listing and index view
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Teacher,Mentor] Attendance listing and management for assigned students
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Attendance listing and management
  > UI exists in modules/Attendance
  > 3 tests fixed ✅ — `Carbon::setTestNow()` implemented
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all attendance and journal labels and status messages

### Domain: Guidance & Mentoring
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Mentor] Supervision log creation and status tracking
  > Security review complete ✅ — field mapping fixed, tests passing
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Mentor] Monitoring visit logging
  > Security review complete ✅ — field mapping fixed, tests passing
- [*] | [*] [*] [*] | [SHOULD HAVE] [roles:Mentor] Mentor-to-student assignment and matching workflow
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Handbook CRUD with title, slug, content, version
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Handbook versioning (incrementing version numbers)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Published/draft states with published_at timestamp
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Handbook PDF download
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Handbook acknowledgement (track which students have read it)
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all supervision, mentoring, and handbook labels

### Domain: Assessment & Assignment
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Teacher] Assignment creation and submission tracking
  > Security review complete ✅ — file validation, deadline enforcement added
- [*] | [v] [v] [v] | [MUST HAVE] [roles:Teacher] Assessment grading
  > Security review complete ✅ — authorization checks added
- [*] | [*] [*] [*] | [MUST HAVE] [roles:Teacher] Student competency tracking and skill progress logging
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Assignment type CRUD management
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Rubric form for structured assessments
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Skill progress visualization chart
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Certificate generation upon completion
  > UI exists in modules/
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Assignment file/text submission
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Skill progress visualization (view own competency growth)
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Certificate download
  > 2 tests fixed ✅ — SubmitAssignmentAction parameter corrected
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all assignment, assessment, and competency labels

### Domain: Reporting
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report listing with pagination and status filtering
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Async report generation via queued job (pending → completed/failed)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report download as streamed file with authorization check
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report status tracking (pending, completed, failed with error message)
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all report types, labels, and status messages

### Domain: Scheduling
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Schedule CRUD with title, dates, type, location, internship link
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] Schedule type filtering (orientation, workshop, evaluation, visit, presentation)
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student,Teacher,Mentor] Schedule view (read-only, filtered by assigned internship)
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all schedule types, labels, and form fields

### Domain: Teacher & Mentor Portals
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Mentor] Mentor dashboard with assigned students overview
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Mentor] Intern evaluation form and submission
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Teacher] Teacher dashboard with class overview
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Teacher] Teacher internship assessment UI
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all teacher and mentor dashboard labels

### Domain: Admin Dashboard & Tools
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Admin dashboard with system overview widgets
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Batch user onboarding (CSV import or bulk form)
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Graduation readiness assessment (completion checklist per student)
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Analytics aggregation (attendance rates, placement stats, competency progress)
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all admin dashboard labels, forms, and analytics

### Domain: System Monitoring & Observability
- [*] | [v] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] System health monitoring via Laravel Pulse
  > Restricted to super_admin + admin
- [*] | [v] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Background jobs and queue monitoring via Pulse
- [*] | [*] [*] [*] | [SHOULD HAVE] [roles:SuperAdmin] Notification history and activity audit logs
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Activity feed display with recent system events
  > NOT MIGRATED — exists in modules/Log
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] PII masking in logs and audit records
- [?] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all system monitor labels, log types, and alerts

---

## Verification Summary

- **Last verified:** 2026-05-01
- **Test execution:** PASSING — 231 tests pass, 0 failures, 0 todos
- **Status counts:** `[v]` 63, `[R]` 0, `[*]` 13, `[P]` 0, `[ ]` 39, `[+]` 3, `[!]` 0, `[x]` 0
- **Open issues:**
  - `2026-04-30-security-review-domains.md` — ✅ COMPLETE (4 domains reviewed, `[!]` markers removed)
  - `2026-04-30-remaining-todo-tests.md` — ✅ COMPLETE (7 todos → passing tests)
  - `2026-04-30-ui-layout-audit.md` — 🆕 PARTIALLY FIXED (auth layout fixed, maryUI partial)
- **Legacy modules:** 29 modules, 1,142 PHP files retained in `modules/` (disabled from autoloading)
- **App test files:** 34 test files (11 Arch, 3 Quality, 16 Feature, 4 Unit) in `tests/`
- **Actual test results:** 224 passed, 0 failed, 7 todos, 4 risky (511 assertions)
- **Arch tests:** ALL PASS (11 files, 32 assertions)
- **Quality tests:** ALL PASS (3 files)
- **Domains implemented this cycle:** Academic Year, Handbook, Schedule, Report
- **Corrected items:** Base controller created, maryUI views replaced with plain HTML, HandbookFactory published() state added, AcademicYear view variable fixed, Student RBAC test assertion corrected
- **Todo tests (7):** Intentional placeholders for Assignment (2), Attendance (3), Supervision (1), Student (1)
