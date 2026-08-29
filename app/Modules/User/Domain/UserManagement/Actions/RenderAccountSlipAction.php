<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Auth\Domain\AccessTokens\Models\AccessToken;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Blade;

final class RenderAccountSlipAction extends BaseProcessAction
{
    public const int CARD_W = 241;

    public const int CARD_H = 156;

    public function execute(User $user): string
    {
        $result = AccessToken::generateFor($user, 'activation', [
            'name' => 'Account Activation',
        ]);

        return Blade::render(
            'user.user-management.account-slip-pdf',
            ['user' => $user, 'code' => $result['plain_text']],
            deleteCachedView: true,
        );
    }
}
