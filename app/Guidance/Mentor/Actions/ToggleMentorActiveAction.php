<?php

declare(strict_types=1);

namespace App\Guidance\Mentor\Actions;

use App\Core\Actions\BaseAction;
use App\Guidance\Mentor\Models\Mentor;

final class ToggleMentorActiveAction extends BaseAction
{
    public function execute(Mentor $mentor): Mentor
    {
        $mentor->update(['is_active' => ! $mentor->is_active]);

        return $mentor->fresh();
    }
}
