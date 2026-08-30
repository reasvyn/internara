<?php

declare(strict_types=1);
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearActivated;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearCreated;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearDeleted;
use App\Modules\Academics\Domain\AcademicYear\Events\AcademicYearUpdated;
use App\Modules\Academics\Domain\Department\Events\DepartmentCreated;
use App\Modules\Academics\Domain\Department\Events\DepartmentDeleted;
use App\Modules\Academics\Domain\Department\Events\DepartmentUpdated;
use App\Modules\Assignment\Events\AssignmentPublished;
use App\Modules\Assignment\Listeners\NotifyOnAssignmentPublished;
use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Auth\Domain\Login\Events\LoginSucceeded;
use App\Modules\Auth\Domain\Login\Listeners\LogLoginFailed;
use App\Modules\Auth\Domain\Login\Listeners\SendRoleWelcomeNotification;
use App\Modules\Auth\Domain\Password\Events\PasswordUpdated;
use App\Modules\Auth\Domain\Password\Listeners\InvalidateSessionOnPasswordChange;
use App\Modules\Auth\Domain\Password\Listeners\SendPasswordChangedMail;
use App\Modules\Auth\Domain\SuperAdmin\Events\SuperAdminRecovered;
use App\Modules\Auth\Domain\SuperAdmin\Listeners\NotifySuperAdminsOfRecovery;
use App\Modules\Document\Domain\Handbook\Events\HandbookCreated;
use App\Modules\Document\Domain\Handbook\Events\HandbookDeleted;
use App\Modules\Document\Domain\Handbook\Events\HandbookUpdated;
use App\Modules\Document\Domain\Handbook\Listeners\ClearHandbookCache;
use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use App\Modules\Enrollment\Domain\Registration\Listeners\ClearDashboardOnRegistration;
use App\Modules\Partners\Domain\Company\Events\CompanyCreated;
use App\Modules\Partners\Domain\Company\Events\CompanyDeleted;
use App\Modules\Partners\Domain\Company\Events\CompanyUpdated;
use App\Modules\Partners\Domain\Company\Listeners\ClearDashboardOnCompanyChange;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipCreated;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipDeleted;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipRenewed;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partners\Domain\Partnership\Events\PartnershipUpdated;
use App\Modules\Partners\Domain\Partnership\Listeners\ClearDashboardOnPartnershipChange;
use App\Modules\Partners\Domain\Partnership\Listeners\NotifyOnPartnershipTerminated;
use App\Modules\Program\Domain\Internship\Events\InternshipCreated;
use App\Modules\Program\Domain\Internship\Listeners\NotifyAdminsInternshipCreated;
use App\Modules\Reports\Domain\StudentReport\Events\GradeCalculated;
use App\Modules\Reports\Domain\StudentReport\Events\StudentReportFinalized;
use App\Modules\Reports\Domain\StudentReport\Listeners\LogGradeCalculated;
use App\Modules\Reports\Domain\StudentReport\Listeners\LogStudentReportFinalized;
use App\Modules\Setup\Domain\SetupWizard\Events\SetupFinalized;
use App\Modules\Setup\Domain\SetupWizard\Listeners\LogSetupFinalized;
use App\Modules\SysAdmin\Domain\Backups\Events\BackupCompleted;
use App\Modules\SysAdmin\Domain\Backups\Events\BackupFailed;
use App\Modules\SysAdmin\Domain\Backups\Listeners\SendBackupFailedNotification;
use App\Modules\User\Domain\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange;
use App\Modules\User\Domain\Dashboard\Listeners\ClearDashboardCacheOnYearChange;
use App\Modules\User\Domain\Notifications\Events\NotificationRead;
use App\Modules\User\Domain\Notifications\Events\NotificationSent;
use App\Modules\User\Domain\Notifications\Listeners\ClearUnreadNotificationCache;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
use App\Modules\User\Domain\Profile\Listeners\SendProfileChangedMail;

return [
    'listen' => [
        SetupFinalized::class => [LogSetupFinalized::class],

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

        GradeCalculated::class => [LogGradeCalculated::class],

        StudentReportFinalized::class => [LogStudentReportFinalized::class],

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
        // StudentReportFinalized — logged in FinalizeStudentReportAction, snapshot captured via StudentReportObserver
        // CertificateIssued — logged in action, QR generation is synchronous
        // AttendanceClockIn — logged in action, geofence check is synchronous
        // AttendanceClockOut — logged in action, duration calculation is synchronous
        // UserAccountLocked — logged in action, status change is synchronous
        // UserAccountUnlocked — logged in action, status change is synchronous
    ],
];
