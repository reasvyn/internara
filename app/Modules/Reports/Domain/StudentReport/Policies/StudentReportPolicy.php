<?php

declare(strict_types=1);

namespace App\Modules\Reports\Domain\StudentReport\Policies;

use App\Modules\Core\Policies\BasePolicy;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use App\Modules\User\Models\User;

class StudentReportPolicy extends BasePolicy
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
