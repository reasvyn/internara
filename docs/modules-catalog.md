# 📚 Modules Catalog

Complete directory of all **29+ modules** in Internara, organized by domain. Each module is an independent, testable unit.

---

## Quick Navigation

| Domain | Modules |
| :--- | :--- |
| **[Identity & Access](#-identity--access)** | Auth, User, Profile, Permission |
| **[Lifecycle Management](#-lifecycle-management)** | Internship, Setup, Student, Mentor, Teacher |
| **[Activity Monitoring](#-activity-monitoring)** | Journal, Attendance, Schedule |
| **[Academic & Assessment](#-academic--assessment)** | Assessment, Assignment, School, Department, Guidance |
| **[Operations & Support](#-operations--support)** | Report, Notification, Log, Setting, Media |
| **[Infrastructure](#-infrastructure)** | Core, Shared, UI, Status, Exception, Admin, Support |

---

## 🔐 Identity & Access

Core identity, authentication, and access control modules.

### Auth Module
**Path**: `modules/Auth/`
**Responsibility**: Multi-guard authentication system
**Key Features**:
- Login/logout for Students, Mentors, Admins
- Password reset and recovery
- Email verification
- Invitation system (sign up via token)
- Session management

**Key Files**:
- `src/Services/Contracts/AuthService.php` — Authentication interface
- `src/Services/AuthService.php` — Implementation
- `src/Livewire/Login.php` — Login component
- `database/migrations/create_auth_tables.php`

**Relations**:
- Uses: User, Profile modules
- Used by: All authenticated routes

**Testing**: `tests/Feature/AuthenticationTest.php`

---

### User Module
**Path**: `modules/User/`
**Responsibility**: User account lifecycle and management
**Key Features**:
- User CRUD operations
- Account activation/deactivation
- Role assignment
- Guard management (student, mentor, admin)
- User listing and filtering

**Key Files**:
- `src/Models/User.php` — User model
- `src/Services/Contracts/UserService.php` — User service contract
- `src/Livewire/UserManager.php` — Admin user management UI
- `database/migrations/create_users_table.php`

**Relations**:
- Uses: Permission, Profile modules
- Used by: Auth, all authenticated modules

**Testing**: `tests/Feature/UserManagementTest.php`

---

### Profile Module
**Path**: `modules/Profile/`
**Responsibility**: Personal information and PII encryption
**Key Features**:
- Store personal data (name, address, phone, etc.)
- **AES-256 encryption** for sensitive fields
- Profile completion tracking
- Avatar/photo management
- National ID (NIK) handling
- Educational background

**Key Files**:
- `src/Models/Profile.php` — Profile model with encryption
- `src/Services/Contracts/ProfileService.php`
- `src/Livewire/ProfileEditor.php` — Profile edit component
- `database/migrations/create_profiles_table.php`

**Encrypted Fields**:
- National ID (NIK)
- Home address
- Phone number
- Bank account (if applicable)

**Relations**:
- Uses: User, Media modules
- Used by: All modules needing user details

**Testing**: `tests/Feature/ProfileManagementTest.php`

---

### Permission Module
**Path**: `modules/Permission/`
**Responsibility**: RBAC (Role-Based Access Control)
**Key Features**:
- Role and permission management (via spatie/laravel-permission)
- Policy-based authorization
- Permission inheritance
- Role assignment UI
- Audit of permission changes

**Key Files**:
- `src/Models/Role.php` — Role model
- `src/Models/Permission.php` — Permission model
- `src/Policies/*.php` — Authorization policies
- `src/Livewire/RoleManager.php` — Role management UI

**Built-in Roles**:
- Super Admin (all permissions)
- Admin (institution-level)
- Teacher (academic oversight)
- Mentor (industry-side)
- Student (basic access)

**Relations**:
- Uses: User module
- Used by: All protected routes

**Testing**: `tests/Feature/AuthorizationTest.php`

---

## 🎓 Lifecycle Management

Modules managing the internship lifecycle from enrollment to completion.

### Internship Module
**Path**: `modules/Internship/`
**Responsibility**: Internship program setup and management
**Key Features**:
- Create and manage internship programs
- Define internship requirements
- Placement slot management
- Registration tracking
- Status lifecycle (planning → active → completed)
- Duration and timeline management

**Key Files**:
- `src/Models/Internship.php` — Internship program
- `src/Models/Registration.php` — Student registration
- `src/Models/Placement.php` — Industry placement slot
- `src/Services/Contracts/InternshipService.php`
- `src/Livewire/InternshipManager.php`

**Relations**:
- Uses: School, Assessment, Notification modules
- Used by: All modules (central to system)

**Testing**: `tests/Feature/InternshipManagementTest.php`

---

### Setup Module
**Path**: `modules/Setup/`
**Responsibility**: **One-time installation wizard**
**Key Features**:
- 8-step guided setup process
- Institution configuration
- Admin account creation
- System preferences
- Department setup
- Internship program initialization
- Security lockdown after completion

**Setup Wizard Steps**:
1. Welcome
2. Environment (name, timezone, locale)
3. Institution (school info, logo)
4. Admin Account (email, password)
5. System Config (queue, cache, logging)
6. Departments (organizational structure)
7. Internship Program (duration, dates, grading)
8. Complete (summary, lock)

**Key Files**:
- `src/Services/SetupService.php` — Setup coordination
- `src/Livewire/SetupWelcome.php` — Step 1
- `src/Livewire/EnvironmentSetup.php` — Step 2
- `src/Http/Middleware/RequireSetupAccess.php` — Security
- `src/Http/Middleware/ProtectSetupRoute.php` — Lockdown

**Security**:
- One-time setup token
- Automatic route lockdown (404 after completion)
- Emergency reset: `php artisan app:setup-reset`

**Relations**:
- Uses: Internship, School, User, Permission modules
- Used by: Middleware (system-wide)

**Testing**: `tests/Feature/SetupWizardTest.php`

---

### Student Module
**Path**: `modules/Student/`
**Responsibility**: Student account and internship tracking
**Key Features**:
- Student profile information
- Enrollment and registration
- Progress tracking
- Grade viewing
- Journal access
- Dashboard and reporting

**Key Files**:
- `src/Models/Student.php` — Student model
- `src/Services/Contracts/StudentService.php`
- `src/Livewire/Dashboard.php` — Student dashboard
- `src/Livewire/StudentManager.php` — Admin student management

**Relations**:
- Uses: User, Profile, Internship, Journal, Assessment
- Used by: Teacher, Mentor modules

**Testing**: `tests/Feature/StudentManagementTest.php`

---

### Mentor Module
**Path**: `modules/Mentor/`
**Responsibility**: Industry mentor account and mentoring
**Key Features**:
- Mentor profile and organization
- Mentee assignment and tracking
- Evaluation and feedback
- Schedule management
- Attendance marking
- Mentee progress monitoring

**Key Files**:
- `src/Models/Mentor.php`
- `src/Services/Contracts/MentorService.php`
- `src/Livewire/Dashboard.php` — Mentor dashboard
- `src/Livewire/MenteeManager.php` — Mentee tracking

**Relations**:
- Uses: User, Profile, Internship, Attendance
- Used by: Internship, Assessment modules

**Testing**: `tests/Feature/MentorManagementTest.php`

---

### Teacher Module
**Path**: `modules/Teacher/`
**Responsibility**: Educational institution teacher/coordinator
**Key Features**:
- Teacher profile and subject assignment
- Class and curriculum management
- Assessment creation and grading
- Student progress oversight
- Report generation
- Internship monitoring

**Key Files**:
- `src/Models/Teacher.php`
- `src/Services/Contracts/TeacherService.php`
- `src/Livewire/Dashboard.php` — Teacher dashboard
- `src/Livewire/StudentGrading.php` — Assessment component

**Relations**:
- Uses: User, Profile, School, Assessment, Report
- Used by: Assessment, Internship modules

**Testing**: `tests/Feature/TeacherManagementTest.php`

---

## 📊 Activity Monitoring

Real-time tracking and logging of internship activities.

### Journal Module
**Path**: `modules/Journal/`
**Responsibility**: Daily activity logging and supervision
**Key Features**:
- Daily journal entries (student writes activities)
- Supervisor validation (mentor reviews)
- Activity categories and tagging
- Media attachments
- Status tracking (draft, submitted, approved)
- History and revision tracking

**Key Files**:
- `src/Models/Journal.php` — Journal entry
- `src/Models/JournalValidator.php` — Validation status
- `src/Services/Contracts/JournalService.php`
- `src/Livewire/JournalWriter.php` — Write component
- `src/Livewire/JournalValidator.php` — Review component

**Statuses**:
- Draft (student writing)
- Submitted (waiting mentor review)
- Approved (mentor validated)
- Rejected (mentor needs changes)

**Relations**:
- Uses: Student, Mentor, Media modules
- Used by: Assessment, Report modules

**Testing**: `tests/Feature/JournalManagementTest.php`

---

### Attendance Module
**Path**: `modules/Attendance/`
**Responsibility**: Check-in tracking and absence management
**Key Features**:
- Daily check-in/check-out
- GPS location tracking (optional)
- Absence requests and approvals
- Late arrival tracking
- Monthly attendance summary
- Compliance reporting

**Key Files**:
- `src/Models/Attendance.php` — Daily record
- `src/Models/AbsenceRequest.php` — Leave request
- `src/Services/Contracts/AttendanceService.php`
- `src/Livewire/CheckinComponent.php` — Mobile check-in

**Relations**:
- Uses: Student, Mentor, Notification modules
- Used by: Assessment, Report modules

**Testing**: `tests/Feature/AttendanceTrackingTest.php`

---

### Schedule Module
**Path**: `modules/Schedule/`
**Responsibility**: Internship timeline and event management
**Key Features**:
- Internship start/end dates
- Important dates (holidays, breaks)
- Event scheduling
- Reminder notifications
- Calendar integration
- Timeline visualization

**Key Files**:
- `src/Models/InternshipSchedule.php`
- `src/Services/Contracts/ScheduleService.php`
- `src/Livewire/ScheduleCalendar.php`

**Relations**:
- Uses: Internship, Notification modules
- Used by: All modules (reference dates)

**Testing**: `tests/Feature/ScheduleManagementTest.php`

---

## 🎓 Academic & Assessment

Grading, evaluation, and academic management.

### Assessment Module
**Path**: `modules/Assessment/`
**Responsibility**: Multi-stakeholder evaluation and grading
**Key Features**:
- Rubric-based assessment
- Multi-evaluator scoring (teacher, mentor)
- Grade calculation and weighting
- Competency tracking
- Transcript generation
- Certificate issuance
- Compliance auditing

**Key Files**:
- `src/Models/Assessment.php` — Assessment definition
- `src/Models/AssessmentScore.php` — Score record
- `src/Models/Rubric.php` — Rubric definition
- `src/Services/Contracts/AssessmentService.php`
- `src/Livewire/GradingComponent.php`

**Assessment Types**:
- Daily performance
- Technical skills
- Soft skills
- Final evaluation
- Competency-based

**Relations**:
- Uses: Student, Teacher, Mentor, Journal modules
- Used by: Report, Notification modules

**Testing**: `tests/Feature/AssessmentGradingTest.php`

---

### Assignment Module
**Path**: `modules/Assignment/`
**Responsibility**: Task and submission management
**Key Features**:
- Assignment creation by teachers
- Submission tracking
- Deadline management
- Peer/mentor review
- Grading integration
- File attachment handling

**Key Files**:
- `src/Models/Assignment.php`
- `src/Models/Submission.php`
- `src/Services/Contracts/AssignmentService.php`
- `src/Livewire/AssignmentViewer.php`

**Relations**:
- Uses: Student, Teacher, Media modules
- Used by: Assessment module

**Testing**: `tests/Feature/AssignmentTrackingTest.php`

---

### School Module
**Path**: `modules/School/`
**Responsibility**: Institution and educational scoping
**Key Features**:
- School profile (name, type, contact)
- Department management
- Curriculum configuration
- Facility information
- Contact person management
- Document storage

**Key Files**:
- `src/Models/School.php`
- `src/Models/Department.php`
- `src/Services/Contracts/SchoolService.php`
- `src/Livewire/SchoolSettings.php`

**Relations**:
- Uses: User, Permission modules
- Used by: Setup, Internship modules

**Testing**: `tests/Feature/SchoolManagementTest.php`

---

### Department Module
**Path**: `modules/Department/`
**Responsibility**: Organizational structure
**Key Features**:
- Create and manage departments
- Department head assignment
- Subject/program assignment
- Budget tracking (optional)
- Staff management

**Key Files**:
- `src/Models/Department.php`
- `src/Services/Contracts/DepartmentService.php`
- `src/Livewire/DepartmentManager.php`

**Relations**:
- Uses: School, User modules
- Used by: Teacher, Internship modules

**Testing**: `tests/Feature/DepartmentManagementTest.php`

---

### Guidance Module
**Path**: `modules/Guidance/`
**Responsibility**: Handbook and guidance material distribution
**Key Features**:
- Upload and organize guidance documents
- PDF handbook generation
- Version tracking
- Access control per role
- Download and printing support
- Search and categorization

**Key Files**:
- `src/Models/Handbook.php`
- `src/Services/Contracts/GuidanceService.php`
- `src/Livewire/HandbookViewer.php`

**Relations**:
- Uses: Media module
- Used by: All modules (reference materials)

**Testing**: `tests/Feature/GuidanceDistributionTest.php`

---

## 📈 Operations & Support

System operations, reporting, and support functions.

### Report Module
**Path**: `modules/Report/`
**Responsibility**: Analytics and reporting dashboards
**Key Features**:
- Progress dashboards
- Attendance reports
- Assessment analytics
- Performance metrics
- Export to Excel/PDF
- Custom report builder
- Compliance reporting

**Key Files**:
- `src/Models/Report.php` — Saved report
- `src/Services/Contracts/ReportService.php`
- `src/Livewire/ReportBuilder.php`
- `src/Http/Controllers/ReportExportController.php`

**Report Types**:
- Attendance summary
- Assessment results
- Progress tracking
- Competency analysis
- Compliance audit

**Relations**:
- Uses: Student, Journal, Attendance, Assessment modules
- Used by: Teacher, Admin dashboards

**Testing**: `tests/Feature/ReportGenerationTest.php`

---

### Notification Module
**Path**: `modules/Notification/`
**Responsibility**: Multi-channel alert system
**Key Features**:
- Email notifications
- SMS notifications (optional)
- In-app notifications
- Notification preferences
- Broadcast support
- Queue-based delivery
- Retry logic

**Key Files**:
- `src/Models/Notification.php`
- `src/Services/Contracts/NotificationService.php`
- `src/Jobs/SendNotificationJob.php`
- `src/Events/NotificationCreated.php`

**Notification Types**:
- Account activation
- Assignment submission deadline
- Grade posted
- Attendance alert
- Journal validation request
- System announcements

**Relations**:
- Uses: User, Media modules
- Used by: All modules (event listeners)

**Testing**: `tests/Feature/NotificationSendingTest.php`

---

### Log Module
**Path**: `modules/Log/`
**Responsibility**: Activity audit trail and logging
**Key Features**:
- Automatic activity logging (via spatie/activitylog)
- PII masking
- Search and filter logs
- Export logs
- Retention policies
- Admin audit view

**Key Files**:
- `src/Livewire/ActivityLog.php` — Log viewer
- `src/Services/Contracts/LogService.php`

**Logged Actions**:
- User login/logout
- Data creation, update, delete
- Permission changes
- Assessment scores
- Attendance records
- Journal submissions

**Relations**:
- Uses: No module (integrates with all)
- Used by: Admin, Security audits

**Testing**: `tests/Feature/ActivityLoggingTest.php`

---

### Setting Module
**Path**: `modules/Setting/`
**Responsibility**: System-wide configuration
**Key Features**:
- Global configuration management
- Feature flags
- Email templates
- SMS templates
- System preferences
- Admin settings UI

**Key Files**:
- `src/Models/Setting.php`
- `src/Services/Contracts/SettingService.php`
- `src/Livewire/SystemSettings.php`

**Configuration Areas**:
- Mail settings
- Queue settings
- Cache settings
- API keys
- Feature toggles
- Notification templates

**Relations**:
- Uses: No module (global)
- Used by: All modules

**Testing**: `tests/Feature/SettingManagementTest.php`

---

### Media Module
**Path**: `modules/Media/`
**Responsibility**: File storage and management
**Key Features**:
- File upload and storage (via spatie/laravel-medialibrary)
- Cloud storage support (S3, etc.)
- Image optimization
- Virus scanning (optional)
- Access control (private/public)
- File cleanup

**Key Files**:
- `src/Services/Contracts/MediaService.php`
- `src/Http/Controllers/MediaUploadController.php`

**Supported Files**:
- Images (JPEG, PNG, GIF, WebP)
- Documents (PDF, Word, Excel)
- Videos (MP4, WebM)
- Archives (ZIP, RAR)

**Relations**:
- Uses: No module (utility)
- Used by: User, Profile, Journal, Guidance modules

**Testing**: `tests/Feature/FileUploadTest.php`

---

## 🏢 Infrastructure

Core infrastructure and shared services.

### Core Module
**Path**: `modules/Core/`
**Responsibility**: Shared kernel and base classes
**Key Features**:
- Base model class (with common traits)
- Base service class
- Eloquent query builder (with common scopes)
- Shared exceptions
- Helper functions
- Constants and enumerations

**Key Files**:
- `src/Models/BaseModel.php` — Base class (UUID, timestamps, soft delete)
- `src/Services/BaseService.php` — Service base class
- `src/Query/EloquentQuery.php` — Query builder
- `src/Exceptions/*` — Custom exceptions

**Relations**:
- Used by: All other modules (foundation)

---

### Shared Module
**Path**: `modules/Shared/`
**Responsibility**: Cross-module utilities and contracts
**Key Features**:
- Common contracts/interfaces
- Shared utilities
- Collection helpers
- String utilities
- Date utilities
- Validation rules

**Key Files**:
- `src/Contracts/*` — Common interfaces
- `src/Utilities/*` — Helper classes

**Relations**:
- Used by: Multiple modules

---

### UI Module
**Path**: `modules/UI/`
**Responsibility**: Design system and shared components
**Key Features**:
- Component library
- Tailwind CSS configuration
- Icon sets
- Form components
- Table components
- Modal components
- Toast notifications

**Key Files**:
- `resources/views/components/*` — Blade components
- `resources/css/ui.css` — Design system
- `src/Livewire/RecordManager.php` — Base CRUD component

**Relations**:
- Used by: All modules (UI)

---

### Status Module
**Path**: `modules/Status/`
**Responsibility**: Shared status enumerations
**Key Features**:
- Internship statuses
- Journal entry statuses
- Attendance statuses
- Assessment statuses
- Registration statuses

**Key Files**:
- `src/Enums/*` — Status enums

**Example Statuses**:
- Internship: draft, active, completed, suspended
- Journal: draft, submitted, approved, rejected
- Attendance: present, absent, late, excused

**Relations**:
- Used by: Internship, Journal, Attendance, Assessment modules

---

### Exception Module
**Path**: `modules/Exception/`
**Responsibility**: Custom application exceptions
**Key Features**:
- Custom exception classes
- HTTP status mapping
- User-friendly error messages
- Logging integration

**Key Files**:
- `src/Exceptions/*` — Custom exceptions

**Example Exceptions**:
- `InternshipNotFoundException`
- `UnauthorizedAccessException`
- `InvalidTransitionException`
- `ValidationException`

**Relations**:
- Used by: All modules

---

### Admin Module
**Path**: `modules/Admin/`
**Responsibility**: Administrative dashboards and tools
**Key Features**:
- Admin dashboard
- Job monitoring
- System health
- Batch operations
- Data import/export
- User management

**Key Files**:
- `src/Livewire/Dashboard.php` — Admin dashboard
- `src/Livewire/JobMonitor.php` — Queue monitoring

**Relations**:
- Uses: All modules
- Used by: Super Admin role

---

### Support Module
**Path**: `modules/Support/`
**Responsibility**: Help and support documentation
**Key Features**:
- Help articles
- FAQ management
- Ticket tracking
- Contact support
- Knowledge base

**Key Files**:
- `src/Models/HelpArticle.php`
- `src/Livewire/SupportCenter.php`

**Relations**:
- Uses: Media module
- Used by: All users

---

## Creating a New Module

To add a new module, follow the standard structure:

```bash
# 1. Create module structure
php artisan module:make FeatureName

# 2. Add to modules/ directory
modules/FeatureName/
├── src/Models/
├── src/Services/Contracts/
├── src/Services/
├── src/Livewire/
├── tests/
├── database/migrations/
└── resources/

# 3. Create service contract
touch modules/FeatureName/src/Services/Contracts/FeatureService.php

# 4. Implement service
touch modules/FeatureName/src/Services/FeatureService.php

# 5. BindServiceProvider auto-discovers and binds
# No manual configuration needed!

# 6. Add migrations
php artisan module:make-migration create_feature_table FeatureName

# 7. Run migrations
php artisan migrate
```

---

## Dependencies Between Modules

```
┌────────────────────────────────────────┐
│ Setup (one-time)                       │
│ ↓                                      │
│ ┌──────────────────────────────────┐   │
│ │ Core (foundation)                │   │
│ │ ├─ BaseModel, BaseService        │   │
│ │ └─ All modules depend on this    │   │
│ └──────────────────────────────────┘   │
│ ↓                                      │
│ ┌──────────────────────────────────┐   │
│ │ Identity (Auth, User, Profile)   │   │
│ │ ├─ Required for authentication   │   │
│ │ └─ All modules depend on this    │   │
│ └──────────────────────────────────┘   │
│ ↓                                      │
│ ┌──────────────────────────────────┐   │
│ │ Lifecycle (Internship, Student)  │   │
│ │ ├─ Core business logic           │   │
│ │ └─ Assessment, Journal depend    │   │
│ └──────────────────────────────────┘   │
│ ↓                                      │
│ ┌──────────────────────────────────┐   │
│ │ Support (Notification, Report)   │   │
│ │ ├─ Optional enhancements         │   │
│ │ └─ Depend on core modules        │   │
│ └──────────────────────────────────┘   │
└────────────────────────────────────────┘
```

---

## Further Reading

- [Architecture Guide](architecture.md) — How modules interact
- [Standards Guide](standards.md) — Module coding standards
- [Testing Guide](testing.md) — Testing modules

---

*Organized modularity at scale.* 📚
