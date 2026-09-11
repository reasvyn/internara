<?php

declare(strict_types=1);

use App\Modules\Core\Services\SmartLogger;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

function activityCount(string $description): int
{
    return DB::table('activity_log')->where('description', $description)->count();
}

describe('89SRA: SmartLogger dual-channel routing and masking', function (): void {
    test('89SRA-FR-LOG-005: both writes to the system log and the activity table', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('dual probe message')
            ->for($user)
            ->module('Core')
            ->both()
            ->save();

        expect($captured->firstWhere('message', 'dual probe message'))->not->toBeNull()
            ->and(activityCount('dual probe message'))->toBe(1);
    });

    test('89SRA-FR-LOG-007: systemOnly reaches the laravel log and skips the table', function (): void {
        $captured = captureLogs();

        SmartLogger::info('system-only probe')
            ->module('Core')
            ->systemOnly()
            ->save();

        $record = $captured->firstWhere('message', 'system-only probe');

        expect($record)->not->toBeNull()
            ->and($record->context['module'])->toBe('Core')
            ->and(activityCount('system-only probe'))->toBe(0);
    });

    test('89SRA-FR-LOG-008: activityOnly lands exactly one row in activity_log', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('activity-only probe')
            ->for($user)
            ->module('Core')
            ->activityOnly()
            ->save();

        $row = DB::table('activity_log')->where('description', 'activity-only probe')->first();

        expect($row)->not->toBeNull()
            ->and($row->log_name)->toBe('Core')
            ->and((string) $row->causer_id)->toBe((string) $user->getKey())
            ->and((string) $row->causer_type)->toBe(User::class);
    });

    test('89SRA-FR-LOG-012: both without a causer skips the activity table', function (): void {
        $captured = captureLogs();

        SmartLogger::info('causerless probe')
            ->module('Core')
            ->both()
            ->save();

        expect($captured->firstWhere('message', 'causerless probe'))->not->toBeNull()
            ->and(activityCount('causerless probe'))->toBe(0);
    });

    test('89SRA-FR-LOG-030: PII is masked before either sink', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('masking probe')
            ->for($user)
            ->module('Core')
            ->withPayload([
                'password' => 'supersecret-value',
                'email' => 'joko.santoso@example.sch.id',
                'school' => 'SMKN 2 Bandung',
            ])
            ->both()
            ->save();

        $row = DB::table('activity_log')->where('description', 'masking probe')->first();
        $properties = json_decode($row->properties, true);
        $record = $captured->firstWhere('message', 'masking probe');

        expect($properties['payload']['password'])->toBe('***')
            ->and($properties['payload']['email'])->toContain('***')
            ->and($properties['payload']['email'])->not->toContain('joko.santoso')
            ->and($properties['payload']['school'])->toBe('SMKN 2 Bandung')
            ->and($record->context['payload']['password'])->toBe('***');
    });

    test('89SRA-FR-LOG-006: masking applies by default without explicit opt-in', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('default-mask probe')
            ->for($user)
            ->module('Core')
            ->withPayload(['token' => 'raw-token-value'])
            ->both()
            ->save();

        $record = $captured->firstWhere('message', 'default-mask probe');

        expect($record->context['payload']['token'])->toBe('***');
    });

    test('89SRA-FR-LOG-002: severity levels map onto system log levels', function (): void {
        $captured = captureLogs();

        SmartLogger::success('severity success')->module('Core')->systemOnly()->save();
        SmartLogger::info('severity info')->module('Core')->systemOnly()->save();
        SmartLogger::warning('severity warning')->module('Core')->systemOnly()->save();
        SmartLogger::error('severity error')->module('Core')->systemOnly()->save();

        expect($captured->firstWhere('message', 'severity success')->level)->toBe('info')
            ->and($captured->firstWhere('message', 'severity info')->level)->toBe('info')
            ->and($captured->firstWhere('message', 'severity warning')->level)->toBe('warning')
            ->and($captured->firstWhere('message', 'severity error')->level)->toBe('error');
    });

    test('89SRA-FR-LOG-004: save runs the pipeline and merges event, payload and module', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('pipeline probe')
            ->for($user)
            ->module('Core')
            ->event('token.missing')
            ->withPayload(['attempt' => 3])
            ->both()
            ->save();

        $record = $captured->firstWhere('message', 'pipeline probe');

        expect($record->context['event'])->toBe('token.missing')
            ->and($record->context['module'])->toBe('Core')
            ->and($record->context['payload'])->toMatchArray(['attempt' => 3])
            ->and(activityCount('pipeline probe'))->toBe(1);
    });

    test('89SRA-FR-LOG-009: activity failure never breaks the caller', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        Schema::drop('activity_log');

        SmartLogger::info('resilient probe')
            ->for($user)
            ->module('Core')
            ->both()
            ->save();

        expect($captured->firstWhere('message', 'Failed to write activity log'))->not->toBeNull()
            ->and($captured->firstWhere('message', 'resilient probe'))->not->toBeNull();
    });
});
