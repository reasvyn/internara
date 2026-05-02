# Key Features Checklist

> Single Source of Truth SSoT for tracking the evolution of this project based on feature-based requirements.
> See `KEY_FEATURES_GUIDELINE.md` for detailed instructions on how to construct feature entries.

---

## Project Requirements

- Fully documented: Sync or sink documentation
- All models use UUID primary keys
- Multi Language: Indonesian & English extensible to additional locales
- Mobile-first responsive design
- Light/Dark Mode Support with system preference detection
- Role-Based Access Control: SuperAdmin Admin Student Teacher Mentor
- Three-tier configuration system: AppInfo to Config to Settings
- Action-Oriented MVC architecture: Stateless Actions Rich Models
- Event-driven side effects for notifications audit logs and emails

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
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] MVC architecture with stateless Action layer
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] Layer separation enforced: Controllers to Actions to Models
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] UUID-based models with embedded business rules
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] FormRequest validation plus thin Controllers
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] Multi-driver database: SQLite MySQL PostgreSQL
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] Cache and session with database default Redis-ready
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] File storage local and S3-ready with Spatie MediaLibrary on 4 models
- [*] | [v] [v] [ ] | [MUST HAVE] [roles:System] Email and in-app notifications: 4 Actions template NotificationManager UI
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] GitHub Actions CI/CD: quality arch tests security jobs
- [*] | [v] [?] [ ] | [MUST HAVE] [roles:System] Background job processing with queued async tasks
- [*] | [v] [ ] [ ] | [MUST HAVE] [roles:System] Localization service for EN/ID switching
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] Metadata service for app info
- [*] | [+] [?] [ ] | [MUST HAVE] [roles:System] Console commands (CLI tools) for system management
- [*] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all system-level strings

### Domain: Configuration & Branding
- [R] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] Three-tier config: AppInfo static Config file Settings database
- [R] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] Dynamic system settings stored in database
- [R] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin] AppInfo single source of truth: app_info.json
- [*] | [v] [?] [ ] | [MUST HAVE] [roles:System] Author signature protection with fatal error
- [*] | [v] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin] Branding: name, logo, favicon, color scheme
- [*] | [v] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin] SMTP mail configuration via settings
- [ ] | [v] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all settings strings, labels and hints

### Domain: UI/UX Foundation
- [*] | [ ] [ ] [ ] | [MUST HAVE] [roles:ALL] Base layout with header slot content area footer slot
- [*] | [v] [v] [ ] | [MUST HAVE] [roles:ALL] Sticky navbar header with role-based navigation
- [*] | [v] [?] [ ] | [MUST HAVE] [roles:ALL] Footer with developer credit signature
- [*] | [v] [?] [ ] | [MUST HAVE] [roles:ALL] Language switcher EN/ID via session storage
- [*] | [v] [?] [ ] | [MUST HAVE] [roles:ALL] Theme switcher light/dark/system via cookie
- [*] | [v] [+] [v] | [MUST HAVE] [roles:ALL] Layout hierarchy with signed vs. guest user awareness
- [ ] | [v] [ ] [ ] | [SHOULD HAVE] [roles:ALL] View style consistency with comprehensive UI design system
- [ ] | [v] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Multi-language, responsive for all screens and light/dark mode support
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all UI components and layouts

### Domain: Authentication & Access Control
- [R] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] RBAC via Spatie with 5 roles: SuperAdmin, Admin, Student, Teacher and Mentor
- [R] | [v] [v] [v] | [MUST HAVE] [roles:SuperAdmin,Admin] User management page with listing and search
  - [ ] | [ ] [ ] [ ] | User registration form with validation
  - [ ] | [ ] [ ] [ ] | Role assignment and modification interface
- [ ] | [v] [?] [ ] | [MUST HAVE] [roles:ALL] Authentication via Laravel auth plus CheckRole middleware
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:ALL] Role-based dashboards: UserDashboard ManagerialWidgets StudentDashboard
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Invitation acceptance flow
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Account claiming with self-service role assignment
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Email verification flow
- [ ] | [v] [?] [ ] | [SHOULD HAVE] [roles:ALL] Login page with form
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Forgot password flow
- [ ] | [v] [?] [ ] | [SHOULD HAVE] [roles:ALL] Reset password flow with token validation
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account lifecycle dashboard
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Admin verification queue
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account lockout and session expiry
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account clone detection
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] GDPR compliance service
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account audit logger
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all auth screens roles and permission labels

