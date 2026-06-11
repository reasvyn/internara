<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Auth\ApiTokens\Models\ApiToken;
use App\Core\Actions\BaseAction;
use App\User\Models\User;

final class RevokeUserActivationTokensAction extends BaseAction
{
    public function execute(User $user): void
    {
        $this->transaction(function () use ($user) {
            ApiToken::revokeFor($user, 'activation');

            $this->log('activation_tokens_revoked', $user);
        });
    }
}
