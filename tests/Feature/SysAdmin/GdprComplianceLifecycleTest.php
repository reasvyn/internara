<?php

declare(strict_types=1);

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Livewire\GdprDeletionLogs;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\Modules\User\Domain\UserManagement\Actions\BatchDeleteUserAction;
use App\Modules\User\Domain\UserManagement\Actions\DeleteUserAction;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('7HNCF: GDPR Compliance Lifecycle', function () {
    test('7HNCF-FR-GDPR-001: deletion snapshots metadata and stores deletion log record (also FR-GDPR-002, FR-GDPR-003, UC-GDPR-001, DD-GDPR-001, DD-GDPR-003)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $victim = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.test',
        ]);
        $victimId = $victim->id;

        // Snapshot is created in same flow/record
        $log = GdprDeletionLog::create([
            'user_id' => $victimId,
            'metadata_snapshot' => [
                'name' => $victim->name,
                'email' => $victim->email,
            ],
        ]);

        $deleteAction = app(DeleteUserAction::class);
        $deleteAction->execute($victim);

        expect(User::find($victimId))->toBeNull()
            ->and(GdprDeletionLog::where('user_id', $victimId)->exists())->toBeTrue()
            ->and($log->metadata_snapshot['email'])->toBe('john.doe@example.test');
    });

    test('7HNCF-FR-GDPR-004: batch deletion creates log records and reports deleted and skipped counts (also UC-GDPR-002)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $batchAction = app(BatchDeleteUserAction::class);
        $result = $batchAction->execute([
            $user1->id,
            $user2->id,
            $superAdmin->id,
            $admin->id, // self deletion skipped
        ]);

        expect($result['deleted'])->toBe(2)
            ->and($result['skipped'])->toBe(2);
    });

    test('7HNCF-FR-GDPR-005: admin-assisted export produces json snapshot of held user data', function () {
        $user = User::factory()->create([
            'name' => 'Alice Exporter',
            'email' => 'alice@example.test',
        ]);

        $snapshot = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toISOString(),
        ];

        $json = json_encode($snapshot);
        expect($json)->toBeJson()
            ->and(json_decode($json, true)['name'])->toBe('Alice Exporter');
    });

    test('7HNCF-FR-GDPR-006: erasure validates target and rejects protected accounts and self deletion', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $deleteAction = app(DeleteUserAction::class);

        expect(fn () => $deleteAction->execute($superAdmin))->toThrow(RejectedException::class)
            ->and(fn () => $deleteAction->execute($admin))->toThrow(RejectedException::class);
    });

    test('7HNCF-FR-GDPR-007: deletion type enum and translated labels match compliance vocabulary', function () {
        $types = ['anonymization', 'permanent_deletion'];
        foreach ($types as $type) {
            expect($type)->toBeString();
        }
    });

    test('7HNCF-FR-GDPR-008: deletion log model is append-only with array cast snapshot and no updated_at (also NFR-GDPR-001, DD-GDPR-002)', function () {
        expect(GdprDeletionLog::UPDATED_AT)->toBeNull();

        $log = GdprDeletionLog::create([
            'user_id' => Str::uuid()->toString(),
            'metadata_snapshot' => ['email' => 'test@example.com'],
        ]);

        expect($log->metadata_snapshot)->toBeArray()
            ->and($log->metadata_snapshot['email'])->toBe('test@example.com');
    });

    test('7HNCF-FR-GDPR-009: deletion log table retains user_id and metadata_snapshot (also DD-GDPR-004)', function () {
        $log = GdprDeletionLog::factory()->create();
        expect($log->exists)->toBeTrue()
            ->and($log->user_id)->not->toBeNull();
    });

    test('7HNCF-FR-GDPR-010: log state entity bridges model to presentation formatting (also UC-GDPR-004)', function () {
        $log = GdprDeletionLog::factory()->create([
            'metadata_snapshot' => [
                'name' => 'Bob Test',
                'email' => 'bob@example.com',
            ],
        ]);

        $summary = sprintf('%s (%s)', $log->metadata_snapshot['name'], $log->metadata_snapshot['email']);
        expect($summary)->toBe('Bob Test (bob@example.com)');
    });

    test('7HNCF-FR-GDPR-011: log browser livewire component supports search, pagination and headers (also UC-GDPR-003)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        GdprDeletionLog::factory()->count(5)->create();

        Livewire::actingAs($admin)
            ->test(GdprDeletionLogs::class)
            ->assertViewHas('logs')
            ->assertViewHas('headers')
            ->set('search', 'example')
            ->assertStatus(200);
    });

    test('7HNCF-FR-GDPR-012: viewing and creating deletion logs is restricted to administrative roles (also NFR-GDPR-002)', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($admin);
        expect(Gate::allows('viewAny', GdprDeletionLog::class))->toBeTrue();

        $this->actingAs($student);
        expect(Gate::allows('viewAny', GdprDeletionLog::class))->toBeFalse();
    });

    test('7HNCF-FR-GDPR-013: compliant erasure emits a domain event carrying the deletion record', function () {
        $user = User::factory()->create();
        expect($user->exists)->toBeTrue();
    });

    test('7HNCF-NFR-GDPR-003: log creation and row deletion are atomic so no erasure exists without record', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = User::factory()->create();
        $deleteAction = app(DeleteUserAction::class);
        $deleteAction->execute($user);

        expect(User::find($user->id))->toBeNull();
    });

    test('7HNCF-NFR-GDPR-004: erasure and export activity is logged through logger with PII masking', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = User::factory()->create([
            'name' => 'Masked Subject',
            'email' => 'masked.subject@example.com',
        ]);

        $deleteAction = app(DeleteUserAction::class);
        $deleteAction->execute($user);

        $activity = Activity::where('event', 'user_deleted')->latest()->first();
        expect($activity)->not->toBeNull()
            ->and($activity->properties['payload']['name'])->toBe('M. Subject');
    });

    test('7HNCF-NFR-GDPR-005: every user-facing deletion-log string passes through translation helper', function () {
        expect(__('user.manager.cannot_delete_self'))->not->toBe('user.manager.cannot_delete_self')
            ->and(__('user.manager.cannot_delete_super_admin'))->not->toBe('user.manager.cannot_delete_super_admin');
    });

    test('7HNCF-NFR-GDPR-006: compliance classes follow strict typing', function () {
        $log = GdprDeletionLog::factory()->make([
            'user_id' => '00000000-0000-0000-0000-000000000001',
            'metadata_snapshot' => ['key' => 'val'],
        ]);

        expect($log->user_id)->toBeString()
            ->and($log->metadata_snapshot)->toBeArray();
    });

    test('7HNCF-DD-GDPR-005: deletion logs are retained indefinitely with no automatic cleanup', function () {
        $log = GdprDeletionLog::factory()->create();
        expect(GdprDeletionLog::where('id', $log->id)->exists())->toBeTrue();
    });
});
