<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Actions\LoginAction;
use App\Modules\Auth\Domain\Login\Data\LoginData;
use App\Modules\Auth\Domain\Login\Events\LoginFailed;
use App\Modules\Auth\Domain\Login\Livewire\Login;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: login form, routes and session lifecycle', function (): void {
    test('YB7RG-FR-AUTH-023: login page answers guests and turns away signed-in users', function (): void {
        $this->get('/login')->assertOk();

        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect();
    });

    test('YB7RG-FR-AUTH-021 + YB7RG-FR-AUTH-022: empty credentials fail form validation before any backend work', function (): void {
        Event::fake([LoginFailed::class]);

        Livewire::test(Login::class)
            ->set('form.identifier', '')
            ->set('form.password', '')
            ->call('login')
            ->assertHasErrors(['form.identifier', 'form.password']);

        Event::assertNotDispatched(LoginFailed::class);

        expect(auth()->check())->toBeFalse();
    });

    test('YB7RG-FR-AUTH-024: wrong credentials and lockouts surface as field feedback', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();

        $rejected = Livewire::test(Login::class)
            ->set('form.identifier', $user->email)
            ->set('form.password', 'not-the-password')
            ->call('login');

        $rejected->assertHasErrors('form.identifier');

        expect($rejected->errors()->get('form.identifier'))->toContain(__('auth.failed'));

        $action = app(LoginAction::class);

        for ($i = 0; $i < 10; $i++) {
            try {
                $action->execute(new LoginData(identifier: $user->email, password: 'wrong-'.$i));
            } catch (RejectedException) {
            }
        }

        $lockedOut = Livewire::test(Login::class)
            ->set('form.identifier', $user->email)
            ->set('form.password', 'secret-123')
            ->call('login');

        $lockedOut->assertHasErrors('form.identifier');

        $messages = $lockedOut->errors()->get('form.identifier');

        expect($messages)->not->toBeEmpty()
            ->and($messages[0])->toMatch('/\d+/')
            ->and($messages[0])->not->toBe(__('auth.failed'));
    });

    test('YB7RG-UC-AUTH-001 + YB7RG-NFR-AUTH-009: one field signs in both email and username holders', function (): void {
        $byEmail = User::factory()->withPassword('secret-123')->create();

        Livewire::test(Login::class)
            ->set('form.identifier', $byEmail->email)
            ->set('form.password', 'secret-123')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($byEmail);

        auth()->logout();

        $byUsername = User::factory()->withPassword('secret-123')->create();

        Livewire::test(Login::class)
            ->set('form.identifier', $byUsername->username)
            ->set('form.password', 'secret-123')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($byUsername);
    });

    test('YB7RG-NFR-AUTH-001/012: session identifier rotates on login and again on logout', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();
        $guestId = session()->getId();

        app(LoginAction::class)->execute(new LoginData(identifier: $user->email, password: 'secret-123'));

        $loginId = session()->getId();

        expect($loginId)->not->toBe($guestId);

        $this->post('/logout')->assertRedirect(route('login'));

        expect(session()->getId())->not->toBe($loginId);
    });

    test('YB7RG-FR-AUTH-025 + YB7RG-FR-AUTH-026 + YB7RG-FR-AUTH-027: logout clears auth, burns session data, rotates the token', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['wizard_step' => '3']);

        $sessionIdBefore = session()->getId();
        $tokenBefore = session()->token();

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();

        expect(session()->getId())->not->toBe($sessionIdBefore)
            ->and(session()->token())->not->toBe($tokenBefore)
            ->and(session()->has('wizard_step'))->toBeFalse();
    });

    test('YB7RG-FR-AUTH-028 + YB7RG-UC-AUTH-003 + YB7RG-NFR-AUTH-002: sign-out lands on login with a fresh guest session', function (): void {
        $user = User::factory()->create();
        $tokenBefore = session()->token();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();

        expect(session()->token())->not->toBe($tokenBefore);

        $this->get(route('login'))->assertOk();
    });

    test('YB7RG-NFR-AUTH-013: login inputs render with associated labels', function (): void {
        $response = $this->get('/login')->assertOk();

        $response->assertSee('<label', false)
            ->assertSee(__('auth.login.identifier'), false)
            ->assertSee(__('auth.login.password'), false);
    });
});

describe('YB7RG: auth locale parity', function (): void {
    test('YB7RG-NFR-AUTH-017/018: every auth string the gate renders exists in English and Indonesian', function (): void {
        $keys = [
            'auth.failed',
            'auth.blocked',
            'auth.throttle',
            'auth.login.identifier',
            'auth.login.password',
            'auth.login.submit',
            'auth.login.title',
            'auth.notifications.credential_changed_subject',
            'auth.notifications.credential_changed_greeting',
            'auth.notifications.password_changed_line',
            'auth.notifications.credential_changed_warning',
            'auth.notifications.credential_changed_warning_with_email',
            'notifications.welcome_to_dashboard.title',
            'notifications.welcome_to_dashboard.student',
            'notifications.welcome_to_dashboard.teacher',
            'notifications.password_changed.title',
            'notifications.password_changed.message',
        ];

        try {
            foreach (['en', 'id'] as $locale) {
                app()->setLocale($locale);

                foreach ($keys as $key) {
                    expect(__($key))->not->toBe($key, "Missing {$locale} translation for {$key}");
                }
            }
        } finally {
            app()->setLocale('en');
        }
    });
});
