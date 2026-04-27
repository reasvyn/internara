<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Integrity & Attribution Verification
|--------------------------------------------------------------------------
|
| This block ensures the application's core metadata is intact and that
| the original author is properly attributed. Tampering with this
| metadata will prevent the application from booting.
*/
(function () {
    $authorIdentity = 'Reas Vyn';
    $path = dirname(__DIR__) . '/app_info.json';

    if (!file_exists($path)) {
        header('HTTP/1.1 403 Forbidden');
        exit('Critical Error: Core system metadata (app_info.json) is missing.');
    }

    $info = json_decode(file_get_contents($path), true);
    if (($info['author']['name'] ?? null) !== $authorIdentity) {
        header('HTTP/1.1 403 Forbidden');
        exit(
            "Attribution Error: Unauthorized author modification detected. This system requires attribution to [{$authorIdentity}]."
        );
    }
})();

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Auth\Http\Middleware\EnsureEmailIsVerified;
use Modules\Core\Localization\Http\Middleware\SetLocale;
use Modules\Exception\Handler;
use Modules\Setup\Http\Middleware\BypassSetupAuthorization;
use Modules\Setup\Http\Middleware\RequireSetupAccess;
use Modules\Status\Middleware\CheckSessionExpiration;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(
            prepend: [RequireSetupAccess::class, BypassSetupAuthorization::class],
            append: [CheckSessionExpiration::class, SetLocale::class],
        );
        $middleware->alias([
            'session.expire' => CheckSessionExpiration::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            // Override Laravel's default verified middleware to allow users without an email
            // address to pass through (they get a soft dashboard banner instead).
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->map(
            ModelNotFoundException::class,
            fn(ModelNotFoundException $e) => Handler::map($e),
        );
    })
    ->create();
