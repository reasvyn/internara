<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('GdprDeletionLogPolicy', function () {
    test('allows admin on viewAny, view, and create', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $log = GdprDeletionLog::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('viewAny', GdprDeletionLog::class))->toBeTrue()
            ->and(Gate::allows('view', $log))->toBeTrue()
            ->and(Gate::allows('create', GdprDeletionLog::class))->toBeTrue();
    });

    test('denies teacher on viewAny, view, and create', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $log = GdprDeletionLog::factory()->create();

        $this->actingAs($teacher);

        expect(Gate::allows('viewAny', GdprDeletionLog::class))->toBeFalse()
            ->and(Gate::allows('view', $log))->toBeFalse()
            ->and(Gate::allows('create', GdprDeletionLog::class))->toBeFalse();
    });

    test('denies student on viewAny, view, and create', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $log = GdprDeletionLog::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', GdprDeletionLog::class))->toBeFalse()
            ->and(Gate::allows('view', $log))->toBeFalse()
            ->and(Gate::allows('create', GdprDeletionLog::class))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $log = GdprDeletionLog::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', GdprDeletionLog::class))->toBeTrue()
            ->and(Gate::allows('view', $log))->toBeTrue();
    });
});
