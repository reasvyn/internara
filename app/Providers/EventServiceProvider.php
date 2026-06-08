<?php

declare(strict_types=1);

namespace App\Providers;

use App\Academics\AcademicYear\Events\AcademicYearActivated;
use App\Academics\AcademicYear\Events\AcademicYearCreated;
use App\Academics\AcademicYear\Listeners\ClearDashboardCacheOnYearChange;
use App\Academics\Department\Events\DepartmentCreated;
use App\Academics\Department\Events\DepartmentDeleted;
use App\Academics\Department\Listeners\ClearDashboardCacheOnDepartmentChange;
use App\Settings\Events\SettingUpdated;
use App\Settings\Listeners\InvalidateSettingsCache;
use App\Setup\SetupWizard\Events\SetupFinalized;
use App\Setup\SetupWizard\Listeners\LogSetupFinalized;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
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
    ];

    public static function registerListener(string $event, string $listener): void
    {
        \Illuminate\Support\Facades\Event::listen($event, $listener);
    }
}