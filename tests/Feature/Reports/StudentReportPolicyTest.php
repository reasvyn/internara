<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('StudentReportPolicy', function () {
    test('allows admin on every ability', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $report = StudentReport::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('viewAny', StudentReport::class))->toBeTrue()
            ->and(Gate::allows('view', $report))->toBeTrue()
            ->and(Gate::allows('create', StudentReport::class))->toBeTrue()
            ->and(Gate::allows('update', $report))->toBeTrue()
            ->and(Gate::allows('delete', $report))->toBeTrue()
            ->and(Gate::allows('finalize', $report))->toBeTrue()
            ->and(Gate::allows('calculate', $report))->toBeTrue()
            ->and(Gate::allows('download', $report))->toBeTrue();
    });

    test('denies teacher on every ability', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $report = StudentReport::factory()->create();

        $this->actingAs($teacher);

        expect(Gate::allows('viewAny', StudentReport::class))->toBeFalse()
            ->and(Gate::allows('view', $report))->toBeFalse()
            ->and(Gate::allows('create', StudentReport::class))->toBeFalse()
            ->and(Gate::allows('update', $report))->toBeFalse()
            ->and(Gate::allows('delete', $report))->toBeFalse()
            ->and(Gate::allows('finalize', $report))->toBeFalse()
            ->and(Gate::allows('calculate', $report))->toBeFalse()
            ->and(Gate::allows('download', $report))->toBeFalse();
    });

    test('denies student on every ability', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $report = StudentReport::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', StudentReport::class))->toBeFalse()
            ->and(Gate::allows('view', $report))->toBeFalse()
            ->and(Gate::allows('create', StudentReport::class))->toBeFalse()
            ->and(Gate::allows('update', $report))->toBeFalse()
            ->and(Gate::allows('delete', $report))->toBeFalse()
            ->and(Gate::allows('finalize', $report))->toBeFalse()
            ->and(Gate::allows('calculate', $report))->toBeFalse()
            ->and(Gate::allows('download', $report))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $report = StudentReport::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', StudentReport::class))->toBeTrue()
            ->and(Gate::allows('finalize', $report))->toBeTrue();
    });
});
