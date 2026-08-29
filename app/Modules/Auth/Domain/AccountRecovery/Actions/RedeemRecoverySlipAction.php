<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\AccountRecovery\Actions;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Auth\Domain\AccountRecovery\Data\RedeemRecoverySlipData;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;

final class RedeemRecoverySlipAction extends BaseCommandAction
{
    public function execute(RedeemRecoverySlipData $data): User
    {
        return $this->transaction(function () use ($data) {
            $user = User::where('username', $data->username)->first();

            if (! $user) {
                throw new RejectedException(__('auth.failed'));
            }

            $recoveryCodes = AccessToken::where('user_id', $user->id)
                ->where('token_type', 'account_recovery')
                ->whereNull('revoked_at')
                ->whereNull('last_used_at')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->get();

            $matchedCode = null;
            foreach ($recoveryCodes as $rc) {
                if (Hash::check(strtoupper($data->code), $rc->token)) {
                    $matchedCode = $rc;
                    break;
                }
            }

            if (! $matchedCode) {
                $this->log('recovery_slip_failed', $user);

                throw new RejectedException(__('passwords.token'));
            }

            $user->update(['password' => Hash::make($data->newPassword)]);
            $matchedCode->update(['last_used_at' => now()]);

            $this->log('recovery_slip_redeemed', $user);

            return $user;
        });
    }
}
