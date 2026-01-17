<?php

declare(strict_types=1);

namespace Modules\School\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\School\Services\Contracts\SchoolService as SchoolServiceContract;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasSchoolRelation
{
    public function changeSchoolId(mixed $schoolId = null): bool
    {
        $schoolService = app()->make(SchoolServiceContract::class);

        // Always use first school id record if it is single record
        if (config('school.single_record', true) && ! $schoolId) {
            $schoolId = $schoolService->first(['id'])?->id;
        }

        // Skip if $schoolId is empty
        if (! $schoolId || $this->school_id === $schoolId) {
            return true;
        }

        // Validate $schoolId
        $schoolService->query()->findOrFail($schoolId);

        $this->school_id = $schoolId;

        return $this->save();
    }

    public function school(): BelongsTo
    {
        $schoolService = app()->make(SchoolServiceContract::class);

        return $schoolService->defineBelongsTo($this, 'school_id');
    }
}
