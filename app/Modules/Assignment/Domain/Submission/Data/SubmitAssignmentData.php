<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Data;

use App\Modules\Core\Data\BaseData;
use Illuminate\Http\UploadedFile;

final readonly class SubmitAssignmentData extends BaseData
{
    public function __construct(
        public string $content,
        public ?UploadedFile $file = null,
    ) {}
}
