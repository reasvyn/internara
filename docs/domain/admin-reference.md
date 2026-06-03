# Admin — API Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Aggregate-rooted layout mapping for the Admin domain including Setup

This reference defines the structured aggregates and code layout within the **Administration** domain.

---

## 1. Setup Aggregate
Handles the first-run application bootstrap, system installation, environment auditing, school/department provisioning, and recovery commands.

- **Eloquent Models**:
  - `Setup` (`app/Domain/Administration/Aggregates/Setup/Models/Setup.php`)
- **Policies**:
  - `SetupPolicy` (`app/Domain/Administration/Aggregates/Setup/Policies/SetupPolicy.php`)
- **Command Actions**:
  - `GenerateSetupTokenAction` (`app/Domain/Administration/Aggregates/Setup/Actions/GenerateSetupTokenAction.php`)
  - `ValidateSetupTokenAction` (`app/Domain/Administration/Aggregates/Setup/Actions/ValidateSetupTokenAction.php`)
  - `InstallSystemAction` (`app/Domain/Administration/Aggregates/Setup/Actions/InstallSystemAction.php`)
  - `SetupSchoolAction` (`app/Domain/Administration/Aggregates/Setup/Actions/SetupSchoolAction.php`)
  - `SetupDepartmentAction` (`app/Domain/Administration/Aggregates/Setup/Actions/SetupDepartmentAction.php`)
  - `SetupSuperAdminAction` (`app/Domain/Administration/Aggregates/Setup/Actions/SetupSuperAdminAction.php`)
  - `FinalizeSetupAction` (`app/Domain/Administration/Aggregates/Setup/Actions/FinalizeSetupAction.php`)
  - `InitializeSuperAdminAction` (`app/Domain/Administration/Aggregates/Setup/Actions/InitializeSuperAdminAction.php`)
  - `RecoverSuperAdminAction` (`app/Domain/Administration/Aggregates/Setup/Actions/RecoverSuperAdminAction.php`)
- **Console Commands**:
  - `SetupInstallCommand` (`app/Domain/Administration/Console/Commands/SetupInstallCommand.php`)
  - `SetupResetTokenCommand` (`app/Domain/Administration/Console/Commands/SetupResetTokenCommand.php`)
- **Livewire UI Components**:
  - `SetupWizard` (`app/Domain/Administration/Aggregates/Setup/Livewire/SetupWizard.php`)
- **Livewire Form Objects**:
  - `SetupSchoolForm` (`app/Domain/Administration/Aggregates/Setup/Livewire/Forms/SetupSchoolForm.php`)
  - `SetupDepartmentForm` (`app/Domain/Administration/Aggregates/Setup/Livewire/Forms/SetupDepartmentForm.php`)
  - `AdminForm` (`app/Domain/Administration/Aggregates/Setup/Livewire/Forms/AdminForm.php`)
  - `InternshipForm` (`app/Domain/Administration/Aggregates/Setup/Livewire/Forms/InternshipForm.php`)
- **Entities (Domain Rules)**:
  - `SetupState` (`app/Domain/Administration/Aggregates/Setup/Entities/SetupState.php`)
- **Support & Services**:
  - `SystemProvisioner` (`app/Domain/Administration/Aggregates/Setup/Support/SystemProvisioner.php`)
  - `EnvironmentAuditor` (`app/Domain/Administration/Aggregates/Setup/Services/EnvironmentAuditor.php`)

---

## 2. Oversight Aggregate
Provides system management, user accounts mapping, recovery key operations, credentials slip generation, and system health checks.

- **Policies**:
  - `UserPolicy` (`app/Domain/User/Policies/UserPolicy.php` — consumed contextually)
- **Command Actions**:
  - `CreateUserAction` (`app/Domain/Administration/Actions/CreateUserAction.php`)
  - `UpdateUserAction` (`app/Domain/Administration/Actions/UpdateUserAction.php`)
  - `ArchiveStudentAccountsAction` (`app/Domain/Administration/Actions/ArchiveStudentAccountsAction.php`)
  - `BatchDeleteUserAction` (`app/Domain/Administration/Actions/BatchDeleteUserAction.php`)
  - `SetUserStatusAction` (`app/Domain/Administration/Actions/SetUserStatusAction.php`)
  - `ToggleUserStatusAction` (`app/Domain/Administration/Actions/ToggleUserStatusAction.php`)
  - `RevokeUserActivationTokensAction` (`app/Domain/Administration/Actions/RevokeUserActivationTokensAction.php`)
  - `GetAdminDashboardStatsAction` (`app/Domain/Administration/Actions/GetAdminDashboardStatsAction.php`)
  - `GetUserManagerStatsAction` (`app/Domain/Administration/Actions/GetUserManagerStatsAction.php`)
  - `SaveRecoveryKeyAction` (`app/Domain/Administration/Actions/SaveRecoveryKeyAction.php`)
  - `ReadRecoveryKeyAction` (`app/Domain/Administration/Actions/ReadRecoveryKeyAction.php`)
  - `GenerateAccountSlipAction` (`app/Domain/Administration/Actions/GenerateAccountSlipAction.php`)
