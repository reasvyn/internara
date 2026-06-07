<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Http\Middleware;

use App\Core\Http\Middleware\LogContext;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;

uses(RefreshDatabase::class);

test('log context middleware pushes context parameters', function () {
    Log::shouldReceive('withContext')->twice()->with(
        Mockery::on(function ($context) {
            if (isset($context['request_id'])) {
                return $context['method'] === 'GET' &&
                    str_contains($context['url'], '/_test_log_context') &&
                    $context['ip'] === '127.0.0.1';
            }
            if (isset($context['status'])) {
                return $context['status'] === 200 && isset($context['duration_ms']);
            }

            return false;
        }),
    );

    Route::get('/_test_log_context', function () {
        return 'ok';
    })->middleware(LogContext::class);

    $this->get('/_test_log_context');
});

test('log context includes authenticated user context', function () {
    Log::shouldReceive('withContext')->twice()->with(
        Mockery::on(function ($context) {
            if (isset($context['request_id'])) {
                return isset($context['user_id']);
            }

            return true;
        }),
    );

    $user = User::factory()->create();

    Route::get('/_test_auth_log_context', function () {
        return 'ok';
    })->middleware(LogContext::class);

    $this->actingAs($user)->get('/_test_auth_log_context');
});

test('log context request id is a valid uuid', function () {
    Log::shouldReceive('withContext')->twice()->with(
        Mockery::on(function ($context) {
            if (isset($context['request_id'])) {
                return preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
                    $context['request_id'],
                ) === 1;
            }

            return true;
        }),
    );

    Route::get('/_test_uuid_log_context', function () {
        return 'ok';
    })->middleware(LogContext::class);

    $this->get('/_test_uuid_log_context');
});

test('log context duration ms is numeric', function () {
    Log::shouldReceive('withContext')->twice()->with(
        Mockery::on(function ($context) {
            if (isset($context['duration_ms'])) {
                return is_numeric($context['duration_ms']) && $context['duration_ms'] > 0;
            }

            return true;
        }),
    );

    Route::get('/_test_duration_log_context', function () {
        return 'ok';
    })->middleware(LogContext::class);

    $this->get('/_test_duration_log_context');
});

test('log context records response status', function () {
    Log::shouldReceive('withContext')->twice()->with(
        Mockery::on(function ($context) {
            if (isset($context['status'])) {
                return $context['status'] === 404;
            }

            return true;
        }),
    );

    Route::get('/_test_404_log_context', function () {
        abort(404);
    })->middleware(LogContext::class);

    $this->get('/_test_404_log_context');
});
