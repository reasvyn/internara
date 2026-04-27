<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Permission\Models\Role;
use Modules\Profile\Livewire\Index;
use Modules\User\Models\User;

beforeEach(function () {
    Role::firstOrCreate([
        'id' => (string) Str::uuid(),
        'name' => 'super-admin',
        'guard_name' => 'web',
    ]);
    Role::firstOrCreate([
        'id' => (string) Str::uuid(),
        'name' => 'student',
        'guard_name' => 'web',
    ]);
});

test('a user can update their basic profile information', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->actingAs($user);
    $this->get(route('profile.index'));

    Livewire::test(Index::class)
        ->set('name', 'Updated Name')
        ->set('phone', '08123456789')
        ->call('saveInfo')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Updated Name');
    expect($user->fresh()->profile->phone)->toBe('08123456789');
});

test('a user cannot update another user profile via the index component', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user);

    // Livewire Index component usually works on the authenticated user
    // But if it accepted a user_id as a property, we must ensure it's gated
    Livewire::test(Index::class, ['user_id' => $otherUser->id])
        ->set('name', 'Hacked Name')
        ->call('saveInfo')
        ->assertForbidden();

    expect($otherUser->fresh()->name)->not->toBe('Hacked Name');
});
