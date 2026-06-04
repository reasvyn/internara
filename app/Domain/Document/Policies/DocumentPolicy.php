<?php

declare(strict_types=1);

namespace App\Domain\Document\Policies;

use App\Domain\Core\Policies\BasePolicy;
use App\Domain\Document\Models\Document;
use App\Domain\User\Models\User;

class DocumentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin', 'admin', 'teacher', 'student',
        ]);
    }

    public function view(User $user, Document $document): bool
    {
        return $this->isAdmin($user) || $document->is_active;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->isAdmin($user);
    }
}
