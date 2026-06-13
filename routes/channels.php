<?php

declare(strict_types=1);

use App\User\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn (User $user, string $id) => (int) $user->id === (int) $id);
