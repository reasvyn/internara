<?php

declare(strict_types=1);

namespace App\Domain\SysAdmin\Aggregates\Account\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\User\Models\User;

final class GetUserManagerStatsAction extends BaseAction
{
    /**
     * @return array{total: int, admins: int, active: int, pending: int}
     */
    public function execute(): array
    {
        return [
            'total' => User::count(),
            'admins' => User::role(['super_admin', 'admin'])->count(),
            'active' => User::whereHas('statuses', fn ($q) => $q->where('name', 'verified')->latest('id'))->count(),
            'pending' => User::whereHas('statuses', fn ($q) => $q->where('name', 'provisioned')->latest('id'))->count(),
        ];
    }
}
