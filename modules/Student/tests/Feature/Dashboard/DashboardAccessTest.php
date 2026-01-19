<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles if they don't exist (in case seeder didn't run in test env)
    Role::firstOrCreate(['name' => 'student']);
    Role::firstOrCreate(['name' => 'admin']);
});

test('student can access student dashboard', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $student->markEmailAsVerified();

    $this->actingAs($student)->get(route('student.dashboard'))->assertStatus(200);
});

test('student cannot access admin dashboard', function () {
    $student = User::factory()->create();
    $student->assignRole('student');
    $student->markEmailAsVerified();

    $this->actingAs($student)->get(route('admin.dashboard'))->assertStatus(403);
});

test('admin cannot access student dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->markEmailAsVerified();

    $this->actingAs($admin)->get(route('student.dashboard'))->assertStatus(403);
});

test('guest is redirected to login', function () {
    $this->get(route('student.dashboard'))->assertRedirect(route('login'));
});
