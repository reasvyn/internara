# User — Technical Reference

> Last updated: 2026-06-10

Detailed structural and implementation reference for the **User** module.

---

## Overview

Handles user identity, profiles, notifications, account status, dashboards, and activity feeds.

### Submodules

- `Profile`
- `Notifications`
- `Dashboard`
- `AccountStatus`

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/GetActivityLogsAction.php` | `GetActivityLogsAction` | Read |
| `Profile/Actions/GetProfileFormDataAction.php` | `GetProfileFormDataAction` | Read |
| `Profile/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` |
| `Notifications/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` |
| `Notifications/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` |
| `Notifications/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` |
| `Notifications/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseAction` |
| `Notifications/Actions/SendNotificationAction.php` | `SendNotificationAction` | Process `BaseAction` |
| `Dashboard/Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | Read |
| `Dashboard/Actions/GetSupervisorDashboardStatsAction.php` | `GetSupervisorDashboardStatsAction` | Read |
| `Dashboard/Actions/GetTeacherDashboardStatsAction.php` | `GetTeacherDashboardStatsAction` | Read |
| `AccountStatus/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseAction` |
| `AccountStatus/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseAction` |
| `AccountStatus/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Models/User.php` | `User` | `Authenticatable` (with manual HasUuids) |
| `Profile/Models/Profile.php` | `Profile` | `BaseModel` |
| `Notifications/Models/Notification.php` | `Notification` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Enums/AccountStatus.php` | `AccountStatus` | `LabelEnum`, `StatusEnum` | active, inactive, locked, suspended |
| `Enums/BloodType.php` | `BloodType` | `LabelEnum` | A, B, AB, O |
| `Enums/EmploymentStatus.php` | `EmploymentStatus` | `LabelEnum` | active, resigned, retired |
| `Enums/Gender.php` | `Gender` | `LabelEnum` | male, female |
| `Enums/StructuralPosition.php` | `StructuralPosition` | `LabelEnum` | principal, vice_principal, head_department, teacher, staff |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Entities/Apprentice.php` | `Apprentice` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Profile/Policies/ProfilePolicy.php` | `ProfilePolicy` | `BasePolicy` |
| `Notifications/Policies/NotificationPolicy.php` | `NotificationPolicy` | `BasePolicy` |

---

## Listeners

| File | Class | Listens To |
| ---- | ----- | ---------- |
| `Dashboard/Listeners/ClearDashboardCacheOnYearChange.php` | `ClearDashboardCacheOnYearChange` | `AcademicYearCreated`, `AcademicYearActivated` |
| `Dashboard/Listeners/ClearDashboardCacheOnDepartmentChange.php` | `ClearDashboardCacheOnDepartmentChange` | `DepartmentCreated`, `DepartmentDeleted` |
| `Notifications/Listeners/ClearUnreadNotificationCache.php` | `ClearUnreadNotificationCache` | `NotificationSent`, `NotificationRead` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Profile/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` |
| `Notifications/Livewire/NotificationBell.php` | `NotificationBell` | `Component` |
| `Notifications/Livewire/NotificationCenter.php` | `NotificationCenter` | `Component` |
| `Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` |
| `Livewire/RecentActivityList.php` | `RecentActivityList` | `Component` |
| `Dashboard/Livewire/UserDashboard.php` | `UserDashboard` | `Component` |
| `Dashboard/Livewire/AdminDashboard.php` | `AdminDashboard` | `Component` |
| `Dashboard/Livewire/StudentDashboard.php` | `StudentDashboard` | `Component` |
| `Dashboard/Livewire/TeacherDashboard.php` | `TeacherDashboard` | `Component` |
| `Dashboard/Livewire/SupervisorDashboard.php` | `SupervisorDashboard` | `Component` |
| `AccountStatus/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Profile/Livewire/Forms/ProfileForm.php` | `ProfileForm` |
| `Profile/Livewire/Forms/PasswordForm.php` | `PasswordForm` |

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Notifications/Data/NotificationData.php` | `NotificationData` | `BaseData` |

## Events

| File | Class | Dispatched By | Consumed By |
| ---- | ----- | ------------- | ----------- |
| `Notifications/Events/NotificationSent.php` | `NotificationSent` | `SendNotificationAction` | `ClearUnreadNotificationCache` |
| `Notifications/Events/NotificationRead.php` | `NotificationRead` | `MarkAsReadAction` | `ClearUnreadNotificationCache` |

## Notifications (Mail)

| File | Notification |
| ---- | ------------ |
| `Notifications/GeneralNotification.php` | `GeneralNotification` |
| `Notifications/WelcomeNotification.php` | `WelcomeNotification` |
| `Notifications/TestMailNotification.php` | `TestMailNotification` |
| `AccountStatus/Notifications/AccountStatusNotification.php` | `AccountStatusNotification` |

## Support

| File | Class | Purpose |
| ---- | ----- | ------- |
| `Support/UserIdentifierGenerator.php` | `UserIdentifierGenerator` | Generates usernames and identifiers |
| `Services/DashboardService.php` | `DashboardService` | Dashboard data aggregation |

## Rules

| File | Rule | Purpose |
| ---- | ---- | ------- |
| `Rules/ReservedAuthoritativeName.php` | `ReservedAuthoritativeName` | Validates reserved names |
| `Rules/SystemUsername.php` | `SystemUsername` | Validates system username format |

## HTTP Controllers

| File | Controller | Extends |
| ---- | ---------- | ------- |
| `Http/Controllers/DashboardController.php` | `DashboardController` | `BaseController` |
| `Http/Controllers/HomeController.php` | `HomeController` | `BaseController` |

---

## Routes

File: `routes/web/user.php`
Naming pattern: `user.{resource}.{action}`

## Views

Views are located in `resources/views/user/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/User/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

