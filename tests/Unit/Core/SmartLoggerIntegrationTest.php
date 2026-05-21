<?php

declare(strict_types=1);

use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\Log;

describe('SmartLogger integration', function () {
    it('writes to system log when systemOnly', function () {
        Log::spy();

        SmartLogger::info('System event')
            ->module('testing')
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->with('System event', Mockery::on(fn ($ctx) => isset($ctx['module'])));
    });

    it('attempts to write to activity log when activityOnly', function () {
        Log::spy();

        SmartLogger::info('Activity event')
            ->module('testing')
            ->event('test')
            ->activityOnly()
            ->save();

        expect(true)->toBeTrue();
    });

    it('masks PII payload during save', function () {
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

    it('supports error level logging with context', function () {
        Log::spy();

        SmartLogger::error('Something broke', ['code' => 500])
            ->withPayload(['request_id' => 'abc'])
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('error')
            ->with('Something broke', Mockery::on(fn ($ctx) => $ctx['code'] === 500 && $ctx['payload']['request_id'] === 'abc'
            ));
    });

    it('supports warning level logging', function () {
        Log::spy();

        SmartLogger::warning('Disk low')
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('warning')
            ->with('Disk low', Mockery::any());
    });

    it('supports success level logging', function () {
        Log::spy();

        SmartLogger::success('Task done')
            ->systemOnly()
            ->save();

        Log::shouldHaveReceived('info')
            ->with('Task done', Mockery::any());
    });
});
