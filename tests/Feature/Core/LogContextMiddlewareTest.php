<?php

declare(strict_types=1);

use App\Modules\Core\Http\Middleware\LogContextMiddleware;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

uses(LazilyRefreshDatabase::class);

function runLogContext(?User $user = null): array
{
    $captured = captureLogs();

    $request = Request::create('http://localhost/admin/users', 'POST');
    if ($user !== null) {
        $request->setUserResolver(fn () => $user);
    }

    $response = app(LogContextMiddleware::class)->handle(
        $request,
        function () {
            Log::info('downstream probe');

            return response('ok', 200);
        },
    );

    Log::info('after probe');

    $records = $captured->values();

    return [$response, $records];
}

describe('89SRA: LogContextMiddleware channel behavior', function (): void {
    test('89SRA-FR-LOG-051: every request carries a UUID request_id', function (): void {
        [, $records] = runLogContext();

        $after = $records->firstWhere('message', 'after probe');

        expect($after->context['request_id'])
            ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
    });

    test('89SRA-FR-LOG-052: method, url and ip land in the context', function (): void {
        [, $records] = runLogContext();

        $after = $records->firstWhere('message', 'after probe');

        expect($after->context['method'])->toBe('POST')
            ->and($after->context['url'])->toBe('http://localhost/admin/users')
            ->and($after->context['ip'])->toBe('127.0.0.1');
    });

    test('89SRA-FR-LOG-053: authenticated requests add user_id and user_role', function (): void {
        $user = User::factory()->create();
        $user->assignRole('admin');

        [, $records] = runLogContext($user);

        $after = $records->firstWhere('message', 'after probe');

        expect($after->context['user_id'])->toBe($user->id)
            ->and($after->context['user_role'])->toBe('admin');
    });

    test('89SRA-FR-LOG-054: duration_ms and status are attached after the response', function (): void {
        [$response, $records] = runLogContext();

        $after = $records->firstWhere('message', 'after probe');

        expect($response->getStatusCode())->toBe(200)
            ->and($after->context['status'])->toBe(200)
            ->and($after->context['duration_ms'])->toBeNumeric();
    });

    test('89SRA-FR-LOG-055: downstream entries inherit context without manual passing', function (): void {
        [, $records] = runLogContext();

        $downstream = $records->firstWhere('message', 'downstream probe');
        $after = $records->firstWhere('message', 'after probe');

        expect($downstream->context['request_id'])->toBe($after->context['request_id'])
            ->and($downstream->context)->not->toHaveKey('duration_ms');
    });
});
