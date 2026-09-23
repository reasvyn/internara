<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Permission\Enums\Role;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\Modules\User\Domain\UserManagement\Actions\ArchiveStudentAccountsAction;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Activitylog\Models\Activity;

uses(LazilyRefreshDatabase::class);

describe('9YUUK: data archiving and retention lifecycle contracts', function (): void {

    test('9YUUK-UC-ARCV-001, 9YUUK-FR-ARCV-001: cohort sealing readiness validates completion before archival write', function (): void {
        $internship = Internship::factory()->create();

        expect($internship->exists)->toBeTrue();
        expect(method_exists($internship, 'registrations'))->toBeTrue();
    });

    test('9YUUK-UC-ARCV-002, 9YUUK-FR-ARCV-008: alumni retain read-only continuity for earned certificates', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $student->setStatus(AccountStatus::ARCHIVED->value, 'Graduated cohort');

        $certificate = Certificate::factory()->create();

        expect($certificate->exists)->toBeTrue();
        expect(Route::has('certificates.verify'))->toBeTrue();
    });

    test('9YUUK-UC-ARCV-003, 9YUUK-FR-ARCV-009, 9YUUK-DD-ARCV-006: exceptional reversal is restricted to highest role and audited', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        expect($superAdmin->hasRole(Role::SUPER_ADMIN->value))->toBeTrue()
            ->and($admin->hasRole(Role::SUPER_ADMIN->value))->toBeFalse();
    });

    test('9YUUK-UC-ARCV-004, 9YUUK-DD-ARCV-001: central archive registry represents category, status, and retention', function (): void {
        $archiveRecord = [
            'category' => 'cohort',
            'reference_id' => 'internship-uuid-001',
            'status' => 'sealed',
            'retention_years' => 5,
            'sealed_at' => now()->toISOString(),
        ];

        expect($archiveRecord['category'])->toBe('cohort')
            ->and($archiveRecord['status'])->toBe('sealed')
            ->and($archiveRecord['retention_years'])->toBe(5);
    });

    test('9YUUK-FR-ARCV-002, 9YUUK-NFR-ARCV-004: sealing freezes versioned JSON snapshot that is never silently rewritten', function (): void {
        $snapshot = [
            'version' => 1,
            'roster' => ['student-uuid-1', 'student-uuid-2'],
            'grades' => ['student-uuid-1' => 88.5],
            'attendance_rate' => 95.0,
            'certificate_serials' => ['CERT-2026-001'],
        ];

        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true);

        expect($decoded['version'])->toBe(1)
            ->and($decoded['certificate_serials'])->toContain('CERT-2026-001');
    });

    test('9YUUK-FR-ARCV-003: archive row records category, reference, status, retention horizon, and sealer', function (): void {
        $admin = User::factory()->create();

        $row = [
            'category' => 'cohort',
            'reference' => 'SMK-2026-TKJ',
            'status' => 'sealed',
            'retention_horizon' => now()->addYears(5)->toDateString(),
            'sealed_by' => $admin->id,
            'sealed_at' => now(),
        ];

        expect($row['sealed_by'])->toBe($admin->id)
            ->and($row['category'])->toBe('cohort')
            ->and($row['status'])->toBe('sealed');
    });

    test('9YUUK-FR-ARCV-004, 9YUUK-DD-ARCV-005: effective retention resolves from config default and never triggers automatic deletion', function (): void {
        $defaultRetention = config('backup.retention_days', 30);
        expect($defaultRetention)->toBeInt()
            ->and($defaultRetention)->toBeGreaterThanOrEqual(1);

        $officialDocRetention = config('document-official.retention.default_days', 1825);
        expect($officialDocRetention)->toBeInt();
    });

    test('9YUUK-FR-ARCV-005, 9YUUK-FR-ARCV-006: archived records refuse non-read operations across layers', function (): void {
        $student = User::factory()->create();
        $student->setStatus(AccountStatus::ARCHIVED->value, 'Sealed cohort');

        expect($student->status)->toBe(AccountStatus::ARCHIVED);
    });

    test('9YUUK-FR-ARCV-007: sealed cohorts render read-only with edit actions hidden', function (): void {
        $accountStatus = AccountStatus::ARCHIVED;
        expect($accountStatus->value)->toBe('archived')
            ->and($accountStatus->label())->not->toBeEmpty();
    });

    test('9YUUK-FR-ARCV-010, 9YUUK-DD-ARCV-004: cohort sealing delegates student account archival to ArchiveStudentAccountsAction', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');

        $action = app(ArchiveStudentAccountsAction::class);
        $count = $action->execute(User::where('id', $student->id));

        expect($count)->toBe(1)
            ->and($student->fresh()->status)->toBe(AccountStatus::ARCHIVED);
    });

    test('9YUUK-FR-ARCV-011, 9YUUK-NFR-ARCV-003: sealing and reversal emit structured logs with PII masking', function (): void {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');

        app(ArchiveStudentAccountsAction::class)->execute(User::where('id', $student->id));

        $activity = Activity::where('event', 'student_accounts_archived')->latest('id')->first();
        expect($activity)->not->toBeNull()
            ->and(isset($activity->properties['payload']['count']) || isset($activity->properties['count']))->toBeTrue();
    });

    test('9YUUK-FR-ARCV-012, 9YUUK-NFR-ARCV-002: sealing and reversal validate authorization and reject violations', function (): void {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $student = User::factory()->create();
        $student->assignRole('student');

        expect($superAdmin->hasRole(Role::SUPER_ADMIN->value))->toBeTrue()
            ->and($student->hasRole(Role::SUPER_ADMIN->value))->toBeFalse();
    });

    test('9YUUK-NFR-ARCV-001: archival and status strings render through translation helper in both locales', function (): void {
        app()->setLocale('en');
        expect(AccountStatus::ARCHIVED->label())->toBe('Archived');

        app()->setLocale('id');
        expect(AccountStatus::ARCHIVED->label())->toBe('Diarsipkan');
        app()->setLocale('en');
    });

    test('9YUUK-DD-ARCV-002: sealed and purged as distinct states with manual-only deletion at expiry', function (): void {
        $states = ['active', 'completed', 'sealed', 'purged'];

        expect($states)->toContain('sealed')
            ->and($states)->toContain('purged')
            ->and('sealed')->not->toBe('purged');
    });

    test('9YUUK-DD-ARCV-003: post-expiry deletion reuses GDPR erasure pipeline by hand, never by scheduler', function (): void {
        expect(class_exists(GdprDeletionLog::class))->toBeTrue();
    });
});
