<?php

declare(strict_types=1);

namespace App\Actions\Requirement;

use App\Models\InternshipDocumentRequirement;

class UpdateRequirementAction
{
    public function execute(InternshipDocumentRequirement $requirement, string $documentId, bool $isMandatory): InternshipDocumentRequirement
    {
        $requirement->update([
            'document_id' => $documentId,
            'is_mandatory' => $isMandatory,
        ]);

        return $requirement->fresh();
    }
}
