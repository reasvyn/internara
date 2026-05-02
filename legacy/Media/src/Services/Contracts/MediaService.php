<?php

declare(strict_types=1);

namespace Modules\Media\Services\Contracts;

interface MediaService
{
    public function upload(string $path, $file, string $disk = 'private'): string;

    public function delete(string $path, string $disk = 'private'): bool;

    public function getUrl(string $path, string $disk = 'private'): string;

    public function listFiles(string $directory, string $disk = 'private'): array;
}
