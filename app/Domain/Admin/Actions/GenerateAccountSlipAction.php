<?php

declare(strict_types=1);

namespace App\Domain\Admin\Actions;

use App\Domain\Auth\Models\ActivationToken;
use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Blade;

final class GenerateAccountSlipAction extends BaseAction
{
    private const int CARD_W = 241;

    private const int CARD_H = 156;

    public function download(User $user): Response
    {
        $code = ActivationToken::generateFor($user);

        $html = Blade::render(
            'admin.account-slip-pdf',
            ['user' => $user, 'code' => $code],
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
            $code = ActivationToken::generateFor($user);

            $html .= Blade::render(
                'admin.account-slip-pdf',
                ['user' => $user, 'code' => $code],
                deleteCachedView: true,
            );
        }

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, self::CARD_W, self::CARD_H])
            ->stream('account-slips-batch.pdf');
    }
}
