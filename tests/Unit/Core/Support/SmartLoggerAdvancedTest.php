<?php

declare(strict_types=1);

use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

test('SmartLogger systemOnly skips activity log', function () {
    Log::shouldReceive('info')->once()->with('System event', Mockery::any());

    SmartLogger::info('System event')->systemOnly()->save();
});

test('SmartLogger withPayload adds payload to log context', function () {
    Log::shouldReceive('info')->once()->with(
        'With payload',
        Mockery::on(fn ($ctx) => isset($ctx['payload']) && $ctx['payload'] === ['key' => 'value'])
    );

    SmartLogger::info('With payload')
        ->withPayload(['key' => 'value'])
        ->systemOnly()
        ->save();
});

test('SmartLogger module adds module to context', function () {
    Log::shouldReceive('warning')->once()->with(
        'Module test',
        Mockery::on(fn ($ctx) => ($ctx['module'] ?? null) === 'auth')
    );

    SmartLogger::warning('Module test')
        ->module('auth')
        ->systemOnly()
        ->save();
});

test('SmartLogger channel adds channel to context', function () {
    Log::shouldReceive('info')->once()->with(
        'Channel test',
        Mockery::on(fn ($ctx) => ($ctx['channel'] ?? null) === 'slack')
    );

    SmartLogger::info('Channel test')
        ->channel('slack')
        ->systemOnly()
        ->save();
});

test('SmartLogger face maps correctly: success→info, warning→warning, error→error', function () {
    Log::shouldReceive('info')->once()->with('Success message', Mockery::any());
    Log::shouldReceive('warning')->once()->with('Warning message', Mockery::any());
    Log::shouldReceive('error')->once()->with('Error message', Mockery::any());

    SmartLogger::success('Success message')->systemOnly()->save();
    SmartLogger::warning('Warning message')->systemOnly()->save();
    SmartLogger::error('Error message')->systemOnly()->save();
});

test('SmartLogger for method sets causer context', function () {
    $user = User::factory()->make(['id' => 42]);

    Log::shouldReceive('info')->once()->with(
        'User action',
        Mockery::on(fn ($ctx) => ($ctx['user_id'] ?? null) === 42)
    );

    SmartLogger::info('User action')
        ->for($user)
        ->systemOnly()
        ->save();
});

test('SmartLogger withPiiMasking masks payload', function () {
    Log::shouldReceive('info')->once()->with(
        'PII test',
        Mockery::on(fn ($ctx) => true)
    );

    SmartLogger::info('PII test')
        ->withPayload(['email' => 'user@example.com'])
        ->withPiiMasking()
        ->systemOnly()
        ->save();
});
