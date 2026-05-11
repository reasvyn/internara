<?php

declare(strict_types=1);

namespace App\Actions\Requirement;

use App\Models\InternshipDocumentRequirement;

class DeleteRequirementAction
{
    public function execute(InternshipDocumentRequirement $requirement): void
    {
        $requirement->delete();
    }
}
