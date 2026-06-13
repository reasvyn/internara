<?php

declare(strict_types=1);

namespace Tests\Feature\User\Profile;

use App\User\Models\User;
use App\User\Profile\Models\Profile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('profile belongs to a user', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create(['user_id' => $user->id]);

    expect($profile->user)->toBeInstanceOf(User::class);
    expect($profile->user->id)->toBe($user->id);
});

test('profile can store phone number', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'phone' => '+6281234567890',
    ]);

    expect($profile->phone)->toBe('+6281234567890');
});

test('profile can store gender', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'gender' => 'male',
    ]);

    expect($profile->gender->value)->toBe('male');
});

test('profile can store address', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'address' => 'Jl. Merdeka No. 1',
    ]);

    expect($profile->address)->toBe('Jl. Merdeka No. 1');
});

test('profile can store id number', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'id_number' => '1234567890',
    ]);

    expect($profile->id_number)->toBe('1234567890');
});

test('profile can store emergency contact', function () {
    $user = User::factory()->create();
    $profile = Profile::factory()->create([
        'user_id' => $user->id,
        'emergency_contact' => '+628111111111',
    ]);

    expect($profile->emergency_contact)->toBe('+628111111111');
});

test('user has one profile', function () {
    $user = User::factory()->hasProfile()->create();

    expect($user->profile)->toBeInstanceOf(Profile::class);
});

test('deleting user cascades to profile', function () {
    $user = User::factory()->hasProfile()->create();
    $profileId = $user->profile->id;

    $user->delete();

    expect(Profile::find($profileId))->toBeNull();
});
