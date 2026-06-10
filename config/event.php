<?php

declare(strict_types=1);
use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Enrollment\Registration\Events\StudentRegistered;
use App\Enrollment\Registration\Listeners\ClearDashboardOnRegistration;
use App\Partners\Company\Events\CompanyCreated;
use App\Partners\Company\Listeners\ClearDashboardOnCompanyChange;
use App\Program\Internship\Events\InternshipCreated;
use App\Program\Internship\Listeners\NotifyAdminsInternshipCreated;
use App\Settings\Events\SettingUpdated;
use App\Settings\Listeners\InvalidateSettingsCache;
use App\Setup\SetupWizard\Events\SetupFinalized;
use App\Setup\SetupWizard\Listeners\LogSetupFinalized;
use App\User\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange;
use App\User\Dashboard\Listeners\ClearDashboardCacheOnYearChange;
use App\User\Notifications\Events\NotificationRead;
use App\User\Notifications\Events\NotificationSent;
use App\User\Notifications\Listeners\ClearUnreadNotificationCache;

return [
    'listen' => [
        SetupFinalized::class => [
            LogSetupFinalized::class,
        ],

        SettingUpdated::class => [
            InvalidateSettingsCache::class,
        ],

        AcademicYearCreated::class => [
            ClearDashboardCacheOnYearChange::class,
        ],

        AcademicYearActivated::class => [
            ClearDashboardCacheOnYearChange::class,
        ],

        DepartmentCreated::class => [
            ClearDashboardCacheOnDepartmentChange::class,
        ],

        DepartmentDeleted::class => [
            ClearDashboardCacheOnDepartmentChange::class,
        ],

        NotificationSent::class => [
            ClearUnreadNotificationCache::class,
        ],

        NotificationRead::class => [
            ClearUnreadNotificationCache::class,
        ],

        CompanyCreated::class => [
            ClearDashboardOnCompanyChange::class,
        ],

        InternshipCreated::class => [
            NotifyAdminsInternshipCreated::class,
        ],

        StudentRegistered::class => [
            ClearDashboardOnRegistration::class,
        ],
    ],
];
