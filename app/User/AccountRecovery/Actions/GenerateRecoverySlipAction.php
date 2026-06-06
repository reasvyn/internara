<?php

declare(strict_types=1);

namespace App\User\AccountRecovery\Actions;

use App\Core\Actions\BaseAction;
use App\User\AccountRecovery\Models\AccountRecoveryCode;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;

class GenerateRecoverySlipAction extends BaseAction
{
    public const int CODE_COUNT = 10;

    /** @return array{code: AccountRecoveryCode, plaintext: array<int, string>, expires_at: null} */
    public function execute(User $user): array
    {
        $codes = [];
        $firstCode = null;

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $plaintext = strtoupper(str()->random(12));

            $recoveryCode = AccountRecoveryCode::create([
                'user_id' => $user->id,
                'token' => Hash::make($plaintext),
                'token_type' => 'account_recovery',
                'expires_at' => now()->addYears(100),
                'attempts' => 0,
                'last_attempt_at' => null,
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
