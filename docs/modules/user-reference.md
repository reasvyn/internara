# User — Technical Reference

> **Last updated:** 2026-07-05 **Changes:** sync — fix base class extends: BaseAction →
> BaseCommandAction/BaseReadAction

## Description

Detailed structural and implementation reference for the **User** module.

---

## Overview

Handles user identity, profiles, notifications, account status, dashboards, and activity feeds.

## Actions

| File                                                          | Class                              | Extends             |
| ------------------------------------------------------------- | ---------------------------------- | ------------------- |
| `Actions/ReadActivityLogAction.php`                           | `ReadActivityLogAction`            | Read                |
| `Profile/Actions/ReadProfileFormAction.php`                   | `ReadProfileFormAction`            | Read                |
| `Profile/Actions/UpdateProfileAction.php`                     | `UpdateProfileAction`              | `BaseCommandAction` |
| `Notifications/Actions/DeleteNotificationAction.php`          | `DeleteNotificationAction`         | `BaseCommandAction` |
| `Notifications/Actions/MarkAllAsReadAction.php`               | `MarkAllAsReadAction`              | `BaseCommandAction` |
| `Notifications/Actions/MarkAsReadAction.php`                  | `MarkAsReadAction`                 | `BaseCommandAction` |
| `Notifications/Actions/MarkBatchAsReadAction.php`             | `MarkBatchAsReadAction`            | `BaseAction`        |
| `Notifications/Actions/SendNotificationAction.php`            | `SendNotificationAction`           | `BaseCommandAction` |
| `Dashboard/Actions/ReadStudentDashboardAction.php`            | `ReadStudentDashboardAction`       | Read                |
| `Dashboard/Actions/ReadSupervisorDashboardAction.php`         | `ReadSupervisorDashboardAction`    | Read                |
| `Dashboard/Actions/ReadTeacherDashboardAction.php`            | `ReadTeacherDashboardAction`       | Read                |
| `AccountStatus/Actions/DetectUserAccountCloneAction.php`      | `DetectUserAccountCloneAction`     | Read                |
| `AccountStatus/Actions/LockUserAccountAction.php`             | `LockUserAccountAction`            | `BaseCommandAction` |
| `AccountStatus/Actions/UnlockUserAccountAction.php`           | `UnlockUserAccountAction`          | `BaseCommandAction` |
| `UserManagement/Actions/ArchiveStudentAccountsAction.php`     | `ArchiveStudentAccountsAction`     | `BaseAction`        |
| `UserManagement/Actions/BatchDeleteUserAction.php`            | `BatchDeleteUserAction`            | `BaseAction`        |
| `UserManagement/Actions/CreateUserAction.php`                 | `CreateUserAction`                 | `BaseAction`        |
| `UserManagement/Actions/DeleteUserAction.php`                 | `DeleteUserAction`                 | `BaseAction`        |
| `UserManagement/Actions/GenerateAccountSlipAction.php`        | `GenerateAccountSlipAction`        | `BaseAction`        |
| `UserManagement/Actions/ReadRecoveryKeyAction.php`            | `ReadRecoveryKeyAction`            | Read                |
| `UserManagement/Actions/ReadUserManagerStatsAction.php`       | `ReadUserManagerStatsAction`       | Read                |
| `UserManagement/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction`        |
| `UserManagement/Actions/SaveRecoveryKeyAction.php`            | `SaveRecoveryKeyAction`            | `BaseAction`        |
| `UserManagement/Actions/SetUserStatusAction.php`              | `SetUserStatusAction`              | `BaseAction`        |
| `UserManagement/Actions/ToggleUserStatusAction.php`           | `ToggleUserStatusAction`           | `BaseAction`        |
| `UserManagement/Actions/UpdateUserAction.php`                 | `UpdateUserAction`                 | `BaseAction`        |

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
| `Enums/AccountStatus.php`      | `AccountStatus`      | `LabelEnum`, `StatusEnum` | provisioned, activated, verified, protected, restricted, suspended, inactive, archived |
| `Enums/BloodType.php`          | `BloodType`          | `LabelEnum`               | A, B, AB, O                                                                            |
| `Enums/EmploymentStatus.php`   | `EmploymentStatus`   | `LabelEnum`               | active, resigned, retired                                                              |
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
| `Entities/MentorEntity.php`     | `MentorEntity`     | `BaseEntity` |

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
| `Dashboard/Listeners/ClearDashboardCacheOnYearChange.php`       | `ClearDashboardCacheOnYearChange`       | `AcademicYearCreated`, `AcademicYearActivated` |
| `Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` | `ClearDashboardCacheOnDepartmentChange` | `DepartmentCreated`, `DepartmentDeleted`       |
| `Notifications/Listeners/ClearUnreadNotificationCache.php`      | `ClearUnreadNotificationCache`          | `NotificationSent`, `NotificationRead`         |

## Livewire Components

