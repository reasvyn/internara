# User — Technical Reference

> **Last updated:** 2026-07-31 **Changes:** sync — fix Action base classes, AccountStatus/EmploymentStatus, add events + listeners, Livewire extends, route names, flat test paths

## Description

Detailed structural and implementation reference for the **User** module.

---

## Overview

Handles user identity, profiles, notifications, account status, dashboards, and activity feeds.

## Actions

| File                                                          | Class                              | Extends             |
| ------------------------------------------------------------- | ---------------------------------- | ------------------- |
| `Actions/ReadActivityLogAction.php`                           | `ReadActivityLogAction`            | `BaseReadAction`    |
| `Profile/Actions/ReadProfileFormAction.php`                   | `ReadProfileFormAction`            | `BaseReadAction`    |
| `Profile/Actions/UpdateProfileAction.php`                     | `UpdateProfileAction`              | `BaseCommandAction` |
| `Notifications/Actions/DeleteNotificationAction.php`          | `DeleteNotificationAction`         | `BaseCommandAction` |
| `Notifications/Actions/MarkAllAsReadAction.php`               | `MarkAllAsReadAction`              | `BaseCommandAction` |
| `Notifications/Actions/MarkAsReadAction.php`                  | `MarkAsReadAction`                 | `BaseCommandAction` |
| `Notifications/Actions/MarkBatchAsReadAction.php`             | `MarkBatchAsReadAction`            | `BaseCommandAction`  |
| `Notifications/Actions/SendNotificationAction.php`            | `SendNotificationAction`           | `BaseCommandAction` |
| `Dashboard/Actions/ReadStudentDashboardAction.php`            | `ReadStudentDashboardAction`       | `BaseReadAction`    |
| `Dashboard/Actions/ReadSupervisorDashboardAction.php`         | `ReadSupervisorDashboardAction`    | `BaseReadAction`    |
| `Dashboard/Actions/ReadTeacherDashboardAction.php`            | `ReadTeacherDashboardAction`       | `BaseReadAction`    |
| `AccountStatus/Actions/DetectUserAccountCloneAction.php`      | `DetectUserAccountCloneAction`     | `BaseReadAction`    |
| `AccountStatus/Actions/LockUserAccountAction.php`             | `LockUserAccountAction`            | `BaseCommandAction` |
| `AccountStatus/Actions/UnlockUserAccountAction.php`           | `UnlockUserAccountAction`          | `BaseCommandAction` |
| `UserManagement/Actions/ArchiveStudentAccountsAction.php`     | `ArchiveStudentAccountsAction`     | `BaseCommandAction` |
| `UserManagement/Actions/BatchDeleteUserAction.php`            | `BatchDeleteUserAction`            | `BaseCommandAction` |
| `UserManagement/Actions/CreateUserAction.php`                 | `CreateUserAction`                 | `BaseCommandAction` |
| `UserManagement/Actions/DeleteUserAction.php`                 | `DeleteUserAction`                 | `BaseCommandAction` |
| `UserManagement/Actions/GenerateAccountSlipAction.php`        | `GenerateAccountSlipAction`        | `BaseCommandAction` |
| `UserManagement/Actions/ReadRecoveryKeyAction.php`            | `ReadRecoveryKeyAction`            | `BaseReadAction`    |
| `UserManagement/Actions/ReadUserManagerStatsAction.php`       | `ReadUserManagerStatsAction`       | `BaseReadAction`    |
| `UserManagement/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseCommandAction` |
| `UserManagement/Actions/SaveRecoveryKeyAction.php`            | `SaveRecoveryKeyAction`            | `BaseCommandAction` |
| `UserManagement/Actions/SetUserStatusAction.php`              | `SetUserStatusAction`              | `BaseCommandAction` |
| `UserManagement/Actions/ToggleUserStatusAction.php`           | `ToggleUserStatusAction`           | `BaseCommandAction` |
| `UserManagement/Actions/UpdateUserAction.php`                 | `UpdateUserAction`                 | `BaseCommandAction` |

---

## Models

| File                                    | Class          | Extends                                  |
| --------------------------------------- | -------------- | ---------------------------------------- |
| `Models/User.php`                       | `User`         | `Authenticatable` (with manual HasUuids) |
| `Profile/Models/Profile.php`            | `Profile`      | `BaseModel`                              |
| `Notifications/Models/Notification.php` | `Notification` | `BaseModel`                              |

