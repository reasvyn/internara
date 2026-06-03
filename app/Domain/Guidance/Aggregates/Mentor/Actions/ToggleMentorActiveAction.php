<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Aggregates\Mentor\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Guidance\Aggregates\Mentor\Models\Mentor;

final class ToggleMentorActiveAction extends BaseAction
{
    public function execute(Mentor $mentor): Mentor
    {
        $mentor->update(['is_active' => ! $mentor->is_active]);

        return $mentor->fresh();
    }
}
