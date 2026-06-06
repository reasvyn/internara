# User — Technical Reference

> Last updated: 2026-06-06
> Changes: refactor: move Auth submodules (AccountRecovery, ActivationToken, Login, Password, SuperAdmin, Permissions) to new Auth module

Detailed structural and implementation reference for the **User** module.

---

## Overview

Handles user profiles, notifications, account status management, and dashboards

### Module Statistics
- **Actions**: 10 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 9 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 4 module submodules

### Submodules
- `AccountStatus`
- `Dashboard`
- `Notifications`
- `Profile`

---

## Dependency Graph

This module depends on:
- **Academics**
- **SysAdmin**
- **Core**
- **Enrollment**
- **Evaluation**
- **Guidance**
- **Journals**
- **Partners**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Notifications/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` |
| `AccountStatus/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseAction` |
| `Actions/GetActivityLogsAction.php` | `GetActivityLogsAction` | `BaseAction` |
| `Profile/Actions/GetProfileFormDataAction.php` | `GetProfileFormDataAction` | `BaseAction` |
| `Dashboard/Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | `BaseAction` |
| `Dashboard/Actions/GetSupervisorDashboardStatsAction.php` | `GetSupervisorDashboardStatsAction` | `BaseAction` |
| `Dashboard/Actions/GetTeacherDashboardStatsAction.php` | `GetTeacherDashboardStatsAction` | `BaseAction` |
| `AccountStatus/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseAction` |
| `Notifications/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` |
| `Notifications/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` |
| `Notifications/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseAction` |
| `Notifications/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseAction` |
| `AccountStatus/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseAction` |
| `Profile/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Notifications/Models/Notification.php` | `Notification` |
| `Profile/Models/Profile.php` | `Profile` |
| `Models/User.php` | `User` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `AccountStatus/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `Component` |
| `Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` |
| `Notifications/Livewire/NotificationBell.php` | `NotificationBell` | `Component` |
| `Notifications/Livewire/NotificationCenter.php` | `NotificationCenter` | `BaseRecordManager` |
| `Profile/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` |
| `Livewire/RecentActivityList.php` | `RecentActivityList` | `Component` |
| `Dashboard/Livewire/UserDashboard.php` | `UserDashboard` | `Component` |
| `Dashboard/Livewire/AdminDashboard.php` | `AdminDashboard` | `UserDashboard` |
| `Dashboard/Livewire/StudentDashboard.php` | `StudentDashboard` | `UserDashboard` |
| `Dashboard/Livewire/SupervisorDashboard.php` | `SupervisorDashboard` | `UserDashboard` |
| `Dashboard/Livewire/TeacherDashboard.php` | `TeacherDashboard` | `UserDashboard` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Notifications/Policies/NotificationPolicy.php` | `NotificationPolicy` |
| `Profile/Policies/ProfilePolicy.php` | `ProfilePolicy` |

---

## File Organization

```
app/User/
├──            ← Submodule roots
│   └── {SubModule}/
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

*For overview and business context, see [user.md](user.md)*