---

## Enums

| File                           | Enum                 | Implements                | Values                                                                                 |
| ------------------------------ | -------------------- | ------------------------- | -------------------------------------------------------------------------------------- |
| `Enums/AccountStatus.php`      | `AccountStatus`      | `ColorableEnum`, `StatusEnum` | provisioned, activated, verified, protected, restricted, suspended, inactive, archived |
| `Enums/BloodType.php`          | `BloodType`          | `LabelEnum`               | A, B, AB, O                                                                            |
| `Enums/EmploymentStatus.php`   | `EmploymentStatus`   | `LabelEnum`               | full_time, part_time, contract, temporary, volunteer                                   |
| `Enums/Gender.php`             | `Gender`             | `LabelEnum`               | male, female                                                                           |
| `Enums/StructuralPosition.php` | `StructuralPosition` | `LabelEnum`               | principal, vice_principal, head_department, teacher, staff                             |

---

## Entities

| File                            | Class              | Extends      |
| ------------------------------- | ------------------ | ------------ |
| `Entities/Apprentice.php`       | `Apprentice`       | `BaseEntity` |
| `Entities/AdminEntity.php`      | `AdminEntity`      | `BaseEntity` |
| `Entities/StudentEntity.php`    | `StudentEntity`    | `BaseEntity` |
| `Entities/SupervisorEntity.php` | `SupervisorEntity` | `BaseEntity` |
| `Entities/TeacherEntity.php`    | `TeacherEntity`    | `BaseEntity` |
| `Mentor/Entities/MentorEntity.php` | `MentorEntity` | `BaseEntity` |

---

## Policies

| File                                            | Policy               | Extends      |
| ----------------------------------------------- | -------------------- | ------------ |
| `Profile/Policies/ProfilePolicy.php`            | `ProfilePolicy`      | `BasePolicy` |
| `Notifications/Policies/NotificationPolicy.php` | `NotificationPolicy` | `BasePolicy` |

### Shared Concerns

| File                                   | Trait            | Purpose                                                                                                               |
| -------------------------------------- | ---------------- | --------------------------------------------------------------------------------------------------------------------- |
| `Policies/Concerns/HasMentorProxy.php` | `HasMentorProxy` | Mentor-scoped authorization via `mentorProxyFor(?Registration, User): ?MentorEntity`; used by 5 cross-module policies |

---

## Listeners

| File                                                            | Class                                   | Listens To                                     |
| --------------------------------------------------------------- | --------------------------------------- | ---------------------------------------------- |
| `Dashboard/Listeners/ClearDashboardCacheOnYearChange.php`       | `ClearDashboardCacheOnYearChange`       | `AcademicYearCreated`, `AcademicYearActivated`, `AcademicYearUpdated`, `AcademicYearDeleted` |
| `Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` | `ClearDashboardCacheOnDepartmentChange` | `DepartmentCreated`, `DepartmentDeleted`, `DepartmentUpdated`       |
| `Notifications/Listeners/ClearUnreadNotificationCache.php`      | `ClearUnreadNotificationCache`          | `NotificationSent`, `NotificationRead`, `ProfileUpdated`         |
| `Profile/Listeners/SendProfileChangedMail.php`                  | `SendProfileChangedMail`                | `ProfileUpdated`                                             |
| `UserManagement/Listeners/InvalidateUserCache.php`              | `InvalidateUserCache`                   | `object` (user model events)                                       |

## Livewire Components

