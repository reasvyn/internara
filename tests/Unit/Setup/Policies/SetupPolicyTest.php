<?php

declare(strict_types=1);

namespace Tests\Unit\Setup\Policies;

use App\Setup\Models\Setup;
use App\Setup\Policies\SetupPolicy;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('setup policy viewAny, view, update only allow admins', function () {
    // Setup roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'student']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $student = User::factory()->create();
    $student->assignRole('student');

    $setup = new Setup;
    $policy = new SetupPolicy;

    // Admin should pass
    expect($policy->viewAny($admin))->toBeTrue();
    expect($policy->view($admin, $setup))->toBeTrue();
    expect($policy->update($admin, $setup))->toBeTrue();

    // Student should fail
    expect($policy->viewAny($student))->toBeFalse();
    expect($policy->view($student, $setup))->toBeFalse();
    expect($policy->update($student, $setup))->toBeFalse();

    // Create and delete always false
    expect($policy->create($admin))->toBeFalse();
    expect($policy->delete($admin, $setup))->toBeFalse();
});
