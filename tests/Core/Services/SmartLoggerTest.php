<?php

declare(strict_types=1);

namespace Tests\Core\Services;

use App\Core\Events\BaseEvent;
use App\Core\Models\ActivityLog;
use App\Core\Services\SmartLogger;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

class TestUserCreatedEvent extends BaseEvent
{
    public function __construct(public readonly string $userId, public readonly string $email) {}

    public function eventName(): string
    {
        return 'user_created';
    }
}

describe('log levels', function () {
    it('success face creates logger and writes with info level', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::success('Operation completed')->module('TestModule')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Operation completed' && $e->level === 'info' && ($e->context['module'] ?? '') === 'TestModule');
    });

    it('info face logs at info level', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('Information')->module('Test')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Information' && $e->level === 'info');
    });

    it('warning face logs at warning level', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::warning('Warning message')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Warning message' && $e->level === 'warning');
    });

    it('error face logs at error level', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::error('Error message')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'Error message' && $e->level === 'error');
    });
});

describe('channel routing', function () {
    it('system only writes to system log but not activity log', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('System only')->module('Test')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => $e->message === 'System only');
        expect(ActivityLog::count())->toBe(0);
    });

    it('activity only writes to database', function () {
        SmartLogger::info('Activity only')->module('TestModule')->event('test_event')->activityOnly()->save();

        $log = ActivityLog::latest()->first();
        expect($log)->not->toBeNull();
        expect($log->description)->toBe('Activity only');
        expect($log->log_name)->toBe('TestModule');
        expect($log->event)->toBe('test_event');
    });

    it('both writes to system and activity logs', function () {
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

    it('default mode without causer does not write activity log', function () {
        SmartLogger::info('No causer')->module('Test')->save();

        expect(ActivityLog::count())->toBe(0);
    });

    it('default mode with causer writes activity log', function () {
        $user = User::factory()->create();

        SmartLogger::info('With causer')->for($user)->module('Test')->save();

        expect(ActivityLog::count())->toBe(1);
        $log = ActivityLog::latest()->first();
        expect($log->causer_id)->toBe((string) $user->getKey());
    });
});

describe('causer and subject', function () {
    it('for method adds causer to activity log', function () {
        $user = User::factory()->create();

        SmartLogger::info('User action')->for($user)->module('Test')->event('causer_test')->activityOnly()->save();

        $log = ActivityLog::latest()->first();
        expect($log->causer_id)->toBe((string) $user->getKey());
        expect($log->causer_type)->toBe($user->getMorphClass());
    });

    it('about method sets subject in activity log', function () {
        $subject = User::factory()->create();

        SmartLogger::info('Subject action')->about($subject)->module('Test')->event('subject_test')->activityOnly()->save();

        $log = ActivityLog::latest()->first();
        expect($log->subject_id)->toBe((string) $subject->getKey());
        expect($log->subject_type)->toBe($subject->getMorphClass());
    });
});

describe('payload handling', function () {
    it('with payload adds payload to system context', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('With payload')->withPayload(['key' => 'value'])->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['key'] ?? '') === 'value');
    });

    it('with payload adds payload to activity log properties', function () {
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

    it('channel adds channel to system context', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('Channeled')->channel('slack')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['channel'] ?? '') === 'slack');
    });

    it('event name is passed through to log context', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('Event test')->event('user_login')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['event'] ?? '') === 'user_login');
    });

    it('adds event description translation when available', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('Login action')->event('login_success')->systemOnly()->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => isset($e->context['event_description']));
    });
});

describe('pii masking', function () {
    it('masks sensitive payload fields', function () {
        Event::fake([MessageLogged::class]);

        SmartLogger::info('Masked')
            ->withPayload(['password' => 'secret123', 'email' => 'john@example.com', 'safe_key' => 'visible'])
            ->withPiiMasking()
            ->systemOnly()
            ->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['password'] ?? '') === '***' &&
            ($e->context['payload']['email'] ?? '') === 'jo***@example.com' &&
            ($e->context['payload']['safe_key'] ?? '') === 'visible');
    });

    it('masks sensitive fields including name', function () {
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
            ($e->context['payload']['safe_key'] ?? '') === 'visible');
    });
});

describe('base event integration', function () {
    it('extracts payload from base event without dispatching it', function () {
        Event::fake();

        $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

        SmartLogger::info('User created')->event($event)->module('TestModule')->activityOnly()->save();

        Event::assertNotDispatched(TestUserCreatedEvent::class);

        $log = ActivityLog::latest()->first();
        expect($log)->not->toBeNull();
        expect($log->event)->toBe('user_created');
        expect($log->description)->toBe('User created');
    });

    it('with base event and explicit payload merges correctly', function () {
        Event::fake([MessageLogged::class]);

        $event = new TestUserCreatedEvent('uuid-123', 'test@example.com');

        SmartLogger::info('User created')
            ->event($event)
            ->withPayload(['source' => 'admin'])
            ->systemOnly()
            ->save();

        Event::assertDispatched(MessageLogged::class, fn (MessageLogged $e) => ($e->context['payload']['userId'] ?? '') === 'uuid-123' &&
            ($e->context['payload']['email'] ?? '') === 'test@example.com' &&
            ($e->context['payload']['source'] ?? '') === 'admin');
    });
});
