<?php

declare(strict_types=1);

use App\User\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\User\AccountRecovery\Actions\RedeemRecoverySlipAction;
use App\User\AccountRecovery\Models\AccountRecoveryCode;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('User can redeem any of the generated recovery codes in any order', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'password' => Hash::make('OldPassword123'),
    ]);

    // 1. Generate 10 recovery codes
    $generator = app(GenerateRecoverySlipAction::class);
    $result = $generator->execute($user);
    $plaintextCodes = $result['plaintext'];

    expect($plaintextCodes)->toHaveCount(10);

    // 2. Select the 5th code (index 4) and try to redeem it
    $fifthCode = $plaintextCodes[4];

    $redeemAction = app(RedeemRecoverySlipAction::class);
    $updatedUser = $redeemAction->execute('testuser', $fifthCode, 'NewPassword123!');

    // 3. Verify user password was updated
    expect(Hash::check('NewPassword123!', $updatedUser->password))->toBeTrue();

    // 4. Verify that the 5th code was marked as used
    $usedCodes = AccountRecoveryCode::where('user_id', $user->id)
        ->whereNotNull('used_at')
        ->get();

    expect($usedCodes)->toHaveCount(1);
    expect(Hash::check(strtoupper($fifthCode), $usedCodes->first()->code_hash))->toBeTrue();

    // 5. Verify the remaining 9 codes are still unused
    $unusedCount = AccountRecoveryCode::where('user_id', $user->id)
        ->whereNull('used_at')
        ->count();

    expect($unusedCount)->toBe(9);
});
