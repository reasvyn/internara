<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Livewire;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Auth\Livewire\ForgotPassword;
use Modules\User\Models\User;

describe('ForgotPassword Component', function () {
    beforeEach(function () {
        config(['services.cloudflare.turnstile.secret_key' => null]);
    });

    test('it renders the forgot password form correctly', function () {
        Livewire::test(ForgotPassword::class)
            ->assertSee(__('auth::ui.forgot_password.title'))
            ->assertSee(__('auth::ui.forgot_password.form.email'));
    });

    test('it sends reset link for existing user', function () {
        Notification::fake();

        $user = User::factory()->create(['email' => 'test@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    });

    test('it does not reveal whether email exists', function () {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nonexistent@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors();
    });

    test('it requires valid email format', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'not-an-email')
            ->call('sendResetLink')
            ->assertHasErrors(['email']);
    });

    test('it clears email after successful send', function () {
        Notification::fake();
        User::factory()->create(['email' => 'test@example.com']);

        $component = Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink');

        expect($component->get('email'))->toBe('');
    });
});
