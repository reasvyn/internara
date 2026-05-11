<?php

declare(strict_types=1);

use App\Actions\Setup\RecoverAdminAccessAction;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('creates a recovery admin account', function () {
        $admin = app(RecoverAdminAccessAction::class)->execute(
            email: 'recovery@school.edu',
            password: 'new-secure-password',
        );

        expect($admin)->toBeInstanceOf(User::class)
            ->and($admin->email)->toBe('recovery@school.edu')
            ->and($admin->hasRole('super_admin'))->toBeTrue();
    });

    it('resets an existing admin account', function () {
        $existing = UserFactory::new()->create(['email' => 'admin@school.edu']);
        $existing->assignRole('super_admin');

        $admin = app(RecoverAdminAccessAction::class)->execute(
            email: 'admin@school.edu',
            password: 'reset-password',
            isReset: true,
        );

        expect($admin->id)->toBe($existing->id);
    });
});
