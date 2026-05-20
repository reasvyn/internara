<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\Models\AccountRecoveryCode;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

class GenerateRecoverySlipAction extends BaseAction
{
    public const int CODE_COUNT = 10;

    /** @return array{code: AccountRecoveryCode, plaintext: array<int, string>, expires_at: string} */
    public function execute(User $user): array
    {
        $codes = [];
        $firstCode = null;

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $plaintext = strtoupper(str()->random(12));

            $recoveryCode = AccountRecoveryCode::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($plaintext),
                'generated_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            if ($i === 0) {
                $firstCode = $recoveryCode;
            }

            $codes[] = $plaintext;
        }

        $this->log('recovery_slips_generated', $user, ['count' => self::CODE_COUNT, 'expires_at' => now()->addHours(24)->toIso8601String()]);

        return [
            'code' => $firstCode,
            'plaintext' => $codes,
            'expires_at' => now()->addHours(24)->format('d M Y H:i'),
        ];
    }
}
