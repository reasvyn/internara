<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Domain\Permission\Enums\Role;
use App\Modules\Auth\Domain\SuperAdmin\Actions\InitializeSuperAdminAction;
use App\Modules\User\Enums\AccountStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

describe('Auth super admin initialization action', function (): void {
    test('95EVB-FR-USER-047 / YB7RG-FR-AUTH-001: initializes super admin account with profile, role and protected status', function (): void {
        $email = 'superadmin@example.org';
        $password = 'SuperSecret123!';

        $user = app(InitializeSuperAdminAction::class)->execute($email, $password);

        expect($user->exists)->toBeTrue()
            ->and($user->email)->toBe($email)
            ->and($user->hasRole(Role::SUPER_ADMIN))->toBeTrue()
            ->and($user->status)->toBe(AccountStatus::PROTECTED)
            ->and(Hash::check($password, $user->password))->toBeTrue();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $email,
            'status' => AccountStatus::PROTECTED->value,
        ]);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
        ]);

        $user->refresh();
        expect($user->profile)->not->toBeNull();
    });
});
