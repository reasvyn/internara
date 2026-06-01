<?php

declare(strict_types=1);

use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Log;

describe('SmartLogger', function () {
    it('logs info message to system log', function () {
        Log::spy();

        SmartLogger::info('System event')
            ->module('testing')
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->with('System event', Mockery::on(fn ($ctx) => isset($ctx['module'])));
    });

    it('logs error level with context', function () {
        Log::spy();

        SmartLogger::error('Something broke', ['code' => 500])
            ->withPayload(['request_id' => 'abc'])
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('error')
            ->with('Something broke', Mockery::on(
                fn ($ctx) => ($ctx['code'] ?? null) === 500 && ($ctx['payload']['request_id'] ?? null) === 'abc'
            ));
    });

    it('masks PII in payload', function () {
        Log::spy();

        SmartLogger::info('User login')
            ->withPayload(['password' => 'secret123', 'email' => 'user@test.com'])
            ->withPiiMasking()
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->with('User login', Mockery::on(function ($ctx) {
                return $ctx['payload']['password'] === '***'
                    && $ctx['payload']['email'] !== 'user@test.com';
            }));
    });

    it('supports warning and success levels', function () {
        Log::spy();

        SmartLogger::warning('Disk low')->systemOnly()->save();
        SmartLogger::success('Task done')->systemOnly()->save();

        Log::shouldHaveReceived('warning')->with('Disk low', Mockery::any());
        Log::shouldHaveReceived('info')->with('Task done', Mockery::any());
    });
});
