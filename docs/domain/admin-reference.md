# Admin — API Reference
> Last updated: 2026-05-26
> Changes: fix: enforce super admin integrity with SuperAdminIntegrityRules across all code paths


Total: 55 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` | Archives inactive student accounts in chunks |
| `Admin/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseAction` | Batch deletes selected users with result summary |
| `Admin/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` | Creates a new user with system username, hashed password, sends welcome |
| `Admin/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` | Deletes a user with logging |
| `Admin/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseAction` | Generates account recovery slip for a user |
| `Admin/Actions/GetAdminDashboardStatsAction.php` | `GetAdminDashboardStatsAction` | `BaseAction` | Aggregates dashboard statistics (users, internships, departments) |
| `Admin/Actions/GetUserManagerStatsAction.php` | `GetUserManagerStatsAction` | `BaseAction` | Aggregates user manager statistics |
| `Admin/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` | Reads the recovery key plaintext from the storage file |
| `Admin/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction` | Revokes all activation tokens for a user |
| `Admin/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` | Saves the recovery key to storage/app/private/.recovery-key |
| `Admin/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseAction` | Creates an announcement and broadcasts to target role |
| `Admin/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseAction` | Sets a user's account status with reason and notification |
| `Admin/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` | Toggles user active/inactive status with notification |
| `Admin/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` | Updates user details and profile with logging |

## Console Commands

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Console/Commands/AdminPromoteCommand.php` | `AdminPromoteCommand` | `Command` | Promotes a user to admin role |
| `Admin/Console/Commands/AutoInactivateAccounts.php` | `AutoInactivateAccounts` | `Command` | Auto-inactivates stale user accounts |
| `Admin/Console/Commands/CreateAdminCommand.php` | `CreateAdminCommand` | `Command` | Interactive CLI to create a new admin user |
| `Admin/Console/Commands/PulseRecordSnapshotsCommand.php` | `PulseRecordSnapshotsCommand` | `Command` | Captures Pulse snapshot data |
| `Admin/Console/Commands/RecoverAdminCommand.php` | `RecoverAdminCommand` | `Command` | Interactive CLI to recover super admin access |
| `Admin/Console/Commands/ShowRecoveryPathCommand.php` | `ShowRecoveryPathCommand` | `Command` | Displays the recovery key file path |
| `Admin/Console/Commands/PruneNotificationsCommand.php` | `PruneNotificationsCommand` | `Command` | Prunes old read notifications |
| `Admin/Console/Commands/PublishScheduledAnnouncementsCommand.php` | `PublishScheduledAnnouncementsCommand` | `Command` | Publishes scheduled announcements |
| `Admin/Console/Commands/ShowRecoveryKeyCommand.php` | `ShowRecoveryKeyCommand` | `Command` | Displays the stored recovery key (with confirmation) |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` | Displays and detects cloned user accounts |
| `Admin/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` | CRUD manager for admin users |
| `Admin/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` | Creates and manages announcements |
| `Admin/Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` | Reviews internship account applications |
| `Admin/Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` | Paginated audit log viewer |
| `Admin/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` | Views GDPR deletion logs |
| `Admin/Livewire/MenteeManager.php` | `MenteeManager` | `BaseRecordManager` | CRUD manager for mentee records |
| `Admin/Livewire/MentorManager.php` | `MentorManager` | `BaseRecordManager` | CRUD manager for mentor records |
| `Admin/Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Card` | Pulse dashboard card showing registration stats |
| `Admin/Livewire/Pulse/SystemCard.php` | `SystemCard` | `Card` | Pulse dashboard card showing system health |
| `Admin/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` | CRUD manager for student users |
| `Admin/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` | CRUD manager for supervisor users |
| `Admin/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` | CRUD manager for teacher users |
| `Admin/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` | CRUD manager for all users with search, filters, sort, bulk lock/unlock/delete, CSV import/export, template download, pagination control |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Models/Announcement.php` | `Announcement` | `BaseModel` | Eloquent model for broadcast announcements |
| `Admin/Models/GdprDeletionLog.php` | `GdprDeletionLog` | `BaseModel` | Eloquent model for GDPR deletion audit trail |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Admin/Enums/AnnouncementStatus.php` | `AnnouncementStatus` | `LabelEnum` | Announcement lifecycle status (DRAFT, SCHEDULED, PUBLISHED) |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Admin/Notifications/ActivationCodeNotification.php` | `ActivationCodeNotification` | `Notification` | Email notification with activation code |
| `Admin/Notifications/AnnouncementNotification.php` | `AnnouncementNotification` | `Notification`, `ShouldQueue` | Queued mail + database notification for announcements |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` | `BasePolicy` | Authorization for GDPR log viewing |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Admin/Livewire/Forms/AdminUserForm.php` | `AdminUserForm` | `Form` | name, email, username, password, role | `AdminManager` |
| `Admin/Livewire/Forms/AnnouncementForm.php` | `AnnouncementForm` | `Form` | title, message, type, target_roles, scheduled_at | `AnnouncementManager` |
| `Admin/Livewire/Forms/MenteeForm.php` | `MenteeForm` | `Form` | internal_notes | `MenteeManager` |
| `Admin/Livewire/Forms/MentorForm.php` | `MentorForm` | `Form` | type, is_active | `MentorManager` |
| `Admin/Livewire/Forms/StudentForm.php` | `StudentForm` | `Form` | name, email, username, password | `StudentManager` |
| `Admin/Livewire/Forms/SupervisorForm.php` | `SupervisorForm` | `Form` | name, email, username, password | `SupervisorManager` |
| `Admin/Livewire/Forms/TeacherForm.php` | `TeacherForm` | `Form` | name, email, username, password | `TeacherManager` |
| `Admin/Livewire/Forms/UserForm.php` | `UserForm` | `Form` | name, email | `UserManager` |

## Recorders (Pulse)

| File | Class | Description |
|---|---|---|
| `Admin/Recorders/RegistrationRecorder.php` | `RegistrationRecorder` | Records registration Pulse metrics |
| `Admin/Recorders/SystemRecorder.php` | `SystemRecorder` | Records system Pulse metrics |

## Services

| File | Class | Description |
|---|---|---|
| `Admin/Services/PulseGuard.php` | `PulseGuard` | Authorizes Pulse dashboard access by role |

## Where to Find It

- `app/Domain/Admin/Models/`
- `app/Domain/Admin/Actions/` — 14 Actions
- `app/Domain/Admin/Console/Commands/` — CLI admin tools
- `app/Domain/Admin/Livewire/` — user managers, announcement manager

## Dependency Graph

```
Admin Domain
├── Core         → BaseAction, BaseRecordManager, SmartLogger, CacheKeys,
│                   HandlesActionErrors, BasePolicy, BaseEntity
├── Auth         → Role, AccountStatus, CheckRoleMiddleware
├── User         → User model, Profile, UserPolicy
├── School       → School, Department, AcademicYear models
├── Settings     → System settings, branding config
├── Shared       → Shared utilities, file handling
├── Partnership  → Company records for admin oversight
├── Placement    → Placement records for admin oversight
├── Registration → Registration records for admin oversight
├── Mentee       → Mentee records for admin management
├── Mentor       → Mentor records for admin management
└── Internship   → Internship records for admin oversight
```

Consumed by: all domains (system administration)

