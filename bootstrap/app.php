<?php

declare(strict_types=1);

use App\Domain\Auth\Http\Middleware\CheckRoleMiddleware;
use App\Domain\Settings\Http\Middleware\SetLocaleMiddleware;
use App\Domain\Setup\Http\Middleware\ProtectSetupRouteMiddleware;
use App\Domain\Setup\Http\Middleware\RequireSetupAccessMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setup.protected' => ProtectSetupRouteMiddleware::class,
            'role' => CheckRoleMiddleware::class,
        ]);

        $middleware->web(append: [
            RequireSetupAccessMiddleware::class,
            SetLocaleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
