<?php

declare(strict_types=1);

use App\Console\Commands\AppInstallCommand;
use App\Console\Commands\SetupResetCommand;
use App\Console\Commands\System\AdminPromoteCommand;
use App\Console\Commands\System\CleanupCommand;
// use Modules\Auth\Http\Middleware\EnsureEmailIsVerified;
// use Modules\Core\Localization\Http\Middleware\SetLocale;
// use Modules\Exception\Handler;
// use Modules\Setup\Http\Middleware\BypassSetupAuthorization;
// use Modules\Setup\Http\Middleware\RequireSetupAccess;
// use Modules\Status\Middleware\CheckSessionExpiration;
use App\Console\Commands\System\HealthCommand;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ProtectSetupRoute;
use App\Http\Middleware\SetLocale;
use App\Support\Integrity;
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
        $middleware->web(
            append: [
                App\Http\Middleware\RequireSetupAccess::class,
                SetLocale::class,
            ],
        );
        $middleware->alias([
            'setup.protected' => ProtectSetupRoute::class,
            'setup.auto-redirect' => RequireSetupAccess::class,
            'role' => CheckRole::class,
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
