<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\SysAdmin\Domain\Backups\Models\Backup;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Gate;

describe('BackupPolicy', function () {
    test('allows admin on every ability', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $backup = Backup::factory()->create();

        $this->actingAs($admin);

        expect(Gate::allows('viewAny', Backup::class))->toBeTrue()
            ->and(Gate::allows('view', $backup))->toBeTrue()
            ->and(Gate::allows('create', Backup::class))->toBeTrue()
            ->and(Gate::allows('delete', $backup))->toBeTrue();
    });

    test('denies teacher on every ability', function () {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $backup = Backup::factory()->create();

        $this->actingAs($teacher);

        expect(Gate::allows('viewAny', Backup::class))->toBeFalse()
            ->and(Gate::allows('view', $backup))->toBeFalse()
            ->and(Gate::allows('create', Backup::class))->toBeFalse()
            ->and(Gate::allows('delete', $backup))->toBeFalse();
    });

    test('denies student on every ability', function () {
        $student = User::factory()->create();
        $student->assignRole('student');
        $backup = Backup::factory()->create();

        $this->actingAs($student);

        expect(Gate::allows('viewAny', Backup::class))->toBeFalse()
            ->and(Gate::allows('view', $backup))->toBeFalse()
            ->and(Gate::allows('create', Backup::class))->toBeFalse()
            ->and(Gate::allows('delete', $backup))->toBeFalse();
    });

    test('allows superadmin via before() bypass', function () {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $backup = Backup::factory()->create();

        $this->actingAs($superadmin);

        expect(Gate::allows('viewAny', Backup::class))->toBeTrue()
            ->and(Gate::allows('delete', $backup))->toBeTrue();
    });
});
