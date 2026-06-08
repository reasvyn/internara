<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Support;

use App\Core\Models\ActivityLog;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('success face creates logger and writes with info level', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::success('Operation completed')->module('TestModule')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Operation completed' && $e->level === 'info' && ($e->context['module'] ?? '') === 'TestModule'
    );
});

test('info face creates logger and writes with info level', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Information')->module('Test')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Information' && $e->level === 'info'
    );
});

test('warning face logs at warning level', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::warning('Warning message')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Warning message' && $e->level === 'warning'
    );
});

test('error face logs at error level', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::error('Error message')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Error message' && $e->level === 'error'
    );
});

test('system only writes to system log but not activity log', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('System only')->module('Test')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'System only');
    expect(ActivityLog::count())->toBe(0);
});

test('activity only writes to database', function () {
    SmartLogger::info('Activity only')->module('TestModule')->event('test_event')->activityOnly()->save();

    $log = ActivityLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->description)->toBe('Activity only');
    expect($log->log_name)->toBe('TestModule');
    expect($log->event)->toBe('test_event');
});

test('both writes to system and activity logs', function () {
    $user = User::factory()->create();
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Both channels')->for($user)->module('TestModule')->event('both_event')->both()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Both channels');

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->not->toBeNull();
    expect($activityLog->description)->toBe('Both channels');
    expect($activityLog->event)->toBe('both_event');
    expect($activityLog->causer_id)->toBe((string) $user->getKey());
});

test('for method adds causer to activity log', function () {
    $user = User::factory()->create();

    SmartLogger::info('User action')->for($user)->module('Test')->event('causer_test')->activityOnly()->save();

    $log = ActivityLog::latest()->first();
    expect($log->causer_id)->toBe((string) $user->getKey());
    expect($log->causer_type)->toBe($user->getMorphClass());
});

test('about method sets subject in activity log', function () {
    $subject = User::factory()->create();

    SmartLogger::info('Subject action')->about($subject)->module('Test')->event('subject_test')->activityOnly()->save();

    $log = ActivityLog::latest()->first();
    expect($log->subject_id)->toBe((string) $subject->getKey());
    expect($log->subject_type)->toBe($subject->getMorphClass());
});

test('with payload adds payload to system context', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('With payload')->withPayload(['key' => 'value'])->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['key'] ?? '') === 'value'
    );
});

test('with payload adds payload to activity log properties', function () {
    $user = User::factory()->create();

    SmartLogger::info('With payload activity')
        ->for($user)
        ->module('Test')
        ->event('payload_test')
        ->withPayload(['action' => 'update'])
        ->activityOnly()
        ->save();

    $log = ActivityLog::latest()->first();
    expect($log->properties->get('payload'))->toBe(['action' => 'update']);
});

test('channel adds channel to system context', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Channeled')->channel('slack')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['channel'] ?? '') === 'slack'
    );
});

test('pii masking masks sensitive payload', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Masked')
        ->withPayload(['password' => 'secret123', 'email' => 'john@example.com', 'safe_key' => 'visible'])
        ->withPiiMasking()
        ->systemOnly()
        ->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['password'] ?? '') === '***' &&
        ($e->context['payload']['email'] ?? '') === 'jo***@example.com' &&
        ($e->context['payload']['safe_key'] ?? '') === 'visible'
    );
});

test('default mode without causer does not write activity log', function () {
    SmartLogger::info('No causer')->module('Test')->save();

    expect(ActivityLog::count())->toBe(0);
});

test('default mode with causer writes activity log', function () {
    $user = User::factory()->create();

    SmartLogger::info('With causer')->for($user)->module('Test')->save();

    expect(ActivityLog::count())->toBe(1);
    $log = ActivityLog::latest()->first();
    expect($log->causer_id)->toBe((string) $user->getKey());
});

test('event name is passed through to log context', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Event test')->event('user_login')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['event'] ?? '') === 'user_login'
    );
});
