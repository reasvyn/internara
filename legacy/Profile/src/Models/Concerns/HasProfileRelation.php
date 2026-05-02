<?php

declare(strict_types=1);

namespace Modules\Profile\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Profile\Services\Contracts\ProfileService;

trait HasProfileRelation
{
    /**
     * Get the profile associated with the model.
     */
    public function profile(): HasOne
    {
        /** @var ProfileService $profileService */
        $profileService = app(ProfileService::class);

        return $profileService->defineHasOne($this);
    }
}
