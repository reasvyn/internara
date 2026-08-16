<?php

declare(strict_types=1);

use App\Auth\Permissions\Http\Middleware\CheckRoleMiddleware;
use App\Auth\Permissions\Policies\UserPolicy;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(LazilyRefreshDatabase::class);

function checkRoleRun(Request $request, string ...$roles): Response
{
    $next = fn (Request $r) => new Response('ok');

    return (new CheckRoleMiddleware)->handle($request, $next, ...$roles);
}

test('2CF4Y-FR-MW6/T4B26-FR-AUTH5: CheckRoleMiddleware lets a user with the required role through', function () {
    $user = User::factory()->create()->assignRole('admin');
    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    $response = checkRoleRun($request, 'admin');

    expect($response->getContent())->toBe('ok');
});

test('2CF4Y-FR-MW6/T4B26-FR-AUTH5: CheckRoleMiddleware rejects a user without the required role', function () {
    $user = User::factory()->create()->assignRole('teacher');
    $request = Request::create('/admin');
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => $user);

    $response = checkRoleRun($request, 'admin');

    expect($response->getStatusCode())->toBe(403);
});

test('T4B26-FR-AUTH5: CheckRoleMiddleware aborts with 403 on non-JSON requests', function () {
    $user = User::factory()->create()->assignRole('teacher');
    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    expect(fn () => checkRoleRun($request, 'admin'))->toThrow(HttpException::class);
});

test('T4B26-FR-AUTH5: CheckRoleMiddleware accepts pipe-delimited role lists', function () {
    $user = User::factory()->create()->assignRole('student');
    $request = Request::create('/guardian');
    $request->setUserResolver(fn () => $user);

    $response = checkRoleRun($request, 'admin|student');

    expect($response->getContent())->toBe('ok');
});

test('T4B26-FR-AUTH5: CheckRoleMiddleware rejects unauthenticated requests on JSON', function () {
    $request = Request::create('/admin');
    $request->headers->set('X-Livewire', 'true');
    $request->setUserResolver(fn () => null);

    $response = checkRoleRun($request, 'admin');

    expect($response->getStatusCode())->toBe(401);
});

test('T4B26-FR-AUTH8: UserPolicy is registered manually for the User model', function () {
    $policy = Gate::getPolicyFor(new User);

    expect($policy)->toBeInstanceOf(UserPolicy::class);
});

test('T4B26-FR-AUTH9: super_admin role is normalized to superadmin for storage and lookups', function () {
    $user = User::factory()->create()->assignRole('super_admin');

    expect($user->hasRole('super_admin'))->toBeTrue();
    expect($user->hasRole('superadmin'))->toBeTrue();
    expect($user->getRoleNames()->first())->toBe('superadmin');
});

test('T4B26-FR-AUTH12: every concrete policy class extends BasePolicy', function () {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path(), RecursiveDirectoryIterator::SKIP_DOTS),
    );
    $policies = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        if (! str_contains($file->getPathname(), '/Policies/')) {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false || ! preg_match('/^namespace\s+(.+?);$/m', $content, $match)) {
            continue;
        }

        $class = $match[1].'\\'.$file->getBasename('.php');

        if ($class === BasePolicy::class) {
            continue;
        }

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);
        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait()) {
            continue;
        }

        $policies[] = $class;
    }

    expect($policies)->not->toBeEmpty();

    foreach ($policies as $policy) {
        expect(is_subclass_of($policy, BasePolicy::class))
            ->toBeTrue("{$policy} must extend ".BasePolicy::class);
    }
});