### Domain: Installation & Setup
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Multi-step setup wizard: welcome school account department internship finalize steps
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Pre-flight audit: EnvAuditor checks PHP version extensions writable dirs DB connection app key
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Middleware: RequireSetupAccess for auto-redirect on uninstalled state
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Middleware: ProtectSetupRoute with token validation plus rate limiting
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Middleware: BypassSetupAuthorization with Gate bypass during setup
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Lock file gate: storage/app/.installed blocks re-access
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Session-based state management with SetupService
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Token generation with encryption and 24h expiry
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Setup actions in app/Actions/Setup with 5 actions
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Complete setup flow with DB transaction
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] System installer for technical initialization: env key migrations seeding
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Onboarding service with batch CSV import for stakeholders
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Post-installation hook execution
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Artisan commands: setup:install setup:reset
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] EN/ID translations for all wizard steps
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin] EN/ID translation coverage across all wizard steps and labels

### Domain: School & Organization
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] School profile management with media attachments
  - [ ] | [ ] [ ] [ ] | School information form with validation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Department management with student teacher assignment
  - [ ] | [ ] [ ] [ ] | Department listing with pagination
  - [ ] | [ ] [ ] [ ] | Department form with validation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Academic year management with name start end dates
  - [ ] | [ ] [ ] [ ] | Academic year service with single active enforcement
  - [ ] | [ ] [ ] [ ] | Academic year activation deactivation workflow
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:ALL] Academic year trait for model scoping by year
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Application setting management with override support
  - [ ] | [ ] [ ] [ ] | Setting model with group key value
  - [ ] | [ ] [ ] [ ] | Setting override mechanism for runtime changes
  - [ ] | [ ] [ ] [ ] | Setting retrieval with default fallbacks
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all school and department labels

### Domain: Internship Management
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Official document management
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Internship requirement submission
  - [ ] | [ ] [ ] [ ] | Requirement submission form with validation
  - [ ] | [ ] [ ] [ ] | Requirement listing and management
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Registration listing and management
  - [ ] | [ ] [ ] [ ] | Registration form for students
  - [ ] | [ ] [ ] [ ] | Registration approval workflow
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Bulk student placement interface
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Placement history tracking
  - [ ] | [ ] [ ] [ ] | Placement form with validation
  - [ ] | [ ] [ ] [ ] | Student placement listing and management
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Company management
  - [ ] | [ ] [ ] [ ] | Company listing with pagination
  - [ ] | [ ] [ ] [ ] | Company form with validation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Student internship registration form
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Internship report and feedback submission
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all internship forms labels and status messages

### Domain: Attendance & Journal
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Clock In Clock Out actions
  - [ ] | [ ] [ ] [ ] | Attendance log with timestamp tracking
  - [ ] | [ ] [ ] [ ] | Attendance listing and management
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Absence request submission and status tracking
  - [ ] | [ ] [ ] [ ] | Absence request form with validation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Journal entry submission
  - [ ] | [ ] [ ] [ ] | Journal entry form with validation
  - [ ] | [ ] [ ] [ ] | Journal listing and index view
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Teacher,Mentor] Attendance listing and management for assigned students
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Attendance listing and management
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all attendance and journal labels and status messages

### Domain: Guidance & Mentoring
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Mentor] Supervision log creation and status tracking
  - [ ] | [ ] [ ] [ ] | Supervision log form with validation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Mentor] Monitoring visit logging
  - [ ] | [ ] [ ] [ ] | Monitoring visit form with validation
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Mentor] Mentor-to-student assignment and matching workflow
  - [ ] | [ ] [ ] [ ] | Mentor listing with assignment interface
  - [ ] | [ ] [ ] [ ] | Mentoring management interface
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Mentor] Intern evaluation form and submission
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Handbook management with title slug content version
  - [ ] | [ ] [ ] [ ] | Handbook listing with pagination
  - [ ] | [ ] [ ] [ ] | Handbook form with validation
  - [ ] | [ ] [ ] [ ] | Handbook PDF download
  - [ ] | [ ] [ ] [ ] | Published draft states with published_at timestamp
  - [ ] | [ ] [ ] [ ] | Handbook versioning with incrementing version numbers
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Handbook acknowledgement tracking
  - [ ] | [ ] [ ] [ ] | Acknowledgement interface for students
  - [ ] | [ ] [ ] [ ] | Acknowledgement status table
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all supervision mentoring and handbook labels

