<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Feature\Livewire;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Auth\Livewire\ClaimAccount;
use Modules\User\Models\User;
use Modules\User\Services\AccountProvisioningService;

describe('ClaimAccount Component', function () {
    beforeEach(function () {
        $this->provisioning = app(AccountProvisioningService::class);
    });

    test('it renders the claim account form correctly', function () {
        Livewire::test(ClaimAccount::class)
            ->assertSee(__('auth::claim.title'))
            ->assertSee(__('auth::claim.form.username'))
            ->assertSee(__('auth::claim.form.activation_code'));
    });

    test('it advances to step 2 with valid activation code', function () {
        $user = User::factory()->create(['username' => 'testuser']);
        $plainCode = $this->provisioning->provision($user);

        Livewire::test(ClaimAccount::class)
            ->set('username', 'testuser')
            ->set('activation_code', $plainCode)
            ->call('verify')
            ->assertSet('step', 2)
            ->assertSet('verifiedTokenId', $user->accountTokens()->active()->first()->id);
    });

    test('it rejects invalid activation code', function () {
        $user = User::factory()->create(['username' => 'testuser']);
        $this->provisioning->provision($user);

        Livewire::test(ClaimAccount::class)
            ->set('username', 'testuser')
            ->set('activation_code', 'WRONGCODE')
            ->call('verify')
            ->assertHasErrors(['activation_code'])
            ->assertSee(__('auth::claim.invalid_code'));
    });

    test('it rejects invalid username', function () {
        Livewire::test(ClaimAccount::class)
            ->set('username', 'nonexistent')
            ->set('activation_code', 'ABC123')
            ->call('verify')
            ->assertHasErrors(['activation_code']);
    });

    test('it blocks after rate limit exceeded', function () {
        $user = User::factory()->create(['username' => 'testuser']);

        $key = 'claim-account:'.Str::lower('testuser');
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 300);
        }

        Livewire::test(ClaimAccount::class)
            ->set('username', 'testuser')
            ->set('activation_code', 'ABC123')
            ->call('verify')
            ->assertHasErrors(['activation_code'])
            ->assertSee(__('auth::claim.throttled'));
    });

    test('it requires username for step 1', function () {
        Livewire::test(ClaimAccount::class)
            ->set('activation_code', 'ABC123')
            ->call('verify')
            ->assertHasErrors(['username']);
    });

    test('it requires activation code for step 1', function () {
        Livewire::test(ClaimAccount::class)
            ->set('username', 'testuser')
            ->call('verify')
            ->assertHasErrors(['activation_code']);
    });

    test('it completes password claim in step 2', function () {
        $user = User::factory()->create(['username' => 'testuser', 'password' => bcrypt('old')]);
        $plainCode = $this->provisioning->provision($user);
        $token = $user->accountTokens()->active()->first();

        Livewire::test(ClaimAccount::class)
            ->set('username', 'testuser')
            ->set('activation_code', $plainCode)
            ->call('verify')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('claim')
            ->assertRedirect(route('login'));
    });

    test('it rejects password confirmation mismatch in step 2', function () {
        $user = User::factory()->create(['username' => 'testuser', 'password' => bcrypt('old')]);
        $plainCode = $this->provisioning->provision($user);

        Livewire::test(ClaimAccount::class)
            ->set('username', 'testuser')
            ->set('activation_code', $plainCode)
            ->call('verify')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'different')
            ->call('claim')
            ->assertHasErrors(['password']);
    });
});
