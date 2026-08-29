<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class GenerateAccountSlipBatchAction extends BaseProcessAction
{
    public function __construct(private readonly RenderAccountSlipAction $renderSlip) {}

    /**
     * @param array<int, User> $users
     */
    public function execute(array $users): Response
    {
        $html = '';

        foreach ($users as $user) {
            $html .= $this->renderSlip->execute($user);
        }

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, RenderAccountSlipAction::CARD_W, RenderAccountSlipAction::CARD_H])
            ->stream('account-slips-batch.pdf');
    }
}
