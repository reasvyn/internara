<?php

declare(strict_types=1);

namespace App\Actions\MentorProfile;

use App\Models\Mentor;

class ToggleMentorActiveAction
{
    public function execute(Mentor $mentor): Mentor
    {
        $mentor->update(['is_active' => ! $mentor->is_active]);

        return $mentor->fresh();
    }
}
