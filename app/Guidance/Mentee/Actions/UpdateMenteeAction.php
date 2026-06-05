<?php

declare(strict_types=1);

namespace App\Guidance\Mentee\Actions;

use App\Core\Actions\BaseAction;
use App\Guidance\Mentee\Models\Mentee;

final class UpdateMenteeAction extends BaseAction
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
