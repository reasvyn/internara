<?php

declare(strict_types=1);

namespace Modules\User\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Services\Contracts\UserService;

trait HasUserRelation
{
    /**
     * Get the user that owns the model.
     */
    public function user(): BelongsTo
    {
        /** @var UserService $userService */
        $userService = app(UserService::class);

        return $userService->defineBelongsTo($this);
    }
}