| File                                                         | Component                       | Extends             |
| ------------------------------------------------------------ | ------------------------------- | ------------------- |
| `Profile/Livewire/ProfileEditor.php`                         | `ProfileEditor`                 | `BaseFormView`     |
| `Notifications/Livewire/NotificationBell.php`                | `NotificationBell`              | `Component`         |
| `Notifications/Livewire/NotificationCenter.php`              | `NotificationCenter`            | `BaseRecordManager` |
| `Livewire/HomePage.php`                                      | `HomePage`                      | `Component`         |
| `Livewire/ActivityFeedManager.php`                           | `ActivityFeedManager`           | `Component`         |
| `Livewire/RecentActivityList.php`                            | `RecentActivityList`            | `Component`         |
| `Dashboard/Livewire/UserDashboard.php`                       | `UserDashboard`                 | `Component`         |
| `Dashboard/Livewire/AdminDashboard.php`                      | `AdminDashboard`                | `UserDashboard`     |
| `Dashboard/Livewire/StudentDashboard.php`                    | `StudentDashboard`              | `UserDashboard`     |
| `Dashboard/Livewire/TeacherDashboard.php`                    | `TeacherDashboard`              | `UserDashboard`     |
| `Dashboard/Livewire/SupervisorDashboard.php`                 | `SupervisorDashboard`           | `UserDashboard`     |
| `AccountStatus/Livewire/AccountLifecycleManager.php`         | `AccountLifecycleManager`       | `Component`         |
| `UserManagement/Livewire/AdminManager.php`                   | `AdminManager`                  | `BaseRecordManager` |
| `UserManagement/Livewire/StudentManager.php`                 | `StudentManager`                | `BaseRecordManager` |
| `UserManagement/Livewire/SupervisorManager.php`              | `SupervisorManager`             | `BaseRecordManager` |
| `UserManagement/Livewire/TeacherManager.php`                 | `TeacherManager`                | `BaseRecordManager` |
| `UserManagement/Livewire/UserManager.php`                    | `UserManager`                   | `BaseRecordManager` |
| `UserManagement/Livewire/Concerns/DownloadsAccountSlips.php` | `DownloadsAccountSlips` (trait) | —                   |

## Livewire Forms

| File                                               | Form             |
| -------------------------------------------------- | ---------------- |
| `Profile/Livewire/Forms/ProfileForm.php`           | `ProfileForm`    |
| `Profile/Livewire/Forms/PasswordForm.php`          | `PasswordForm`   |
| `UserManagement/Livewire/Forms/AdminUserForm.php`  | `AdminUserForm`  |
| `UserManagement/Livewire/Forms/StudentForm.php`    | `StudentForm`    |
| `UserManagement/Livewire/Forms/SupervisorForm.php` | `SupervisorForm` |
| `UserManagement/Livewire/Forms/TeacherForm.php`    | `TeacherForm`    |
| `UserManagement/Livewire/Forms/UserForm.php`       | `UserForm`       |

## Data / DTOs

| File                                      | Class              | Extends    |
| ----------------------------------------- | ------------------ | ---------- |
| `Notifications/Data/NotificationData.php` | `NotificationData` | `BaseData` |

## Events

| File                                        | Class              | Dispatched By            | Consumed By                    |
| ------------------------------------------- | ------------------ | ------------------------ | ------------------------------ |
| `Notifications/Events/NotificationSent.php` | `NotificationSent` | `SendNotificationAction` | `ClearUnreadNotificationCache` |
| `Notifications/Events/NotificationRead.php` | `NotificationRead` | `MarkAsReadAction`       | `ClearUnreadNotificationCache` |
| `Profile/Events/ProfileUpdated.php`         | `ProfileUpdated`   | `UpdateProfileAction`    | `ClearUnreadNotificationCache`, `SendProfileChangedMail` |
| `AccountStatus/Events/UserAccountLocked.php` | `UserAccountLocked` | `LockUserAccountAction` | — |
| `AccountStatus/Events/UserAccountUnlocked.php` | `UserAccountUnlocked` | `UnlockUserAccountAction` | — |
| `UserManagement/Events/UserCreated.php`     | `UserCreated`       | `CreateUserAction`       | — |
| `UserManagement/Events/UserUpdated.php`     | `UserUpdated`       | `UpdateUserAction`       | — |
| `UserManagement/Events/UserDeleted.php`     | `UserDeleted`       | `DeleteUserAction`       | — |
| `UserManagement/Events/UserStatusChanged.php` | `UserStatusChanged` | `SetUserStatusAction`, `ToggleUserStatusAction` | — |

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

| File                                  | Class                     | Purpose                             |
| ------------------------------------- | ------------------------- | ----------------------------------- |
| `Services/UserIdentifierGenerator.php` | `UserIdentifierGenerator` | Generates usernames and identifiers |
| `Services/DashboardService.php`       | `DashboardService`        | Dashboard data aggregation          |

## Rules

| File                                  | Rule                        | Purpose                          |
| ------------------------------------- | --------------------------- | -------------------------------- |
| `Rules/ReservedAuthoritativeName.php` | `ReservedAuthoritativeName` | Validates reserved names         |
| `Rules/SystemUsername.php`            | `SystemUsername`            | Validates system username format |

