<?php

declare(strict_types=1);

namespace Modules\Profile\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Profile\Models\Profile;

/**
 * Trait HasProfileRelation
 *
 * For models that can have a Profile (Student, Teacher, etc.).
 */
trait HasProfileMorphRelation
{
    /**
     * Get the model's profile.
     */
    public function profile(): MorphOne
    {
        return $this->morphOne(Profile::class, 'profileable');
    }
}
