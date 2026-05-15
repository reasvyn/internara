<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Domain.User.Models.User.{userId}', function (User $user, string $userId) {
    return (string) $user->id === $userId;
});
