<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Actions;

use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;

final class ReadUserManagerStatsAction extends BaseReadAction
{
    public function execute(): array
    {
        return [
            'total' => User::count(),
            'admins' => User::role(['super_admin', 'admin'])->count(),
            'active' => User::where('status', AccountStatus::VERIFIED->value)->count(),
            'pending' => User::where('status', AccountStatus::PROVISIONED->value)->count(),
        ];
    }
}
