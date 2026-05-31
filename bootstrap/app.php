<?php

declare(strict_types=1);

use App\Domain\Auth\Http\Middleware\AuthThrottleMiddleware;
use App\Domain\Auth\Http\Middleware\CheckRoleMiddleware;
use App\Domain\Core\Exceptions\AppException;
use App\Domain\Core\Exceptions\NotFoundException;
use App\Domain\Core\Exceptions\RateLimitException;
use App\Domain\Core\Exceptions\UnauthorizedException;
use App\Domain\Core\Exceptions\ValidationFailedException;
use App\Domain\Core\Http\Middleware\LogContext;
use App\Domain\Core\Http\Middleware\SecurityHeaders;
use App\Domain\Settings\Http\Middleware\SetLocaleMiddleware;
use App\Domain\Setup\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Domain\Setup\Http\Middleware\RequireSetupAccessMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Domain/Core/Console/Commands',
        __DIR__.'/../app/Domain/Setup/Console/Commands',
        __DIR__.'/../app/Domain/Auth/Console/Commands',
        __DIR__.'/../app/Domain/Admin/Console/Commands',
        __DIR__.'/../app/Domain/User/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setup.protected' => ProtectSetupRouteMiddleware::class,
            'role' => CheckRoleMiddleware::class,
            'auth.throttle' => AuthThrottleMiddleware::class,
        ]);

        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SecurityHeaders::class,
            LogContext::class,
            RequireSetupAccessMiddleware::class,
            SetLocaleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['password', 'password_confirmation', 'current_password']);

        $exceptions->render(function (AppException $e, Request $request) {
            $status = match (true) {
                $e instanceof NotFoundException => 404,
                $e instanceof UnauthorizedException => 403,
                $e instanceof ValidationFailedException => 422,
                $e instanceof RateLimitException => 429,
                default => 500,
            };

            $message = $e->isUserFacing() ? $e->getMessage() : __('An unexpected error occurred.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $status);
            }

            if ($status === 500) {
                return response()->view('errors.500', ['message' => $message], 500);
            }

            abort($status, $message);
        });
    })
    ->create();
