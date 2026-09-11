<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\LogContextMiddleware;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

describe('2CF4Y: middleware pipeline context and resilience', function (): void {
    test('2CF4Y-FR-MID-002: LogContextMiddleware attaches identity, timing and request envelope', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $user->assignRole('teacher');

        $request = Request::create('http://localhost/admin/users', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = app(LogContextMiddleware::class)->handle(
            $request,
            fn () => response('ok', 200),
        );

        Log::info('pipeline probe');
        $record = $captured->firstWhere('message', 'pipeline probe');

        expect($response->getStatusCode())->toBe(200)
            ->and($record->context['request_id'])->not->toBeEmpty()
            ->and($record->context['user_id'])->toBe($user->id)
            ->and($record->context['user_role'])->toBe('teacher')
            ->and($record->context['status'])->toBe(200)
            ->and($record->context['duration_ms'])->toBeNumeric();
    });

    test('2CF4Y-NFR-MID-003: request succeeds when the log sink is unwritable', function (): void {
        config()->set('logging.default', 'single');
        config()->set('logging.channels.single.path', '/proc/internara-unwritable-xyz/laravel.log');

        $request = Request::create('http://localhost/admin/users', 'GET');

        $response = app(LogContextMiddleware::class)->handle(
            $request,
            fn () => response('ok', 200),
        );

        expect($response->getStatusCode())->toBe(200)
            ->and($response->getContent())->toBe('ok');
    });
});
