<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Policies;

use App\Core\Policies\BasePolicy;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\Journals\Logbook\Models\Logbook;
use App\User\Models\User;

class LogbookPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAnyOfRoles($user, [
            'super_admin',
            'admin',
            'teacher',
            'supervisor',
            'student',
        ]);
    }

    public function view(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($entry->user_id === $user->id) {
            return true;
        }

        return $this->mentorProxyFor($entry->registration, $user)?->canVerifyLogbook($user) ?? false;
    }

    public function addSupervisorNote(User $user, Logbook $entry): bool
    {
        return $this->mentorProxyFor($entry->registration, $user)?->canVerifyLogbook($user) ?? false;
    }

    public function create(User $user): bool
    {
        return $this->isStudent($user);
    }

    public function update(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== LogbookStatus::SUBMITTED;
    }

    public function delete(User $user, Logbook $entry): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $entry->user_id === $user->id && $entry->status !== LogbookStatus::SUBMITTED;
    }
}
