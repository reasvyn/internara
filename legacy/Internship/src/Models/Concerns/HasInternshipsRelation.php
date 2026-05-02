<?php

declare(strict_types=1);

namespace Modules\Internship\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Internship\Services\Contracts\InternshipService;

trait HasInternshipsRelation
{
    /**
     * Get the internships for the school.
     */
    public function internships(): HasMany
    {
        /** @var InternshipService $internshipService */
        $internshipService = app(InternshipService::class);

        return $internshipService->defineHasMany($this);
    }
}
