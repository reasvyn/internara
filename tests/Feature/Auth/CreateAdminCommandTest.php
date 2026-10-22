<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Domain\Permission\Enums\Role;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: CreateAdminCommand CLI', function (): void {
    test('YB7RG-FR-AUTH-001: creates super admin from CLI arguments and saves recovery key', function (): void {
        expect(User::role(Role::SUPER_ADMIN->value)->count())->toBe(0);

        $exitCode = Artisan::call('admin:create', [
            'email' => 'admin@internara.local',
            'password' => 'SecurePass123!',
        ]);

        expect($exitCode)->toBe(0);

        $output = Artisan::output();
        expect($output)->toContain('admin@internara.local');

        $user = User::where('email', 'admin@internara.local')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole(Role::SUPER_ADMIN))->toBeTrue()
            ->and($user->status)->toBe(AccountStatus::PROTECTED)
            ->and(Hash::check('SecurePass123!', $user->password))->toBeTrue();

        $this->assertDatabaseHas('settings', [
            'key' => 'setup.install_recovery_key',
        ]);
    });

    test('YB7RG-FR-AUTH-001: refuses creation when a super admin already exists', function (): void {
        $existing = User::factory()->create();
        $existing->assignRole(Role::SUPER_ADMIN);

        $exitCode = Artisan::call('admin:create', [
            'email' => 'duplicate@internara.local',
            'password' => 'AnotherPass123!',
        ]);

        expect($exitCode)->toBe(1);

        $output = Artisan::output();
        expect($output)->toContain(__('superadmin.create.already_exists'));

        expect(User::where('email', 'duplicate@internara.local')->exists())->toBeFalse();
    });
});
