<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Academics\Department\Models\Department;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Models\Registration;
use App\Partners\Company\Models\Company;
use App\Settings\Models\Setting;
use App\User\Models\User;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\Support\DummyData;

uses(LazilyRefreshDatabase::class);

it('NFR-S1: DummySeeder refuses to run in production', function () {
    app()->detectEnvironment(fn () => 'production');

    try {
        expect(fn () => app(DummySeeder::class)->run())
            ->toThrow(RuntimeException::class, __('dummy.production_guard'));
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});

it('FR-H1/FR-H5/UC-2/FR-C7/DD-8/FR-H12/NFR-S2: generates a coherent dataset with deterministic demo accounts', function () {
    $counts = DummyData::make()->run();

    // FR-H1 — per-entity counts returned, matching the database.
    expect($counts)->toHaveKey('users');
    expect($counts)->toHaveKey('registrations');
    expect($counts)->toHaveKey('academic_years');
    expect($counts['users'])->toBe(1 + config('dummy.accounts.teacher_count') + config('dummy.accounts.supervisor_count') + config('dummy.accounts.student_count'));
    expect(User::count())->toBe($counts['users']);
    expect(Registration::count())->toBe(30);

    // UC-2 / FR-C7 — deterministic demo accounts for every role.
    foreach (DummyData::demoAccounts() as $email) {
        expect(User::firstWhere('email', $email))->not->toBeNull();
    }

    $admin = User::firstWhere('email', config('dummy.accounts.admin_email'));
    expect($admin)->not->toBeNull();

    // NFR-S2 — passwords are hashed, never plaintext.
    expect(Hash::check(config('dummy.password'), $admin->password))->toBeTrue();
    expect(Hash::check(config('dummy.password'), User::firstWhere('email', 'student24@example.com')->password))->toBeTrue();

    // DD-8 — no superadmin among the demo accounts.
    expect(User::role('superadmin')->exists())->toBeFalse();

    // FR-H12 — every placement's filled_quota matches its active registrations.
    Placement::query()->each(function (Placement $placement): void {
        $active = Registration::query()
            ->where('placement_id', $placement->id)
            ->where('status', 'active')
            ->count();

        expect($placement->filled_quota)->toBe($active);
    });

    // FR-H5 — a second run is a no-op (idempotent).
    expect(DummyData::make()->run())->toBe([]);
    expect(User::count())->toBe(1 + config('dummy.accounts.teacher_count') + config('dummy.accounts.supervisor_count') + config('dummy.accounts.student_count'));
});

it('FR-E1/FR-E2/FR-E4/FR-E5/FR-H14/NFR-U1: DummySeeder is the opt-in entry point with base-data gating and a summary', function () {
    // Start from an empty base state to prove the seeder bootstraps it.
    DB::table('roles')->delete();
    DB::table('settings')->delete();
    DB::table('academic_years')->delete();

    Artisan::call('db:seed', ['--class' => DummySeeder::class]);

    // FR-E4 / FR-H14 — base data re-seeded when absent and reused when present.
    expect(Role::count())->toBe(5);
    expect(Setting::count())->toBeGreaterThan(0);
    expect(AcademicYear::where('is_active', true)->count())->toBe(1);

    // FR-E5 / NFR-U1 — bilingual per-entity summary printed via __().
    $output = Artisan::output();
    expect($output)->toContain(__('dummy.complete'));
    expect($output)->toContain(__('dummy.summary_header'));
    expect($output)->toContain(__('dummy.entities.users'));
    expect($output)->toContain('admin@example.com');

    Artisan::call('db:seed', ['--class' => DummySeeder::class]);

    // FR-E4 — no duplication on a second run.
    expect(Role::count())->toBe(5);
    expect(AcademicYear::count())->toBe(2);
    expect(Setting::count())->toBeGreaterThan(0);
});

it('FR-E4/FR-H14: gates each base seeder independently when base data is only partially present', function () {
    // Roles exist (from setUp) — prove RolePermissionSeeder is skipped by deleting one role
    // and asserting it is NOT re-created, while settings/years (absent) are still seeded.
    DB::table('roles')->where('name', 'superadmin')->delete();
    DB::table('settings')->delete();
    DB::table('academic_years')->delete();

    Artisan::call('db:seed', ['--class' => DummySeeder::class]);

    // Roles untouched → RolePermissionSeeder skipped (roles exist).
    expect(Role::count())->toBe(4);
    // Settings and years were absent → their seeders ran.
    expect(Setting::count())->toBeGreaterThan(0);
    expect(AcademicYear::where('is_active', true)->count())->toBe(1);
});

it('FR-H13/NFR-R2: a mid-run failure rolls back the entire dataset', function () {
    Hash::shouldReceive('make')->andThrow(new RuntimeException('hash failure'));

    expect(fn () => DummyData::make()->run())
        ->toThrow(RuntimeException::class, 'hash failure');

    expect(User::count())->toBe(0);
    expect(AcademicYear::count())->toBe(0);
    expect(Department::count())->toBe(0);
    expect(Company::count())->toBe(0);
    expect(Placement::count())->toBe(0);
    expect(Registration::count())->toBe(0);
});
