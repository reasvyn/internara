<?php

declare(strict_types=1);

use App\Actions\User\UpdateUserAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('updates user data', function () {
        $user = UserFactory::new()->create();

        $result = app(UpdateUserAction::class)->execute($user, [
            'name' => 'Updated Name',
        ]);

        expect($result->name)->toBe('Updated Name');
    });

    it('updates user with profile and roles', function () {
        $user = UserFactory::new()->create();

        $result = app(UpdateUserAction::class)->execute(
            $user,
            userData: ['name' => 'New Name'],
            profileData: ['phone' => '9999999999'],
            roles: ['super_admin'],
        );

        expect($result->name)->toBe('New Name')
            ->and($result->profile->phone)->toBe('9999999999');
    });
});
