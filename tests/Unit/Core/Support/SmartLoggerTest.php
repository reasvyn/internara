<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Support;

use App\Core\Support\SmartLogger;

test('success face creates logger with success face', function () {
    $logger = SmartLogger::success('Operation completed');

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('info face creates logger with info face', function () {
    $logger = SmartLogger::info('Information');

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('warning face creates logger with warning face', function () {
    $logger = SmartLogger::warning('Warning message');

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('error face creates logger with error face', function () {
    $logger = SmartLogger::error('Error message');

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('fluent api returns self for chaining', function () {
    $result = SmartLogger::info('test')
        ->for(null)
        ->about(null)
        ->withPayload([])
        ->module('Test')
        ->event('test_event')
        ->channel('slack')
        ->systemOnly()
        ->activityOnly()
        ->both()
        ->withPiiMasking();

    expect($result)->toBeInstanceOf(SmartLogger::class);
});

test('system only disables activity channel', function () {
    $logger = SmartLogger::info('test')->systemOnly();

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('activity only disables system channel', function () {
    $logger = SmartLogger::info('test')->activityOnly();

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('both enables both channels', function () {
    $logger = SmartLogger::info('test')->both();

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('with pii masking enables masking flag', function () {
    $logger = SmartLogger::info('test')->withPiiMasking();

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});

test('event accepts string event name', function () {
    $logger = SmartLogger::info('test')->event('user_login');

    expect($logger)->toBeInstanceOf(SmartLogger::class);
});
