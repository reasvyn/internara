<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Domain\Academics\Aggregates\AcademicYear\Models\AcademicYear;
use App\Domain\User\Enums\Role;
use Carbon\Carbon;
use Database\Seeders\SetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(RefreshDatabase::class);

test('SetupSeeder creates all 5 standard roles', function () {
    $seeder = new SetupSeeder;
    $seeder->run();

    expect(RoleModel::where('name', Role::SUPER_ADMIN->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::ADMIN->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::STUDENT->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::TEACHER->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::SUPERVISOR->value)->exists())->toBeTrue();
});

test('SetupSeeder does not create functional roles', function () {
    $seeder = new SetupSeeder;
    $seeder->run();

    expect(RoleModel::where('name', 'func_mentor')->exists())->toBeFalse();
    expect(RoleModel::where('name', 'func_mentee')->exists())->toBeFalse();
});

test('SetupSeeder seeds AcademicYear YY-1/YY when month is June or earlier', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 15));

    $seeder = new SetupSeeder;
    $seeder->run();

    $activeYear = AcademicYear::where('is_active', true)->first();

    expect($activeYear)->not->toBeNull();
    expect($activeYear->name)->toBe('2025/2026');
    expect($activeYear->start_date->format('Y-m-d'))->toBe('2025-07-01');
    expect($activeYear->end_date->format('Y-m-d'))->toBe('2026-06-30');

    Carbon::setTestNow();
});

test('SetupSeeder seeds AcademicYear YY/YY+1 when month is July or later', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 15));

    $seeder = new SetupSeeder;
    $seeder->run();

    $activeYear = AcademicYear::where('is_active', true)->first();

    expect($activeYear)->not->toBeNull();
    expect($activeYear->name)->toBe('2026/2027');
    expect($activeYear->start_date->format('Y-m-d'))->toBe('2026-07-01');
    expect($activeYear->end_date->format('Y-m-d'))->toBe('2027-06-30');

    Carbon::setTestNow();
});

test('SetupSeeder is idempotent', function () {
    $seeder = new SetupSeeder;
    $seeder->run();
    $seeder->run();
    $seeder->run();

    expect(RoleModel::count())->toBe(5);
    expect(AcademicYear::count())->toBe(1);
});

afterEach(function () {
    Carbon::setTestNow();
});
