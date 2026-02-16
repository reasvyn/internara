<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Registration\Livewire;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Auth\Registration\Livewire\Register;
use Modules\Permission\Models\Role;
use Modules\User\Models\User;


describe('Register Component', function () {
    beforeEach(function () {
        // Ensure essential roles exist
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
            Http::fake([
                'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                    'success' => true,
                ]),
            ]);

            Livewire::test(Register::class)
                ->set('name', 'Test Student')
                ->set('email', 'student@example.com')
                ->set('password', 'password123')
                ->set('password_confirmation', 'password123')
                ->set('captcha_token', 'fake-token')
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
});
