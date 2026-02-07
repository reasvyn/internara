<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Modules\Core\Services\Contracts\MetadataService as Contract;

/**
 * Class MetadataService
 *
 * Implements the authoritative metadata management logic for Internara.
 */
class MetadataService implements Contract
{
    /**
     * Cached application information.
     */
    protected ?array $info = null;

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->getAll(), $key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): array
    {
        if ($this->info === null) {
            $path = base_path('app_info.json');

            if (! File::exists($path)) {
                $this->info = [];
            } else {
                $this->info = json_decode(File::get($path), true) ?? [];
            }
        }

        return $this->info;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return (string) $this->get('version', '0.0.0');
    }

    /**
     * {@inheritdoc}
     */
    public function getSeriesCode(): string
    {
        return (string) $this->get('series_code', 'UNKNOWN');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor(): array
    {
        return (array) $this->get('author', []);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyIntegrity(): void
    {
        $author = (string) $this->get('author.name');

        if ($author !== self::AUTHOR_IDENTITY) {
            throw new \RuntimeException(
                "Integrity Violation: Unauthorized author detected [{$author}]. ".
                    'This system requires attribution to ['.
                    self::AUTHOR_IDENTITY.
                    '].',
            );
        }
    }
}
