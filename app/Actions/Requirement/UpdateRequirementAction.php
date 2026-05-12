<?php

declare(strict_types=1);

namespace App\Actions\Requirement;

use App\Models\InternshipDocumentRequirement;
use RuntimeException;

class UpdateRequirementAction
{
    public function execute(InternshipDocumentRequirement $requirement, string $documentId, bool $isMandatory): InternshipDocumentRequirement
    {
        $exists = InternshipDocumentRequirement::where('internship_id', $requirement->internship_id)
            ->where('document_id', $documentId)
            ->where('id', '!=', $requirement->id)
            ->exists();

        if ($exists) {
            throw new RuntimeException('This document is already a requirement for this internship.');
        }

        $requirement->update([
            'document_id' => $documentId,
            'is_mandatory' => $isMandatory,
        ]);

        return $requirement->fresh();
    }
}
