<?php

declare(strict_types=1);

namespace Modules\Core\Metadata\Services;

use Modules\Core\Metadata\Services\Contracts\MetadataService as Contract;
use Modules\Shared\Support\AppInfo;
use RuntimeException;

/**
 * Class MetadataService
 *
 * Implements the authoritative metadata management logic for Internara.
 */
class MetadataService implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return AppInfo::get($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): array
    {
        return AppInfo::all();
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
    public function getAuthor(): array
    {
        return (array) $this->get('author', []);
    }

    /**
     * {@inheritdoc}
     */
    public function getAppName(): string
    {
        return (string) $this->get('name', 'Internara');
    }

    /**
     * {@inheritdoc}
     */
    public function getBrandName(): string
    {
        return (string) setting('brand_name', $this->getAppName());
    }

    /**
     * {@inheritdoc}
     */
    public function verifyIntegrity(): void
    {
        // Ensure we are checking the fresh state of metadata
        \Modules\Shared\Support\AppInfo::clearCache();

        $author = (string) $this->get('author.name');

        if ($author !== self::AUTHOR_IDENTITY) {
            throw new RuntimeException(
                "Integrity Violation: Unauthorized author detected [{$author}]. ".
                    'This system requires attribution to ['.
                    self::AUTHOR_IDENTITY.
                    '].',
            );
        }
    }
}
