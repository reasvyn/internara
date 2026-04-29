<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Registration\Livewire;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Modules\Auth\Registration\Livewire\Register;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;
use Modules\User\Services\UsernameGenerator;

describe('Register Component', function () {
    beforeEach(function () {
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);
    });

    test('it renders the registration form correctly', function () {
        Livewire::test(Register::class)
            ->assertSee(__('auth::ui.register.title'))
            ->assertSee(__('auth::ui.register.form.name'));
    });

    test(
        'it allows a new user to register and assigns the student role [SYRS-NF-502]',
        function () {
            Notification::fake();

            $this->get(route('register'));

            Livewire::test(Register::class)
                ->set('form.name', 'Test Student')
                ->set('form.email', 'student@example.com')
                ->set('form.password', 'password123')
                ->set('form.password_confirmation', 'password123')
                ->call('register')
                ->assertHasNoErrors()
                ->assertRedirect();

            $user = User::where('email', 'student@example.com')->first();

            expect($user)
                ->not->toBeNull()
                ->and($user->hasRole('student'))
                ->toBeTrue()
                ->and($user->email_verified_at)
                ->toBeNull();

            Notification::assertSentTo($user, VerifyEmail::class);
        },
    );

    test('it rejects duplicate email registration', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(Register::class)
            ->set('form.name', 'Test Student')
            ->set('form.email', 'existing@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['form.email']);

        $this->assertGuest();
    });

    test('it rejects password confirmation mismatch', function () {
        Livewire::test(Register::class)
            ->set('form.name', 'Test Student')
            ->set('form.email', 'new@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'different_password')
            ->call('register')
            ->assertHasErrors(['form.password']);

        $this->assertGuest();
    });

    test('it requires all required fields', function () {
        Livewire::test(Register::class)
            ->call('register')
            ->assertHasErrors(['form.name', 'form.email', 'form.password']);
    });

    test('it blocks registration after rate limit exceeded', function () {
        $throttleKey = 'registration|' . request()->ip();
        RateLimiter::hit($throttleKey, 3600);
        RateLimiter::hit($throttleKey, 3600);

        Livewire::test(Register::class)
            ->set('form.name', 'Test Student')
            ->set('form.email', 'new@example.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['form.email'])
            ->assertSee(__('auth::ui.register.form.rate_limited'));

        $this->assertGuest();
    });

    test('it rejects invalid email format', function () {
        Livewire::test(Register::class)
            ->set('form.name', 'Test Student')
            ->set('form.email', 'not-an-email')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors(['form.email']);
    });
});
