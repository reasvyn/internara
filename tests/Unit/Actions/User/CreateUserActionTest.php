<?php

declare(strict_types=1);

use App\Actions\User\CreateUserAction;
use App\Models\User;
use Database\Factories\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::create(['name' => 'teacher', 'guard_name' => 'web']);
});

describe('execute', function () {
    it('creates a user with profile and roles', function () {
        $department = DepartmentFactory::new()->create();

        $user = app(CreateUserAction::class)->execute(
            userData: [
                'name' => 'John Teacher',
                'email' => 'john@school.edu',
                'password' => 'secret123',
            ],
            profileData: [
                'phone' => '1234567890',
                'department_id' => $department->id,
            ],
            roles: ['teacher'],
        );

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Teacher')
            ->and($user->email)->toBe('john@school.edu')
            ->and($user->hasRole('teacher'))->toBeTrue()
            ->and($user->profile)->not->toBeNull()
            ->and($user->profile->phone)->toBe('1234567890');
    });

    it('throws validation error with missing required fields', function () {
        expect(fn () => app(CreateUserAction::class)->execute(
            userData: ['name' => 'No Email'],
        ))->toThrow(ValidationException::class);
    });
});
