<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permission\Http\Middleware\CheckRoleMiddleware;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;

uses(LazilyRefreshDatabase::class);

describe('T4B26: role authorization flows', function (): void {
    test('T4B26-FR-RBAC-007: guest web request is redirected to login before protected execution', function (): void {
        $request = Request::create('/admin/users', 'GET');

        $response = app(CheckRoleMiddleware::class)->handle($request, function (): never {
            throw new RuntimeException('protected endpoint executed');
        }, 'admin');

        expect($response->isRedirect())->toBeTrue()
            ->and($response->getTargetUrl())->toBe(route('login'));
    });

    test('T4B26-FR-RBAC-007: authenticated wrong role receives JSON 403 and protected execution is denied', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $request = Request::create('/admin/users', 'GET');
        $request->setUserResolver(fn () => $student);
        $request->headers->set('Accept', 'application/json');

        $response = app(CheckRoleMiddleware::class)->handle($request, function (): never {
            throw new RuntimeException('protected endpoint executed');
        }, 'admin');

        expect($response->getStatusCode())->toBe(403)
            ->and($response->getData(true)['message'])->toContain('Security Access Denied');
    });

    test('T4B26-FR-RBAC-007: matching concrete role reaches the protected endpoint', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $request = Request::create('/admin/users', 'GET');
        $request->setUserResolver(fn () => $admin);
        $executed = false;

        $response = app(CheckRoleMiddleware::class)->handle($request, function () use (&$executed) {
            $executed = true;

            return response('authorized');
        }, 'admin');

        expect($executed)->toBeTrue()
            ->and($response->getContent())->toBe('authorized');
    });
});
