<?php

declare(strict_types=1);

use App\Auth\Login\Livewire\Login;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: Login component error classification', function (): void {
    it('YB7RG-FR-LE2: invalid credentials surface a form error without an ERROR-level login.error log entry', function (): void {
        $user = User::factory()->create([
            'email' => 'wrongpass@example.com',
            'password' => 'correct-password',
            'status' => 'verified',
        ]);
        $user->assignRole('teacher');

        Log::spy();

        Livewire::test(Login::class)
            ->set('form.identifier', 'wrongpass@example.com')
            ->set('form.password', 'not-the-password')
            ->call('login')
            ->assertHasErrors('form.identifier');

        Log::shouldNotHaveReceived('error');
    });

    it('YB7RG-FR-LF1: valid credentials log in and redirect to the intended dashboard', function (): void {
        $user = User::factory()->create([
            'email' => 'happy-path@example.com',
            'password' => 'correct-password',
            'status' => 'verified',
        ]);
        $user->assignRole('teacher');

        Livewire::test(Login::class)
            ->set('form.identifier', 'happy-path@example.com')
            ->set('form.password', 'correct-password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    });
});
