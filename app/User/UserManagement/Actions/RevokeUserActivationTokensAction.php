<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Auth\AccessTokens\Models\AccessToken;
use App\Core\Actions\BaseCommandAction;
use App\User\Models\User;

final class RevokeUserActivationTokensAction extends BaseCommandAction
{
    public function execute(User $user): void
    {
        $this->transaction(function () use ($user) {
            AccessToken::revokeFor($user, 'activation');

            $this->log('activation_tokens_revoked', $user);
        });
    }
}
