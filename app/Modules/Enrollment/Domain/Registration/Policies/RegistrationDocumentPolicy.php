<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\User\Models\User;

class RegistrationDocumentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, RegistrationDocument $document): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $document->registration?->mentee?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, RegistrationDocument $document): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, RegistrationDocument $document): bool
    {
        return $this->isAdmin($user);
    }
}
