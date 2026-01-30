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
     * Set media for a specific collection, optionally clearing existing media.
     */
    public function setMedia(
        mixed $file,
        string $collectionName = 'default',
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
        string $collectionName = 'default',
        string $conversionName = '',
    ): string {
        return $this->getFirstMediaUrl($collectionName, $conversionName);
    }
}
