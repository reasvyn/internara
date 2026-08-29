<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Data;

use App\Modules\Core\Data\BaseData;

final readonly class CreateAssignmentData extends BaseData
{
    public function __construct(
        public string $assignmentType,
        public string $internshipId,
        public string $title,
        public ?string $description = null,
        public bool $isMandatory = false,
        public ?string $dueDate = null,
    ) {}
}
