<?php

declare(strict_types=1);

namespace App\Core\Policies;

use App\Core\Policies\Concerns\AuthorizesOwnership;
use App\Core\Policies\Concerns\AuthorizesRoles;
use App\Enrollment\Registration\Models\Registration;
use App\User\Mentor\Entities\MentorEntity;
use App\User\Models\User;
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

    protected function mentorProxyFor(?Registration $registration, User $user): ?MentorEntity
    {
        if ($registration === null) {
            return null;
        }

        return $registration->asMentorEntity();
    }

    protected function allowIfAdmin(Model $user): Response
    {
        return $this->isAdmin($user)
            ? Response::allow()
            : Response::deny(__('policies.admin_only'));
    }

    protected function allowIfAdminOrTeacher(Model $user): Response
    {
        return $this->isAdminOrTeacher($user)
            ? Response::allow()
            : Response::deny(__('policies.admin_or_teacher_only'));
    }

    protected function allowIfOwner(Model $user, Model $model, string $foreignKey = 'user_id'): Response
    {
        return $this->isOwner($user, $model, $foreignKey)
            ? Response::allow()
            : Response::deny(__('policies.owner_only'));
    }
}
