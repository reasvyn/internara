<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'superadmin']);
    Role::create(['name' => 'student']);

    $this->superAdmin = User::factory()->create(['name' => 'Administrator']);
    $this->superAdmin->assignRole('superadmin');
});

test('settings route is denied for non-admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $response = $this->actingAs($user)->get('/admin/settings');

    $response->assertStatus(403);
});
