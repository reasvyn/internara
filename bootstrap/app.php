<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Http\Middleware\AuthThrottleMiddleware;
use App\Modules\Auth\Domain\Permissions\Http\Middleware\CheckRoleMiddleware;
use App\Modules\Core\Exceptions\AppException;
use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use App\Modules\Core\Exceptions\ValidationFailedException;
use App\Modules\Core\Http\Middleware\LogContextMiddleware;
use App\Modules\Core\Http\Middleware\SecurityHeadersMiddleware;
use App\Modules\Settings\Domain\Locale\Http\Middleware\SetLocaleMiddleware;
use App\Modules\Setup\Domain\Installation\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Modules\Setup\Domain\Installation\Http\Middleware\RequireSetupAccessMiddleware;
use App\Providers\EventServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Modules/Auth/Domain/SuperAdmin/Console/Commands',
        __DIR__.'/../app/Modules/Core/Console/Commands',
        __DIR__.'/../app/Modules/Setup/Domain/Installation/Console/Commands',
        __DIR__.'/../app/Modules/SysAdmin/Console/Commands',
        __DIR__.'/../app/Modules/SysAdmin/Domain/Announcement/Console/Commands',
        __DIR__.'/../app/Modules/SysAdmin/Domain/Observability/Console/Commands',
        __DIR__.'/../app/Modules/SysAdmin/Domain/Backups/Console/Commands',
        __DIR__.'/../app/Modules/User/Console/Commands',
        __DIR__.'/../app/Modules/User/Domain/UserManagement/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setup.protected' => ProtectSetupRouteMiddleware::class,
            'role' => CheckRoleMiddleware::class,
            'auth.throttle' => AuthThrottleMiddleware::class,
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->preventRequestForgery(except: ['setup']);

        $middleware->web(
            append: [
                SecurityHeadersMiddleware::class,
                LogContextMiddleware::class,
                RequireSetupAccessMiddleware::class,
                SetLocaleMiddleware::class,
            ],
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password']);

        $exceptions->render(function (AppException $e, Request $request) {
            $status = match (true) {
                $e instanceof UnauthorizedException => 403,
                $e instanceof ValidationFailedException => 422,
                default => 500,
            };

            $message = $e->isUserFacing() ? $e->getMessage() : __('exceptions.unexpected');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $status);
            }

            if ($status === 500) {
                return response()->view('errors.500', ['message' => $message], 500);
            }

            abort($status, $message);
        });

        $exceptions->render(function (ModuleException $e, Request $request) {
            $message = $e->getMessage();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 400);
            }

            abort(400, $message);
        });
    })
    ->create();
