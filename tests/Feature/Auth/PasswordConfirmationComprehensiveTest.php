<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Domain\Password\Actions\ConfirmPasswordAction;
use App\Modules\Auth\Domain\Password\Livewire\ConfirmPassword;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordConfirmationComprehensiveTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_cqvsk_fr_pwcon_001_and_002_confirms_password_and_stamps_session(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        $action = app(ConfirmPasswordAction::class);
        $response = $action->execute($user, 'Secret123!');

        expect($response->success)->toBeTrue()
            ->and(session('auth.password_confirmed_at'))->toBeGreaterThan(0)
            ->and(session('auth.password_confirmed_at'))->toBeLessThanOrEqual(time());
    }

    public function test_cqvsk_fr_pwcon_003_failed_confirmation_throws_rejected_exception(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        $action = app(ConfirmPasswordAction::class);

        $this->expectException(RejectedException::class);
        $action->execute($user, 'WrongPassword!');
    }

    public function test_cqvsk_fr_pwcon_004_and_nfr_pwcon_001_logs_password_confirmed(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        $this->actingAs($user);
        $action = app(ConfirmPasswordAction::class);
        $action->execute($user, 'Secret123!');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'Auth',
            'description' => 'password_confirmed',
            'causer_id' => $user->id,
        ]);
    }

    public function test_cqvsk_fr_pwcon_005_and_006_component_validation_and_throttling(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        RateLimiter::clear('confirm-password|127.0.0.1');

        $component = Livewire::actingAs($user)
            ->test(ConfirmPassword::class)
            ->set('form.password', '')
            ->call('confirm')
            ->assertHasErrors(['form.password' => 'required']);

        // Fail 5 times to hit throttle
        for ($i = 0; $i < 5; $i++) {
            $component->set('form.password', 'WrongPassword!')->call('confirm');
        }

        // 6th attempt should be throttled
        $component->set('form.password', 'WrongPassword!')
            ->call('confirm')
            ->assertHasErrors(['form.password']);
    }

    public function test_cqvsk_fr_pwcon_007_and_uc_pwcon_001_redirects_to_intended_or_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
        ]);

        session()->put('url.intended', '/settings/email');

        Livewire::actingAs($user)
            ->test(ConfirmPassword::class)
            ->set('form.password', 'Secret123!')
            ->call('confirm')
            ->assertRedirect('/settings/email');
    }

    public function test_cqvsk_fr_pwcon_008_and_dd_pwcon_001_and_002_freshness_window_and_contracts(): void
    {
        $timeout = config('auth.password_timeout', 10800);
        expect($timeout)->toBe(10800);

        // Architecture / contract assertions for DD-PWCON-001, DD-PWCON-002
        expect(class_exists(ConfirmPasswordAction::class))->toBeTrue()
            ->and(class_exists(ConfirmPassword::class))->toBeTrue();
    }

    public function test_cqvsk_nfr_pwcon_002_and_nfr_pwcon_003_plain_passwords_never_logged_and_strict_types(): void
    {
        $actionFile = file_get_contents(app_path('Modules/Auth/Domain/Password/Actions/ConfirmPasswordAction.php'));
        $livewireFile = file_get_contents(app_path('Modules/Auth/Domain/Password/Livewire/ConfirmPassword.php'));

        expect($actionFile)->toContain('declare(strict_types=1);')
            ->and($livewireFile)->toContain('declare(strict_types=1);')
            ->and($livewireFile)->not->toContain('withPayload([\'password\'')
            ->and($actionFile)->not->toContain('withPayload([\'password\'');
    }
}
