<?php

declare(strict_types=1);

namespace App\Actions\Requirement;

use App\Models\InternshipDocumentRequirement;

class CreateRequirementAction
{
    public function execute(string $internshipId, string $documentId, bool $isMandatory = true): InternshipDocumentRequirement
    {
        $maxSort = InternshipDocumentRequirement::where('internship_id', $internshipId)->max('sort_order') ?? 0;

        return InternshipDocumentRequirement::create([
            'internship_id' => $internshipId,
            'document_id' => $documentId,
            'is_mandatory' => $isMandatory,
            'sort_order' => $maxSort + 1,
        ]);
    }
}
