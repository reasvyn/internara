<?php

declare(strict_types=1);

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Core\Services\SmartLogger;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

describe('89SRA: SmartLogger pipeline, resilience and privacy', function (): void {
    test('89SRA-FR-LOG-003: the fluent chain composes into a single save', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $subject = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('fluent chain probe')
            ->for($user)
            ->about($subject)
            ->withPayload(['attempt' => 3])
            ->withContext(['source' => 'spec-probe'])
            ->module('Core')
            ->event('login_success')
            ->channel('audit-trail')
            ->both()
            ->save();

        $record = $captured->firstWhere('message', 'fluent chain probe');
        $row = DB::table('activity_log')->where('description', 'fluent chain probe')->first();

        expect($record->context['module'])->toBe('Core')
            ->and($record->context['event'])->toBe('login_success')
            ->and($record->context['payload'])->toMatchArray(['attempt' => 3])
            ->and($record->context['source'])->toBe('spec-probe')
            ->and($record->context['channel'])->toBe('audit-trail')
            ->and($record->context['user_id'])->toBe($user->getKey())
            ->and($row->log_name)->toBe('Core')
            ->and((string) $row->subject_id)->toBe((string) $subject->getKey())
            ->and((string) $row->subject_type)->toBe(User::class);
    });

    test('89SRA-FR-LOG-010 + 89SRA-NFR-LOG-005 + 89SRA-NFR-LOG-006: lost audit rows never fail the caller and leave one diagnostic', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        Schema::drop('activity_log');

        SmartLogger::info('degraded probe')
            ->for($user)
            ->module('Core')
            ->event('probe_diag_event')
            ->both()
            ->save();

        $diagnostic = $captured->firstWhere('message', 'Failed to write activity log');

        expect($diagnostic)->not->toBeNull()
            ->and($diagnostic->level)->toBe('error')
            ->and($diagnostic->context['module'])->toBe('Core')
            ->and($diagnostic->context['event'])->toBe('probe_diag_event')
            ->and($diagnostic->context['error'])->toContain('activity_log')
            ->and($captured->firstWhere('message', 'degraded probe'))->not->toBeNull();
    });

    test('89SRA-FR-LOG-011 + 89SRA-NFR-LOG-007: an unwritable system log surfaces instead of vanishing', function (): void {
        Log::shouldReceive('info')->once()->andThrow(new RuntimeException('disk full'));

        expect(fn (): mixed => SmartLogger::info('loud failure probe')->module('Core')->systemOnly()->save())
            ->toThrow('disk full');
    });

    test('89SRA-FR-LOG-014: save dispatches a passed BaseEvent through Laravel', function (): void {
        $captured = captureLogs();
        $dispatched = [];

        $loginEvent = new class extends BaseEvent
        {
            public int $attempt = 1;

            public function eventName(): string
            {
                return 'login_success';
            }
        };

        Event::listen($loginEvent::class, function (BaseEvent $event) use (&$dispatched): void {
            $dispatched[] = $event;
        });

        SmartLogger::info('dispatch probe')->event($loginEvent)->module('Core')->systemOnly()->save();

        $record = $captured->firstWhere('message', 'dispatch probe');

        expect($dispatched)->toHaveCount(1)
            ->and($dispatched[0])->toBe($loginEvent)
            ->and($record)->not->toBeNull()
            ->and($record->context['payload'])->toMatchArray(['attempt' => 1]);
    });

    test('89SRA-NFR-LOG-001: no raw PII reaches either sink', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('pii sweep probe')
            ->for($user)
            ->module('Core')
            ->withPayload([
                'password' => 'S3cret-pw-99',
                'token' => 'tok_live_abc123',
                'email' => 'joko.secret99@example.sch.id',
                'phone' => '+6281234567890',
            ])
            ->both()
            ->save();

        $record = $captured->firstWhere('message', 'pii sweep probe');
        $row = DB::table('activity_log')->where('description', 'pii sweep probe')->first();
        $systemHaystack = json_encode($record->context);
        $tableHaystack = $row->properties;

        foreach (['S3cret-pw-99', 'tok_live_abc123', 'joko.secret99', '+6281234567890'] as $raw) {
            expect($systemHaystack)->not->toContain($raw)
                ->and($tableHaystack)->not->toContain($raw);
        }

        expect($record->context['payload']['password'])->toBe('***')
            ->and($record->context['payload']['token'])->toBe('***');
    });
});
