<?php

declare(strict_types=1);

namespace App\User\Policies\Concerns;

use App\Enrollment\Registration\Models\Registration;
use App\User\Mentor\Entities\MentorEntity;
use App\User\Models\User;

trait HasMentorProxy
{
    protected function mentorProxyFor(?Registration $registration, User $user): ?MentorEntity
    {
        if ($registration === null) {
            return null;
        }

        return $registration->asMentorEntity();
    }
}
