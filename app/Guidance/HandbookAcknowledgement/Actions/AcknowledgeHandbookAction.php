<?php

declare(strict_types=1);

namespace App\Guidance\HandbookAcknowledgement\Actions;

use App\Core\Actions\BaseAction;
use App\Guidance\Handbook\Models\Handbook;
use App\User\Models\User;

/**
 * Records user acknowledgement of a handbook.
 *
 * S1 - Secure: Creates immutable audit trail for compliance.
 */
final class AcknowledgeHandbookAction extends BaseAction
{
    public function execute(User $user, Handbook $handbook): void
    {
        $handbook->acknowledgements()->create([
            'user_id' => $user->id,
            'acknowledged_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        $this->log('handbook_acknowledged', $handbook, ['user_id' => $user->id]);
    }
}
