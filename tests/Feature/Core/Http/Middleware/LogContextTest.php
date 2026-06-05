<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Http\Middleware;

use App\Core\Http\Middleware\LogContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;

test('log context middleware pushes context parameters', function () {
    Log::shouldReceive('withContext')
        ->twice()
        ->with(Mockery::on(function ($context) {
            if (isset($context['request_id'])) {
                return $context['method'] === 'GET'
                    && str_contains($context['url'], '/_test_log_context')
                    && $context['ip'] === '127.0.0.1';
            }
            if (isset($context['status'])) {
                return $context['status'] === 200
                    && isset($context['duration_ms']);
            }

            return false;
        }));

    Route::get('/_test_log_context', function () {
        return 'ok';
    })->middleware(LogContext::class);

    $this->get('/_test_log_context');
});
