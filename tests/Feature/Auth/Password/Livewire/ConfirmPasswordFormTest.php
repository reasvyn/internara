<?php

declare(strict_types=1);

use App\Auth\Password\Livewire\ConfirmPassword;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    User::factory()->create();
    test()->actingAs(User::first());
});

test('confirm password form has correct default values', function () {
    Livewire::test(ConfirmPassword::class)
        ->assertSet('form.password', '');
});

test('confirm password form validates through component', function () {
    Livewire::test(ConfirmPassword::class)
        ->set('form.password', '')
        ->call('confirm')
        ->assertHasErrors(['form.password' => 'required']);
});
