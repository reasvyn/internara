<?php

declare(strict_types=1);

namespace App\Auth\AccountRecovery\Actions;

use App\Auth\AccountRecovery\Data\RecoveryCodeData;
use App\Auth\ApiTokens\Models\ApiToken;
use App\Core\Actions\BaseCommandAction;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

class GenerateRecoverySlipAction extends BaseCommandAction
{
    public const int CODE_COUNT = 10;

    /** @return array{code: RecoveryCodeData, plaintext: array<int, string>, expires_at: null} */
    public function execute(User $user): array
    {
        $codes = [];
        $firstCode = null;

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $plaintext = strtoupper(str()->random(12));
            $hashed = Hash::make($plaintext);

            $recoveryCode = RecoveryCodeData::from([
                'plainText' => $plaintext,
                'hashedToken' => $hashed,
                'expiresAt' => now()->addYears(100)->toDateTimeString(),
            ]);

            ApiToken::create([
                'user_id' => $user->id,
                'token' => $hashed,
                'token_type' => 'account_recovery',
                'expires_at' => now()->addYears(100),
                'attempts' => 0,
            ]);

            if ($i === 0) {
                $firstCode = $recoveryCode;
            }

            $codes[] = $plaintext;
        }

        $this->log('recovery_slips_generated', $user, ['count' => self::CODE_COUNT]);

        return [
            'code' => $firstCode,
            'plaintext' => $codes,
            'expires_at' => null,
        ];
    }
}
