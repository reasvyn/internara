<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    $this->superAdmin = User::factory()->create(['name' => 'Administrator']);
    $this->superAdmin->assignRole('superadmin');
});

test('settings route is denied for non-admin users', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $response = $this->actingAs($user)->get('/admin/settings');

    $response->assertStatus(403);
});