| File | What It Tests |
| ---- | ------------- |
| `Unit/User/Enums/AccountStatusTest.php` | AccountStatus: allowsLogin, isTerminal, transitions, color |
| `Unit/User/Enums/UserEnumsTest.php` | BloodType, Gender, EmploymentStatus, StructuralPosition cases and labels |
| `Unit/User/Entities/ApprenticeTest.php` | Apprentice: status, locked, setup required, transitions |
| `Unit/User/Rules/ReservedAuthoritativeNameTest.php` | Reserved name validation |
| `Unit/User/Rules/SystemUsernameTest.php` | Username format validation |
| `Unit/User/Services/DashboardServiceTest.php` | Dashboard routing by role |
| `Unit/User/Support/UserIdentifierGeneratorTest.php` | Username generation from email, collision handling |
| `Unit/User/Notifications/Data/NotificationDataTest.php` | NotificationData DTO |
| `Feature/User/Profile/ProfileModelTest.php` | Profile model: relationships, fields, cascade delete |
| `Feature/User/Profile/UpdateProfileActionTest.php` | UpdateProfileAction |
| `Feature/User/Dashboard/*Test.php` | Dashboard data actions |
| `Feature/User/AccountStatus/*Test.php` | Lock/Unlock account actions |
| `Feature/User/Notifications/*Test.php` | Send, mark read, notification events |
| `Feature/User/Models/UserTest.php` | User model: roles, UUID, scopes |

## Factories

| Factory | Model |
| ------- | ----- |
| `UserFactory` | `User` |
| `ProfileFactory` | `Profile` |
| `NotificationFactory` | `Notification` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_users_table` | `users` |
| `create_profiles_table` | `profiles` |
| `create_notifications_table` | `notifications` |

---

## File Organization

```
app/User/
├── Actions/GetActivityLogsAction.php
├── AccountStatus/
│   ├── Actions/
│   │   ├── DetectUserAccountCloneAction.php
│   │   ├── LockUserAccountAction.php
│   │   └── UnlockUserAccountAction.php
│   ├── Livewire/AccountLifecycleManager.php
│   └── Notifications/AccountStatusNotification.php
├── Dashboard/
│   ├── Actions/
│   │   ├── GetStudentDashboardDataAction.php
│   │   ├── GetSupervisorDashboardStatsAction.php
│   │   └── GetTeacherDashboardStatsAction.php
│   └── Livewire/
│       ├── AdminDashboard.php
│       ├── StudentDashboard.php
│       ├── SupervisorDashboard.php
│       ├── TeacherDashboard.php
│       └── UserDashboard.php
├── Entities/Apprentice.php
├── Enums/
│   ├── AccountStatus.php
│   ├── BloodType.php
│   ├── EmploymentStatus.php
│   ├── Gender.php
│   └── StructuralPosition.php
├── Http/Controllers/
│   ├── DashboardController.php
│   └── HomeController.php
├── Livewire/
│   ├── ActivityFeedManager.php
│   └── RecentActivityList.php
├── Models/User.php
├── Notifications/
│   ├── Actions/
│   │   ├── DeleteNotificationAction.php
│   │   ├── MarkAllAsReadAction.php
│   │   ├── MarkAsReadAction.php
│   │   ├── MarkBatchAsReadAction.php
│   │   └── SendNotificationAction.php
│   ├── Data/NotificationData.php
│   ├── Events/
│   │   ├── NotificationRead.php
│   │   └── NotificationSent.php
│   ├── Listeners/ClearUnreadNotificationCache.php
│   ├── Livewire/
│   │   ├── NotificationBell.php
│   │   └── NotificationCenter.php
│   ├── Models/Notification.php
│   ├── Policies/NotificationPolicy.php
│   ├── GeneralNotification.php
│   ├── TestMailNotification.php
│   └── WelcomeNotification.php
├── Profile/
│   ├── Actions/
│   │   ├── GetProfileFormDataAction.php
│   │   └── UpdateProfileAction.php
│   ├── Livewire/
│   │   ├── Forms/
│   │   │   ├── PasswordForm.php
│   │   │   └── ProfileForm.php
│   │   └── ProfileEditor.php
│   ├── Models/Profile.php
│   └── Policies/ProfilePolicy.php
├── Rules/
│   ├── ReservedAuthoritativeName.php
│   └── SystemUsername.php
├── Services/DashboardService.php
└── Support/UserIdentifierGenerator.php
```

---

## Architectural Integration

- **Submodules**: `Profile`, `Notifications`, `Dashboard`, `AccountStatus`
- **Business Logic**: `app/User/`
- **Routing**: `routes/web/user.php`
- **Views**: `resources/views/user/`
- **Testing**: `tests/Feature/User/`, `tests/Unit/User/`
- **Dependencies**: Core, SysAdmin

*For overview and business context, see [user.md](user.md).*
