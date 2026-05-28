# Admin — API Reference
> Last updated: 2026-05-26
> Changes: fix: enforce super admin integrity with SuperAdminIntegrityRules across all code paths


Total: 46 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` | Archives inactive student accounts in chunks |
| `Admin/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` | Creates a new user with system username, hashed password, sends welcome |
| `User/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` | Deletes a single notification — *moved from `Admin`* |
| `Admin/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` | Deletes a user with logging |
| `Admin/Actions/GetAdminDashboardStatsAction.php` | `GetAdminDashboardStatsAction` | `BaseAction` | Aggregates dashboard statistics (users, internships, departments) |
| `User/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` | Marks all notifications as read — *moved from `Admin`* |
| `User/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` | Marks a single notification as read — *moved from `Admin`* |
| `Admin/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` | Reads the recovery key plaintext from the storage file |
| `Admin/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` | Saves the recovery key to storage/app/private/.recovery-key |
| `Admin/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseAction` | Creates an announcement and broadcasts to target role |
| `User/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseAction`, `SendsNotifications` | Sends a notification to a user — *moved from `Admin`* |
| `Admin/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` | Toggles user active/inactive status with notification |
| `Admin/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` | Updates user details with logging |

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
| `User/Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` | Shows paginated activity log feed — *moved from `Admin`* |
| `Admin/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` | CRUD manager for admin users |
| `Admin/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` | Creates and manages announcements |
| `Admin/Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` | Reviews internship account applications |
| `Admin/Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` | Paginated audit log viewer |
| `Admin/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` | Views GDPR deletion logs |
| `Admin/Livewire/MenteeManager.php` | `MenteeManager` | `BaseRecordManager` | CRUD manager for mentee records |
| `Admin/Livewire/MentorManager.php` | `MentorManager` | `BaseRecordManager` | CRUD manager for mentor records |
| `User/Livewire/NotificationBell.php` | `NotificationBell` | `Component` | Dropdown notification bell widget — *moved from `Admin`* |
| `User/Livewire/NotificationCenter.php` | `NotificationCenter` | `BaseRecordManager` | Full notification inbox with read/mark/delete — *moved from `Admin`* |
| `Admin/Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Card` | Pulse dashboard card showing registration stats |
| `Admin/Livewire/Pulse/SystemCard.php` | `SystemCard` | `Card` | Pulse dashboard card showing system health (users, notifications) |
| `Admin/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` | CRUD manager for student users |
| `Admin/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` | CRUD manager for supervisor users |
| `Admin/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` | CRUD manager for teacher users |
| `Admin/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` | CRUD manager for all users with status toggle and password reset |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Models/Announcement.php` | `Announcement` | `BaseModel` | Eloquent model for broadcast announcements |
| `Admin/Models/GdprDeletionLog.php` | `GdprDeletionLog` | `BaseModel` | Eloquent model for GDPR deletion audit trail |
| `User/Models/Notification.php` | `Notification` | `BaseModel` | Eloquent model for system notifications — *moved from `Admin`* |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Admin/Enums/AnnouncementStatus.php` | `AnnouncementStatus` | `LabelEnum` | Announcement lifecycle status (DRAFT, SCHEDULED, PUBLISHED) |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Admin/Notifications/AnnouncementNotification.php` | `AnnouncementNotification` | `Notification`, `ShouldQueue` | Queued mail + database notification for announcements |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Admin/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` | `BasePolicy` | Authorization for GDPR log viewing |
| `User/Policies/NotificationPolicy.php` | `NotificationPolicy` | `BasePolicy` | Authorization for notification access — *moved from `Admin`* |

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
