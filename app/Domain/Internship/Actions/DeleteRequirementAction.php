<?php

declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Internship\Models\InternshipDocumentRequirement;

class DeleteRequirementAction extends BaseAction
{
    public function execute(InternshipDocumentRequirement $requirement): void
    {
        $requirement->delete();
    }
}
