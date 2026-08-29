<?php

declare(strict_types=1);

namespace App\Modules\User\Policies\Concerns;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Domain\Mentor\Entities\MentorEntity;
use App\Modules\User\Models\User;

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
