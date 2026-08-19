<?php

declare(strict_types=1);

namespace App\Reports\Report\Policies;

use App\Core\Policies\BasePolicy;
use App\Reports\Report\Models\Report;
use App\User\Models\User;

class ReportPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function finalize(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function calculate(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function download(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }
}
