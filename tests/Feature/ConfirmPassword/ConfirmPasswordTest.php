<?php

declare(strict_types=1);

use App\Livewire\Auth\ConfirmPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => Hash::make('password123'),
    ]);
});

describe('ConfirmPassword', function () {

    it('renders the confirm password page', function () {
        $this->actingAs($this->user);

        Livewire::test(ConfirmPassword::class)
            ->assertSuccessful();
    });

    it('validates required password', function () {
        $this->actingAs($this->user);

        Livewire::test(ConfirmPassword::class)
            ->call('confirm')
            ->assertHasErrors(['password' => 'required']);
    });

    it('confirms with correct password', function () {
        $this->actingAs($this->user);

        Livewire::test(ConfirmPassword::class)
            ->set('password', 'password123')
            ->call('confirm')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));
    });

    it('stores confirmation timestamp in session', function () {
        $this->actingAs($this->user);

        Livewire::test(ConfirmPassword::class)
            ->set('password', 'password123')
            ->call('confirm');

        expect(session()->has('auth.password_confirmed_at'))->toBeTrue();
    });

    it('fails with incorrect password', function () {
        $this->actingAs($this->user);

        Livewire::test(ConfirmPassword::class)
            ->set('password', 'wrongpassword')
            ->call('confirm')
            ->assertHasErrors('password');
    });

    it('redirects to intended URL after confirmation', function () {
        $this->actingAs($this->user);

        session()->put('url.intended', route('profile'));

        Livewire::test(ConfirmPassword::class)
            ->set('password', 'password123')
            ->call('confirm')
            ->assertRedirect(route('profile'));
    });

});
