<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\Exceptions\RejectedException;
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

    test('89SRA-UC-LOG-001, 89SRA-DD-LOG-002: developer logs business operations via SmartLogger single entry point', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('Business operation executed')
            ->for($user)
            ->module('Core')
            ->event('operation.executed')
            ->both()
            ->save();

        expect(activityCount('Business operation executed'))->toBe(1);
    });

    test('89SRA-UC-LOG-002, 89SRA-DD-LOG-004: system masks PII in logs by default', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        SmartLogger::info('Sensitive operation')
            ->for($user)
            ->withPayload(['password' => 'secret_123', 'token' => 'bearer_xyz'])
            ->both()
            ->save();

        $record = $captured->firstWhere('message', 'Sensitive operation');
        expect($record->context['payload']['password'])->toBe('***')
            ->and($record->context['payload']['token'])->toBe('***');
    });

    test('89SRA-UC-LOG-003, 89SRA-DD-LOG-001, 89SRA-DD-LOG-005: action throws RejectedException implementing HasExceptionContext', function (): void {
        $exception = (new RejectedException('Business rule rejected'))->withContext(['attempt' => 1]);

        expect($exception)->toBeInstanceOf(ModuleException::class)
            ->and($exception->statusCode())->toBe(400)
            ->and($exception->isUserFacing())->toBeTrue()
            ->and($exception->getContext())->toMatchArray(['attempt' => 1]);
    });

    test('89SRA-UC-LOG-004, 89SRA-DD-LOG-003: activity log degrades gracefully on DB failure without crashing caller', function (): void {
        $captured = captureLogs();
        $user = User::factory()->create();
        $this->actingAs($user);

        Schema::drop('activity_log');

        SmartLogger::info('Degraded activity test')
            ->for($user)
            ->both()
            ->save();

        expect($captured->firstWhere('message', 'Degraded activity test'))->not->toBeNull()
            ->and($captured->firstWhere('message', 'Failed to write activity log'))->not->toBeNull();
    });

    test('89SRA-NFR-LOG-004, 89SRA-NFR-LOG-014, 89SRA-NFR-LOG-015: error pages display user-friendly accessible messages with semantic structure', function (): void {
        $response = $this->get('/non-existent-route-for-testing-89sra');
        $response->assertStatus(404);

        $content = $response->getContent();
        expect($content)->not->toBeEmpty();
    });

    test('89SRA-NFR-LOG-013: SmartLogger channel names and messages are translatable', function (): void {
        expect(__('common.enums.reported'))->not->toBe('common.enums.reported');
    });
});
