<?php

declare(strict_types=1);

use App\Console\Commands\AppInstallCommand;
use App\Console\Commands\SetupResetCommand;
use App\Console\Commands\System\AdminPromoteCommand;
use App\Console\Commands\System\CleanupCommand;
use App\Console\Commands\System\HealthCommand;
use App\Domain\Core\Support\Integrity;
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

/*
|--------------------------------------------------------------------------
| Integrity & Attribution Verification
|--------------------------------------------------------------------------
|
| This block ensures the application's core metadata is intact and that
| the original author is properly attributed.
*/
Integrity::verify();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        AppInstallCommand::class,
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
