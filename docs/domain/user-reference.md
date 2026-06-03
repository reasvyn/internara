# User — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the User domain.

Detailed structural and implementation reference for the **User** domain.

---

## Overview

Handles authentication, user profiles, notifications, and account recovery

### Domain Statistics
- **Actions**: 25 business logic operations
- **Models**: 5 data entities
- **Livewire Components**: 15 UI components
- **Policies**: 3 authorization rules
- **Aggregates**: 8 domain aggregates

### Aggregates
- `AccountRecovery`
- `AccountStatus`
- `ActivationToken`
- `Login`
- `Notification`
- `Password`
- `Profile`
- `SuperAdmin`

---

## Dependency Graph

This domain depends on:
- **Academics**
- **Admin**
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
| `Aggregates/Password/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseAction` |
| `Aggregates/Notification/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` |
| `Aggregates/AccountStatus/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseAction` |
| `Aggregates/AccountRecovery/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseAction` |
| `Actions/GetActivityLogsAction.php` | `GetActivityLogsAction` | `BaseAction` |
| `Aggregates/Profile/Actions/GetProfileFormDataAction.php` | `GetProfileFormDataAction` | `BaseAction` |
| `Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | `BaseAction` |
| `Actions/GetSupervisorDashboardStatsAction.php` | `GetSupervisorDashboardStatsAction` | `BaseAction` |
| `Actions/GetTeacherDashboardStatsAction.php` | `GetTeacherDashboardStatsAction` | `BaseAction` |
| `Aggregates/SuperAdmin/Actions/InitializeSuperAdminAction.php` | `InitializeSuperAdminAction` | `BaseAction` |
| `Aggregates/AccountStatus/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseAction` |
| `Aggregates/Login/Actions/LoginAction.php` | `LoginAction` | `BaseAction` |
| `Aggregates/Notification/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` |
| `Aggregates/Notification/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` |
| `Aggregates/Notification/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseAction` |
| `Aggregates/SuperAdmin/Actions/RecoverSuperAdminAction.php` | `RecoverSuperAdminAction` | `BaseAction` |
| `Aggregates/AccountRecovery/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseAction` |
| `Aggregates/Password/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseAction` |
| `Aggregates/Password/Actions/ResetUserPasswordAction.php` | `ResetUserPasswordAction` | `BaseAction` |
| `Aggregates/Notification/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseAction` |
| `Aggregates/Password/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseAction` |
| `Aggregates/SuperAdmin/Actions/SetupSuperAdminAction.php` | `SetupSuperAdminAction` | `BaseAction` |
| `Aggregates/AccountStatus/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseAction` |
| `Aggregates/Profile/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` |
| `Aggregates/Password/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/AccountRecovery/Models/AccountRecoveryCode.php` | `AccountRecoveryCode` |
| `Aggregates/ActivationToken/Models/ActivationToken.php` | `ActivationToken` |
| `Aggregates/Notification/Models/Notification.php` | `Notification` |
| `Aggregates/Profile/Models/Profile.php` | `Profile` |
| `Models/User.php` | `User` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/AccountStatus/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `Component` |
| `Aggregates/AccountRecovery/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` |
| `Aggregates/ActivationToken/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` |
| `Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` |
| `Aggregates/Password/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` |
| `Aggregates/Password/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` |
| `Aggregates/Login/Livewire/Login.php` | `Login` | `Component` |
| `Aggregates/Notification/Livewire/NotificationBell.php` | `NotificationBell` | `Component` |
| `Aggregates/Notification/Livewire/NotificationCenter.php` | `NotificationCenter` | `BaseRecordManager` |
| `Aggregates/Profile/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` |
| `Livewire/RecentActivityList.php` | `RecentActivityList` | `Component` |
| `Aggregates/AccountRecovery/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` |
| `Aggregates/AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` |
| `Aggregates/Password/Livewire/ResetPassword.php` | `ResetPassword` | `Component` |
| `Livewire/UserDashboard.php` | `UserDashboard` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/Notification/Policies/NotificationPolicy.php` | `NotificationPolicy` |
| `Aggregates/Profile/Policies/ProfilePolicy.php` | `ProfilePolicy` |
| `Policies/UserPolicy.php` | `UserPolicy` |

---

## File Organization

```
app/Domain/User/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
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
