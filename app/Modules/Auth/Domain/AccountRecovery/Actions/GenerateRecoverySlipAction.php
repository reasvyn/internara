<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\AccountRecovery\Actions;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Auth\Domain\AccountRecovery\Data\RecoveryCodeData;
use App\Modules\Auth\Domain\AccountRecovery\Events\RecoverySlipGenerated;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class GenerateRecoverySlipAction extends BaseCommandAction
{
    public const int CODE_COUNT = 10;

    public function execute(User $user): ActionResponse
    {
        AccessToken::revokeFor($user, 'account_recovery');

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

            AccessToken::create([
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
        $this->dispatchEvent(new RecoverySlipGenerated($user, self::CODE_COUNT));

        return ActionResponse::ok([
            'code' => $firstCode,
            'plaintext' => $codes,
            'expires_at' => null,
        ]);
    }
}