### Domain: Assessment & Assignment
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Teacher] Assignment creation and submission tracking
  - [ ] | [ ] [ ] [ ] | Assignment listing with pagination
  - [ ] | [ ] [ ] [ ] | Assignment form with validation
  - [ ] | [ ] [ ] [ ] | Assignment submission interface for students
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Teacher] Assessment grading interface
  - [ ] | [ ] [ ] [ ] | Assessment form with grading criteria
  - [ ] | [ ] [ ] [ ] | Certificate and transcript generation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Teacher] Student competency tracking and skill progress logging
  - [ ] | [ ] [ ] [ ] | Competency model with department mapping
  - [ ] | [ ] [ ] [ ] | Skill progress tracking interface
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Assignment type management
  - [ ] | [ ] [ ] [ ] | Assignment type listing with pagination
  - [ ] | [ ] [ ] [ ] | Assignment type form with validation
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Rubric form for structured assessments
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Skill progress visualization chart
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Certificate generation upon completion
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student] Assignment file text submission
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Skill progress visualization to view own competency growth
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Certificate download
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all assignment assessment and competency labels

### Domain: Reporting
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report listing with pagination and status filtering
  - [ ] | [ ] [ ] [ ] | Report model with status tracking
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Async report generation via queued job
  - [ ] | [ ] [ ] [ ] | Background job for report processing
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report download with authorization check
  - [ ] | [ ] [ ] [ ] | Secure download controller
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report status tracking
  - [ ] | [ ] [ ] [ ] | Report notification service
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Report export to PDF
  - [ ] | [ ] [ ] [ ] | PDF generation service
  - [ ] | [ ] [ ] [ ] | Export history tracking
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Attendance report generation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Grade report generation
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin,Teacher] Student report generation
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Student] Certificate download for students
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all report types labels and status messages

### Domain: Scheduling
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Schedule management with title dates type location internship link
  - [ ] | [ ] [ ] [ ] | Schedule form with validation
  - [ ] | [ ] [ ] [ ] | Calendar timeline view
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Schedule type filtering
  - [ ] | [ ] [ ] [ ] | Schedule type enumeration
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Calendar events retrieval for internship
  - [ ] | [ ] [ ] [ ] | Calendar event data provider
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin,Admin] Schedule filtering by academic year
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:Student,Teacher,Mentor] Schedule view read-only filtered by assigned internship
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all schedule types labels and form fields

### Domain: Teacher & Mentor Portals
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Mentor] Mentor dashboard with assigned students overview
  - [ ] | [ ] [ ] [ ] | Mentor listing with assignment interface
  - [ ] | [ ] [ ] [ ] | Mentoring management interface
  - [ ] | [ ] [ ] [ ] | Mentoring log with visit tracking
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Mentor] Intern evaluation form and submission
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Teacher] Teacher dashboard with class overview
  - [ ] | [ ] [ ] [ ] | Teacher listing with overview
  - [ ] | [ ] [ ] [ ] | Teacher internship assessment interface
  - [ ] | [ ] [ ] [ ] | Teacher mentoring view
  - [ ] | [ ] [ ] [ ] | Teacher reports view
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all teacher and mentor dashboard labels

### Domain: Account Status & Lifecycle
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account lifecycle dashboard
  - [ ] | [ ] [ ] [ ] | Account status history with timeline
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Admin verification queue
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account lockout and session expiry
  - [ ] | [ ] [ ] [ ] | Account restriction management
  - [ ] | [ ] [ ] [ ] | Quick action buttons for account management
  - [ ] | [ ] [ ] [ ] | Status selector with history tracking
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account clone detection
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] GDPR compliance service
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Account audit logger
  - [ ] | [ ] [ ] [ ] | Audit log with activity tracking
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all status and account labels

### Domain: Activity Log & Monitoring
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Activity feed display with recent system events
  - [ ] | [ ] [ ] [ ] | Activity log with widget display
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] PII masking in logs and audit records
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all log and activity labels

### Domain: Media & File Management
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] File storage local plus S3 with Spatie MediaLibrary on 4 models
  - [ ] | [ ] [ ] [ ] | Media handling with Spatie traits
  - [ ] | [ ] [ ] [ ] | File operations service
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all media labels

