# User — Technical Reference

> Last updated: 2026-06-06
> Changes: refactor: use activation_tokens for recovery codes; rename Notification/ to Notifications/

Detailed structural and implementation reference for the **User** module.

---

## Overview

Handles authentication, user profiles, notifications, and account recovery

### Module Statistics
- **Actions**: 25 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 15 UI components
- **Policies**: 3 authorization rules
- **Submodules**: 9 module submodules

### Submodules
- `AccountRecovery`
- `AccountStatus`
- `ActivationToken`
- `Dashboard`
- `Login`
- `Notifications`
- `Password`
- `Profile`
- `SuperAdmin`

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
| `Password/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseAction` |
| `Notifications/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` |
| `AccountStatus/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseAction` |
| `AccountRecovery/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseAction` |
| `Actions/GetActivityLogsAction.php` | `GetActivityLogsAction` | `BaseAction` |
| `Profile/Actions/GetProfileFormDataAction.php` | `GetProfileFormDataAction` | `BaseAction` |
| `Dashboard/Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | `BaseAction` |
| `Dashboard/Actions/GetSupervisorDashboardStatsAction.php` | `GetSupervisorDashboardStatsAction` | `BaseAction` |
| `Dashboard/Actions/GetTeacherDashboardStatsAction.php` | `GetTeacherDashboardStatsAction` | `BaseAction` |
| `SuperAdmin/Actions/InitializeSuperAdminAction.php` | `InitializeSuperAdminAction` | `BaseAction` |
| `AccountStatus/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseAction` |
| `Login/Actions/LoginAction.php` | `LoginAction` | `BaseAction` |
| `Notifications/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` |
| `Notifications/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` |
| `Notifications/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseAction` |
| `SuperAdmin/Actions/RecoverSuperAdminAction.php` | `RecoverSuperAdminAction` | `BaseAction` |
| `AccountRecovery/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseAction` |
| `Password/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseAction` |
| `Password/Actions/ResetUserPasswordAction.php` | `ResetUserPasswordAction` | `BaseAction` |
| `Notifications/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseAction` |
| `Password/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseAction` |
| `SuperAdmin/Actions/SetupSuperAdminAction.php` | `SetupSuperAdminAction` | `BaseAction` |
| `AccountStatus/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseAction` |
| `Profile/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` |
| `Password/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `AccountRecovery/Models/AccountRecoveryCode.php` | `AccountRecoveryCode` |
| `ActivationToken/Models/ActivationToken.php` | `ActivationToken` |
| `Notifications/Models/Notification.php` | `Notification` |
| `Profile/Models/Profile.php` | `Profile` |
| `Models/User.php` | `User` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `AccountStatus/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `Component` |
| `AccountRecovery/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` |
| `ActivationToken/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` |
| `Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` |
| `Password/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` |
| `Password/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` |
| `Login/Livewire/Login.php` | `Login` | `Component` |
| `Notifications/Livewire/NotificationBell.php` | `NotificationBell` | `Component` |
| `Notifications/Livewire/NotificationCenter.php` | `NotificationCenter` | `BaseRecordManager` |
| `Profile/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` |
| `Livewire/RecentActivityList.php` | `RecentActivityList` | `Component` |
| `AccountRecovery/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` |
| `AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` |
| `Password/Livewire/ResetPassword.php` | `ResetPassword` | `Component` |
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
| `Policies/UserPolicy.php` | `UserPolicy` |

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
