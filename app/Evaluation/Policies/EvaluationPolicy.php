<?php

declare(strict_types=1);

namespace App\Evaluation\Policies;

use App\Core\Policies\BasePolicy;
use App\Evaluation\Models\Evaluation;
use App\User\Models\User;

class EvaluationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin', 'admin', 'teacher', 'supervisor', 'student',
        ]);
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $evaluation->evaluator_id === $user->id
            || $evaluation->mentor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin', 'admin', 'teacher', 'supervisor', 'student',
        ]);
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return $this->isAdmin($user) || $evaluation->evaluator_id === $user->id;
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        return $this->isAdmin($user);
    }
}