### Domain: Permissions & Access Control
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Permission management interface
  - [ ] | [ ] [ ] [ ] | Permission model with Spatie Role
  - [ ] | [ ] [ ] [ ] | Access management interface
  - [ ] | [ ] [ ] [ ] | Role badge display
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all permission labels

### Domain: User Profile & Settings
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] User profile management
  - [ ] | [ ] [ ] [ ] | Profile form with preferences
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] User preferences and settings
- [ ] | [v] [?] [ ] | [MUST HAVE] [roles:ALL] Role-based dashboards: UserDashboard ManagerialWidgets StudentDashboard
  - [ ] | [ ] [ ] [ ] | Dashboard for students
  - [ ] | [ ] [ ] [ ] | Dashboard for teachers
  - [ ] | [ ] [ ] [ ] | Dashboard for mentors
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all profile and dashboard labels

### Domain: Notification System
- [R] | [v] [v] [v] | [MUST HAVE] [roles:System] Email plus in-app notifications: 4 Actions template NotificationManager UI
  - [ ] | [ ] [ ] [ ] | Notification model with sending service
  - [ ] | [ ] [ ] [ ] | 4 notification Actions for different types
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin] Notification history and activity audit logs
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all notification labels

### Domain: Admin Dashboard & Tools
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Admin dashboard with system overview widgets
  - [ ] | [ ] [ ] [ ] | AppInfo widget display
  - [ ] | [ ] [ ] [ ] | Job monitor widget
  - [ ] | [ ] [ ] [ ] | Analytics widget
  - [ ] | [ ] [ ] [ ] | Graduation readiness widget
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Batch user onboarding with CSV import
  - [ ] | [ ] [ ] [ ] | CSV-based student import interface
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Graduation readiness assessment
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Analytics aggregation: attendance rates placement stats competency progress
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Job monitor for queued jobs
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] User management with AdminManager
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Student listing and management
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Teacher listing and management
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Mentor listing and management
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all admin dashboard labels forms and analytics

### Domain: System Monitoring & Observability
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] System health monitoring via Laravel Pulse
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:SuperAdmin] Background jobs and queue monitoring via Pulse
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin] Notification history and activity audit logs
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] Activity feed display with recent system events
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:SuperAdmin,Admin] PII masking in logs and audit records
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all system monitor labels log types and alerts

### Domain: Shared Services & Utilities
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Exportable data provider for listings
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Wizard step management service
  - [ ] | [ ] [ ] [ ] | Wizard step initialization and state props
  - [ ] | [ ] [ ] [ ] | Wizard navigation: next step back to previous
  - [ ] | [ ] [ ] [ ] | Wizard step validation and continue check
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Module activation and status detection
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Environment mode detection
  - [ ] | [ ] [ ] [ ] | Debug mode check
  - [ ] | [ ] [ ] [ ] | Development environment check
  - [ ] | [ ] [ ] [ ] | Testing environment check
  - [ ] | [ ] [ ] [ ] | Production environment check
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Shared URL and asset helpers
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Custom validation rules
  - [ ] | [ ] [ ] [ ] | Honeypot validation rule
  - [ ] | [ ] [ ] [ ] | Turnstile captcha validation rule
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Username generator utility
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Base service with Eloquent query contract
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Data formatting utilities
  - [ ] | [ ] [ ] [ ] | Currency formatting
  - [ ] | [ ] [ ] [ ] | Date formatting
  - [ ] | [ ] [ ] [ ] | Phone number formatting
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] PII masking utilities
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Safe encrypted cast for attributes
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all shared service labels

### Domain: Development & Testing Tools
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Console commands for system management
  - [ ] | [ ] [ ] [ ] | System information display command
  - [ ] | [ ] [ ] [ ] | Installation command for setup
  - [ ] | [ ] [ ] [ ] | Setup reset command for re-initialization
  - [ ] | [ ] [ ] [ ] | Test orchestration command
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Scaffolding commands for rapid development
  - [ ] | [ ] [ ] [ ] | Class generation command
  - [ ] | [ ] [ ] [ ] | Trait generation command
  - [ ] | [ ] [ ] [ ] | Interface generation command
  - [ ] | [ ] [ ] [ ] | Dusk test generation command
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Test infrastructure for automated testing
  - [ ] | [ ] [ ] [ ] | Test orchestrator for running tests
  - [ ] | [ ] [ ] [ ] | Test reporter for results
  - [ ] | [ ] [ ] [ ] | Session manager for test sessions
  - [ ] | [ ] [ ] [ ] | Target discovery for test targets
  - [ ] | [ ] [ ] [ ] | Process executor for test processes
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all development and testing labels

