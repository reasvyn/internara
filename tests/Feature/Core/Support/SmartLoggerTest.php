<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Support;

use App\Core\Events\BaseEvent;
use App\Core\Models\ActivityLog;
use App\Core\Support\SmartLogger;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;

class TestUserCreatedEvent extends BaseEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
    ) {}

    public function eventName(): string
    {
        return 'user_created';
    }
}

uses(RefreshDatabase::class);

test('smart logger can write system log', function () {
    $log = Log::spy();

    SmartLogger::info('Hello System')
        ->module('TestModule')
        ->systemOnly()
        ->save();

    $log->shouldHaveReceived('info')
        ->once()
        ->with('Hello System', Mockery::on(function ($context) {
            return ($context['module'] ?? '') === 'TestModule';
        }));
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
    $log = Log::spy();

    SmartLogger::warning('Warning message')->systemOnly()->save();

    $log->shouldHaveReceived('warning')
        ->once()
        ->with('Warning message', Mockery::type('array'));
});

test('smart logger error face logs at error level', function () {
    $log = Log::spy();

    SmartLogger::error('Error message')->systemOnly()->save();

    $log->shouldHaveReceived('error')
        ->once()
        ->with('Error message', Mockery::type('array'));
});

test('smart logger for method adds causer context to system log', function () {
    $user = User::factory()->create();
    $log = Log::spy();

    SmartLogger::info('User action')->for($user)->systemOnly()->save();

    $log->shouldHaveReceived('info')
        ->once()
        ->with('User action', Mockery::on(fn ($c) => ($c['user_id'] ?? null) === $user->getKey()));
});

test('smart logger with payload adds payload to context', function () {
    $log = Log::spy();

    SmartLogger::info('With payload')
        ->withPayload(['key' => 'value'])
        ->systemOnly()
        ->save();

    $log->shouldHaveReceived('info')
        ->once()
        ->with('With payload', Mockery::on(fn ($c) => ($c['payload'] ?? []) === ['key' => 'value']));
});

test('smart logger channel adds channel to context', function () {
    $log = Log::spy();

    SmartLogger::info('Channeled')
        ->channel('slack')
        ->systemOnly()
        ->save();

    $log->shouldHaveReceived('info')
        ->once()
        ->with('Channeled', Mockery::on(fn ($c) => ($c['channel'] ?? '') === 'slack'));
});

test('smart logger both writes to system and activity logs', function () {
    $user = User::factory()->create();
    $log = Log::spy();

    SmartLogger::info('Both channels')
        ->for($user)
        ->module('TestModule')
        ->event('test_both_event')
        ->both()
        ->save();

    $log->shouldHaveReceived('info')->once();

    $activityLog = ActivityLog::latest()->first();
    expect($activityLog)->not->toBeNull();
    expect($activityLog->description)->toBe('Both channels');
    expect($activityLog->causer_id)->toBe((string) $user->getKey());
});

test('smart logger with pii masking masks sensitive payload', function () {
    $log = Log::spy();

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

    $log->shouldHaveReceived('info')
        ->once()
        ->with('Masked action', Mockery::on(function ($context) {
            return ($context['payload']['password'] ?? '') === '***'
                && ($context['payload']['email'] ?? '') === 'jo***@example.com'
                && ($context['payload']['name'] ?? '') === 'J. Doe'
                && ($context['payload']['safe_key'] ?? '') === 'visible';
        }));
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
    Log::shouldReceive('info')
        ->once()
        ->with('Login action', Mockery::on(function ($context) {
            return isset($context['event_description']);
        }));

    SmartLogger::info('Login action')
        ->event('login_success')
        ->systemOnly()
        ->save();
});

test('smart logger dispatches base event and writes activity log', function () {
    Event::fake();

    $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

    SmartLogger::info('User created')
        ->event($event)
        ->module('TestModule')
        ->activityOnly()
        ->save();

    Event::assertDispatched(TestUserCreatedEvent::class);

    $log = ActivityLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->event)->toBe('user_created');
    expect($log->description)->toBe('User created');
});

test('smart logger with base event and explicit payload merges correctly', function () {
    $logSpy = Log::spy();

    $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

    SmartLogger::info('User created')
        ->event($event)
        ->withPayload(['source' => 'admin'])
        ->systemOnly()
        ->save();

    $logSpy->shouldHaveReceived('info')
        ->once()
        ->with('User created', Mockery::on(function ($context) {
            return ($context['payload']['userId'] ?? '') === 'uuid-123'
                && ($context['payload']['email'] ?? '') === 'test@example.com'
                && ($context['payload']['source'] ?? '') === 'admin';
        }));
});
