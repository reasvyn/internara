<?php

declare(strict_types=1);

namespace App\User\UserManagement\Listeners;

use App\Core\Services\SmartLogger;

class InvalidateUserCache
{
    public function handle(object $event): void
    {
        $user = $event->user ?? $event->{$event->user ?? null};

        cache()->forget('user_'.$event->user?->id);
        cache()->forget('users_count');

        SmartLogger::info('User cache invalidated')
            ->withPayload(['event' => $event::class])
            ->systemOnly()
            ->save();
    }
}
