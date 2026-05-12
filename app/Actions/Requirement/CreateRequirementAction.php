<?php

declare(strict_types=1);

namespace App\Actions\Requirement;

use App\Models\InternshipDocumentRequirement;
use RuntimeException;

class CreateRequirementAction
{
    public function execute(string $internshipId, string $documentId, bool $isMandatory = true): InternshipDocumentRequirement
    {
        $exists = InternshipDocumentRequirement::where('internship_id', $internshipId)
            ->where('document_id', $documentId)
            ->exists();

        if ($exists) {
            throw new RuntimeException('This document is already a requirement for this internship.');
        }

        $maxSort = InternshipDocumentRequirement::where('internship_id', $internshipId)->max('sort_order') ?? 0;

        return InternshipDocumentRequirement::create([
            'internship_id' => $internshipId,
            'document_id' => $documentId,
            'is_mandatory' => $isMandatory,
            'sort_order' => $maxSort + 1,
        ]);
    }
}
