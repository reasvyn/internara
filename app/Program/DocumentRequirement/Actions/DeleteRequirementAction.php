<?php

declare(strict_types=1);

namespace App\Program\DocumentRequirement\Actions;

use App\Core\Actions\BaseAction;
use App\Program\Internship\Models\InternshipDocumentRequirement;

final class DeleteRequirementAction extends BaseAction
{
    public function execute(InternshipDocumentRequirement $requirement): void
    {
        $requirement->delete();
    }
}
