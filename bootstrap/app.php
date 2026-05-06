<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'setup.protected' => \App\Http\Middleware\ProtectSetupRouteMiddleware::class,
            'role' => \App\Http\Middleware\CheckRoleMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\RequireSetupAccessMiddleware::class,
            \App\Http\Middleware\SetLocaleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
