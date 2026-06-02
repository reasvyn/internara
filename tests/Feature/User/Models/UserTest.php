<?php

declare(strict_types=1);

use App\Domain\Auth\Enums\Role;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role as RoleModel;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    collect(Role::userRoles())->each(fn ($r) => RoleModel::create(['name' => $r->value, 'guard_name' => 'web']));
});

describe('User', function () {
    it('generates initials from two-word name', function () {
        $user = User::factory()->create(['name' => 'John Doe']);

        expect($user->initials())->toBe('JD');
    });

    it('generates initials from single-word name', function () {
        $user = User::factory()->create(['name' => 'Prince']);

        expect($user->initials())->toBe('PR');
    });

    it('generates initials from multi-word name', function () {
        $user = User::factory()->create(['name' => 'John Michael Doe']);

        expect($user->initials())->toBe('JD');
    });

    it('scope locked returns locked users', function () {
        $lockedUser = User::factory()->locked()->create();
        User::factory()->create();

        $locked = User::locked()->get();

        expect($locked)->toHaveCount(1);
        expect($locked->first()->id)->toBe($lockedUser->id);
    });

    it('scope unlocked returns unlocked users', function () {
        User::factory()->locked()->create();
        $unlockedUser = User::factory()->create();

        $unlocked = User::unlocked()->get();

        expect($unlocked)->toHaveCount(1);
        expect($unlocked->first()->id)->toBe($unlockedUser->id);
    });

    it('scope active returns unlocked users without setup required', function () {
        User::factory()->locked()->create();
        User::factory()->requiresSetup()->create();
        $activeUser = User::factory()->create();

        $active = User::active()->get();

        expect($active)->toHaveCount(1);
        expect($active->first()->id)->toBe($activeUser->id);
    });

    it('scope roleType filters by role', function () {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $student = User::factory()->create();
        $student->assignRole('student');

        $admins = User::roleType('admin')->get();
        $students = User::roleType('student')->get();

        expect($admins)->toHaveCount(1);
        expect($admins->first()->id)->toBe($admin->id);
        expect($students)->toHaveCount(1);
        expect($students->first()->id)->toBe($student->id);
    });

    it('has fillable attributes', function () {
        $user = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

        expect($user->name)->toBe('Test User');
        expect($user->email)->toBe('test@example.com');
    });
});
