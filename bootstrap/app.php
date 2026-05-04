<?php

declare(strict_types=1);

use App\Console\Commands\Admin\AdminPromoteCommand;
use App\Console\Commands\Core\CleanupCommand;
use App\Console\Commands\Core\HealthCommand;
use App\Console\Commands\Setup\SetupInstallCommand;
use App\Console\Commands\Setup\SetupResetCommand;
use App\Http\Middleware\CheckRoleMiddleware;
use App\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        SetupInstallCommand::class,
        SetupResetCommand::class,
        HealthCommand::class,
        CleanupCommand::class,
        AdminPromoteCommand::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('system:cleanup --force')->daily();
        $schedule->command('model:prune')->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [App\Http\Middleware\RequireSetupAccessMiddleware::class, SetLocaleMiddleware::class]);
        $middleware->alias([
            'setup.protected' => ProtectSetupRouteMiddleware::class,
            'setup.auto-redirect' => RequireSetupAccessMiddleware::class,
            'role' => CheckRoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // $exceptions->map(
        //     ModelNotFoundException::class,
        //     fn(ModelNotFoundException $e) => Handler::map($e),
        // );
    })
    ->create();
