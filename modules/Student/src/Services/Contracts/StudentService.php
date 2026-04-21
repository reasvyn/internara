<?php

declare(strict_types=1);

namespace Modules\Student\Services\Contracts;

use Modules\Shared\Services\Contracts\EloquentQuery;
use Modules\User\Models\User;

/**
 * @extends EloquentQuery<User>
 */
interface StudentService extends EloquentQuery
{
    /**
     * Send a password setup/reset link to a managed student.
     */
    public function sendPasswordResetLink(mixed $id): void;
}
