<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Data;

use App\Core\Data\BaseData;

final readonly class SubmitAssignmentData extends BaseData
{
    public function __construct(
        public string $content,
    ) {}
}
