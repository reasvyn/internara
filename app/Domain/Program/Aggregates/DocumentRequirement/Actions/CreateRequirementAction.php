<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\DocumentRequirement\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Program\Aggregates\Internship\Models\InternshipDocumentRequirement;

final class CreateRequirementAction extends BaseAction
{
    public function execute(string $internshipId, string $documentId, bool $isMandatory = true): InternshipDocumentRequirement
    {
        $exists = InternshipDocumentRequirement::where('internship_id', $internshipId)
            ->where('document_id', $documentId)
            ->exists();

        if ($exists) {
            throw new RejectedException('This document is already a requirement for this internship.');
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
