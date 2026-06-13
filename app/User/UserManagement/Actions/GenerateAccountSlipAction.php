<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\Auth\ApiTokens\Models\ApiToken;
use App\Core\Actions\BaseAction;
use App\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;

final class GenerateAccountSlipAction extends BaseAction
{
    private const int CARD_W = 241;

    private const int CARD_H = 156;

    public function execute(User $user): Response
    {
        return $this->download($user);
    }

    public function download(User $user): Response
    {
        $result = ApiToken::generateFor($user, 'activation', ['name' => 'Account Activation']);

        $html = Blade::render(
            'user.user-management.account-slip-pdf',
            ['user' => $user, 'code' => $result['plain_text']],
            deleteCachedView: true,
        );

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, self::CARD_W, self::CARD_H])
            ->stream('account-slip-'.$user->username.'.pdf');
    }

    public function downloadBatch(array $users): Response
    {
        $html = '';

        foreach ($users as $i => $user) {
            $result = ApiToken::generateFor($user, 'activation', ['name' => 'Account Activation']);

            $html .= Blade::render(
                'user.user-management.account-slip-pdf',
                ['user' => $user, 'code' => $result['plain_text']],
                deleteCachedView: true,
            );
        }

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, self::CARD_W, self::CARD_H])
            ->stream('account-slips-batch.pdf');
    }
}
