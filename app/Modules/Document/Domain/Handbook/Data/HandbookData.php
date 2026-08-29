<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Handbook\Data;

use App\Modules\Core\Data\BaseData;
use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;
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
