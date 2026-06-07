<?php

declare(strict_types=1);

use App\Auth\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\Auth\AccountRecovery\Models\AccountRecoveryCode;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(GenerateRecoverySlipAction::class);
});

test('generates 10 recovery codes for user', function () {
    $result = $this->action->execute($this->user);

    expect($result['code'])->toBeInstanceOf(AccountRecoveryCode::class);
    expect($result['plaintext'])->toHaveCount(10);
    expect(
        AccountRecoveryCode::where('user_id', $this->user->id)
            ->where('token_type', 'account_recovery')
            ->count(),
    )->toBe(10);
});

test('generated codes have unique plaintext values', function () {
    $result = $this->action->execute($this->user);

    expect(array_unique($result['plaintext']))->toHaveCount(10);
});

test('all generated codes are unused', function () {
    $this->action->execute($this->user);

    $usedCount = AccountRecoveryCode::where('user_id', $this->user->id)
        ->where('token_type', 'account_recovery')
        ->whereNotNull('last_attempt_at')
        ->count();

    expect($usedCount)->toBe(0);
});
