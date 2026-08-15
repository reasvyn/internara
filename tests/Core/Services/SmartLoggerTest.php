<?php

declare(strict_types=1);

use App\Core\Events\BaseEvent;
use App\Core\Services\SmartLogger;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

final class TestLoggerEvent extends BaseEvent
{
    public function __construct(
        public Model $subject,
        public string $action,
    ) {}

    public function eventName(): string
    {
        return 'test.subject_updated';
    }
}

it('89SRA-FR-SL1: SmartLogger is only reachable through its static factories', function () {
    expect((new ReflectionClass(SmartLogger::class))->getConstructor()->isPrivate())->toBeTrue();
});

it('89SRA-FR-SL2/FR-DC1: system channel maps success to info and error to error', function () {
    $logs = captureLogs();

    SmartLogger::success('Operation succeeded')->systemOnly()->save();
    SmartLogger::error('Operation failed')->systemOnly()->save();

    expect($logs->where('level', 'info')->pluck('message'))->toContain('Operation succeeded');
    expect($logs->where('level', 'error')->pluck('message'))->toContain('Operation failed');
});

it('89SRA-FR-SL3: for() and about() attach the causer and subject context', function () {
    $logs = captureLogs();
    $user = User::factory()->create();
    $subject = User::factory()->create();

    SmartLogger::info('test message')->for($user)->about($subject)->systemOnly()->save();

    $entry = $logs->firstWhere('message', 'test message');
    expect($entry)->not->toBeNull();
    expect($entry->context['user_id'])->toBe($user->id);
});

it('89SRA-FR-SL6: PII masking scrubs sensitive payload keys in the system log', function () {
    $logs = captureLogs();

    SmartLogger::info('test message')
        ->withPayload(['password' => 'secret', 'count' => 1])
        ->systemOnly()
        ->save();

    $entry = $logs->firstWhere('message', 'test message');
    expect($entry->context['payload']['password'])->toBe('***');
    expect($entry->context['payload']['count'])->toBe(1);
});

it('89SRA-FR-DC5: channel() tags the system log context', function () {
    $logs = captureLogs();

    SmartLogger::info('test message')->channel('cron')->systemOnly()->save();

    $entry = $logs->firstWhere('message', 'test message');
    expect($entry->context['channel'])->toBe('cron');
});

it('89SRA-FR-DC6: systemOnly() writes to the system channel and never to the activity log', function () {
    $logs = captureLogs();

    SmartLogger::info('test message')->systemOnly()->save();

    expect($logs->where('level', 'info')->pluck('message'))->toContain('test message');
    expect(DB::table('activity_log')->where('description', 'test message')->count())->toBe(0);
});

it('89SRA-FR-DC6: activityOnly() writes an activity row even without a causer', function () {
    SmartLogger::info('test message')->activityOnly()->save();

    expect(DB::table('activity_log')->where('description', 'test message')->count())->toBe(1);
});

it('89SRA-FR-DC3: both() writes to the system and activity channels with a causer', function () {
    $logs = captureLogs();
    $user = User::factory()->create();

    SmartLogger::info('test message')->for($user)->both()->save();

    expect($logs->where('level', 'info')->pluck('message'))->toContain('test message');
    expect(DB::table('activity_log')->where('description', 'test message')->where('causer_id', $user->id)->count())->toBe(1);
});

it('89SRA-FR-EI1: event() accepts a dot-notation event name and tags the system log', function () {
    $logs = captureLogs();

    SmartLogger::info('test message')->event('module.discover.completed')->systemOnly()->save();

    $entry = $logs->firstWhere('message', 'test message');
    expect($entry->context['event'])->toBe('module.discover.completed');
});

it('89SRA-FR-EI2: save() dispatches a BaseEvent through the event dispatcher', function () {
    Event::fake();
    $subject = User::factory()->create();

    SmartLogger::info('test message')->event(new TestLoggerEvent($subject, 'updated'))->save();

    Event::assertDispatched(TestLoggerEvent::class);
});

it('89SRA-FR-EI3/FR-EI4: event payload is merged and PII-masked before writing', function () {
    $logs = captureLogs();
    $subject = User::factory()->create();

    SmartLogger::info('test message')
        ->event(new TestLoggerEvent($subject, 'updated'))
        ->withPayload(['password' => 'secret'])
        ->systemOnly()
        ->save();

    $entry = $logs->firstWhere('message', 'test message');
    expect($entry->context['event'])->toBe('test.subject_updated');
    expect($entry->context['payload']['subject_id'])->toBe($subject->id);
    expect($entry->context['payload']['action'])->toBe('updated');
    expect($entry->context['payload']['password'])->toBe('***');
});

it('89SRA-FR-TR1/FR-TR2: translation context resolves for the active and alternate locale', function () {
    $logs = captureLogs();
    App::setLocale('en');

    SmartLogger::info('test message')->event('module.discover.completed')->systemOnly()->save();

    $entry = $logs->firstWhere('message', 'test message');
    expect($entry->context['event_description'])->toBe('Module service discovery has completed successfully.');
    expect($entry->context['event_description_id'])->toBe('Penemuan layanan module telah selesai dengan sukses.');
});