## HTTP Controllers

| File                                       | Controller            | Extends          |
| ------------------------------------------ | --------------------- | ---------------- |
| `Http/Controllers/AuthController.php`      | `AuthController`      | `BaseController` |
| `Http/Controllers/DashboardController.php` | `DashboardController` | `BaseController` |

## Observers

| File                         | Observer       | Observes |
| ---------------------------- | -------------- | -------- |
| `Observers/UserObserver.php` | `UserObserver` | `User`   |

---

## Routes

File: `routes/web/user.php` Named routes: `home`, `dashboard`, `user.dashboard`, `profile`,
`profile.recovery`, `notifications`, `logout`, `password.request`, `password.reset`,
`recover.account`, `password.confirm`; role dashboards at `sysadmin.dashboard`,
`student.dashboard`, `teacher.dashboard`, `supervisor.dashboard`

## Views

Views are located in `resources/views/user/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/User/`. See [Testing](../infrastructure/testing.md) for
the testing conventions.

| File                                                                   | What It Tests                                                            |
| ---------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `User/Enums/AccountStatusTest.php`                                | AccountStatus: allowsLogin, isTerminal, transitions, color               |
| `User/Enums/UserEnumsTest.php`                                    | BloodType, Gender, EmploymentStatus, StructuralPosition cases and labels |
| `User/Entities/ApprenticeTest.php`                                | Apprentice: status, locked, setup required, transitions                  |
| `User/Rules/ReservedAuthoritativeNameTest.php`                    | Reserved name validation                                                 |
| `User/Rules/SystemUsernameTest.php`                               | Username format validation                                               |
| `User/Services/DashboardServiceTest.php`                          | Dashboard routing by role                                                |
| `User/Services/UserIdentifierGeneratorTest.php`                   | Username generation from email, collision handling                       |
| `User/Notifications/Data/NotificationDataTest.php`                | NotificationData DTO                                                     |
| `User/Profile/ProfileModelTest.php`                            | Profile model: relationships, fields, cascade delete                     |
| `User/Profile/UpdateProfileActionTest.php`                     | UpdateProfileAction                                                      |
| `User/Dashboard/*Test.php`                                     | Dashboard data actions                                                   |
| `User/AccountStatus/*Test.php`                                 | Lock/Unlock account actions                                              |
| `User/Notifications/*Test.php`                                 | Send, mark read, notification events                                     |
| `User/Models/UserTest.php`                                     | User model: roles, UUID, scopes                                          |
| `User/UserManagement/CreateUserActionTest.php`                 | CreateUserAction                                                         |
| `User/UserManagement/UpdateUserActionTest.php`                 | UpdateUserAction                                                         |
| `User/UserManagement/DeleteUserActionTest.php`                 | DeleteUserAction                                                         |
| `User/UserManagement/BatchDeleteUserActionTest.php`            | BatchDeleteUserAction                                                    |
| `User/UserManagement/ReadUserManagerStatsActionTest.php`       | ReadUserManagerStatsAction                                               |
| `User/UserManagement/ArchiveStudentAccountsActionTest.php`     | ArchiveStudentAccountsAction                                             |
| `User/UserManagement/ToggleUserStatusActionTest.php`           | ToggleUserStatusAction                                                   |
| `User/UserManagement/SetUserStatusActionTest.php`              | SetUserStatusAction                                                      |
| `User/UserManagement/RevokeUserActivationTokensActionTest.php` | RevokeUserActivationTokensAction                                         |
| `User/UserManagement/GenerateAccountSlipActionTest.php`        | GenerateAccountSlipAction                                                |
| `User/UserManagement/Actions/SaveRecoveryKeyActionTest.php`    | SaveRecoveryKeyAction                                                    |
| `User/UserManagement/Actions/ReadRecoveryKeyActionTest.php`    | ReadRecoveryKeyAction                                                    |
| `User/UserManagement/AutoInactivateAccountsCommandTest.php`    | AutoInactivateAccounts command                                           |

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
- **Business Logic**: `app/User/`
- **Routing**: `routes/web/user.php`
- **Views**: `resources/views/user/`
- **Testing**: `tests/User/`
- **Dependencies**: Core, SysAdmin

_For overview and business context, see [user.md](user.md)._
