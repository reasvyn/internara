<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class GenerateAccountSlipAction extends BaseCommandAction
{
    public function __construct(private readonly RenderAccountSlipAction $renderSlip) {}

    public function execute(User $user): Response
    {
        if (in_array($user->status->value, [AccountStatus::ARCHIVED->value, AccountStatus::SUSPENDED->value], true)) {
            throw new RejectedException(__('user.account_ineligible_for_slip'));
        }

        $this->log('account_slip_generated', $user, ['user_id' => $user->id]);

        $html = $this->renderSlip->execute($user);

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, RenderAccountSlipAction::CARD_W, RenderAccountSlipAction::CARD_H])
            ->stream('account-slip-'.$user->username.'.pdf');
    }
}
