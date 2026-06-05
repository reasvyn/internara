<?php

declare(strict_types=1);

namespace App\SysAdmin\Account\Actions;

use App\Core\Actions\BaseAction;
use App\User\ActivationToken\Models\ActivationToken;
use App\User\Models\User;

final class RevokeUserActivationTokensAction extends BaseAction
{
    public function execute(User $user): void
    {
        $this->transaction(function () use ($user) {
            ActivationToken::revokeFor($user);

            $this->log('activation_tokens_revoked', $user);
        });
    }
}
