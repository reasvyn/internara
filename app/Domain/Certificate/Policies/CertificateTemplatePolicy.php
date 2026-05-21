<?php

declare(strict_types=1);

namespace App\Domain\Certificate\Policies;

use App\Domain\Certificate\Models\CertificateTemplate;
use App\Domain\Core\Policies\BasePolicy;
use App\Domain\User\Models\User;

class CertificateTemplatePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, CertificateTemplate $template): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, CertificateTemplate $template): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, CertificateTemplate $template): bool
    {
        return $this->isAdmin($user);
    }
}
