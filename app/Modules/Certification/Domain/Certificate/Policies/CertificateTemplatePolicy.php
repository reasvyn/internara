<?php

declare(strict_types=1);

namespace App\Modules\Certification\Domain\Certificate\Policies;

use App\Modules\Certification\Domain\Certificate\Models\CertificateTemplate;
use App\Modules\Core\Policies\BasePolicy;
use App\Modules\User\Models\User;

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
