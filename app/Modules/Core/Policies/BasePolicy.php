<?php

declare(strict_types=1);

namespace App\Modules\Core\Policies;

use App\Modules\Core\Policies\Concerns\AuthorizesOwnership;
use App\Modules\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    use AuthorizesOwnership;
    use AuthorizesRoles;

    public const SUPER_ADMIN = 'super_admin';

    public function before(Model $user): ?Response
    {
        if ($user->hasRole(self::SUPER_ADMIN)) {
            return Response::allow();
        }

        return null;
    }
}
