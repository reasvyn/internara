<?php

declare(strict_types=1);

namespace App\Domain\Program\Aggregates\DocumentRequirement\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Program\Aggregates\Internship\Models\InternshipDocumentRequirement;

final class UpdateRequirementAction extends BaseAction
{
    public function execute(InternshipDocumentRequirement $requirement, string $documentId, bool $isMandatory): InternshipDocumentRequirement
    {
        $exists = InternshipDocumentRequirement::where('internship_id', $requirement->internship_id)
            ->where('document_id', $documentId)
            ->where('id', '!=', $requirement->id)
            ->exists();

        if ($exists) {
            throw new RejectedException('This document is already a requirement for this internship.');
        }

        $requirement->update([
            'document_id' => $documentId,
            'is_mandatory' => $isMandatory,
        ]);

        return $requirement->fresh();
    }
}
