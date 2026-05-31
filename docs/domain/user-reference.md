# User — API Reference
> Last updated: 2026-05-26
> Changes: docs: update school-reference and user-reference for recent changes; add AcademicYearEditTest


Total: 40 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` | Deletes a single notification |
| `User/Actions/GetActivityLogsAction.php` | `GetActivityLogsAction` | `BaseAction` | Retrieves paginated activity logs |
| `User/Actions/GetProfileFormDataAction.php` | `GetProfileFormDataAction` | `BaseAction` | Resolves role-appropriate profile form fields |
| `User/Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | `BaseAction` | Gathers all data needed for student dashboard |
| `User/Actions/GetSupervisorDashboardStatsAction.php` | `GetSupervisorDashboardStatsAction` | `BaseAction` | Aggregates supervisor dashboard statistics |
| `User/Actions/GetTeacherDashboardStatsAction.php` | `GetTeacherDashboardStatsAction` | `BaseAction` | Aggregates teacher dashboard statistics |
| `User/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` | Marks all unread notifications as read |
| `User/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` | Marks a single notification as read |
| `User/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseAction` | Marks multiple notifications as read in batch |
| `User/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseAction` | Sends a notification to a user |
| `User/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` | Updates user profile with avatar upload |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Http/Controllers/DashboardController.php` | `DashboardController` | `BaseController` | Redirects users to role-appropriate dashboard |
| `User/Http/Controllers/HomeController.php` | `HomeController` | `BaseController` | Handles root URL routing (setup vs login) |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `User/Enums/BloodType.php` | `BloodType` | `LabelEnum` | Blood type enum |
| `User/Enums/EmploymentStatus.php` | `EmploymentStatus` | `LabelEnum` | Employment status enum |
| `User/Enums/Gender.php` | `Gender` | `LabelEnum` | Gender enum |
| `User/Enums/StructuralPosition.php` | `StructuralPosition` | `LabelEnum` | Structural position enum (legacy — position is now free text field) |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` | Paginated activity feed with filters |
| `User/Livewire/NotificationBell.php` | `NotificationBell` | `Component` | Dropdown notification bell indicator |
| `User/Livewire/NotificationCenter.php` | `NotificationCenter` | `BaseRecordManager` | Full-page notification center with viewer modal, Markdown rendering, filters, bulk actions |
| `User/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` | Profile editing form (with avatar upload and password change) |
| `User/Livewire/RecentActivityList.php` | `RecentActivityList` | `Component` | Recent activity log listing |
| `User/Livewire/UserDashboard.php` | `UserDashboard` | `Component` | Generic user dashboard with recent activity |

### Dashboard Components

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Livewire/Dashboards/AdminDashboard.php` | `AdminDashboard` | `Component` | Admin/SA dashboard with system stats and readiness |
| `User/Livewire/Dashboards/StudentDashboard.php` | `StudentDashboard` | `Component` | Student dashboard with registration and journal stats |
| `User/Livewire/Dashboards/TeacherDashboard.php` | `TeacherDashboard` | `Component` | Teacher dashboard with supervised students and journals |
| `User/Livewire/Dashboards/SupervisorDashboard.php` | `SupervisorDashboard` | `Component` | Supervisor dashboard with active interns and evaluations |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `User/Livewire/Forms/ProfileForm.php` | `ProfileForm` | `Form` | name, email, phone, address, bio | `ProfileEditor` |
| `User/Livewire/Forms/PasswordForm.php` | `PasswordForm` | `Form` | current_password, password, password_confirmation | `ProfileEditor` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Models/Notification.php` | `Notification` | `BaseModel` | Eloquent model for in-app notifications |
| `User/Models/Profile.php` | `Profile` | `BaseModel` | Eloquent model for user profiles (department, school, bio) |
| `User/Models/User.php` | `User` | `Model` (Authenticatable) | Eloquent model for users (with relations to mentor, mentee, roles) |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `User/Notifications/TestMailNotification.php` | `TestMailNotification` | `Notification` | Simple test mail notification |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Policies/NotificationPolicy.php` | `NotificationPolicy` | `BasePolicy` | Authorization for notification operations |
| `User/Policies/ProfilePolicy.php` | `ProfilePolicy` | `BasePolicy` | Authorization for profile operations |
| `User/Policies/UserPolicy.php` | `UserPolicy` | `BasePolicy` | Authorization for user management operations |

## Rules

| File | Class | Implements | Description |
|---|---|---|---|
| `User/Rules/ReservedAuthoritativeName.php` | `ReservedAuthoritativeName` | `ValidationRule` | Blocks reserved names (admin, superadmin, etc.) for non-super-admin users |
| `User/Rules/SystemUsername.php` | `SystemUsername` | `ValidationRule` | Validates system-generated usernames |

## Services

| File | Class | Description |
|---|---|---|
| `User/Services/DashboardService.php` | `DashboardService` | Resolves role-appropriate dashboard for a user |

## Support

| File | Class | Description |
|---|---|---|
| `User/Support/UserIdentifierGenerator.php` | `UserIdentifierGenerator` | Generates unique system usernames |

## Where to Find It

- `app/Domain/User/Models/User.php` — central identity model
- `app/Domain/User/Models/Profile.php` — extended personal data
- `app/Domain/User/Actions/UpdateProfileAction.php` — profile editing
- `app/Domain/User/Actions/SendNotificationAction.php` — notification dispatch
- `app/Domain/User/Livewire/` — dashboards, profile editor, notification center
- `app/Domain/User/Support/UserIdentifierGenerator.php` — username generation
- `app/Domain/User/Rules/` — username and reserved name validation

## Dependency Graph

```
User Domain
├── Core   → BaseModel, BaseAction, BaseEntity, SmartLogger, PasswordRules,
│            HandlesActionErrors, BasePolicy, PiiMasker
├── Auth   → Role, AccountStatus, activation tokens
└── School → School, Department (user affiliation)
```

Consumed by: all domains (universal identity and profile provider)

