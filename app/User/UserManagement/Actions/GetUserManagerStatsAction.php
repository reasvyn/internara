<?php

declare(strict_types=1);

namespace App\User\UserManagement\Actions;

use App\User\Models\User;

final class GetUserManagerStatsAction
{
    public function execute(): array
    {
        return [
            'total' => User::count(),
            'admins' => User::role(['super_admin', 'admin'])->count(),
            'active' => User::where('status', 'verified')->count(),
            'pending' => User::where('status', 'provisioned')->count(),
        ];
    }
}
