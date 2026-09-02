# User — Technical Reference

## Description

Detailed structural and implementation reference for the **User** module.

---

## Overview

Handles user identity, profiles, notifications, account status, dashboards, and activity feeds.

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/ReadActivityLogAction.php` | `ReadActivityLogAction` | `BaseReadAction` |
| `Domain/AccountStatus/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseCommandAction` |
| `Domain/AccountStatus/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseCommandAction` |
| `Domain/AccountStatus/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseCommandAction` |
| `Domain/Dashboard/Actions/ReadStudentDashboardAction.php` | `ReadStudentDashboardAction` | `BaseReadAction` |
| `Domain/Dashboard/Actions/ReadSupervisorDashboardAction.php` | `ReadSupervisorDashboardAction` | `BaseReadAction` |
| `Domain/Dashboard/Actions/ReadTeacherDashboardAction.php` | `ReadTeacherDashboardAction` | `BaseReadAction` |
| `Domain/Notifications/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseCommandAction` |
| `Domain/Notifications/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseCommandAction` |
| `Domain/Notifications/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseCommandAction` |
| `Domain/Notifications/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseCommandAction` |
| `Domain/Notifications/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseCommandAction` |
| `Domain/Profile/Actions/ReadProfileFormAction.php` | `ReadProfileFormAction` | `BaseReadAction` |
| `Domain/Profile/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/DispatchArchiveStudentAccountsAction.php` | `DispatchArchiveStudentAccountsAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/GenerateAccountSlipBatchAction.php` | `GenerateAccountSlipBatchAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseReadAction` |
| `Domain/UserManagement/Actions/ReadUserManagerStatsAction.php` | `ReadUserManagerStatsAction` | `BaseReadAction` |
| `Domain/UserManagement/Actions/RenderAccountSlipAction.php` | `RenderAccountSlipAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseCommandAction` |
| `Domain/UserManagement/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseCommandAction` |

## Jobs

| File                                | Class                       | Queue     | Purpose                                           |
| ----------------------------------- | --------------------------- | --------- | ------------------------------------------------- |
| `Jobs/ArchiveStudentAccountsJob.php` | `ArchiveStudentAccountsJob` | `default` | Queued batch archival for large cohorts >500 (E1MSJ NFR-R2, dispatched via `DispatchArchiveStudentAccountsAction`) |

---

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Notifications/Models/Notification.php` | `Notification` | `BaseModel` |
| `Domain/Profile/Models/Profile.php` | `Profile` | `BaseModel` |
| `Models/User.php` | `User` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Enums/AccountStatus.php` | `AccountStatus` | `LabelEnum` | — |
| `Enums/BloodType.php` | `BloodType` | `LabelEnum` | — |
| `Enums/EmploymentStatus.php` | `EmploymentStatus` | `LabelEnum` | — |
| `Enums/Gender.php` | `Gender` | `LabelEnum` | — |
| `Enums/StructuralPosition.php` | `StructuralPosition` | `LabelEnum` | — |

## Entities

| File | Class | Extends |
|---|---|---|
| `Domain/Mentor/Entities/MentorEntity.php` | `MentorEntity` | `BaseEntity` |
| `Entities/AdminEntity.php` | `AdminEntity` | `BaseEntity` |
| `Entities/StudentEntity.php` | `StudentEntity` | `BaseEntity` |
| `Entities/SupervisorEntity.php` | `SupervisorEntity` | `BaseEntity` |
| `Entities/TeacherEntity.php` | `TeacherEntity` | `BaseEntity` |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Notifications/Policies/NotificationPolicy.php` | `NotificationPolicy` | `BasePolicy` |
| `Domain/Profile/Policies/ProfilePolicy.php` | `ProfilePolicy` | `BasePolicy` |
| `Policies/Concerns/HasMentorProxy.php` | `HasMentorProxy` | `BasePolicy` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` | `ClearDashboardCacheOnDepartmentChange` | — |
| `Domain/Dashboard/Listeners/ClearDashboardCacheOnYearChange.php` | `ClearDashboardCacheOnYearChange` | — |
| `Domain/Notifications/Listeners/ClearUnreadNotificationCache.php` | `ClearUnreadNotificationCache` | — |
| `Domain/Profile/Listeners/SendProfileChangedMail.php` | `SendProfileChangedMail` | — |
| `Domain/UserManagement/Listeners/InvalidateUserCache.php` | `InvalidateUserCache` | — |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/AccountStatus/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `BaseRecordManager` |
| `Domain/Dashboard/Livewire/AdminDashboard.php` | `AdminDashboard` | `Component` |
| `Domain/Dashboard/Livewire/StudentDashboard.php` | `StudentDashboard` | `Component` |
| `Domain/Dashboard/Livewire/SupervisorDashboard.php` | `SupervisorDashboard` | `Component` |
| `Domain/Dashboard/Livewire/TeacherDashboard.php` | `TeacherDashboard` | `Component` |
| `Domain/Dashboard/Livewire/UserDashboard.php` | `UserDashboard` | `Component` |
| `Domain/Notifications/Livewire/NotificationBell.php` | `NotificationBell` | `Component` |
| `Domain/Notifications/Livewire/NotificationCenter.php` | `NotificationCenter` | `Component` |
| `Domain/Profile/Livewire/Forms/PasswordForm.php` | `PasswordForm` | `BaseFormView` |
| `Domain/Profile/Livewire/Forms/ProfileForm.php` | `ProfileForm` | `BaseFormView` |
| `Domain/Profile/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` |
| `Domain/UserManagement/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` |
| `Domain/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php` | `DownloadsAccountSlips` | `Component` |
| `Domain/UserManagement/Livewire/Forms/AdminUserForm.php` | `AdminUserForm` | `BaseFormView` |
| `Domain/UserManagement/Livewire/Forms/StudentForm.php` | `StudentForm` | `BaseFormView` |
| `Domain/UserManagement/Livewire/Forms/SupervisorForm.php` | `SupervisorForm` | `BaseFormView` |
| `Domain/UserManagement/Livewire/Forms/TeacherForm.php` | `TeacherForm` | `BaseFormView` |
| `Domain/UserManagement/Livewire/Forms/UserForm.php` | `UserForm` | `BaseFormView` |
| `Domain/UserManagement/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` |
| `Domain/UserManagement/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` |
| `Domain/UserManagement/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` |
| `Domain/UserManagement/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` |
| `Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `BaseRecordManager` |
| `Livewire/HomePage.php` | `HomePage` | `Component` |
| `Livewire/RecentActivityList.php` | `RecentActivityList` | `BaseRecordManager` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/Profile/Data/UpdateProfileData.php` | `UpdateProfileData` | `BaseData` |
| `Domain/UserManagement/Data/CreateUserData.php` | `CreateUserData` | `BaseData` |
| `Domain/UserManagement/Data/SetUserStatusData.php` | `SetUserStatusData` | `BaseData` |
| `Domain/UserManagement/Data/UpdateUserData.php` | `UpdateUserData` | `BaseData` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/AccountStatus/Events/UserAccountLocked.php` | `UserAccountLocked` | `BaseEvent` |
| `Domain/AccountStatus/Events/UserAccountUnlocked.php` | `UserAccountUnlocked` | `BaseEvent` |
| `Domain/Notifications/Events/NotificationRead.php` | `NotificationRead` | `BaseEvent` |
| `Domain/Notifications/Events/NotificationSent.php` | `NotificationSent` | `BaseEvent` |
| `Domain/Profile/Events/ProfileUpdated.php` | `ProfileUpdated` | `BaseEvent` |
| `Domain/UserManagement/Events/UserCreated.php` | `UserCreated` | `BaseEvent` |
| `Domain/UserManagement/Events/UserDeleted.php` | `UserDeleted` | `BaseEvent` |
| `Domain/UserManagement/Events/UserStatusChanged.php` | `UserStatusChanged` | `BaseEvent` |
| `Domain/UserManagement/Events/UserUpdated.php` | `UserUpdated` | `BaseEvent` |

