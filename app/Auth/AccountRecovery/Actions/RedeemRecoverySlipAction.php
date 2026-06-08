<?php

declare(strict_types=1);

namespace App\Auth\AccountRecovery\Actions;

use App\Auth\ApiTokens\Models\ApiToken;
use App\Core\Actions\BaseAction;
use App\User\Models\User;
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

            $recoveryCodes = ApiToken::where('user_id', $user->id)
                ->where('token_type', 'account_recovery')
                ->whereNull('revoked_at')
                ->whereNull('last_used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->get();

            $matchedCode = null;
            foreach ($recoveryCodes as $rc) {
                if (
                    $rc->isValid() &&
                    Hash::check(strtoupper($code), $rc->token)
                ) {
                    $matchedCode = $rc;
                    break;
                }
            }

            if (! $matchedCode) {
                $this->log('recovery_slip_failed', $user);

                throw new RuntimeException(__('passwords.token'));
            }

            $user->update(['password' => Hash::make($newPassword)]);
            $matchedCode->update(['last_used_at' => now()]);

            $this->log('recovery_slip_redeemed', $user);

            return $user;
        });
    }
}
