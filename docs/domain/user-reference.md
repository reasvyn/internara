# User & Auth — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Reference synchronized with 16 domains structure

This reference details the class structures, models, controllers, actions, and Livewire components belonging to the **User & Auth** domain.

---

## Actions

### Authentication & Lifecycle Actions
| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Actions/LoginAction.php` | `LoginAction` | `BaseAction` | Authenticates users, validates lock state, and regenerates sessions |
| `Auth/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseAction` | Verifies current user password before sensitive actions |
| `Auth/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseAction` | Suspends an account with audit comments, protecting super admins |
| `Auth/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseAction` | Restores access to a suspended account |
| `Auth/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseAction` | Identifies duplicate profiles by scanning national IDs, emails, or phone numbers |
| `Auth/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseAction` | Creates 10 secure, single-use, timing-attack resistant recovery codes |
| `Auth/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseAction` | Consumes a recovery code to bypass forgotten passwords |
| `Auth/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseAction` | Sends a time-limited email reset token |
| `Auth/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseAction` | Resets user password via email token |
| `Auth/Actions/ResetUserPasswordAction.php` | `ResetUserPasswordAction` | `BaseAction` | Admin-initiated password reset command |
| `Auth/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseAction` | Updates user password after validating current password |

### Profile & Notification Actions
| File | Class | Extends | Description |
|---|---|---|---|
| `User/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` | Updates name, contact details, and uploads avatar image |
| `User/Actions/GetProfileFormDataAction.php` | `GetProfileFormDataAction` | `BaseAction` | Resolves profile attributes based on user role |
| `User/Actions/SendNotificationAction.php` | `SendNotificationAction` | `BaseAction` | Dispatches in-app and email notifications; implements `SendsNotifications` |
| `User/Actions/MarkAsReadAction.php` | `MarkAsReadAction` | `BaseAction` | Marks a single notification as read |
| `User/Actions/MarkAllAsReadAction.php` | `MarkAllAsReadAction` | `BaseAction` | Marks all notifications for a user as read |
| `User/Actions/MarkBatchAsReadAction.php` | `MarkBatchAsReadAction` | `BaseAction` | Marks multiple selected notifications as read |
| `User/Actions/DeleteNotificationAction.php` | `DeleteNotificationAction` | `BaseAction` | Deletes a single notification |

### Dashboard & Analytics Actions (Read-only Queries)
| File | Class | Description |
|---|---|---|
| `User/Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | Gathers enrollment status, phase information, and journal stats for students |
| `User/Actions/GetTeacherDashboardStatsAction.php` | `GetTeacherDashboardStatsAction` | Gathers statistics of supervised students, pending journals, and visits |
| `User/Actions/GetSupervisorDashboardStatsAction.php` | `GetSupervisorDashboardStatsAction` | Gathers company-level active interns and pending evaluations |
| `User/Actions/GetActivityLogsAction.php` | `GetActivityLogsAction` | Retrieves paginated user activity logs |

---

## Livewire Components

### Security & Lifecycle UI
| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Livewire/Login.php` | `Login` | `Component` | Credentials entry form with validation and rate limits |
| `Auth/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` | Requests recovery email link |
| `Auth/Livewire/ResetPassword.php` | `ResetPassword` | `Component` | Token password reset entry |
| `Auth/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` | Secure action gate validation |
| `Auth/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` | Code recovery slip redemption UI |
| `Auth/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` | PDF recovery slip export and visual copy interface |
| `Auth/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` | Admin workspace to manage user recovery slips |
| `Auth/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `Component` | Interface to lock, unlock, and audit account status changes |
| `Auth/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` | Token activation flow for newly provisioned accounts |

### Profile & Dashboard UI
| File | Class | Extends | Description |
|---|---|---|---|
| `User/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` | Tabbed editor managing personal, professional, and password details |
| `User/Livewire/NotificationBell.php` | `NotificationBell` | `Component` | Header bell dropdown displaying top 5 unread alerts and cached count |
| `User/Livewire/NotificationCenter.php` | `NotificationCenter` | `BaseRecordManager` | Full-page notification viewer supporting search and bulk deletions |
| `User/Livewire/ActivityFeedManager.php` | `ActivityFeedManager` | `Component` | Paginated causal audit feed filtered by date and severity |
| `User/Livewire/UserDashboard.php` | `UserDashboard` | `Component` | Fallback dashboard for accounts without clear roles |
| `User/Livewire/Dashboards/AdminDashboard.php` | `AdminDashboard` | `Component` | Visual overview of setup state, system cards, and administrator shortcuts |
| `User/Livewire/Dashboards/StudentDashboard.php` | `StudentDashboard` | `Component` | Interactive timeline showing student placement status and journals |
| `User/Livewire/Dashboards/TeacherDashboard.php` | `TeacherDashboard` | `Component` | Grid highlighting assigned student records and outstanding logs |
| `User/Livewire/Dashboards/SupervisorDashboard.php` | `SupervisorDashboard` | `Component` | Industrial feedback center displaying current interns and rubrics |

---

## Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Auth/Livewire/Forms/LoginForm.php` | `LoginForm` | `Form` | identifier, password, remember | `Login` |
| `Auth/Livewire/Forms/ForgotPasswordForm.php` | `ForgotPasswordForm` | `Form` | email | `ForgotPassword` |
| `Auth/Livewire/Forms/ResetPasswordForm.php` | `ResetPasswordForm` | `Form` | token, email, password, password_confirmation | `ResetPassword` |
| `Auth/Livewire/Forms/ConfirmPasswordForm.php` | `ConfirmPasswordForm` | `Form` | password | `ConfirmPassword` |
| `Auth/Livewire/Forms/AccountRecoveryForm.php` | `AccountRecoveryForm` | `Form` | username, recoveryCode, password, password_confirmation | `AccountRecovery` |
| `User/Livewire/Forms/ProfileForm.php` | `ProfileForm` | `Form` | name, email, phone, address, gender, blood_type, national_id_number, emergency_contact, staff fields | `ProfileEditor` |
| `User/Livewire/Forms/PasswordForm.php` | `PasswordForm` | `Form` | current_password, password, password_confirmation | `ProfileEditor` |

---

## Models

### User (`User.php`)
- **Extends**: `Illuminate\Foundation\Auth\User` (implements authenticatable contracts)
- **Relationships**:
  - `profile` → `HasOne` (Profile)
  - `notifications` → `HasMany` (Notification)
  - `recoveryCodes` → `HasMany` (AccountRecoveryCode)
  - `activationToken` → `HasOne` (ActivationToken)

### Profile (`Profile.php`)
- **Extends**: `BaseModel`
- **Fields**: phone, address, gender, blood_type, national_id_number, employee_id_number, educator_id_number, job_title, department_id, company_id, school_id
- **Relationships**:
  - `user` → `BelongsTo` (User)

### Notification (`Notification.php`)
- **Extends**: `BaseModel`
- **Fields**: type, title, message, data (json), link, is_read, read_at

### AccountRecoveryCode (`AccountRecoveryCode.php`)
- **Extends**: `BaseModel`
- **Fields**: code (hashed), is_used, used_at

### ActivationToken (`ActivationToken.php`)
- **Extends**: `BaseModel`
- **Fields**: token (hashed), expires_at, is_used

---

## Policies

- `UserPolicy`: Controls view, edit, creation, and deletion checks. Restricts self-deletion, super admin deletion, and limits non-owners from modifying profiles.
- `NotificationPolicy`: Ensures only users themselves can read and mark their notifications as read, and restricts global creations to administrators.
