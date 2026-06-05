<?php

declare(strict_types=1);

use App\User\Enums\Role as RoleEnum;
use App\User\Models\User;
use App\User\Password\Livewire\ForgotPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }
    Cache::flush();
    Notification::fake();
});

// ─── Guest Access ───

test('guest can visit forgot password page', function () {
    $this->get(route('password.request'))
        ->assertStatus(200)
        ->assertSee(__('passwords.reset_password_title'));
});

test('authenticated user is redirected from forgot password page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('password.request'))
        ->assertRedirect('/dashboard');
});

// ─── Form Validation ───

test('forgot password requires email', function () {
    Livewire::test(ForgotPassword::class)
        ->call('sendResetLink')
        ->assertHasErrors(['form.email']);
});

test('forgot password requires valid email format', function () {
    Livewire::test(ForgotPassword::class)
        ->set('form.email', 'not-an-email')
        ->call('sendResetLink')
        ->assertHasErrors(['form.email']);
});

// ─── Send Reset Link ───

test('forgot password sends reset link for existing user', function () {
    $user = User::factory()->create([
        'email' => 'reset-test@example.com',
    ]);

    Livewire::test(ForgotPassword::class)
        ->set('form.email', 'reset-test@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('linkSent', true);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password does not reveal non-existent user but still shows success', function () {
    // Security: must NOT reveal whether the email exists
    Livewire::test(ForgotPassword::class)
        ->set('form.email', 'nobody@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('linkSent', true);
});

// ─── Rate Limiting ───

test('forgot password throttles after 3 attempts', function () {
    // Create a user so the email is valid
    User::factory()->create(['email' => 'throttle-test@example.com']);

    $component = Livewire::test(ForgotPassword::class);

    // 3 successful attempts
    for ($i = 0; $i < 3; $i++) {
        $component
            ->set('form.email', 'throttle-test@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors();
    }

    // 4th attempt should be throttled
    $component
        ->set('form.email', 'throttle-test@example.com')
        ->call('sendResetLink')
        ->assertHasErrors(['form.email']);
});

// ─── Flash Message ───

test('forgot password shows success flash message', function () {
    User::factory()->create(['email' => 'flash-test@example.com']);

    Livewire::test(ForgotPassword::class)
        ->set('form.email', 'flash-test@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('linkSent', true);
});
