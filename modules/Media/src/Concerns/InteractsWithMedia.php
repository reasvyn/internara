<?php

declare(strict_types=1);

namespace Modules\Media\Concerns;

use Spatie\MediaLibrary\InteractsWithMedia as SpatieInteractsWithMedia;

/**
 * Trait InteractsWithMedia
 *
 * Provides a standardized way to interact with the media library within the Internara ecosystem.
 */
trait InteractsWithMedia
{
    use SpatieInteractsWithMedia;

    /**
     * Standard collection names.
     */
    public const COLLECTION_DEFAULT = 'default';

    public const COLLECTION_AVATAR = 'avatar';

    public const COLLECTION_LOGO = 'logo';

    /**
     * Set media for a specific collection, optionally clearing existing media.
     */
    public function setMedia(
        mixed $file,
        string $collectionName = self::COLLECTION_DEFAULT,
        bool $clearExisting = true,
    ): bool {
        if ($clearExisting) {
            $this->clearMediaCollection($collectionName);
        }

        return (bool) $this->addMedia($file)->toMediaCollection($collectionName);
    }

    /**
     * Get the first media URL for a specific collection.
     */
    public function getMediaUrl(
        string $collectionName = self::COLLECTION_DEFAULT,
        string $conversionName = '',
    ): string {
        return $this->getFirstMediaUrl($collectionName, $conversionName);
    }
}
