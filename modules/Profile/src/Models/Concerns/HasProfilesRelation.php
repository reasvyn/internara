<?php

declare(strict_types=1);

namespace Modules\Profile\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Profile\Services\Contracts\ProfileService;

trait HasProfilesRelation
{
    /**
     * Get the profiles associated with the model.
     */
    public function profiles(): HasMany
    {
        /** @var ProfileService $profileService */
        $profileService = app(ProfileService::class);

        return $profileService->defineHasMany($this);
    }
}
