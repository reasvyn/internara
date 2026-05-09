<?php

declare(strict_types=1);

use App\Livewire\User\RecoveryCode;
use App\Models\AccountRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => Hash::make('password123'),
    ]);
});

describe('UserRecoveryCode (self-service)', function () {

    it('renders the recovery code page', function () {
        $this->actingAs($this->user);

        Livewire::test(RecoveryCode::class)
            ->assertSuccessful();
    });

    it('generates a recovery code', function () {
        $this->actingAs($this->user);

        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->assertSet('generatedCode', fn ($code) => strlen($code) === 12);
    });

    it('stores recovery code hash in database', function () {
        $this->actingAs($this->user);

        Livewire::test(RecoveryCode::class)
            ->call('generate');

        expect(AccountRecoveryCode::where('user_id', $this->user->id)->count())->toBe(1);
    });

    it('displays expiration date after generation', function () {
        $this->actingAs($this->user);

        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->assertSet('expiresAt', fn ($value) => $value !== null);
    });

    it('resets code display after calling resetCode', function () {
        $this->actingAs($this->user);

        Livewire::test(RecoveryCode::class)
            ->call('generate')
            ->call('resetCode')
            ->assertSet('generatedCode', null)
            ->assertSet('expiresAt', null);
    });

    it('generates a new code after reset', function () {
        $this->actingAs($this->user);

        $component = Livewire::test(RecoveryCode::class);

        $component->call('generate');
        $firstCode = $component->get('generatedCode');

        $component->call('resetCode')
            ->call('generate')
            ->assertSet('generatedCode', fn ($code) => $code !== $firstCode);
    });

});
