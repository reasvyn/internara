<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Domain\Auth\Actions\GenerateRecoverySlipAction;
use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\Auth\Enums\Role;
use App\Domain\Auth\Livewire\AccountRecovery;
use App\Domain\Auth\Livewire\ConfirmPassword;
use App\Domain\Auth\Livewire\ForgotPassword;
use App\Domain\Auth\Livewire\Login;
use App\Domain\Auth\Livewire\ResetPassword;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    app()->setLocale('en');
    RoleModel::create(['name' => Role::SUPER_ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::ADMIN->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::TEACHER->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::STUDENT->value, 'guard_name' => 'web']);
    RoleModel::create(['name' => Role::SUPERVISOR->value, 'guard_name' => 'web']);
});

describe('Login', function () {
    it('renders the login page', function () {
        $this->get(route('login'))->assertStatus(200);
    });

    it('logs in with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        Livewire::test(Login::class)
            ->set('form.identifier', 'user@test.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');
    });

    it('rejects wrong password', function () {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        Livewire::test(Login::class)
            ->set('form.identifier', 'user@test.com')
            ->set('form.password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('form.identifier');
    });

    it('rejects suspended user', function () {
        $user = User::factory()->create([
            'email' => 'suspended@test.com',
            'password' => Hash::make('password'),
        ]);
        $user->setStatus(AccountStatus::SUSPENDED->value);

        Livewire::test(Login::class)
            ->set('form.identifier', 'suspended@test.com')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasErrors('form.identifier');
    });

    it('validates required fields', function () {
        Livewire::test(Login::class)
            ->call('login')
            ->assertHasErrors(['form.identifier' => 'required', 'form.password' => 'required']);
    });

    it('logs in with username', function () {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        Livewire::test(Login::class)
            ->set('form.identifier', 'testuser')
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');
    });

    it('rate limits after multiple failures', function () {
        $user = User::factory()->create([
            'email' => 'user@test.com',
            'password' => Hash::make('password'),
        ]);
        $user->setStatus(AccountStatus::VERIFIED->value);

        $component = Livewire::test(Login::class);

        for ($i = 0; $i < 5; $i++) {
            $component->set('form.identifier', 'user@test.com')
                ->set('form.password', 'wrong')
                ->call('login');
        }

        $component->set('form.identifier', 'user@test.com')
            ->set('form.password', 'wrong')
            ->call('login')
            ->assertHasErrors('form.identifier');
    });
});

describe('ForgotPassword', function () {
    it('renders the forgot password page', function () {
        $this->get(route('password.request'))->assertStatus(200);
    });

    it('sends reset link for existing email', function () {
        User::factory()->create(['email' => 'user@test.com']);

        Livewire::test(ForgotPassword::class)
            ->set('form.email', 'user@test.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('linkSent', true);
    });

    it('validates email is required', function () {
        Livewire::test(ForgotPassword::class)
            ->call('sendResetLink')
            ->assertHasErrors('form.email');
    });

    it('validates email format', function () {
        Livewire::test(ForgotPassword::class)
            ->set('form.email', 'invalid')
            ->call('sendResetLink')
            ->assertHasErrors('form.email');
    });

    it('handles non-existent email gracefully', function () {
        Livewire::test(ForgotPassword::class)
            ->set('form.email', 'nonexistent@test.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('linkSent', true);
    });
});

describe('ResetPassword', function () {
    it('renders the reset password page with token', function () {
        $this->get(route('password.reset', ['token' => 'valid-token']))
            ->assertStatus(200);
    });

    it('validates required fields', function () {
        Livewire::test(ResetPassword::class, ['token' => 'some-token'])
            ->call('resetPassword')
            ->assertHasErrors(['form.email' => 'required', 'form.password' => 'required']);
    });

    it('validates password confirmation match', function () {
        Livewire::test(ResetPassword::class, ['token' => 'some-token'])
            ->set('form.email', 'user@test.com')
            ->set('form.password', 'NewPass123')
            ->set('form.password_confirmation', 'DifferentPass')
            ->call('resetPassword')
            ->assertHasErrors(['form.password' => 'confirmed']);
    });

    it('rejects invalid token', function () {
        Livewire::test(ResetPassword::class, ['token' => 'bad-token'])
            ->set('form.email', 'user@test.com')
            ->set('form.password', 'NewPass123')
            ->set('form.password_confirmation', 'NewPass123')
            ->call('resetPassword')
            ->assertHasErrors('form.email');
    });
});

describe('ConfirmPassword', function () {
    it('does not throw when unauthenticated', function () {
        Livewire::test(ConfirmPassword::class)
            ->call('confirm');
        expect(true)->toBeTrue();
    });

    it('validates password is required', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ConfirmPassword::class)
            ->call('confirm')
            ->assertHasErrors('form.password');
    });

    it('rejects wrong password for authenticated user', function () {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);
        $this->actingAs($user);

        Livewire::test(ConfirmPassword::class)
            ->set('form.password', 'wrong-password')
            ->call('confirm')
            ->assertHasErrors('form.password');
    });

    it('confirms with correct password', function () {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);
        $this->actingAs($user);

        Livewire::test(ConfirmPassword::class)
            ->set('form.password', 'correct-password')
            ->call('confirm')
            ->assertHasNoErrors();
    });
});

describe('AccountRecovery', function () {
    it('renders the recovery page', function () {
        $this->get(route('recover.account'))->assertStatus(200);
    });

    it('validates required fields', function () {
        Livewire::test(AccountRecovery::class)
            ->call('redeem')
            ->assertHasErrors(['form.username' => 'required', 'form.password' => 'required']);
    });

    it('validates password confirmation', function () {
        Livewire::test(AccountRecovery::class)
            ->set('form.username', 'testuser')
            ->set('form.password', 'NewPass123')
            ->set('form.password_confirmation', 'Different')
            ->call('redeem')
            ->assertHasErrors(['form.password' => 'confirmed']);
    });

    it('rejects invalid recovery code', function () {
        $user = User::factory()->create(['username' => 'testuser']);

        Livewire::test(AccountRecovery::class)
            ->set('form.username', 'testuser')
            ->set('form.recoveryCode', 'INVALID-CODE')
            ->set('form.password', 'NewPass123')
            ->set('form.password_confirmation', 'NewPass123')
            ->call('redeem')
            ->assertHasErrors('form.recoveryCode');
    });

    it('recovers account with valid code', function () {
        $user = User::factory()->create(['username' => 'redeemuser']);

        $codes = app(GenerateRecoverySlipAction::class)->execute($user);
        $plaintextCode = $codes['plaintext'][0];

        Livewire::test(AccountRecovery::class)
            ->set('form.username', 'redeemuser')
            ->set('form.recoveryCode', $plaintextCode)
            ->set('form.password', 'NewPass123')
            ->set('form.password_confirmation', 'NewPass123')
            ->call('redeem')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('NewPass123', $user->fresh()->password))->toBeTrue();
    });
});
