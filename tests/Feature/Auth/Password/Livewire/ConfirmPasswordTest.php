<?php

declare(strict_types=1);

use App\Auth\Password\Livewire\ConfirmPassword;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    test()->actingAs($user);
});

test('confirm password component renders', function () {
    Livewire::test(ConfirmPassword::class)
        ->assertSuccessful();
});

test('confirm password validates required field', function () {
    Livewire::test(ConfirmPassword::class)
        ->set('form.password', '')
        ->call('confirm')
        ->assertHasErrors(['form.password' => 'required']);
});
