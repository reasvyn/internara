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
    $path = dirname(__DIR__).'/app_info.json';

    if (! file_exists($path)) {
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

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(
            prepend: [
                \Modules\Setup\Http\Middleware\RequireSetupAccess::class,
                \Modules\Setup\Http\Middleware\BypassSetupAuthorization::class,
            ],
            append: [\Modules\Core\Localization\Http\Middleware\SetLocale::class],
        );
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->map(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            fn (
                \Illuminate\Database\Eloquent\ModelNotFoundException $e,
            ) => \Modules\Exception\Handler::map($e),
        );
    })
    ->create();
