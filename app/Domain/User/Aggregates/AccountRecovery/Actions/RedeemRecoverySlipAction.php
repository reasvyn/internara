<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\AccountRecovery\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Aggregates\AccountRecovery\Models\AccountRecoveryCode;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class RedeemRecoverySlipAction extends BaseAction
{
    public function execute(string $username, string $code, string $newPassword): User
    {
        return $this->transaction(function () use ($username, $code, $newPassword) {
            $user = User::where('username', $username)->first();

            if (! $user) {
                throw new RuntimeException(__('auth.failed'));
            }

            $recoveryCodes = AccountRecoveryCode::where('user_id', $user->id)
                ->whereNull('used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->get();

            $matchedCode = null;
            foreach ($recoveryCodes as $rc) {
                if ($rc->asRecoveryCodeState()->isValid() && Hash::check(strtoupper($code), $rc->code_hash)) {
                    $matchedCode = $rc;
                    break;
                }
            }

            if (! $matchedCode) {
                $this->log('recovery_slip_failed', $user);

                throw new RuntimeException(__('passwords.token'));
            }

            $user->update(['password' => Hash::make($newPassword)]);
            $matchedCode->update(['used_at' => now()]);

            $this->log('recovery_slip_redeemed', $user);

            return $user;
        });
    }
}