| File                                                         | Component                       | Extends             |
| ------------------------------------------------------------ | ------------------------------- | ------------------- |
| `Profile/Livewire/ProfileEditor.php`                         | `ProfileEditor`                 | `Component`         |
| `Notifications/Livewire/NotificationBell.php`                | `NotificationBell`              | `Component`         |
| `Notifications/Livewire/NotificationCenter.php`              | `NotificationCenter`            | `Component`         |
| `Livewire/HomePage.php`                                      | `HomePage`                      | `Component`         |
| `Livewire/ActivityFeedManager.php`                           | `ActivityFeedManager`           | `Component`         |
| `Livewire/RecentActivityList.php`                            | `RecentActivityList`            | `Component`         |
| `Dashboard/Livewire/UserDashboard.php`                       | `UserDashboard`                 | `Component`         |
| `Dashboard/Livewire/AdminDashboard.php`                      | `AdminDashboard`                | `Component`         |
| `Dashboard/Livewire/StudentDashboard.php`                    | `StudentDashboard`              | `Component`         |
| `Dashboard/Livewire/TeacherDashboard.php`                    | `TeacherDashboard`              | `Component`         |
| `Dashboard/Livewire/SupervisorDashboard.php`                 | `SupervisorDashboard`           | `Component`         |
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
| `Profile/Events/ProfileUpdated.php`         | `ProfileUpdated`   | `UpdateProfileAction`    | —                              |

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
| `Support/UserIdentifierGenerator.php` | `UserIdentifierGenerator` | Generates usernames and identifiers |
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

File: `routes/web/user.php` Naming pattern: `user.{resource}.{action}`

## Views

Views are located in `resources/views/user/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/{Feature,Unit}/User/`. See [Testing](../infrastructure/testing.md) for
the testing conventions.

| File                                                                   | What It Tests                                                            |
| ---------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| `Unit/User/Enums/AccountStatusTest.php`                                | AccountStatus: allowsLogin, isTerminal, transitions, color               |
| `Unit/User/Enums/UserEnumsTest.php`                                    | BloodType, Gender, EmploymentStatus, StructuralPosition cases and labels |
| `Unit/User/Entities/ApprenticeTest.php`                                | Apprentice: status, locked, setup required, transitions                  |
| `Unit/User/Rules/ReservedAuthoritativeNameTest.php`                    | Reserved name validation                                                 |
| `Unit/User/Rules/SystemUsernameTest.php`                               | Username format validation                                               |
| `Unit/User/Services/DashboardServiceTest.php`                          | Dashboard routing by role                                                |
| `Unit/User/Support/UserIdentifierGeneratorTest.php`                    | Username generation from email, collision handling                       |
| `Unit/User/Notifications/Data/NotificationDataTest.php`                | NotificationData DTO                                                     |
| `Feature/User/Profile/ProfileModelTest.php`                            | Profile model: relationships, fields, cascade delete                     |
| `Feature/User/Profile/UpdateProfileActionTest.php`                     | UpdateProfileAction                                                      |
| `Feature/User/Dashboard/*Test.php`                                     | Dashboard data actions                                                   |
| `Feature/User/AccountStatus/*Test.php`                                 | Lock/Unlock account actions                                              |
| `Feature/User/Notifications/*Test.php`                                 | Send, mark read, notification events                                     |
| `Feature/User/Models/UserTest.php`                                     | User model: roles, UUID, scopes                                          |
| `Feature/User/UserManagement/CreateUserActionTest.php`                 | CreateUserAction                                                         |
| `Feature/User/UserManagement/UpdateUserActionTest.php`                 | UpdateUserAction                                                         |
| `Feature/User/UserManagement/DeleteUserActionTest.php`                 | DeleteUserAction                                                         |
| `Feature/User/UserManagement/BatchDeleteUserActionTest.php`            | BatchDeleteUserAction                                                    |
| `Feature/User/UserManagement/ReadUserManagerStatsActionTest.php`       | ReadUserManagerStatsAction                                               |
| `Feature/User/UserManagement/ArchiveStudentAccountsActionTest.php`     | ArchiveStudentAccountsAction                                             |
| `Feature/User/UserManagement/ToggleUserStatusActionTest.php`           | ToggleUserStatusAction                                                   |
| `Feature/User/UserManagement/SetUserStatusActionTest.php`              | SetUserStatusAction                                                      |
| `Feature/User/UserManagement/RevokeUserActivationTokensActionTest.php` | RevokeUserActivationTokensAction                                         |
| `Feature/User/UserManagement/GenerateAccountSlipActionTest.php`        | GenerateAccountSlipAction                                                |
| `Feature/User/UserManagement/Actions/SaveRecoveryKeyActionTest.php`    | SaveRecoveryKeyAction                                                    |
| `Feature/User/UserManagement/Actions/ReadRecoveryKeyActionTest.php`    | ReadRecoveryKeyAction                                                    |
| `Feature/User/UserManagement/AutoInactivateAccountsCommandTest.php`    | AutoInactivateAccounts command                                           |

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

---

## Architectural Integration

- **Submodules**: `Profile`, `Notifications`, `Dashboard`, `AccountStatus`, `UserManagement`
- **Business Logic**: `app/User/`
- **Routing**: `routes/web/user.php`
- **Views**: `resources/views/user/`
- **Testing**: `tests/User/`, `tests/User/`
- **Dependencies**: Core, SysAdmin

_For overview and business context, see [user.md](user.md)._
