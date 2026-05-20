<?php

declare(strict_types=1);

use App\Domain\Core\Support\SmartLogger;

describe('SmartLogger', function () {
    it('creates instance via success factory', function () {
        $logger = SmartLogger::success('User registered');

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('creates instance via info factory', function () {
        $logger = SmartLogger::info('Profile updated');

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('creates instance via warning factory', function () {
        $logger = SmartLogger::warning('Disk space low');

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('creates instance via error factory', function () {
        $logger = SmartLogger::error('Payment failed');

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('supports fluent setters chain', function () {
        $logger = SmartLogger::info('test')
            ->for(null)
            ->about(null)
            ->withPayload([])
            ->module('users')
            ->event('created')
            ->channel('slack');

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('allows routing via systemOnly', function () {
        $logger = SmartLogger::info('test')->systemOnly();

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('allows routing via activityOnly', function () {
        $logger = SmartLogger::info('test')->activityOnly();

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('allows routing via both', function () {
        $logger = SmartLogger::info('test')->both();

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('supports PII masking flag via withPiiMasking', function () {
        $logger = SmartLogger::info('test')->withPiiMasking();

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });

    it('chains full fluent API', function () {
        $logger = SmartLogger::error('Something broke', ['code' => 500])
            ->withPayload(['request_id' => 'abc'])
            ->module('billing')
            ->event('payment_failed')
            ->channel('ops')
            ->systemOnly()
            ->withPiiMasking();

        expect($logger)->toBeInstanceOf(SmartLogger::class);
    });
});
