<?php

declare(strict_types=1);

use App\Domain\User\Aggregates\Profile\Actions\GetProfileFormDataAction;
use App\Domain\User\Enums\Role as RoleEnum;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
});

test('it returns correct configuration for student', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $action = app(GetProfileFormDataAction::class);
    $result = $action->execute($student);

    expect($result['canChangeName'])->toBeTrue();
    expect($result['canChangeUsername'])->toBeTrue();
    expect($result['staffFields'])->toBeEmpty();
    expect($result['role'])->toBe('student');
});

test('it returns correct configuration for super admin', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $action = app(GetProfileFormDataAction::class);
    $result = $action->execute($superAdmin);

    expect($result['canChangeName'])->toBeFalse();
    expect($result['canChangeUsername'])->toBeFalse();
    expect($result['staffFields'])->not->toBeEmpty();
    expect($result['role'])->toBe('superadmin');
});
