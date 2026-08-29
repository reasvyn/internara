<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\User\Models\User;

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