## Notifications (Mail)

| File                                                          | Notification                 |
| ------------------------------------------------------------- | ---------------------------- |
| `Notifications/GeneralNotification.php`                       | `GeneralNotification`        |
| `Notifications/WelcomeNotification.php`                       | `WelcomeNotification`        |
| `Notifications/TestMailNotification.php`                      | `TestMailNotification`       |
| `AccountStatus/Notifications/AccountStatusNotification.php`   | `AccountStatusNotification`  |
| `UserManagement/Notifications/ActivationCodeNotification.php` | `ActivationCodeNotification` |

## Commands

| File                                                         | Command                  | Signature                  |
| ------------------------------------------------------------ | ------------------------ | -------------------------- |
| `UserManagement/Console/Commands/AutoInactivateAccounts.php` | `AutoInactivateAccounts` | `accounts:auto-inactivate` |

## Support

| File                                   | Class                     | Purpose                             |
| -------------------------------------- | ------------------------- | ----------------------------------- |
| `Services/UserIdentifierGenerator.php` | `UserIdentifierGenerator` | Generates usernames and identifiers |
| `Services/DashboardService.php`        | `DashboardService`        | Dashboard data aggregation          |

## Rules

| File | Rule | Purpose |
|---|---|---|
| `Rules/ReservedAuthoritativeName.php` | `ReservedAuthoritativeName` | — |
| `Rules/SystemUsername.php` | `SystemUsername` | — |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Http/Controllers/AuthController.php` | `AuthController` | `BaseController` |
| `Http/Controllers/DashboardController.php` | `DashboardController` | `BaseController` |

## Observers

| File                         | Observer       | Observes |
| ---------------------------- | -------------- | -------- |
| `Observers/UserObserver.php` | `UserObserver` | `User`   |

---

## Routes

File: `routes/web/user.php` Named routes: `home`, `dashboard`, `user.dashboard`, `profile`,
`profile.recovery`, `notifications`, `logout`, `password.request`, `password.reset`,
`recover.account`, `password.confirm`; role dashboards at `sysadmin.dashboard`, `student.dashboard`,
`teacher.dashboard`, `supervisor.dashboard`

## Views

Views are located in `resources/views/user/`. See [UI/UX](../../guides/ui-ux/design-system.md) for the design
system.

## Tests

Tests are located in `tests/User/`. See [Testing](../../guides/infra/testing.md) for the testing
conventions. Tests are spec-driven: each test traces to a spec requirement ID (`FR-*` / `NFR-*` /
`UC-*`) using the `test("{SpecID}-{ReqID}: Test description...")` convention (grouped under
`describe("{SpecID}: Test description...")`); there is no one-test-per-class mandate.

## Factories

| Factory               | Model          |
| --------------------- | -------------- |
| `UserFactory`         | `User`         |
| `ProfileFactory`      | `Profile`      |
| `NotificationFactory` | `Notification` |

## Migrations

| Migration                    | Table           |
| ---------------------------- | --------------- |
| `create_users_table`         | `users`         |
| `create_profiles_table`      | `profiles`      |
| `create_notifications_table` | `notifications` |

---

## Architectural Integration

- **Submodules**: `Profile`, `Notifications`, `Dashboard`, `AccountStatus`, `UserManagement`
- **Business Logic**: `app/Modules/User/`
- **Routing**: `routes/web/user.php`
- **Views**: `resources/views/user/`
- **Testing**: `tests/User/`
- **Dependencies**: Core, SysAdmin

_For overview and business context, see [user.md](user.md)._
