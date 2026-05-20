<?php

declare(strict_types=1);

namespace App\Domain\Mentee\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Mentee\Models\Mentee;

class UpdateMenteeAction extends BaseAction
{
    public function execute(Mentee $mentee, array $menteeData): Mentee
    {
        return $this->transaction(function () use ($mentee, $menteeData) {
            $mentee->update($menteeData);

            $this->log('mentee_updated', $mentee, [
                'user_id' => $mentee->user_id,
            ]);

            return $mentee;
        });
    }
}
