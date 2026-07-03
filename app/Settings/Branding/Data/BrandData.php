<?php

declare(strict_types=1);

namespace App\Settings\Branding\Data;

use App\Core\Data\BaseData;

final readonly class BrandData extends BaseData
{
    public function __construct(
        public string $name,
        public string $title,
        public string $logo,
        public string $favicon,
        public array $colors,
        public string $version,
        public string $authorName,
        public string $authorEmail,
        public string $description,
        public string $license,
        public string $gitUrl,
    ) {}

}
