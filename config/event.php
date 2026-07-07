<?php

declare(strict_types=1);
use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\AcademicYear\Events\AcademicYearDeleted;
use App\Academics\AcademicYear\Events\AcademicYearUpdated;
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Events\DepartmentUpdated;
use App\Assignment\Events\AssignmentPublished;
use App\Assignment\Listeners\NotifyOnAssignmentPublished;
use App\Auth\Login\Events\LoginFailed;
use App\Auth\Login\Events\LoginSucceeded;
use App\Auth\Login\Listeners\LogLoginFailed;
use App\Auth\Login\Listeners\SendRoleWelcomeNotification;
use App\Auth\Password\Events\PasswordUpdated;
use App\Auth\Password\Listeners\InvalidateSessionOnPasswordChange;
use App\Auth\Password\Listeners\SendPasswordChangedMail;
use App\Auth\SuperAdmin\Events\SuperAdminRecovered;
use App\Auth\SuperAdmin\Listeners\NotifySuperAdminsOfRecovery;
use App\Enrollment\Registration\Events\StudentRegistered;
use App\Enrollment\Registration\Listeners\ClearDashboardOnRegistration;
use App\Guidance\Handbook\Events\HandbookCreated;
use App\Guidance\Handbook\Events\HandbookDeleted;
use App\Guidance\Handbook\Events\HandbookUpdated;
use App\Guidance\Handbook\Listeners\ClearHandbookCache;
use App\Partners\Company\Events\CompanyCreated;
use App\Partners\Company\Events\CompanyDeleted;
use App\Partners\Company\Events\CompanyUpdated;
use App\Partners\Company\Listeners\ClearDashboardOnCompanyChange;
use App\Partners\Partnership\Events\PartnershipCreated;
use App\Partners\Partnership\Events\PartnershipDeleted;
use App\Partners\Partnership\Events\PartnershipRenewed;
use App\Partners\Partnership\Events\PartnershipTerminated;
use App\Partners\Partnership\Events\PartnershipUpdated;
use App\Partners\Partnership\Listeners\ClearDashboardOnPartnershipChange;
use App\Partners\Partnership\Listeners\NotifyOnPartnershipTerminated;
use App\Program\Internship\Events\InternshipCreated;
use App\Program\Internship\Listeners\NotifyAdminsInternshipCreated;
use App\Settings\Events\SettingUpdated;
use App\Settings\Listeners\InvalidateSettingsCache;
use App\Setup\SetupWizard\Events\SetupFinalized;
use App\Setup\SetupWizard\Listeners\LogSetupFinalized;
use App\SysAdmin\Backups\Events\BackupCompleted;
use App\SysAdmin\Backups\Events\BackupFailed;
use App\SysAdmin\Backups\Listeners\SendBackupFailedNotification;
use App\User\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange;
use App\User\Dashboard\Listeners\ClearDashboardCacheOnYearChange;
use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Events\NotificationSent;
use App\User\Notifications\Listeners\ClearUnreadNotificationCache;
use App\User\Profile\Events\ProfileUpdated;
use App\User\Profile\Listeners\SendProfileChangedMail;

return [
    'listen' => [
        SetupFinalized::class => [LogSetupFinalized::class],

        SettingUpdated::class => [InvalidateSettingsCache::class],

        AcademicYearCreated::class => [ClearDashboardCacheOnYearChange::class],

        AcademicYearActivated::class => [ClearDashboardCacheOnYearChange::class],

        AcademicYearUpdated::class => [ClearDashboardCacheOnYearChange::class],

        AcademicYearDeleted::class => [ClearDashboardCacheOnYearChange::class],

        DepartmentCreated::class => [ClearDashboardCacheOnDepartmentChange::class],

        DepartmentDeleted::class => [ClearDashboardCacheOnDepartmentChange::class],

        DepartmentUpdated::class => [ClearDashboardCacheOnDepartmentChange::class],

        NotificationSent::class => [ClearUnreadNotificationCache::class],

        NotificationRead::class => [ClearUnreadNotificationCache::class],

        ProfileUpdated::class => [
            ClearUnreadNotificationCache::class,
            SendProfileChangedMail::class,
        ],

        CompanyCreated::class => [ClearDashboardOnCompanyChange::class],

        CompanyUpdated::class => [ClearDashboardOnCompanyChange::class],

        CompanyDeleted::class => [ClearDashboardOnCompanyChange::class],

        PartnershipCreated::class => [ClearDashboardOnPartnershipChange::class],

        PartnershipUpdated::class => [ClearDashboardOnPartnershipChange::class],

        PartnershipDeleted::class => [ClearDashboardOnPartnershipChange::class],

        PartnershipRenewed::class => [ClearDashboardOnPartnershipChange::class],

        PartnershipTerminated::class => [
            ClearDashboardOnPartnershipChange::class,
            NotifyOnPartnershipTerminated::class,
        ],

        HandbookCreated::class => [ClearHandbookCache::class],

        HandbookUpdated::class => [ClearHandbookCache::class],

        HandbookDeleted::class => [ClearHandbookCache::class],

        AssignmentPublished::class => [NotifyOnAssignmentPublished::class],

        PasswordUpdated::class => [
            InvalidateSessionOnPasswordChange::class,
            SendPasswordChangedMail::class,
        ],

        LoginFailed::class => [LogLoginFailed::class],

        InternshipCreated::class => [NotifyAdminsInternshipCreated::class],

        StudentRegistered::class => [ClearDashboardOnRegistration::class],

        LoginSucceeded::class => [SendRoleWelcomeNotification::class],

        SuperAdminRecovered::class => [NotifySuperAdminsOfRecovery::class],

        BackupFailed::class => [SendBackupFailedNotification::class],

        // Fire-and-forget events (intentionally no listeners):
        // AssessmentFinalized — logged in FinalizeAssessmentAction, no side effects
        // SubmissionRevisionRequested — logged in action, notification not yet implemented
        // AccountApplicationApproved — logged in action, notification not yet implemented
        // AccountApplicationRejected — logged in action, notification not yet implemented
        // ReportSubmitted — logged in SubmitReportAction, no side effects
        // UserCreated — logged in CreateUserAction, cache not yet needed
        // UserDeleted — logged in DeleteUserAction, cache not yet needed
        // UserStatusChanged — logged in ToggleUserStatusAction, cache not yet needed
        // UserUpdated — logged in UpdateUserAction, cache not yet needed
        // BackupCompleted — logged in CreateBackupAction, no side effects needed
        // GradeCalculated — synchronous calculation, logged in action
        // RecoverySlipGenerated — OTP flow, logged in action
        // InternshipStatusBatchUpdated — batch operation, logged in action
        // ReportFinalized — not currently dispatched from any Action
        // CertificateIssued — logged in action, QR generation is synchronous
        // AttendanceClockIn — logged in action, geofence check is synchronous
        // AttendanceClockOut — logged in action, duration calculation is synchronous
        // UserAccountLocked — logged in action, status change is synchronous
        // UserAccountUnlocked — logged in action, status change is synchronous
    ],
];
