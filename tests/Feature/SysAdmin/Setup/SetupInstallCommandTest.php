<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin\Setup;

use App\Academics\AcademicYear\Models\AcademicYear;
use App\SysAdmin\Setup\Models\Setup;
use App\User\Enums\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Spatie\Permission\Models\Role as RoleModel;

uses(DatabaseMigrations::class);

beforeEach(function () {
    Setup::factory()->create(['is_installed' => true]);
});

test('setup:install warns and exits if system is already installed', function () {
    $this->artisan('setup:install')
        ->expectsOutputToContain(__('setup.cli.already_installed'))
        ->assertFailed();
});

test('setup:install runs audit only with --check-only without provisioning', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --check-only')
        ->expectsOutputToContain(__('setup.cli.check_only_complete'))
        ->assertSuccessful();
});

test('setup:install with --force provisions the system', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();
});

test('setup:install seeds all 5 standard roles', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    expect(RoleModel::where('name', Role::SUPER_ADMIN->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::ADMIN->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::STUDENT->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::TEACHER->value)->exists())->toBeTrue();
    expect(RoleModel::where('name', Role::SUPERVISOR->value)->exists())->toBeTrue();
});

test('setup:install does not seed functional roles', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    expect(RoleModel::where('name', 'func_mentor')->exists())->toBeFalse();
    expect(RoleModel::where('name', 'func_mentee')->exists())->toBeFalse();
});

test('setup:install seeds AcademicYear', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    expect(AcademicYear::count())->toBe(1);
    expect(AcademicYear::where('is_active', true)->exists())->toBeTrue();
});

test('setup:install generates a setup token', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
});

test('setup:install token expiry is within configured window', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    $setup = Setup::first();
    $expectedExpiry = (int) config('setup.token.expiry_minutes', 60);

    expect($setup->token_expires_at->diffInMinutes(now()))->toBeLessThanOrEqual($expectedExpiry + 1);
});

test('setup:install does not mark system as fully installed (set by wizard finalize)', function () {
    Setup::query()->update(['is_installed' => false]);

    $this->artisan('setup:install --force')
        ->assertSuccessful();

    // is_installed is only set to true by FinalizeSetupAction
    // when the web wizard completes
    expect(Setup::state()->isInstalled())->toBeFalse();
});

afterEach(function () {
    Carbon::setTestNow();
});
