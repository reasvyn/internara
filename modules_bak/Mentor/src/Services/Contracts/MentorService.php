<?php

declare(strict_types=1);

namespace Modules\Mentor\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;
use Modules\User\Models\User;

/**
 * @extends EloquentQuery<User>
 */
interface MentorService extends EloquentQuery
{
    /**
     * Get summary metrics for mentor distribution and status.
     *
     * @return array<string, int>
     */
    public function getStats(): array;
}
