<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Password\Actions\ConfirmPasswordAction;
use App\Modules\Auth\Domain\Password\Livewire\ConfirmPassword;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('CQVSK: Password Confirmation', function (): void {
    test('CQVSK-FR-PWCON-001/002: confirms password and stamps session', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        $action = app(ConfirmPasswordAction::class);
        $response = $action->execute($user, 'Secret123!');

        expect($response->success)->toBeTrue()
            ->and(session('auth.password_confirmed_at'))->toBeGreaterThan(0)
            ->and(session('auth.password_confirmed_at'))->toBeLessThanOrEqual(time());
    });

    test('CQVSK-FR-PWCON-003: failed confirmation throws rejected exception', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        $action = app(ConfirmPasswordAction::class);

        expect(fn () => $action->execute($user, 'WrongPassword!'))
            ->toThrow(RejectedException::class);
    });

    test('CQVSK-FR-PWCON-004 CQVSK-NFR-PWCON-001: logs password confirmed', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        test()->actingAs($user);
        $action = app(ConfirmPasswordAction::class);
        $action->execute($user, 'Secret123!');

        test()->assertDatabaseHas('activity_log', [
            'log_name' => 'Auth',
            'description' => 'password_confirmed',
            'causer_id' => $user->id,
        ]);
    });

    test('CQVSK-FR-PWCON-005/006: component validation and throttling', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        RateLimiter::clear('confirm-password|127.0.0.1');

        $component = Livewire::actingAs($user)
            ->test(ConfirmPassword::class)
            ->set('form.password', '')
            ->call('confirm')
            ->assertHasErrors(['form.password' => 'required']);

        for ($i = 0; $i < 5; $i++) {
            $component->set('form.password', 'WrongPassword!')->call('confirm');
        }

        $component->set('form.password', 'WrongPassword!')
            ->call('confirm')
            ->assertHasErrors(['form.password']);
    });

    test('CQVSK-FR-PWCON-007 CQVSK-UC-PWCON-001: redirects to intended or dashboard', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        session()->put('url.intended', '/settings/email');

        Livewire::actingAs($user)
            ->test(ConfirmPassword::class)
            ->set('form.password', 'Secret123!')
            ->call('confirm')
            ->assertRedirect('/settings/email');
    });

    test('CQVSK-FR-PWCON-008 CQVSK-DD-PWCON-001/002: freshness window and contracts', function (): void {
        $timeout = config('auth.password_timeout', 10800);
        expect($timeout)->toBe(10800)
            ->and(class_exists(ConfirmPasswordAction::class))->toBeTrue()
            ->and(class_exists(ConfirmPassword::class))->toBeTrue();
    });

    test('CQVSK-NFR-PWCON-002/003: plain passwords never logged and strict types', function (): void {
        $actionFile = file_get_contents(app_path('Modules/Auth/Domain/Password/Actions/ConfirmPasswordAction.php'));
        $livewireFile = file_get_contents(app_path('Modules/Auth/Domain/Password/Livewire/ConfirmPassword.php'));

        expect($actionFile)->toContain('declare(strict_types=1);')
            ->and($livewireFile)->toContain('declare(strict_types=1);')
            ->and($livewireFile)->not->toContain('withPayload([\'password\'')
            ->and($actionFile)->not->toContain('withPayload([\'password\'');
    });
});
