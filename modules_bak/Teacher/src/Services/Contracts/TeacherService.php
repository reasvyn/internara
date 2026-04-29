<?php

declare(strict_types=1);

namespace Modules\Teacher\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;

/**
 * @extends EloquentQuery<User>
 */
interface TeacherService extends EloquentQuery
{
    /**
     * Get summary metrics for teacher distribution and status.
     *
     * @return array<string, int>
     */
    public function getStats(): array;

    /**
     * Send a password setup/reset link to a managed teacher.
     */
    public function sendPasswordResetLink(mixed $id): void;
}