- **Console Commands**:
  - `AdminPromoteCommand` (`app/Domain/Administration/Console/Commands/AdminPromoteCommand.php`)
  - `CreateAdminCommand` (`app/Domain/Administration/Console/Commands/CreateAdminCommand.php`)
  - `RecoverAdminCommand` (`app/Domain/Administration/Console/Commands/RecoverAdminCommand.php`)
  - `AutoInactivateAccounts` (`app/Domain/Administration/Console/Commands/AutoInactivateAccounts.php`)
  - `PruneNotificationsCommand` (`app/Domain/Administration/Console/Commands/PruneNotificationsCommand.php`)
  - `ShowRecoveryPathCommand` (`app/Domain/Administration/Console/Commands/ShowRecoveryPathCommand.php`)
  - `ShowRecoveryKeyCommand` (`app/Domain/Administration/Console/Commands/ShowRecoveryKeyCommand.php`)
  - `PulseRecordSnapshotsCommand` (`app/Domain/Administration/Console/Commands/PulseRecordSnapshotsCommand.php`)
- **Livewire UI Components**:
  - `UserManager` (`app/Domain/Administration/Livewire/UserManager.php`)
  - `AdminManager` (`app/Domain/Administration/Livewire/AdminManager.php`)
  - `StudentManager` (`app/Domain/Administration/Livewire/StudentManager.php`)
  - `TeacherManager` (`app/Domain/Administration/Livewire/TeacherManager.php`)
  - `SupervisorManager` (`app/Domain/Administration/Livewire/SupervisorManager.php`)
  - `ApplicationReview` (`app/Domain/Administration/Livewire/ApplicationReview.php`)
  - `AccountCloneDetector` (`app/Domain/Administration/Livewire/AccountCloneDetector.php`)
  - `Pulse/SystemCard` (`app/Domain/Administration/Livewire/Pulse/SystemCard.php`)
  - `Pulse/RegistrationsCard` (`app/Domain/Administration/Livewire/Pulse/RegistrationsCard.php`)
- **Livewire Form Objects**:
  - `UserForm` (`app/Domain/Administration/Livewire/Forms/UserForm.php`)
  - `AdminUserForm` (`app/Domain/Administration/Livewire/Forms/AdminUserForm.php`)
  - `StudentForm` (`app/Domain/Administration/Livewire/Forms/StudentForm.php`)
  - `TeacherForm` (`app/Domain/Administration/Livewire/Forms/TeacherForm.php`)
  - `SupervisorForm` (`app/Domain/Administration/Livewire/Forms/SupervisorForm.php`)
- **Pulse Metrics Recorders**:
  - `SystemRecorder` (`app/Domain/Administration/Recorders/SystemRecorder.php`)
  - `RegistrationRecorder` (`app/Domain/Administration/Recorders/RegistrationRecorder.php`)
- **Oversight Services**:
  - `PulseGuard` (`app/Domain/Administration/Services/PulseGuard.php`)

---

## 3. Announcement Aggregate
Manages broadcast notices lifecycle, future schedules, and role-targeted dispatches.

- **Eloquent Models**:
  - `Announcement` (`app/Domain/Administration/Models/Announcement.php`)
- **Command Actions**:
  - `SendAnnouncementAction` (`app/Domain/Administration/Actions/SendAnnouncementAction.php`)
- **Console Commands**:
  - `PublishScheduledAnnouncementsCommand` (`app/Domain/Administration/Console/Commands/PublishScheduledAnnouncementsCommand.php`)
- **Livewire UI Components**:
  - `AnnouncementManager` (`app/Domain/Administration/Livewire/AnnouncementManager.php`)
- **Livewire Form Objects**:
  - `AnnouncementForm` (`app/Domain/Administration/Livewire/Forms/AnnouncementForm.php`)
- **Notifications**:
  - `AnnouncementNotification` (`app/Domain/Administration/Notifications/AnnouncementNotification.php`)
  - `ActivationCodeNotification` (`app/Domain/Administration/Notifications/ActivationCodeNotification.php`)
- **Enums**:
  - `AnnouncementStatus` (`app/Domain/Administration/Enums/AnnouncementStatus.php`)

---

## 4. Compliance Aggregate
Tracks GDPR erasure logs and displays activity audit logs.

- **Eloquent Models**:
  - `GdprDeletionLog` (`app/Domain/Administration/Models/GdprDeletionLog.php`)
- **Policies**:
  - `GdprDeletionLogPolicy` (`app/Domain/Administration/Policies/GdprDeletionLogPolicy.php`)
- **Livewire UI Components**:
  - `GdprDeletionLogs` (`app/Domain/Administration/Livewire/GdprDeletionLogs.php`)
  - `AuditLogManager` (`app/Domain/Administration/Livewire/AuditLogManager.php`)
