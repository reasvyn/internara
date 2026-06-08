<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Support;

use App\Core\Events\BaseEvent;
use App\Core\Models\ActivityLog;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

class TestUserCreatedEvent extends BaseEvent
{
    public function __construct(public readonly string $userId, public readonly string $email) {}

    public function eventName(): string
    {
        return 'user_created';
    }
}

uses(RefreshDatabase::class);

test('smart logger can write system log', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Hello System')->module('TestModule')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Hello System' && ($e->context['module'] ?? '') === 'TestModule'
    );
});

test('smart logger can write database activity log', function () {
    SmartLogger::success('Hello Activity')
        ->module('TestModule')
        ->event('test_event')
        ->activityOnly()
        ->save();

    $log = ActivityLog::latest()->first();

    expect($log)->not->toBeNull();
    expect($log->description)->toBe('Hello Activity');
    expect($log->log_name)->toBe('TestModule');
    expect($log->event)->toBe('test_event');
});

test('smart logger warning face logs at warning level', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::warning('Warning message')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Warning message');
});

test('smart logger error face logs at error level', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::error('Error message')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Error message');
});

test('smart logger for method adds causer context to system log', function () {
    $user = User::factory()->create();
    Event::fake([MessageLogged::class]);

    SmartLogger::info('User action')->for($user)->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'User action' && ($e->context['user_id'] ?? null) === $user->getKey()
    );
});

test('smart logger with payload adds payload to context', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('With payload')
        ->withPayload(['key' => 'value'])
        ->systemOnly()
        ->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload'] ?? []) === ['key' => 'value']
    );
});

test('smart logger channel adds channel to context', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Channeled')->channel('slack')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['channel'] ?? '') === 'slack'
    );
});

test('smart logger both writes to system and activity logs', function () {
    $user = User::factory()->create();
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Both channels')
        ->for($user)
        ->module('TestModule')
        ->event('test_both_event')
        ->both()
        ->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Both channels');

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->not->toBeNull();
    expect($activityLog->description)->toBe('Both channels');
    expect($activityLog->causer_id)->toBe((string) $user->getKey());
});

test('smart logger with pii masking masks sensitive payload', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Masked action')
        ->withPayload([
            'password' => 'secret123',
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'safe_key' => 'visible',
        ])
        ->withPiiMasking()
        ->systemOnly()
        ->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['password'] ?? '') === '***' &&
        ($e->context['payload']['email'] ?? '') === 'jo***@example.com' &&
        ($e->context['payload']['name'] ?? '') === 'J. Doe' &&
        ($e->context['payload']['safe_key'] ?? '') === 'visible'
    );
});

test('smart logger about method sets subject in activity log', function () {
    $subject = User::factory()->create();

    SmartLogger::info('Subject action')
        ->about($subject)
        ->module('TestModule')
        ->event('test_subject_event')
        ->activityOnly()
        ->save();

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->not->toBeNull();
    expect($activityLog->subject_id)->toBe((string) $subject->getKey());
    expect($activityLog->subject_type)->toBe($subject->getMorphClass());
});

test('smart logger for method sets causer in activity log', function () {
    $user = User::factory()->create();

    SmartLogger::info('Causer action')
        ->for($user)
        ->module('TestModule')
        ->event('test_causer_event')
        ->activityOnly()
        ->save();

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->not->toBeNull();
    expect($activityLog->causer_id)->toBe((string) $user->getKey());
    expect($activityLog->causer_type)->toBe($user->getMorphClass());
});

test('smart logger adds event description translation when available', function () {
    Event::fake([MessageLogged::class]);

    SmartLogger::info('Login action')->event('login_success')->systemOnly()->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => isset($e->context['event_description'])
    );
});

test('smart logger dispatches base event and writes activity log', function () {
    Event::fake();

    $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

    SmartLogger::info('User created')->event($event)->module('TestModule')->activityOnly()->save();

    Event::assertDispatched(TestUserCreatedEvent::class);

    $log = ActivityLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->event)->toBe('user_created');
    expect($log->description)->toBe('User created');
});

test('smart logger default mode without causer does not write activity log', function () {
    SmartLogger::info('No causer default')->module('TestModule')->save();

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->toBeNull();
});

test('smart logger default mode with causer writes activity log', function () {
    $user = User::factory()->create();

    SmartLogger::info('With causer default')->for($user)->module('TestModule')->save();

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->not->toBeNull();
    expect($activityLog->causer_id)->toBe((string) $user->getKey());
});

test('smart logger with base event and explicit payload merges correctly', function () {
    Event::fake([MessageLogged::class]);

    $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

    SmartLogger::info('User created')
        ->event($event)
        ->withPayload(['source' => 'admin'])
        ->systemOnly()
        ->save();

    Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['userId'] ?? '') === 'uuid-123' &&
        ($e->context['payload']['email'] ?? '') === 'test@example.com' &&
        ($e->context['payload']['source'] ?? '') === 'admin'
    );
});
