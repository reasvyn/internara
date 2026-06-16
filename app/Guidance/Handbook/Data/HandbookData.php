<?php

declare(strict_types=1);

namespace App\Guidance\Handbook\Data;

use App\Core\Data\BaseData;
use App\Guidance\Handbook\Enums\HandbookAudience;
use Illuminate\Http\UploadedFile;

final readonly class HandbookData extends BaseData
{
    public function __construct(
        public string $title,
        public HandbookAudience $audience,
        public ?string $description = null,
        public bool $isActive = true,
        public ?UploadedFile $file = null,
    ) {}
}
