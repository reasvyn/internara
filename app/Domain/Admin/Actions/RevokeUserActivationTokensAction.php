<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Auth\Models\ActivationToken;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;

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
