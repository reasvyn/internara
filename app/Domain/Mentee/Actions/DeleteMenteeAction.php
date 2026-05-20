<?php

declare(strict_types=1);

namespace App\Domain\Mentee\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentee\Models\Mentee;

class DeleteMenteeAction extends BaseAction
{
    public function execute(Mentee $mentee): void
    {
        $this->transaction(function () use ($mentee) {
            $this->log('mentee_deleted', $mentee, [
                'user_id' => $mentee->user_id,
                'email' => $mentee->user->email,
            ]);

            $mentee->user->delete();
        });
    }
}
