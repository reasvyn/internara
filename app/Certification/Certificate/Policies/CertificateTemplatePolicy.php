<?php

declare(strict_types=1);

namespace App\Certification\Certificate\Policies;

use App\Certification\Certificate\Models\CertificateTemplate;
use App\Core\Policies\BasePolicy;
use App\User\Models\User;

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
