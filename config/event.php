<?php

declare(strict_types=1);

return [
    'listen' => [
        App\Setup\SetupWizard\Events\SetupFinalized::class => [
            App\Setup\SetupWizard\Listeners\LogSetupFinalized::class,
        ],

        App\Settings\Events\SettingUpdated::class => [
            App\Settings\Listeners\InvalidateSettingsCache::class,
        ],

        App\Academics\AcademicYear\Events\AcademicYearCreated::class => [
            App\User\Dashboard\Listeners\ClearDashboardCacheOnYearChange::class,
        ],

        App\Academics\AcademicYear\Events\AcademicYearActivated::class => [
            App\User\Dashboard\Listeners\ClearDashboardCacheOnYearChange::class,
        ],

        App\Academics\Department\Events\DepartmentCreated::class => [
            App\User\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange::class,
        ],

        App\Academics\Department\Events\DepartmentDeleted::class => [
            App\User\Dashboard\Listeners\ClearDashboardCacheOnDepartmentChange::class,
        ],

        App\User\Notifications\Events\NotificationSent::class => [
            App\User\Notifications\Listeners\ClearUnreadNotificationCache::class,
        ],

        App\User\Notifications\Events\NotificationRead::class => [
            App\User\Notifications\Listeners\ClearUnreadNotificationCache::class,
        ],

        App\Partners\Company\Events\CompanyCreated::class => [
            App\Partners\Company\Listeners\ClearDashboardOnCompanyChange::class,
        ],

        App\Program\Internship\Events\InternshipCreated::class => [
            App\Program\Internship\Listeners\NotifyAdminsInternshipCreated::class,
        ],

        App\Enrollment\Registration\Events\StudentRegistered::class => [
            App\Enrollment\Registration\Listeners\ClearDashboardOnRegistration::class,
        ],
    ],
];