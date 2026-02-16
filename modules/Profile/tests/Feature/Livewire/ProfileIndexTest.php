<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Permission\Models\Role;
use Modules\Profile\Livewire\Index;
use Modules\User\Models\User;


beforeEach(function () {
    Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
    Role::create(['name' => 'student', 'guard_name' => 'web']);
});

test('a user can update their basic profile information', function () {
    $user = User::factory()->create();
    $user->assignRole('student');

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('name', 'Updated Name')
        ->set('phone', '08123456789')
        ->call('saveInfo')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Updated Name');
    expect($user->fresh()->profile->phone)->toBe('08123456789');
});
