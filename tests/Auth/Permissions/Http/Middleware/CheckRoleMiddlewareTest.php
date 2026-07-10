<?php

declare(strict_types=1);

use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {

    Route::get('/_test_role', function () {
        return 'ok';
    })->middleware('role:super_admin');

    Route::get('/_test_role_multi', function () {
        return 'ok';
    })->middleware('role:admin|super_admin');

    Route::get('/_test_role_json', function () {
        return 'ok';
    })->middleware('role:super_admin');
});

test('allows access when user has required role', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)->get('/_test_role');

    $response->assertStatus(200);
});

test('denies access when user lacks required role', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/_test_role');

    $response->assertStatus(403);
});

test('allows access with multiple roles via pipe syntax', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)->get('/_test_role_multi');

    $response->assertStatus(200);
});

test('redirects unauthenticated users to login', function () {
    $response = $this->get('/_test_role');

    $response->assertRedirect(route('login'));
});

test('returns json for unauthenticated json request', function () {
    $response = $this->getJson('/_test_role_json');

    $response->assertStatus(401);
    $response->assertJson(['message' => 'Unauthenticated.']);
});

test('denies role mismatch and returns json for json request', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/_test_role_json');

    $response->assertStatus(403);
    $response->assertJson([
        'message' => 'Security Access Denied. Your identity profile does not have the required clearance level.',
    ]);
});
