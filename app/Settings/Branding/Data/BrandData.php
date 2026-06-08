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

    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'name' => $this->name,
            'title' => $this->title,
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'colors' => $this->colors,
            'version' => $this->version,
            'author_name' => $this->authorName,
            'author_email' => $this->authorEmail,
            'description' => $this->description,
            'license' => $this->license,
            'gitUrl' => $this->gitUrl,
            default => $default,
        };
    }
}