### Domain: Event System & Audit
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Event system for system events
  - [ ] | [ ] [ ] [ ] | Post-installation hook execution
  - [ ] | [ ] [ ] [ ] | New registration event triggering
  - [ ] | [ ] [ ] [ ] | School deletion cleanup handling
  - [ ] | [ ] [ ] [ ] | Institutional branding configuration
  - [ ] | [ ] [ ] [ ] | Failed login logging for security
  - [ ] | [ ] [ ] [ ] | Successful login logging for audit
  - [ ] | [ ] [ ] [ ] | Department cleanup on school deletion
  - [ ] | [ ] [ ] [ ] | Password reset initiated tracking
  - [ ] | [ ] [ ] [ ] | Password reset success tracking
  - [ ] | [ ] [ ] [ ] | Password reset failed tracking
  - [ ] | [ ] [ ] [ ] | Account claim throttled tracking
  - [ ] | [ ] [ ] [ ] | Account claim success tracking
  - [ ] | [ ] [ ] [ ] | Account claim failed tracking
  - [ ] | [ ] [ ] [ ] | Registration success tracking
  - [ ] | [ ] [ ] [ ] | Registration failed tracking
  - [ ] | [ ] [ ] [ ] | Brute force lockout tracking
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Model event observers
  - [ ] | [ ] [ ] [ ] | Media cleanup on deletion
  - [ ] | [ ] [ ] [ ] | Account status change tracking
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all event and audit labels

### Domain: Legacy Integration
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:System] Legacy module files retained for reference
  - [ ] | [ ] [ ] [ ] | 29 legacy modules preserved in legacy directory
  - [ ] | [ ] [ ] [ ] | 1,142 PHP files retained for archaeology
- [ ] | [ ] [ ] [ ] | [MUST HAVE] [roles:System] Legacy modules disabled from autoloading
  - [ ] | [ ] [ ] [ ] | Config returns empty array for legacy modules
  - [ ] | [ ] [ ] [ ] | Legacy routes not registered in active application
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Legacy feature parity audit
  - [ ] | [ ] [ ] [ ] | Feature-by-feature comparison with active app
  - [ ] | [ ] [ ] [ ] | Gap analysis document for engineer action
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:System] Legacy UI architecture shift
  - [ ] | [ ] [ ] [ ] | Migration path from legacy views to maryUI
  - [ ] | [ ] [ ] [ ] | Decision record for architecture direction
- [ ] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] EN/ID translation coverage across all legacy reference labels

---

## Verification Summary

- **Last verified:** 2026-05-01
- **Test execution:** FAILING with tests failing for Department Internship Placement Report School
- **Status counts:** `[v]` 42, `[R]` 0, `[*]` 19, `[P]` 0, `[ ]` 36, `[+]` 3, `[!]` 0, `[?]` 12
- **Legacy modules:** 29 modules 1,142 PHP files retained in modules and disabled from autoloading
- **App test files:** 12 test files with 11 Arch 3 Quality 4 Unit in tests and Feature tests in tests/Feature
- **Actual test results:** 93 passed 5 failed 2 todos 4 risky
- **Arch tests:** ALL PASS with 41 passed and 140 assertions
- **Quality tests:** ALL PASS with 12 passed
- **Unit tests:** ALL PASS with 40 passed and 66 assertions
- **Feature tests:** PARTIAL with 9 passed 5 failed 2 todos

---

## Known Context

- UI stack: TailwindCSS 4 plus DaisyUI 5 plus maryUI plus Alpine.js plus Livewire 4
- Setup: session-based state token TTL 24h lock file storage/app/.installed
- Test status: Arch 41 passed Quality 12 passed Unit 40 passed Feature 9 passed 5 failed 2 todos
- Legacy modules: 29 modules 1,142 PHP files retained in modules and disabled from autoloading
- config/modules.php returns empty array: legacy modules disabled from autoloading
- SVG icon o-building not found in sidebar.blade.php causes test failures
