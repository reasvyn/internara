<?php

declare(strict_types=1);

use App\User\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\User\AccountRecovery\Actions\RedeemRecoverySlipAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['username' => 'testuser']);
    $this->generateAction = app(GenerateRecoverySlipAction::class);
    $this->redeemAction = app(RedeemRecoverySlipAction::class);
});

test('redeems a valid recovery code and resets password', function () {
    $slip = $this->generateAction->execute($this->user);
    $plaintextCode = $slip['plaintext'][0];

    $result = $this->redeemAction->execute('testuser', $plaintextCode, 'NewPass123!');

    expect($result->id)->toBe($this->user->id);
    expect(Hash::check('NewPass123!', $result->fresh()->password))->toBeTrue();
});

test('marks code as used after redemption', function () {
    $slip = $this->generateAction->execute($this->user);
    $plaintextCode = $slip['plaintext'][0];

    $this->redeemAction->execute('testuser', $plaintextCode, 'NewPass123!');

    expect($slip['code']->fresh()->last_attempt_at)->not->toBeNull();
});

test('fails with invalid code', function () {
    expect(fn () => $this->redeemAction->execute('testuser', 'INVALIDCODE', 'NewPass123!'))
        ->toThrow(RuntimeException::class);
});

test('fails with non-existent username', function () {
    $slip = $this->generateAction->execute($this->user);
    $plaintextCode = $slip['plaintext'][0];

    expect(fn () => $this->redeemAction->execute('nonexistent', $plaintextCode, 'NewPass123!'))
        ->toThrow(RuntimeException::class);
});
