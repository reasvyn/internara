<?php

declare(strict_types=1);

namespace App\Assignment\Data;

use App\Core\Data\BaseData;

final readonly class UpdateAssignmentData extends BaseData
{
    public function __construct(
        public ?string $assignmentType = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?bool $isMandatory = null,
        public ?string $dueDate = null,
    ) {}
}
