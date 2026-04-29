<?php

declare(strict_types=1);

namespace Modules\Media\Observers;

use Modules\Media\Models\Media;

class MediaObserver
{
    /**
     * Handle the Media "created" event.
     */
    public function created(Media $media): void {}

    /**
     * Handle the Media "updated" event.
     */
    public function updated(Media $media): void {}

    /**
     * Handle the Media "deleted" event.
     */
    public function deleted(Media $media): void {}

    /**
     * Handle the Media "restored" event.
     */
    public function restored(Media $media): void {}

    /**
     * Handle the Media "force deleted" event.
     */
    public function forceDeleted(Media $media): void {}
}
