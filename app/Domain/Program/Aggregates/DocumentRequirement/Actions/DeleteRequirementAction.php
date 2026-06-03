<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\DocumentRequirement\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Program\Aggregates\Internship\Models\InternshipDocumentRequirement;

final class DeleteRequirementAction extends BaseAction
{
    public function execute(InternshipDocumentRequirement $requirement): void
    {
        $requirement->delete();
    }
}
