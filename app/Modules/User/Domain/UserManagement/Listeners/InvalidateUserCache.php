<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\UserManagement\Listeners;

use App\Modules\Core\Services\SmartLogger;

class InvalidateUserCache
{
    public function handle(object $event): void
    {
        $user = $event->user ?? $event->{$event->user ?? null};

        cache()->forget(config('cache-keys.user_single').$event->user?->id);
        cache()->forget(config('cache-keys.users_count'));

        SmartLogger::info('User cache invalidated')
            ->withPayload(['event' => $event::class])
            ->systemOnly()
            ->save();
    }
}
