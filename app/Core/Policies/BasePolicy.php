<?php

declare(strict_types=1);

namespace App\Core\Policies;

use App\Core\Policies\Concerns\AuthorizesOwnership;
use App\Core\Policies\Concerns\AuthorizesRoles;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    use AuthorizesOwnership;
    use AuthorizesRoles;

    public function before(Model $user): ?Response
    {
        if ($user->hasRole('super_admin')) {
            return Response::allow();
        }

        return null;
    }
}
